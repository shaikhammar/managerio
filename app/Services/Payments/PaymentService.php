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
            $exchangeRate = (float) ($data['exchange_rate'] ?? 1);
            $currencyCode = $data['currency_code'] ?? $business->currency_code;

            $payment = Payment::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'contact_id' => $data['contact_id'],
                'type' => PaymentType::RECEIPT,
                'number' => $this->numberSequence->getNext($business, 'receipt'),
                'date' => $data['date'],
                'amount' => $data['amount'],
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'bank_account_id' => $data['bank_account_id'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            // Allocate to invoices and calculate base-currency AR credit
            $allocatedBaseAR = '0';
            if (! empty($data['allocations'])) {
                $allocatedBaseAR = $this->allocatePayment($payment, $data['allocations']);
            }

            // Base currency amounts for journal entry
            $paymentBaseAmount = bcmul((string) $data['amount'], (string) $exchangeRate, 2);

            // If no allocations, CR AR by the full payment base amount
            if (bccomp($allocatedBaseAR, '0', 2) === 0) {
                $allocatedBaseAR = $paymentBaseAmount;
            }

            $arAccount = Account::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('sub_type', AccountSubType::ACCOUNTS_RECEIVABLE)
                ->firstOrFail();

            // Build journal lines: DR Bank (base), CR AR (base), +/- FX gain/loss
            $journalLines = [
                [
                    'account_id' => $data['bank_account_id'],
                    'debit' => (float) $paymentBaseAmount,
                    'credit' => 0,
                    'description' => "Payment received - {$payment->number}",
                ],
                [
                    'account_id' => $arAccount->id,
                    'contact_id' => $data['contact_id'],
                    'debit' => 0,
                    'credit' => (float) $allocatedBaseAR,
                    'description' => "Payment received - {$payment->number}",
                ],
            ];

            $fxDiff = bcsub($paymentBaseAmount, $allocatedBaseAR, 2);
            $journalLines = $this->appendForexLine($business, $journalLines, $fxDiff, $payment->number);

            $journalEntry = $this->journalService->createAndPost(
                business: $business,
                date: $payment->date,
                lines: $journalLines,
                description: "Receipt {$payment->number}",
                sourceType: 'payment',
                sourceId: $payment->id,
            );

            $payment->update(['journal_entry_id' => $journalEntry->id]);

            BankTransaction::create([
                'business_id' => $business->id,
                'bank_account_id' => $data['bank_account_id'],
                'date' => $payment->date,
                'description' => $payment->description ?? "Payment received - {$payment->number}",
                'amount' => (float) $paymentBaseAmount,
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
            $exchangeRate = (float) ($data['exchange_rate'] ?? 1);
            $currencyCode = $data['currency_code'] ?? $business->currency_code;

            $payment = Payment::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'contact_id' => $data['contact_id'],
                'type' => PaymentType::PAYMENT,
                'number' => $this->numberSequence->getNext($business, 'payment'),
                'date' => $data['date'],
                'amount' => $data['amount'],
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'bank_account_id' => $data['bank_account_id'],
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            // Allocate to purchase invoices and calculate base-currency AP debit
            $allocatedBaseAP = '0';
            if (! empty($data['allocations'])) {
                $allocatedBaseAP = $this->allocatePayment($payment, $data['allocations']);
            }

            $paymentBaseAmount = bcmul((string) $data['amount'], (string) $exchangeRate, 2);

            if (bccomp($allocatedBaseAP, '0', 2) === 0) {
                $allocatedBaseAP = $paymentBaseAmount;
            }

            $apAccount = Account::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('sub_type', AccountSubType::ACCOUNTS_PAYABLE)
                ->firstOrFail();

            // Build journal lines: DR AP (base), CR Bank (base), +/- FX gain/loss
            $journalLines = [
                [
                    'account_id' => $apAccount->id,
                    'contact_id' => $data['contact_id'],
                    'debit' => (float) $allocatedBaseAP,
                    'credit' => 0,
                    'description' => "Payment to supplier - {$payment->number}",
                ],
                [
                    'account_id' => $data['bank_account_id'],
                    'debit' => 0,
                    'credit' => (float) $paymentBaseAmount,
                    'description' => "Payment to supplier - {$payment->number}",
                ],
            ];

            // FX diff for AP: if we paid less base than what AP was recorded at = gain
            $fxDiff = bcsub($allocatedBaseAP, $paymentBaseAmount, 2);
            $journalLines = $this->appendForexLine($business, $journalLines, $fxDiff, $payment->number);

            $journalEntry = $this->journalService->createAndPost(
                business: $business,
                date: $payment->date,
                lines: $journalLines,
                description: "Payment {$payment->number}",
                sourceType: 'payment',
                sourceId: $payment->id,
            );

            $payment->update(['journal_entry_id' => $journalEntry->id]);

            BankTransaction::create([
                'business_id' => $business->id,
                'bank_account_id' => $data['bank_account_id'],
                'date' => $payment->date,
                'description' => $payment->description ?? "Payment to supplier - {$payment->number}",
                'amount' => -(float) $paymentBaseAmount,
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
     * Returns the total base-currency amount allocated (using each invoice's own exchange rate).
     */
    private function allocatePayment(Payment $payment, array $allocations): string
    {
        $invoiceIds = array_column($allocations, 'invoice_id');
        $invoices = Invoice::withoutGlobalScopes()
            ->whereIn('id', $invoiceIds)
            ->get()
            ->keyBy('id');

        $totalBaseAmount = '0';

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

            // Accumulate base-currency amount using the invoice's own exchange rate
            $invoiceBase = bcmul((string) $allocation['amount'], (string) $invoice->exchange_rate, 2);
            $totalBaseAmount = bcadd($totalBaseAmount, $invoiceBase, 2);

            $newBalanceDue = bcsub((string) $invoice->balance_due, (string) $allocation['amount'], 2);
            $newAmountPaid = bcadd((string) $invoice->amount_paid, (string) $allocation['amount'], 2);

            $invoice->update([
                'amount_paid' => $newAmountPaid,
                'balance_due' => $newBalanceDue,
                'status' => (float) $newBalanceDue <= 0
                    ? InvoiceStatus::PAID
                    : ((float) $newAmountPaid > 0 ? InvoiceStatus::PARTIALLY_PAID : $invoice->status),
            ]);

            $invoice->balance_due = $newBalanceDue;
            $invoice->amount_paid = $newAmountPaid;
        }

        return $totalBaseAmount;
    }

    /**
     * Append a Forex Gain/Loss journal line if there is a non-zero FX difference.
     *
     * @param  array<array<string, mixed>>  $lines
     * @return array<array<string, mixed>>
     */
    private function appendForexLine(Business $business, array $lines, string $fxDiff, string $paymentNumber): array
    {
        if (bccomp($fxDiff, '0', 2) === 0) {
            return $lines;
        }

        $forexAccount = Account::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('sub_type', AccountSubType::FOREX_GAIN_LOSS)
            ->first();

        if (! $forexAccount) {
            throw new DomainException('No Foreign Exchange Gain/Loss account found. Please ensure the chart of accounts includes a FOREX_GAIN_LOSS account.');
        }

        if ((float) $fxDiff > 0) {
            // FX Gain: CR Forex account
            $lines[] = [
                'account_id' => $forexAccount->id,
                'debit' => 0,
                'credit' => (float) $fxDiff,
                'description' => "FX gain on payment {$paymentNumber}",
            ];
        } else {
            // FX Loss: DR Forex account
            $lines[] = [
                'account_id' => $forexAccount->id,
                'debit' => (float) abs((float) $fxDiff),
                'credit' => 0,
                'description' => "FX loss on payment {$paymentNumber}",
            ];
        }

        return $lines;
    }
}
