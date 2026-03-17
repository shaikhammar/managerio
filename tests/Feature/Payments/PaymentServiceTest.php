<?php

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Sales\Enums\InvoiceStatus;
use App\Models\Account;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->business = Business::factory()->create();
    $this->user = User::factory()->create();
    $this->business->users()->attach($this->user, ['role' => 'owner']);
    Auth::login($this->user);

    $this->paymentService = app(PaymentService::class);

    // Set up bank account
    $this->bankAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'sub_type' => AccountSubType::BANK,
        'code' => '1010',
    ]);

    // Set up AR account
    $this->arAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'sub_type' => AccountSubType::ACCOUNTS_RECEIVABLE,
        'code' => '1100',
    ]);

    // Set up AP account
    $this->apAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'sub_type' => AccountSubType::ACCOUNTS_PAYABLE,
        'code' => '2000',
    ]);

    $this->customer = Contact::factory()->create(['business_id' => $this->business->id, 'type' => 'customer']);
    $this->supplier = Contact::factory()->supplier()->create(['business_id' => $this->business->id]);
});

it('can receive a payment and allocate to an invoice', function () {
    // Create an invoice first
    $invoice = Invoice::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->customer->id,
        'total' => 1000,
        'balance_due' => 1000,
        'status' => InvoiceStatus::SENT,
    ]);

    $data = [
        'contact_id' => $this->customer->id,
        'date' => now()->format('Y-m-d'),
        'amount' => 1000,
        'bank_account_id' => $this->bankAccount->id,
        'allocations' => [
            ['invoice_id' => $invoice->id, 'amount' => 1000],
        ],
    ];

    $payment = $this->paymentService->receivePayment($this->business, $data);

    expect($payment->amount)->toBe('1000.00')
        ->and($payment->allocations)->toHaveCount(1)
        ->and($payment->journal_entry_id)->not->toBeNull();

    // Check invoice balance
    expect($invoice->fresh()->balance_due)->toBe('0.00')
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::PAID);

    // Check journal entry: DR Bank, CR AR
    $journal = $payment->journalEntry;
    expect($journal->lines()->where('account_id', $this->bankAccount->id)->first()->debit)->toBe('1000.00')
        ->and($journal->lines()->where('account_id', $this->arAccount->id)->first()->credit)->toBe('1000.00');
});

it('can make a payment to a supplier', function () {
    $data = [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'amount' => 500,
        'bank_account_id' => $this->bankAccount->id,
    ];

    $payment = $this->paymentService->makePayment($this->business, $data);

    expect($payment->amount)->toBe('500.00')
        ->and($payment->journal_entry_id)->not->toBeNull();

    // Check journal entry: DR AP, CR Bank
    $journal = $payment->journalEntry;
    expect($journal->lines()->where('account_id', $this->apAccount->id)->first()->debit)->toBe('500.00')
        ->and($journal->lines()->where('account_id', $this->bankAccount->id)->first()->credit)->toBe('500.00');
});

it('throws exception when allocating more than invoice balance', function () {
    $invoice = Invoice::factory()->create([
        'business_id' => $this->business->id,
        'balance_due' => 100,
    ]);

    $data = [
        'contact_id' => $this->customer->id,
        'date' => now()->format('Y-m-d'),
        'amount' => 150,
        'bank_account_id' => $this->bankAccount->id,
        'allocations' => [
            ['invoice_id' => $invoice->id, 'amount' => 150],
        ],
    ];

    $this->paymentService->receivePayment($this->business, $data);
})->throws(DomainException::class, 'exceeds invoice balance due');
