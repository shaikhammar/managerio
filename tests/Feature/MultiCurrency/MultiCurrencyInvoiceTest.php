<?php

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Sales\Enums\InvoiceStatus;
use App\Models\Account;
use App\Models\Business;
use App\Models\Contact;
use App\Models\ExchangeRate;
use App\Models\User;
use App\Services\Sales\InvoiceService;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->business = Business::factory()->create(['currency_code' => 'USD']);
    $this->user = User::factory()->create();
    $this->business->users()->attach($this->user, ['role' => 'owner']);
    Auth::login($this->user);
    session(['current_business_id' => $this->business->id]);

    $this->invoiceService = app(InvoiceService::class);

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

    $this->apAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'sub_type' => AccountSubType::ACCOUNTS_PAYABLE,
        'code' => '2000',
    ]);

    $this->expenseAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'expense',
        'code' => '5000',
    ]);

    $this->contact = Contact::factory()->create(['business_id' => $this->business->id]);
});

it('creates a foreign currency sales invoice with correct currency and exchange rate', function () {
    $invoice = $this->invoiceService->create($this->business, [
        'contact_id' => $this->contact->id,
        'date' => '2024-01-15',
        'currency_code' => 'EUR',
        'exchange_rate' => 1.10,
        'lines' => [
            [
                'account_id' => $this->revenueAccount->id,
                'description' => 'Translation service',
                'quantity' => 1,
                'unit_price' => 1000,
            ],
        ],
    ]);

    expect($invoice->currency_code)->toBe('EUR')
        ->and((float) $invoice->exchange_rate)->toBe(1.10)
        ->and((float) $invoice->total)->toBe(1000.0)
        ->and((float) $invoice->balance_due)->toBe(1000.0);
});

it('posts a foreign currency sales invoice with base-currency journal entries', function () {
    // EUR invoice at 1 EUR = 1.10 USD
    $invoice = $this->invoiceService->create($this->business, [
        'contact_id' => $this->contact->id,
        'date' => '2024-01-15',
        'currency_code' => 'EUR',
        'exchange_rate' => 1.10,
        'lines' => [
            [
                'account_id' => $this->revenueAccount->id,
                'description' => 'Translation service',
                'quantity' => 1,
                'unit_price' => 1000,
            ],
        ],
    ]);

    $this->invoiceService->postInvoice($invoice->load('lines'));

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::SENT);

    // Journal entry should be in base currency (USD)
    $journal = $invoice->journalEntry;
    expect($journal)->not->toBeNull();

    $arLine = $journal->lines()->where('account_id', $this->arAccount->id)->first();
    $revenueLine = $journal->lines()->where('account_id', $this->revenueAccount->id)->first();

    // AR debit: 1000 EUR × 1.10 = 1100 USD
    expect((float) $arLine->debit)->toBe(1100.0)
        ->and((float) $arLine->credit)->toBe(0.0);

    // Revenue credit: 1000 EUR × 1.10 = 1100 USD
    expect((float) $revenueLine->credit)->toBe(1100.0)
        ->and((float) $revenueLine->debit)->toBe(0.0);
});

it('posts a base currency invoice with a rate of 1 — no FX conversion', function () {
    $invoice = $this->invoiceService->create($this->business, [
        'contact_id' => $this->contact->id,
        'date' => '2024-01-15',
        'lines' => [
            [
                'account_id' => $this->revenueAccount->id,
                'description' => 'Service',
                'quantity' => 1,
                'unit_price' => 500,
            ],
        ],
    ]);

    $this->invoiceService->postInvoice($invoice->load('lines'));

    $journal = $invoice->fresh()->journalEntry;
    $arLine = $journal->lines()->where('account_id', $this->arAccount->id)->first();

    // 500 USD × 1.0 = 500 USD (no change)
    expect((float) $arLine->debit)->toBe(500.0);
});

it('stores and retrieves exchange rates for a business', function () {
    $rate = ExchangeRate::create([
        'business_id' => $this->business->id,
        'currency_code' => 'EUR',
        'rate' => 1.085,
        'date' => '2024-01-15',
    ]);

    expect($rate->currency_code)->toBe('EUR')
        ->and((float) $rate->rate)->toBe(1.085)
        ->and($rate->business_id)->toBe($this->business->id);
});

it('defaults to business currency when no currency_code is provided', function () {
    $invoice = $this->invoiceService->create($this->business, [
        'contact_id' => $this->contact->id,
        'date' => '2024-01-15',
        'lines' => [
            [
                'account_id' => $this->revenueAccount->id,
                'description' => 'Service',
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ],
    ]);

    expect($invoice->currency_code)->toBe('USD')
        ->and((float) $invoice->exchange_rate)->toBe(1.0);
});

it('posts a foreign currency purchase invoice with base-currency journal entries', function () {
    // GBP purchase invoice at 1 GBP = 1.27 USD
    $invoice = $this->invoiceService->createPurchaseInvoice($this->business, [
        'contact_id' => $this->contact->id,
        'date' => '2024-01-15',
        'currency_code' => 'GBP',
        'exchange_rate' => 1.27,
        'lines' => [
            [
                'account_id' => $this->expenseAccount->id,
                'description' => 'Freelance translation',
                'quantity' => 1,
                'unit_price' => 500,
            ],
        ],
    ]);

    $journal = $invoice->journalEntry;
    expect($journal)->not->toBeNull();

    $apLine = $journal->lines()->where('account_id', $this->apAccount->id)->first();
    $expenseLine = $journal->lines()->where('account_id', $this->expenseAccount->id)->first();

    // AP credit: 500 GBP × 1.27 = 635 USD
    expect((float) $apLine->credit)->toBe(635.0)
        ->and((float) $apLine->debit)->toBe(0.0);

    // Expense debit: 500 GBP × 1.27 = 635 USD
    expect((float) $expenseLine->debit)->toBe(635.0)
        ->and((float) $expenseLine->credit)->toBe(0.0);
});
