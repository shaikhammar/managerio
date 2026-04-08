<?php

use App\Domain\Accounting\Enums\AccountSubType;
use App\Models\Account;
use App\Models\Business;
use App\Models\Contact;
use App\Models\User;
use App\Services\Payments\PaymentService;
use App\Services\Sales\InvoiceService;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->business = Business::factory()->create(['currency_code' => 'USD']);
    $this->user = User::factory()->create();
    $this->business->users()->attach($this->user, ['role' => 'owner']);
    Auth::login($this->user);
    session(['current_business_id' => $this->business->id]);

    $this->invoiceService = app(InvoiceService::class);
    $this->paymentService = app(PaymentService::class);

    $this->arAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'sub_type' => AccountSubType::ACCOUNTS_RECEIVABLE,
        'code' => '1100',
    ]);

    $this->revenueAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'revenue',
        'code' => '4000',
    ]);

    $this->forexAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'sub_type' => AccountSubType::FOREX_GAIN_LOSS,
        'code' => '8000',
        'type' => 'revenue',
    ]);

    $this->bankAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'sub_type' => AccountSubType::BANK,
        'code' => '1010',
    ]);

    $this->contact = Contact::factory()->create(['business_id' => $this->business->id]);
});

it('records a base-currency receipt with no FX gain/loss', function () {
    // USD invoice
    $invoice = $this->invoiceService->create($this->business, [
        'contact_id' => $this->contact->id,
        'date' => '2024-01-15',
        'lines' => [
            [
                'account_id' => $this->revenueAccount->id,
                'description' => 'Service',
                'quantity' => 1,
                'unit_price' => 1000,
            ],
        ],
    ]);

    $this->invoiceService->postInvoice($invoice->load('lines'));
    $invoice->refresh();

    $payment = $this->paymentService->receivePayment($this->business, [
        'contact_id' => $this->contact->id,
        'date' => '2024-01-20',
        'amount' => 1000,
        'currency_code' => 'USD',
        'exchange_rate' => 1,
        'bank_account_id' => $this->bankAccount->id,
        'allocations' => [['invoice_id' => $invoice->id, 'amount' => 1000]],
    ]);

    $journal = $payment->journalEntry;
    expect($journal->lines()->count())->toBe(2);

    $bankLine = $journal->lines()->where('account_id', $this->bankAccount->id)->first();
    $arLine = $journal->lines()->where('account_id', $this->arAccount->id)->first();

    expect((float) $bankLine->debit)->toBe(1000.0)
        ->and((float) $arLine->credit)->toBe(1000.0);

    // No FX line
    $fxLine = $journal->lines()->where('account_id', $this->forexAccount->id)->first();
    expect($fxLine)->toBeNull();
});

it('records a foreign currency receipt with FX loss when payment rate is lower than invoice rate', function () {
    // EUR invoice at 1 EUR = 1.10 USD — AR debited 1100 USD
    $invoice = $this->invoiceService->create($this->business, [
        'contact_id' => $this->contact->id,
        'date' => '2024-01-15',
        'currency_code' => 'EUR',
        'exchange_rate' => 1.10,
        'lines' => [
            [
                'account_id' => $this->revenueAccount->id,
                'description' => 'Translation',
                'quantity' => 1,
                'unit_price' => 1000,
            ],
        ],
    ]);

    $this->invoiceService->postInvoice($invoice->load('lines'));
    $invoice->refresh();

    // Payment received at 1 EUR = 1.08 USD (rate dropped)
    $payment = $this->paymentService->receivePayment($this->business, [
        'contact_id' => $this->contact->id,
        'date' => '2024-01-25',
        'amount' => 1000,
        'currency_code' => 'EUR',
        'exchange_rate' => 1.08,
        'bank_account_id' => $this->bankAccount->id,
        'allocations' => [['invoice_id' => $invoice->id, 'amount' => 1000]],
    ]);

    $journal = $payment->journalEntry;

    $bankLine = $journal->lines()->where('account_id', $this->bankAccount->id)->first();
    $arLine = $journal->lines()->where('account_id', $this->arAccount->id)->first();
    $fxLine = $journal->lines()->where('account_id', $this->forexAccount->id)->first();

    // Bank DR: 1000 EUR × 1.08 = 1080 USD
    expect((float) $bankLine->debit)->toBe(1080.0);

    // AR CR: 1000 EUR × 1.10 (invoice rate) = 1100 USD
    expect((float) $arLine->credit)->toBe(1100.0);

    // FX Loss DR: 1100 - 1080 = 20 USD (we received less than expected)
    expect($fxLine)->not->toBeNull()
        ->and((float) $fxLine->debit)->toBe(20.0)
        ->and((float) $fxLine->credit)->toBe(0.0);
});

it('records a foreign currency receipt with FX gain when payment rate is higher than invoice rate', function () {
    // EUR invoice at 1 EUR = 1.08 USD
    $invoice = $this->invoiceService->create($this->business, [
        'contact_id' => $this->contact->id,
        'date' => '2024-01-15',
        'currency_code' => 'EUR',
        'exchange_rate' => 1.08,
        'lines' => [
            [
                'account_id' => $this->revenueAccount->id,
                'description' => 'Translation',
                'quantity' => 1,
                'unit_price' => 1000,
            ],
        ],
    ]);

    $this->invoiceService->postInvoice($invoice->load('lines'));
    $invoice->refresh();

    // Payment received at 1 EUR = 1.12 USD (rate improved)
    $payment = $this->paymentService->receivePayment($this->business, [
        'contact_id' => $this->contact->id,
        'date' => '2024-01-25',
        'amount' => 1000,
        'currency_code' => 'EUR',
        'exchange_rate' => 1.12,
        'bank_account_id' => $this->bankAccount->id,
        'allocations' => [['invoice_id' => $invoice->id, 'amount' => 1000]],
    ]);

    $journal = $payment->journalEntry;

    $bankLine = $journal->lines()->where('account_id', $this->bankAccount->id)->first();
    $arLine = $journal->lines()->where('account_id', $this->arAccount->id)->first();
    $fxLine = $journal->lines()->where('account_id', $this->forexAccount->id)->first();

    // Bank DR: 1000 EUR × 1.12 = 1120 USD
    expect((float) $bankLine->debit)->toBe(1120.0);

    // AR CR: 1000 EUR × 1.08 (invoice rate) = 1080 USD
    expect((float) $arLine->credit)->toBe(1080.0);

    // FX Gain CR: 1120 - 1080 = 40 USD (we received more than expected)
    expect($fxLine)->not->toBeNull()
        ->and((float) $fxLine->credit)->toBe(40.0)
        ->and((float) $fxLine->debit)->toBe(0.0);
});

it('records a foreign currency receipt with currency and exchange_rate on the payment', function () {
    $payment = $this->paymentService->receivePayment($this->business, [
        'contact_id' => $this->contact->id,
        'date' => '2024-01-20',
        'amount' => 500,
        'currency_code' => 'EUR',
        'exchange_rate' => 1.10,
        'bank_account_id' => $this->bankAccount->id,
    ]);

    expect($payment->currency_code)->toBe('EUR')
        ->and((float) $payment->exchange_rate)->toBe(1.10)
        ->and((float) $payment->amount)->toBe(500.0);
});
