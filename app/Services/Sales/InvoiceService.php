<?php

namespace App\Services\Sales;

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Sales\Enums\InvoiceStatus;
use App\Domain\Sales\Enums\InvoiceType;
use App\Models\Account;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\TaxCode;
use App\Services\Accounting\JournalService;
use App\Services\Accounting\NumberSequenceService;
use DomainException;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        private JournalService $journalService,
        private NumberSequenceService $numberSequence,
    ) {}

    /**
     * Create a new sales invoice with line items and post journal entries.
     */
    public function create(Business $business, array $data): Invoice
    {
        return DB::transaction(function () use ($business, $data) {
            $invoice = Invoice::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'contact_id' => $data['contact_id'],
                'type' => InvoiceType::INVOICE,
                'number' => $this->numberSequence->getNext($business, 'invoice'),
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

            [$subtotal, $taxTotal] = $this->createLines($invoice, $data['lines']);

            $total = round($subtotal + $taxTotal, 2);

            $invoice->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total' => $total,
                'balance_due' => $total,
            ]);

            // Auto-post the invoice
            $this->postInvoice($invoice);

            return $invoice->fresh(['lines', 'journalEntry', 'contact']);
        });
    }

    /**
     * Update an existing draft invoice.
     */
    public function update(Invoice $invoice, array $data): Invoice
    {
        if ($invoice->status !== InvoiceStatus::DRAFT) {
            throw new DomainException('Only draft invoices can be edited.');
        }

        return DB::transaction(function () use ($invoice, $data) {
            $invoice->update([
                'contact_id' => $data['contact_id'] ?? $invoice->contact_id,
                'date' => $data['date'] ?? $invoice->date,
                'due_date' => $data['due_date'] ?? $invoice->due_date,
                'reference' => $data['reference'] ?? $invoice->reference,
                'notes' => $data['notes'] ?? $invoice->notes,
                'terms' => $data['terms'] ?? $invoice->terms,
            ]);

            if (isset($data['lines'])) {
                $invoice->lines()->delete();
                [$subtotal, $taxTotal] = $this->createLines($invoice, $data['lines']);
                $total = round($subtotal + $taxTotal, 2);

                $invoice->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxTotal,
                    'total' => $total,
                    'balance_due' => $total - $invoice->amount_paid,
                ]);
            }

            return $invoice->fresh(['lines', 'contact']);
        });
    }

    /**
     * Post an invoice — generates the accounting journal entry.
     */
    public function postInvoice(Invoice $invoice): void
    {
        $business = $invoice->business;
        $arAccount = Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('sub_type', AccountSubType::ACCOUNTS_RECEIVABLE)
            ->firstOrFail();

        $lines = [];

        // DR: Accounts Receivable (total including tax)
        $lines[] = [
            'account_id' => $arAccount->id,
            'contact_id' => $invoice->contact_id,
            'debit' => (float) $invoice->total,
            'credit' => 0,
            'description' => "Invoice {$invoice->number}",
        ];

        // CR: Revenue accounts (per invoice line)
        foreach ($invoice->lines as $invoiceLine) {
            $lines[] = [
                'account_id' => $invoiceLine->account_id,
                'debit' => 0,
                'credit' => (float) $invoiceLine->line_total,
                'description' => $invoiceLine->description,
            ];

            // CR: Tax Payable (if applicable)
            if ($invoiceLine->tax_amount > 0) {
                $taxAccount = Account::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->where('sub_type', AccountSubType::TAX_PAYABLE)
                    ->firstOrFail();

                $lines[] = [
                    'account_id' => $taxAccount->id,
                    'debit' => 0,
                    'credit' => (float) $invoiceLine->tax_amount,
                    'description' => "Tax on {$invoiceLine->description}",
                    'tax_code_id' => $invoiceLine->tax_code_id,
                ];
            }
        }

        $journalEntry = $this->journalService->createAndPost(
            business: $business,
            date: $invoice->date,
            lines: $lines,
            description: "Sales Invoice {$invoice->number}",
            sourceType: 'invoice',
            sourceId: $invoice->id,
        );

        $invoice->update([
            'journal_entry_id' => $journalEntry->id,
            'status' => InvoiceStatus::SENT,
        ]);
    }

    /**
     * Void an invoice — reverses the journal entry.
     */
    public function void(Invoice $invoice, string $reason = 'Voided'): void
    {
        if (in_array($invoice->status, [InvoiceStatus::VOID, InvoiceStatus::CANCELLED])) {
            throw new DomainException('This invoice is already void/cancelled.');
        }

        if ($invoice->amount_paid > 0) {
            throw new DomainException('Cannot void an invoice with payments. Reverse the payments first.');
        }

        DB::transaction(function () use ($invoice, $reason) {
            if ($invoice->journalEntry) {
                $this->journalService->reverse($invoice->journalEntry, $reason);
            }

            $invoice->update(['status' => InvoiceStatus::VOID]);
        });
    }

    // ── Private Helpers ─────────────────────────────────────────

    private function createLines(Invoice $invoice, array $linesData): array
    {
        $subtotal = 0;
        $taxTotal = 0;

        foreach ($linesData as $index => $line) {
            $lineTotal = round((float) $line['quantity'] * (float) $line['unit_price'], 2);

            if (isset($line['discount_percent']) && $line['discount_percent'] > 0) {
                $lineTotal = round($lineTotal * (1 - (float) $line['discount_percent'] / 100), 2);
            }

            $taxAmount = 0;
            if (! empty($line['tax_code_id'])) {
                $taxCode = TaxCode::withoutGlobalScopes()->find($line['tax_code_id']);
                if ($taxCode) {
                    $taxAmount = round($lineTotal * (float) $taxCode->rate / 100, 2);
                }
            }

            $invoice->lines()->create([
                'account_id' => $line['account_id'],
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'discount_percent' => $line['discount_percent'] ?? 0,
                'tax_code_id' => $line['tax_code_id'] ?? null,
                'tax_amount' => $taxAmount,
                'line_total' => $lineTotal,
                'sort_order' => $index,
            ]);

            $subtotal += $lineTotal;
            $taxTotal += $taxAmount;
        }

        return [$subtotal, $taxTotal];
    }
}
