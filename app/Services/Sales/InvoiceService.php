<?php

namespace App\Services\Sales;

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Sales\Enums\InvoiceStatus;
use App\Domain\Sales\Enums\InvoiceType;
use App\Events\InvoicePosted;
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
        return $this->createInvoice($business, $data, InvoiceType::INVOICE);
    }

    /**
     * Create a new purchase invoice with line items and post journal entries.
     */
    public function createPurchaseInvoice(Business $business, array $data): Invoice
    {
        return $this->createInvoice($business, $data, InvoiceType::PURCHASE_INVOICE);
    }

    /**
     * Create a new credit note with line items and post journal entries.
     */
    public function createCreditNote(Business $business, array $data): Invoice
    {
        return $this->createInvoice($business, $data, InvoiceType::CREDIT_NOTE);
    }

    /**
     * Create a new debit note with line items and post journal entries.
     */
    public function createDebitNote(Business $business, array $data): Invoice
    {
        return $this->createInvoice($business, $data, InvoiceType::DEBIT_NOTE);
    }

    /**
     * Create a new purchase order (no journal entry — commitment document only).
     */
    public function createPurchaseOrder(Business $business, array $data): Invoice
    {
        return DB::transaction(function () use ($business, $data) {
            $purchaseOrder = Invoice::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'contact_id' => $data['contact_id'],
                'type' => InvoiceType::PURCHASE_ORDER,
                'number' => $this->numberSequence->getNext($business, 'purchase_order'),
                'date' => $data['date'],
                'due_date' => $data['due_date'] ?? null,
                'reference' => $data['reference'] ?? null,
                'status' => InvoiceStatus::DRAFT,
                'currency_code' => $business->currency_code,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'amount_paid' => 0,
                'balance_due' => 0,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
            ]);

            [$subtotal, $taxTotal] = $this->createLines($purchaseOrder, $data['lines']);

            $total = round($subtotal + $taxTotal, 2);

            $purchaseOrder->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total' => $total,
                'balance_due' => $total,
            ]);

            return $purchaseOrder->fresh(['lines', 'contact']);
        });
    }

    /**
     * Convert a purchase order to a purchase invoice, posting journal entries.
     */
    public function convertPurchaseOrderToInvoice(Invoice $purchaseOrder): Invoice
    {
        if (! $purchaseOrder->isPurchaseOrder()) {
            throw new DomainException('Only purchase orders can be converted to purchase invoices.');
        }

        if ($purchaseOrder->status === InvoiceStatus::INVOICED) {
            throw new DomainException('This purchase order has already been invoiced.');
        }

        return DB::transaction(function () use ($purchaseOrder) {
            $business = $purchaseOrder->business;

            $linesData = $purchaseOrder->lines->map(fn ($line) => [
                'account_id' => $line->account_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'discount_percent' => $line->discount_percent,
                'tax_code_id' => $line->tax_code_id,
            ])->all();

            $invoiceData = [
                'contact_id' => $purchaseOrder->contact_id,
                'date' => now()->format('Y-m-d'),
                'due_date' => $purchaseOrder->due_date?->format('Y-m-d'),
                'reference' => $purchaseOrder->reference ?? $purchaseOrder->number,
                'notes' => $purchaseOrder->notes,
                'terms' => $purchaseOrder->terms,
                'lines' => $linesData,
            ];

            $purchaseInvoice = $this->createPurchaseInvoice($business, $invoiceData);

            $purchaseInvoice->update(['purchase_order_id' => $purchaseOrder->id]);
            $purchaseOrder->update(['status' => InvoiceStatus::INVOICED]);

            return $purchaseInvoice;
        });
    }

    /**
     * Mark a purchase order as sent.
     */
    public function sendPurchaseOrder(Invoice $purchaseOrder): void
    {
        if (! $purchaseOrder->isPurchaseOrder()) {
            throw new DomainException('Only purchase orders can be marked as sent.');
        }

        if ($purchaseOrder->status !== InvoiceStatus::DRAFT) {
            throw new DomainException('Only draft purchase orders can be marked as sent.');
        }

        $purchaseOrder->update(['status' => InvoiceStatus::SENT]);
    }

    private function createInvoice(Business $business, array $data, InvoiceType $type): Invoice
    {
        return DB::transaction(function () use ($business, $data, $type) {
            $numberType = match ($type) {
                InvoiceType::INVOICE => 'invoice',
                InvoiceType::PURCHASE_INVOICE => 'purchase_invoice',
                InvoiceType::CREDIT_NOTE => 'credit_note',
                InvoiceType::DEBIT_NOTE => 'debit_note',
                default => 'invoice',
            };

            $invoice = Invoice::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'contact_id' => $data['contact_id'],
                'type' => $type,
                'number' => $this->numberSequence->getNext($business, $numberType),
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
            match ($type) {
                InvoiceType::INVOICE => $this->postInvoice($invoice),
                InvoiceType::PURCHASE_INVOICE => $this->postPurchaseInvoice($invoice),
                InvoiceType::CREDIT_NOTE => $this->postCreditNote($invoice),
                InvoiceType::DEBIT_NOTE => $this->postDebitNote($invoice),
                default => null,
            };

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
     * Post a sales invoice — generates the accounting journal entry.
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

        InvoicePosted::dispatch($invoice->fresh());
    }

    /**
     * Post a purchase invoice — generates the accounting journal entry.
     */
    public function postPurchaseInvoice(Invoice $invoice): void
    {
        $business = $invoice->business;
        $apAccount = Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('sub_type', AccountSubType::ACCOUNTS_PAYABLE)
            ->firstOrFail();

        $lines = [];

        // CR: Accounts Payable (total including tax)
        $lines[] = [
            'account_id' => $apAccount->id,
            'contact_id' => $invoice->contact_id,
            'debit' => 0,
            'credit' => (float) $invoice->total,
            'description' => "Purchase Invoice {$invoice->number}",
        ];

        // DR: Expense accounts (per invoice line)
        foreach ($invoice->lines as $invoiceLine) {
            $lines[] = [
                'account_id' => $invoiceLine->account_id,
                'debit' => (float) $invoiceLine->line_total,
                'credit' => 0,
                'description' => $invoiceLine->description,
            ];

            // DR: Tax Receivable (if applicable)
            if ($invoiceLine->tax_amount > 0) {
                $taxAccount = Account::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->where('sub_type', AccountSubType::TAX_RECEIVABLE)
                    ->firstOrFail();

                $lines[] = [
                    'account_id' => $taxAccount->id,
                    'debit' => (float) $invoiceLine->tax_amount,
                    'credit' => 0,
                    'description' => "Tax on {$invoiceLine->description}",
                    'tax_code_id' => $invoiceLine->tax_code_id,
                ];
            }
        }

        $journalEntry = $this->journalService->createAndPost(
            business: $business,
            date: $invoice->date,
            lines: $lines,
            description: "Purchase Invoice {$invoice->number}",
            sourceType: 'purchase_invoice',
            sourceId: $invoice->id,
        );

        $invoice->update([
            'journal_entry_id' => $journalEntry->id,
            'status' => InvoiceStatus::SENT,
        ]);

        InvoicePosted::dispatch($invoice->fresh());
    }

    /**
     * Post a credit note — generates the accounting journal entry.
     */
    public function postCreditNote(Invoice $invoice): void
    {
        $business = $invoice->business;
        $arAccount = Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('sub_type', AccountSubType::ACCOUNTS_RECEIVABLE)
            ->firstOrFail();

        $lines = [];

        // CR: Accounts Receivable (total including tax) - Reducing what customer owes
        $lines[] = [
            'account_id' => $arAccount->id,
            'contact_id' => $invoice->contact_id,
            'debit' => 0,
            'credit' => (float) $invoice->total,
            'description' => "Credit Note {$invoice->number}",
        ];

        // DR: Revenue accounts (per line) - Reducing revenue
        foreach ($invoice->lines as $invoiceLine) {
            $lines[] = [
                'account_id' => $invoiceLine->account_id,
                'debit' => (float) $invoiceLine->line_total,
                'credit' => 0,
                'description' => $invoiceLine->description,
            ];

            // DR: Tax Payable (if applicable) - Reducing tax liability
            if ($invoiceLine->tax_amount > 0) {
                $taxAccount = Account::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->where('sub_type', AccountSubType::TAX_PAYABLE)
                    ->firstOrFail();

                $lines[] = [
                    'account_id' => $taxAccount->id,
                    'debit' => (float) $invoiceLine->tax_amount,
                    'credit' => 0,
                    'description' => "Tax on {$invoiceLine->description}",
                    'tax_code_id' => $invoiceLine->tax_code_id,
                ];
            }
        }

        $journalEntry = $this->journalService->createAndPost(
            business: $business,
            date: $invoice->date,
            lines: $lines,
            description: "Credit Note {$invoice->number}",
            sourceType: 'credit_note',
            sourceId: $invoice->id,
        );

        $invoice->update([
            'journal_entry_id' => $journalEntry->id,
            'status' => InvoiceStatus::SENT,
        ]);

        InvoicePosted::dispatch($invoice->fresh());
    }

    /**
     * Post a debit note — generates the accounting journal entry.
     * Purchase-side equivalent of a credit note: reduces AP and reverses the expense.
     */
    public function postDebitNote(Invoice $invoice): void
    {
        $business = $invoice->business;
        $apAccount = Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('sub_type', AccountSubType::ACCOUNTS_PAYABLE)
            ->firstOrFail();

        $lines = [];

        // DR: Accounts Payable (total including tax) - Reducing what we owe the supplier
        $lines[] = [
            'account_id' => $apAccount->id,
            'contact_id' => $invoice->contact_id,
            'debit' => (float) $invoice->total,
            'credit' => 0,
            'description' => "Debit Note {$invoice->number}",
        ];

        // CR: Expense accounts (per line) - Reducing expense
        foreach ($invoice->lines as $invoiceLine) {
            $lines[] = [
                'account_id' => $invoiceLine->account_id,
                'debit' => 0,
                'credit' => (float) $invoiceLine->line_total,
                'description' => $invoiceLine->description,
            ];

            // CR: Tax Receivable (if applicable) - Reducing tax receivable
            if ($invoiceLine->tax_amount > 0) {
                $taxAccount = Account::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->where('sub_type', AccountSubType::TAX_RECEIVABLE)
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
            description: "Debit Note {$invoice->number}",
            sourceType: 'debit_note',
            sourceId: $invoice->id,
        );

        $invoice->update([
            'journal_entry_id' => $journalEntry->id,
            'status' => InvoiceStatus::SENT,
        ]);

        InvoicePosted::dispatch($invoice->fresh());
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
        $subtotal = '0';
        $taxTotal = '0';

        foreach ($linesData as $index => $line) {
            $lineTotal = bcmul((string) $line['quantity'], (string) $line['unit_price'], 2);

            if (isset($line['discount_percent']) && $line['discount_percent'] > 0) {
                $discountMultiplier = bcsub('1', bcdiv((string) $line['discount_percent'], '100', 10), 10);
                $lineTotal = bcmul($lineTotal, $discountMultiplier, 2);
            }

            $taxAmount = '0';
            if (! empty($line['tax_code_id'])) {
                $taxCode = TaxCode::withoutGlobalScopes()->find($line['tax_code_id']);
                if ($taxCode) {
                    $taxAmount = bcmul($lineTotal, bcdiv((string) $taxCode->rate, '100', 10), 2);
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

            $subtotal = bcadd($subtotal, $lineTotal, 2);
            $taxTotal = bcadd($taxTotal, $taxAmount, 2);
        }

        return [(float) $subtotal, (float) $taxTotal];
    }
}
