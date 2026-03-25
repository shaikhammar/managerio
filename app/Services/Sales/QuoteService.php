<?php

namespace App\Services\Sales;

use App\Domain\Sales\Enums\InvoiceStatus;
use App\Domain\Sales\Enums\InvoiceType;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\TaxCode;
use App\Services\Accounting\NumberSequenceService;
use DomainException;
use Illuminate\Support\Facades\DB;

class QuoteService
{
    public function __construct(
        private InvoiceService $invoiceService,
        private NumberSequenceService $numberSequence,
    ) {}

    /**
     * Create a new sales quote.
     * Quotes do NOT generate journal entries.
     */
    public function create(Business $business, array $data): Invoice
    {
        return DB::transaction(function () use ($business, $data) {
            $quote = Invoice::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'contact_id' => $data['contact_id'],
                'type' => InvoiceType::QUOTE,
                'number' => $this->numberSequence->getNext($business, 'quote'),
                'date' => $data['date'],
                'due_date' => $data['due_date'] ?? null,
                'reference' => $data['reference'] ?? null,
                'status' => InvoiceStatus::DRAFT,
                'currency_code' => $business->currency_code,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'balance_due' => 0,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
            ]);

            [$subtotal, $taxTotal] = $this->createLines($quote, $data['lines']);

            $total = bcadd($subtotal, $taxTotal, 2);

            $quote->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total' => $total,
                'balance_due' => $total,
            ]);

            return $quote->fresh(['lines', 'contact']);
        });
    }

    /**
     * Update an existing draft quote.
     */
    public function update(Invoice $quote, array $data): Invoice
    {
        if ($quote->type !== InvoiceType::QUOTE) {
            throw new DomainException('Provided document is not a quote.');
        }

        if ($quote->status !== InvoiceStatus::DRAFT) {
            throw new DomainException('Only draft quotes can be edited.');
        }

        $quote->loadMissing('lines');

        if (! $this->quoteHasChanges($quote, $data)) {
            return $quote->load(['lines', 'contact']);
        }

        return DB::transaction(function () use ($quote, $data) {
            $quote->update([
                'contact_id' => $data['contact_id'] ?? $quote->contact_id,
                'date' => $data['date'] ?? $quote->date,
                'due_date' => $data['due_date'] ?? $quote->due_date,
                'reference' => $data['reference'] ?? $quote->reference,
                'notes' => $data['notes'] ?? $quote->notes,
                'terms' => $data['terms'] ?? $quote->terms,
            ]);

            if (isset($data['lines'])) {
                $quote->lines()->delete();
                [$subtotal, $taxTotal] = $this->createLines($quote, $data['lines']);
                $total = bcadd($subtotal, $taxTotal, 2);

                $quote->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxTotal,
                    'total' => $total,
                    'balance_due' => $total,
                ]);
            }

            return $quote->fresh(['lines', 'contact']);
        });
    }

    /**
     * Convert a quote to a draft invoice.
     * The invoice starts as DRAFT; the user must edit it to assign accounts and then post it.
     */
    public function convertToInvoice(Invoice $quote): Invoice
    {
        if ($quote->type !== InvoiceType::QUOTE) {
            throw new DomainException('Only quotes can be converted.');
        }

        return DB::transaction(function () use ($quote) {
            $business = $quote->business;

            $invoice = Invoice::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'contact_id' => $quote->contact_id,
                'type' => InvoiceType::INVOICE,
                'number' => $this->numberSequence->getNext($business, 'invoice'),
                'date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(30)->format('Y-m-d'),
                'reference' => "Converted from Quote #{$quote->number}",
                'status' => InvoiceStatus::DRAFT,
                'currency_code' => $quote->currency_code,
                'subtotal' => $quote->subtotal,
                'tax_amount' => $quote->tax_amount,
                'total' => $quote->total,
                'balance_due' => $quote->total,
                'notes' => $quote->notes,
                'terms' => $quote->terms,
            ]);

            foreach ($quote->lines as $line) {
                $invoice->lines()->create([
                    'account_id' => null,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'discount_percent' => $line->discount_percent,
                    'tax_code_id' => $line->tax_code_id,
                    'tax_amount' => $line->tax_amount,
                    'line_total' => $line->line_total,
                    'sort_order' => $line->sort_order,
                ]);
            }

            // Mark quote as approved/closed
            $quote->update(['status' => InvoiceStatus::APPROVED]);

            return $invoice;
        });
    }

    // ── Private Helpers (Duplicate of InvoiceService for isolation) ──

    private function quoteHasChanges(Invoice $quote, array $data): bool
    {
        $headerComparisons = [
            'contact_id' => fn ($v) => (int) $v !== (int) $quote->contact_id,
            'date' => fn ($v) => $v !== $quote->date->format('Y-m-d'),
            'due_date' => fn ($v) => ($v ?: null) !== ($quote->due_date?->format('Y-m-d')),
            'reference' => fn ($v) => ($v ?: null) !== ($quote->reference ?: null),
            'notes' => fn ($v) => ($v ?: null) !== ($quote->notes ?: null),
            'terms' => fn ($v) => ($v ?: null) !== ($quote->terms ?: null),
        ];

        foreach ($headerComparisons as $field => $differs) {
            if (array_key_exists($field, $data) && $differs($data[$field])) {
                return true;
            }
        }

        $existing = $quote->lines;

        if (count($data['lines']) !== $existing->count()) {
            return true;
        }

        foreach ($data['lines'] as $i => $line) {
            $current = $existing[$i] ?? null;

            if ($current === null) {
                return true;
            }

            if (($line['description'] ?? '') !== (string) ($current->description ?? '')
                || (float) ($line['quantity'] ?? 1) !== (float) $current->quantity
                || (float) ($line['unit_price'] ?? 0) !== (float) $current->unit_price
                || (float) ($line['discount_percent'] ?? 0) !== (float) $current->discount_percent
                || (($line['tax_code_id'] ?? null) != $current->tax_code_id)
            ) {
                return true;
            }
        }

        return false;
    }

    private function createLines(Invoice $invoice, array $linesData): array
    {
        $subtotal = '0';
        $taxTotal = '0';

        foreach ($linesData as $index => $line) {
            $lineTotal = bcmul((string) $line['quantity'], (string) $line['unit_price'], 2);

            if (isset($line['discount_percent']) && $line['discount_percent'] > 0) {
                $discountMultiplier = bcsub('1', bcdiv((string) $line['discount_percent'], '100', 10), 10);
                $lineTotal = bcmul($lineTotal, $discountMultiplier, 2);
            }

            $taxCodeId = $line['tax_code_id'] ?? 'none';
            $taxAmount = '0';
            if (! empty($taxCodeId) && $taxCodeId !== 'none') {
                $taxCode = TaxCode::withoutGlobalScopes()->find($taxCodeId);
                if ($taxCode) {
                    $taxAmount = bcmul($lineTotal, bcdiv((string) $taxCode->rate, '100', 10), 2);
                }
            }

            $invoice->lines()->create([
                'account_id' => null,
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'discount_percent' => $line['discount_percent'] ?? 0,
                'tax_code_id' => ($taxCodeId === 'none') ? null : $taxCodeId,
                'tax_amount' => $taxAmount,
                'line_total' => $lineTotal,
                'sort_order' => $index,
            ]);

            $subtotal = bcadd($subtotal, $lineTotal, 2);
            $taxTotal = bcadd($taxTotal, $taxAmount, 2);
        }

        return [$subtotal, $taxTotal];
    }
}
