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

it('can create a purchase order', function () {
    $data = [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            [
                'account_id' => $this->expenseAccount->id,
                'description' => 'Office Supplies',
                'quantity' => 5,
                'unit_price' => 20,
                'tax_code_id' => null,
            ],
        ],
    ];

    $po = $this->invoiceService->createPurchaseOrder($this->business, $data);

    expect($po->type)->toBe(InvoiceType::PURCHASE_ORDER)
        ->and($po->status)->toBe(InvoiceStatus::DRAFT)
        ->and($po->number)->toStartWith('PO-')
        ->and($po->total)->toBe('100.00')
        ->and($po->journal_entry_id)->toBeNull();
});

it('does not post journal entries for a purchase order', function () {
    $po = $this->invoiceService->createPurchaseOrder($this->business, [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            ['account_id' => $this->expenseAccount->id, 'description' => 'Item', 'quantity' => 1, 'unit_price' => 100, 'tax_code_id' => null],
        ],
    ]);

    expect($po->journalEntry)->toBeNull();
    $this->assertDatabaseMissing('journal_entries', ['source_type' => 'purchase_order']);
});

it('can be marked as sent', function () {
    $po = $this->invoiceService->createPurchaseOrder($this->business, [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            ['account_id' => $this->expenseAccount->id, 'description' => 'Item', 'quantity' => 1, 'unit_price' => 50, 'tax_code_id' => null],
        ],
    ]);

    $this->invoiceService->sendPurchaseOrder($po);

    expect($po->fresh()->status)->toBe(InvoiceStatus::SENT);
});

it('can be converted to a purchase invoice', function () {
    $po = $this->invoiceService->createPurchaseOrder($this->business, [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            ['account_id' => $this->expenseAccount->id, 'description' => 'Laptop', 'quantity' => 2, 'unit_price' => 500, 'tax_code_id' => null],
        ],
    ]);

    $purchaseInvoice = $this->invoiceService->convertPurchaseOrderToInvoice($po);

    expect($purchaseInvoice->type)->toBe(InvoiceType::PURCHASE_INVOICE)
        ->and($purchaseInvoice->number)->toStartWith('PI-')
        ->and($purchaseInvoice->purchase_order_id)->toBe($po->id)
        ->and($purchaseInvoice->total)->toBe('1000.00')
        ->and($purchaseInvoice->journal_entry_id)->not->toBeNull()
        ->and($po->fresh()->status)->toBe(InvoiceStatus::INVOICED);
});

it('cannot convert an already invoiced purchase order', function () {
    $po = $this->invoiceService->createPurchaseOrder($this->business, [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            ['account_id' => $this->expenseAccount->id, 'description' => 'Item', 'quantity' => 1, 'unit_price' => 100, 'tax_code_id' => null],
        ],
    ]);

    $this->invoiceService->convertPurchaseOrderToInvoice($po);

    expect(fn () => $this->invoiceService->convertPurchaseOrderToInvoice($po->fresh()))
        ->toThrow(DomainException::class, 'already been invoiced');
});

it('lists purchase orders on index page', function () {
    Invoice::factory()->count(3)->create([
        'business_id' => $this->business->id,
        'type' => InvoiceType::PURCHASE_ORDER,
        'status' => InvoiceStatus::DRAFT,
    ]);

    $this->actingAs($this->user)
        ->get('/purchases/purchase-orders')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('purchases/purchase-orders/index')
            ->has('purchaseOrders.data', 3)
        );
});

it('shows the create form', function () {
    $this->actingAs($this->user)
        ->get('/purchases/purchase-orders/create')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('purchases/purchase-orders/create'));
});

it('creates a purchase order via POST', function () {
    $data = [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            [
                'account_id' => $this->expenseAccount->id,
                'description' => 'Stationery',
                'quantity' => 10,
                'unit_price' => 5,
                'discount_percent' => 0,
                'tax_code_id' => null,
            ],
        ],
    ];

    $this->actingAs($this->user)
        ->post('/purchases/purchase-orders', $data)
        ->assertRedirect();

    $this->assertDatabaseHas('invoices', [
        'business_id' => $this->business->id,
        'type' => InvoiceType::PURCHASE_ORDER->value,
        'total' => 50,
    ]);
});

it('validates required fields on store', function () {
    $this->actingAs($this->user)
        ->post('/purchases/purchase-orders', [])
        ->assertSessionHasErrors(['contact_id', 'date', 'lines']);
});

it('shows a purchase order', function () {
    $po = $this->invoiceService->createPurchaseOrder($this->business, [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            ['account_id' => $this->expenseAccount->id, 'description' => 'Item', 'quantity' => 1, 'unit_price' => 75, 'tax_code_id' => null],
        ],
    ]);

    $this->actingAs($this->user)
        ->get("/purchases/purchase-orders/{$po->id}")
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('purchases/purchase-orders/show'));
});

it('marks a purchase order as sent via POST', function () {
    $po = $this->invoiceService->createPurchaseOrder($this->business, [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            ['account_id' => $this->expenseAccount->id, 'description' => 'Item', 'quantity' => 1, 'unit_price' => 100, 'tax_code_id' => null],
        ],
    ]);

    $this->actingAs($this->user)
        ->post("/purchases/purchase-orders/{$po->id}/send")
        ->assertRedirect();

    expect($po->fresh()->status)->toBe(InvoiceStatus::SENT);
});

it('converts a purchase order to a purchase invoice via POST', function () {
    $po = $this->invoiceService->createPurchaseOrder($this->business, [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            ['account_id' => $this->expenseAccount->id, 'description' => 'Monitor', 'quantity' => 1, 'unit_price' => 300, 'tax_code_id' => null],
        ],
    ]);

    $this->actingAs($this->user)
        ->post("/purchases/purchase-orders/{$po->id}/convert")
        ->assertRedirect();

    expect($po->fresh()->status)->toBe(InvoiceStatus::INVOICED);
    $this->assertDatabaseHas('invoices', [
        'purchase_order_id' => $po->id,
        'type' => InvoiceType::PURCHASE_INVOICE->value,
    ]);
});
