<?php

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Sales\Enums\InvoiceStatus;
use App\Domain\Sales\Enums\InvoiceType;
use App\Models\Account;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
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

    $this->supplier = Contact::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'supplier',
    ]);

    $this->taxCode = TaxCode::factory()->create(['business_id' => $this->business->id, 'rate' => 10]);
});

it('can create a debit note and post journal entries', function () {
    $data = [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            [
                'account_id' => $this->expenseAccount->id,
                'description' => 'Returned Goods',
                'quantity' => 2,
                'unit_price' => 50,
                'tax_code_id' => null,
            ],
        ],
    ];

    $debitNote = $this->invoiceService->createDebitNote($this->business, $data);

    expect($debitNote->type)->toBe(InvoiceType::DEBIT_NOTE)
        ->and($debitNote->status)->toBe(InvoiceStatus::SENT)
        ->and($debitNote->number)->toStartWith('DN-')
        ->and($debitNote->total)->toBe('100.00')
        ->and($debitNote->journalEntry)->not->toBeNull();
});

it('posts correct journal entries for a debit note', function () {
    $data = [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            [
                'account_id' => $this->expenseAccount->id,
                'description' => 'Returned Service',
                'quantity' => 1,
                'unit_price' => 200,
                'tax_code_id' => null,
            ],
        ],
    ];

    $debitNote = $this->invoiceService->createDebitNote($this->business, $data);
    $journalLines = $debitNote->journalEntry->lines;

    // DR: Accounts Payable (reducing liability)
    $apLine = $journalLines->firstWhere('account_id', $this->apAccount->id);
    expect($apLine)->not->toBeNull()
        ->and((float) $apLine->debit)->toBe(200.0)
        ->and((float) $apLine->credit)->toBe(0.0);

    // CR: Expense account (reversing the expense)
    $expenseLine = $journalLines->firstWhere('account_id', $this->expenseAccount->id);
    expect($expenseLine)->not->toBeNull()
        ->and((float) $expenseLine->debit)->toBe(0.0)
        ->and((float) $expenseLine->credit)->toBe(200.0);
});

it('posts correct journal entries for a debit note with tax', function () {
    $data = [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            [
                'account_id' => $this->expenseAccount->id,
                'description' => 'Returned Goods with Tax',
                'quantity' => 1,
                'unit_price' => 100,
                'tax_code_id' => $this->taxCode->id,
            ],
        ],
    ];

    $debitNote = $this->invoiceService->createDebitNote($this->business, $data);

    expect((float) $debitNote->tax_amount)->toBe(10.0)
        ->and((float) $debitNote->total)->toBe(110.0);

    $journalLines = $debitNote->journalEntry->lines;

    // CR: Tax Receivable (reducing tax receivable)
    $taxLine = $journalLines->firstWhere('account_id', $this->taxReceivableAccount->id);
    expect($taxLine)->not->toBeNull()
        ->and((float) $taxLine->credit)->toBe(10.0);
});

it('lists debit notes on index page', function () {
    Invoice::factory()->count(3)->create([
        'business_id' => $this->business->id,
        'type' => InvoiceType::DEBIT_NOTE,
        'status' => InvoiceStatus::SENT,
    ]);

    $this->actingAs($this->user)
        ->get('/purchases/debit-notes')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('purchases/debit-notes/index')
            ->has('debitNotes.data', 3)
        );
});

it('shows create form', function () {
    $this->actingAs($this->user)
        ->get('/purchases/debit-notes/create')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('purchases/debit-notes/create'));
});

it('creates a debit note via POST request', function () {
    $data = [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            [
                'account_id' => $this->expenseAccount->id,
                'description' => 'Returned Item',
                'quantity' => 1,
                'unit_price' => 75,
                'discount_percent' => 0,
                'tax_code_id' => null,
            ],
        ],
    ];

    $this->actingAs($this->user)
        ->post('/purchases/debit-notes', $data)
        ->assertRedirect();

    $this->assertDatabaseHas('invoices', [
        'business_id' => $this->business->id,
        'type' => InvoiceType::DEBIT_NOTE->value,
        'total' => 75,
    ]);
});

it('validates required fields', function () {
    $this->actingAs($this->user)
        ->post('/purchases/debit-notes', [])
        ->assertSessionHasErrors(['contact_id', 'date', 'lines']);
});

it('shows a debit note', function () {
    $debitNote = $this->invoiceService->createDebitNote($this->business, [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            ['account_id' => $this->expenseAccount->id, 'description' => 'Return', 'quantity' => 1, 'unit_price' => 50, 'tax_code_id' => null],
        ],
    ]);

    $this->actingAs($this->user)
        ->get("/purchases/debit-notes/{$debitNote->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('purchases/debit-notes/show'));
});
