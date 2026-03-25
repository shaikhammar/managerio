<?php

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Sales\Enums\InvoiceStatus;
use App\Models\Account;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\TaxCode;
use App\Models\User;
use App\Services\Sales\InvoiceService;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->business = Business::factory()->create(['currency_code' => 'USD']);
    $this->user = User::factory()->create();
    $this->business->users()->attach($this->user, ['role' => 'owner']);
    Auth::login($this->user);

    $this->invoiceService = app(InvoiceService::class);

    // Set up required accounts
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

    $this->taxAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'sub_type' => AccountSubType::TAX_PAYABLE,
        'code' => '2100',
    ]);

    $this->contact = Contact::factory()->create(['business_id' => $this->business->id]);
    $this->taxCode = TaxCode::factory()->create(['business_id' => $this->business->id, 'rate' => 10]);

    // Set up AP and Expense accounts for purchase tests
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

    $this->taxReceivableAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'sub_type' => AccountSubType::TAX_RECEIVABLE,
        'code' => '1200',
    ]);
});

it('can create a purchase invoice and post journal entries', function () {
    $data = [
        'contact_id' => $this->contact->id,
        'date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
        'lines' => [
            [
                'account_id' => $this->expenseAccount->id,
                'description' => 'Business Utility',
                'quantity' => 1,
                'unit_price' => 100,
                'tax_code_id' => $this->taxCode->id,
            ],
        ],
    ];

    $invoice = $this->invoiceService->createPurchaseInvoice($this->business, $data);

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->total)->toBe('110.00')
        ->and($invoice->status)->toBe(InvoiceStatus::SENT);

    $journalEntry = $invoice->journalEntry;
    expect($journalEntry->lines)->toHaveCount(3); // AP, Expense, Tax Receivable

    $apLine = $journalEntry->lines()->where('account_id', $this->apAccount->id)->first();
    expect($apLine->credit)->toBe('110.00');

    $expLine = $journalEntry->lines()->where('account_id', $this->expenseAccount->id)->first();
    expect($expLine->debit)->toBe('100.00');

    $taxLine = $journalEntry->lines()->where('account_id', $this->taxReceivableAccount->id)->first();
    expect($taxLine->debit)->toBe('10.00');
});

it('can create a credit note and post journal entries', function () {
    $data = [
        'contact_id' => $this->contact->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            [
                'account_id' => $this->revenueAccount->id,
                'description' => 'Service Refund',
                'quantity' => 1,
                'unit_price' => 200,
                'tax_code_id' => $this->taxCode->id,
            ],
        ],
    ];

    $creditNote = $this->invoiceService->createCreditNote($this->business, $data);

    expect($creditNote)->toBeInstanceOf(Invoice::class)
        ->and($creditNote->total)->toBe('220.00')
        ->and($creditNote->status)->toBe(InvoiceStatus::SENT);

    $journalEntry = $creditNote->journalEntry;
    expect($journalEntry->lines)->toHaveCount(3); // AR (CR), Revenue (DR), Tax (DR)

    $arLine = $journalEntry->lines()->where('account_id', $this->arAccount->id)->first();
    expect($arLine->credit)->toBe('220.00');

    $revLine = $journalEntry->lines()->where('account_id', $this->revenueAccount->id)->first();
    expect($revLine->debit)->toBe('200.00');

    $taxLine = $journalEntry->lines()->where('account_id', $this->taxAccount->id)->first();
    expect($taxLine->debit)->toBe('20.00');
});

it('can create an invoice and post journal entries', function () {
    $data = [
        'contact_id' => $this->contact->id,
        'date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
        'lines' => [
            [
                'account_id' => $this->revenueAccount->id,
                'description' => 'Test Service',
                'quantity' => 2,
                'unit_price' => 100,
                'tax_code_id' => $this->taxCode->id,
            ],
        ],
    ];

    $invoice = $this->invoiceService->create($this->business, $data);
    $this->invoiceService->postInvoice($invoice->fresh(['lines']));
    $invoice = $invoice->fresh(['lines', 'journalEntry.lines']);

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->subtotal)->toBe('200.00')
        ->and($invoice->tax_amount)->toBe('20.00')
        ->and($invoice->total)->toBe('220.00')
        ->and($invoice->status)->toBe(InvoiceStatus::SENT)
        ->and($invoice->journal_entry_id)->not->toBeNull();

    // Verify journal entry lines
    $journalEntry = $invoice->journalEntry;
    expect($journalEntry->lines)->toHaveCount(3); // AR, Revenue, Tax

    $arLine = $journalEntry->lines()->where('account_id', $this->arAccount->id)->first();
    expect($arLine->debit)->toBe('220.00');

    $revLine = $journalEntry->lines()->where('account_id', $this->revenueAccount->id)->first();
    expect($revLine->credit)->toBe('200.00');

    $taxLine = $journalEntry->lines()->where('account_id', $this->taxAccount->id)->first();
    expect($taxLine->credit)->toBe('20.00');
});

it('can void an invoice', function () {
    $data = [
        'contact_id' => $this->contact->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            ['account_id' => $this->revenueAccount->id, 'description' => 'Test', 'quantity' => 1, 'unit_price' => 100],
        ],
    ];

    $invoice = $this->invoiceService->create($this->business, $data);
    $this->invoiceService->postInvoice($invoice->fresh(['lines']));
    $invoice = $invoice->fresh();
    $originalJournalId = $invoice->journal_entry_id;

    $this->invoiceService->void($invoice);

    expect($invoice->status)->toBe(InvoiceStatus::VOID);

    // Verify reversal journal entry exists
    $reversal = JournalEntry::where('reversal_of_id', $originalJournalId)->first();
    expect($reversal)->not->toBeNull();
});

it('cannot void an invoice with payments', function () {
    $invoice = Invoice::factory()->create([
        'business_id' => $this->business->id,
        'amount_paid' => 50,
        'total' => 100,
        'balance_due' => 50,
    ]);

    $this->invoiceService->void($invoice);
})->throws(DomainException::class, 'Cannot void an invoice with payments');
