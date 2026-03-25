<?php

namespace App\Services\Payments;

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Payments\Enums\PaymentType;
use App\Domain\Sales\Enums\InvoiceStatus;
use App\Events\PaymentReceived as PaymentReceivedEvent;
use App\Models\Account;
use App\Models\BankTransaction;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Services\Accounting\JournalService;
use App\Services\Accounting\NumberSequenceService;
use DomainException;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private JournalService $journalService,
        private NumberSequenceService $numberSequence,
    ) {}

    /**
     * Receive a payment from a customer (receipt).
     */
    public function receivePayment(Business $business, array $data): Payment
    {
        return DB::transaction(function () use ($business, $data) {
            $payment = Payment::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'contact_id' => $data['contact_id'],
                'type' => PaymentType::RECEIPT,
                'number' => $this->numberSequence->getNext($business, 'receipt'),
                'date' => $data['date'],
                'amount' => $data['amount'],
                'bank_account_id' => $data['bank_account_id'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            // Allocate to invoices
            if (! empty($data['allocations'])) {
                $this->allocatePayment($payment, $data['allocations']);
            }

            // Create journal entry:
            //   DR Bank Account
            //   CR Accounts Receivable
            $arAccount = Account::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('sub_type', AccountSubType::ACCOUNTS_RECEIVABLE)
                ->firstOrFail();

            $journalEntry = $this->journalService->createAndPost(
                business: $business,
                date: $payment->date,
                lines: [
                    [
                        'account_id' => $data['bank_account_id'],
                        'debit' => (float) $data['amount'],
                        'credit' => 0,
                        'description' => "Payment received - {$payment->number}",
                    ],
                    [
                        'account_id' => $arAccount->id,
                        'contact_id' => $data['contact_id'],
                        'debit' => 0,
                        'credit' => (float) $data['amount'],
                        'description' => "Payment received - {$payment->number}",
                    ],
                ],
                description: "Receipt {$payment->number}",
                sourceType: 'payment',
                sourceId: $payment->id,
            );

            $payment->update(['journal_entry_id' => $journalEntry->id]);

            // Create bank transaction record
            BankTransaction::create([
                'business_id' => $business->id,
                'bank_account_id' => $data['bank_account_id'],
                'date' => $payment->date,
                'description' => $payment->description ?? "Payment received - {$payment->number}",
                'amount' => (float) $payment->amount,
                'reference' => $payment->reference,
                'payment_id' => $payment->id,
                'journal_entry_id' => $journalEntry->id,
            ]);

            $fresh = $payment->fresh(['allocations', 'journalEntry', 'contact']);
            PaymentReceivedEvent::dispatch($fresh);

            return $fresh;
        });
    }

    /**
     * Make a payment to a supplier.
     */
    public function makePayment(Business $business, array $data): Payment
    {
        return DB::transaction(function () use ($business, $data) {
            $payment = Payment::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'contact_id' => $data['contact_id'],
                'type' => PaymentType::PAYMENT,
                'number' => $this->numberSequence->getNext($business, 'payment'),
                'date' => $data['date'],
                'amount' => $data['amount'],
                'bank_account_id' => $data['bank_account_id'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            // Allocate to purchase invoices
            if (! empty($data['allocations'])) {
                $this->allocatePayment($payment, $data['allocations']);
            }

            // Create journal entry:
            //   DR Accounts Payable
            //   CR Bank Account
            $apAccount = Account::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('sub_type', AccountSubType::ACCOUNTS_PAYABLE)
                ->firstOrFail();

            $journalEntry = $this->journalService->createAndPost(
                business: $business,
                date: $payment->date,
                lines: [
                    [
                        'account_id' => $apAccount->id,
                        'contact_id' => $data['contact_id'],
                        'debit' => (float) $data['amount'],
                        'credit' => 0,
                        'description' => "Payment to supplier - {$payment->number}",
                    ],
                    [
                        'account_id' => $data['bank_account_id'],
                        'debit' => 0,
                        'credit' => (float) $data['amount'],
                        'description' => "Payment to supplier - {$payment->number}",
                    ],
                ],
                description: "Payment {$payment->number}",
                sourceType: 'payment',
                sourceId: $payment->id,
            );

            $payment->update(['journal_entry_id' => $journalEntry->id]);

            // Create bank transaction record (negative amount for payments)
            BankTransaction::create([
                'business_id' => $business->id,
                'bank_account_id' => $data['bank_account_id'],
                'date' => $payment->date,
                'description' => $payment->description ?? "Payment to supplier - {$payment->number}",
                'amount' => -(float) $payment->amount,
                'reference' => $payment->reference,
                'payment_id' => $payment->id,
                'journal_entry_id' => $journalEntry->id,
            ]);

            $fresh = $payment->fresh(['allocations', 'journalEntry', 'contact']);
            PaymentReceivedEvent::dispatch($fresh);

            return $fresh;
        });
    }

    /**
     * Allocate payment to invoices and update their balances.
     */
    private function allocatePayment(Payment $payment, array $allocations): void
    {
        $invoiceIds = array_column($allocations, 'invoice_id');
        $invoices = Invoice::withoutGlobalScopes()
            ->whereIn('id', $invoiceIds)
            ->get()
            ->keyBy('id');

        foreach ($allocations as $allocation) {
            /** @var Invoice $invoice */
            $invoice = $invoices->get($allocation['invoice_id']);

            if (bccomp((string) $allocation['amount'], (string) $invoice->balance_due, 2) > 0) {
                throw new DomainException(
                    "Allocation amount ({$allocation['amount']}) exceeds invoice balance due ({$invoice->balance_due})."
                );
            }

            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'amount' => $allocation['amount'],
            ]);

            $newBalanceDue = bcsub((string) $invoice->balance_due, (string) $allocation['amount'], 2);
            $newAmountPaid = bcadd((string) $invoice->amount_paid, (string) $allocation['amount'], 2);

            $invoice->update([
                'amount_paid' => $newAmountPaid,
                'balance_due' => $newBalanceDue,
                'status' => (float) $newBalanceDue <= 0
                    ? InvoiceStatus::PAID
                    : ((float) $newAmountPaid > 0 ? InvoiceStatus::PARTIALLY_PAID : $invoice->status),
            ]);

            // Sync in-memory model so subsequent allocations on the same invoice use the updated balance
            $invoice->balance_due = $newBalanceDue;
            $invoice->amount_paid = $newAmountPaid;
        }
    }
}
