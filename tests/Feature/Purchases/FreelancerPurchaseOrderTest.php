<?php

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Sales\Enums\InvoiceStatus;
use App\Models\Account;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Language;
use App\Models\LanguagePair;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\Sales\InvoiceService;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->business = Business::factory()->create(['currency_code' => 'USD']);
    $this->user = User::factory()->create();
    $this->business->users()->attach($this->user, ['role' => 'owner']);
    Auth::login($this->user);

    $this->invoiceService = app(InvoiceService::class);

    Account::factory()->create([
        'business_id' => $this->business->id,
        'sub_type' => AccountSubType::ACCOUNTS_PAYABLE,
        'code' => '2000',
    ]);

    $this->expenseAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'expense',
        'code' => '5100',
    ]);

    $this->supplier = Contact::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'supplier',
    ]);

    $sourceLang = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'en', 'name' => 'English']);
    $targetLang = Language::factory()->create(['business_id' => $this->business->id, 'code' => 'fr', 'name' => 'French']);

    $this->languagePair = LanguagePair::factory()->create([
        'business_id' => $this->business->id,
        'source_language_id' => $sourceLang->id,
        'target_language_id' => $targetLang->id,
        'is_active' => true,
    ]);

    $this->serviceType = ServiceType::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Translation',
        'default_unit' => 'word',
    ]);
});

// ── Helper ─────────────────────────────────────────────────────────────────

function makeFreelancerPo(InvoiceService $service, Business $business, Contact $supplier, Account $account): Invoice
{
    return $service->createPurchaseOrder($business, [
        'contact_id' => $supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            [
                'account_id' => $account->id,
                'description' => 'Translation EN→FR',
                'quantity' => 1000,
                'unit_price' => 0.10,
                'tax_code_id' => null,
            ],
        ],
    ]);
}

// ── Status enum ─────────────────────────────────────────────────────────────

it('has the accepted status label and color', function () {
    expect(InvoiceStatus::ACCEPTED->label())->toBe('Accepted')
        ->and(InvoiceStatus::ACCEPTED->color())->toBe('violet');
});

it('has the in_progress status label and color', function () {
    expect(InvoiceStatus::IN_PROGRESS->label())->toBe('In Progress')
        ->and(InvoiceStatus::IN_PROGRESS->color())->toBe('cyan');
});

it('has the delivered status label and color', function () {
    expect(InvoiceStatus::DELIVERED->label())->toBe('Delivered')
        ->and(InvoiceStatus::DELIVERED->color())->toBe('emerald');
});

// ── Service: accept ─────────────────────────────────────────────────────────

it('accepts a sent purchase order', function () {
    $po = makeFreelancerPo($this->invoiceService, $this->business, $this->supplier, $this->expenseAccount);
    $this->invoiceService->sendPurchaseOrder($po);

    $this->invoiceService->acceptPurchaseOrder($po->fresh());

    expect($po->fresh()->status)->toBe(InvoiceStatus::ACCEPTED);
});

it('cannot accept a draft purchase order', function () {
    $po = makeFreelancerPo($this->invoiceService, $this->business, $this->supplier, $this->expenseAccount);

    expect(fn () => $this->invoiceService->acceptPurchaseOrder($po))
        ->toThrow(DomainException::class, 'Only sent purchase orders can be accepted.');
});

// ── Service: start ──────────────────────────────────────────────────────────

it('starts progress on an accepted purchase order', function () {
    $po = makeFreelancerPo($this->invoiceService, $this->business, $this->supplier, $this->expenseAccount);
    $this->invoiceService->sendPurchaseOrder($po);
    $this->invoiceService->acceptPurchaseOrder($po->fresh());

    $this->invoiceService->startPurchaseOrderProgress($po->fresh());

    expect($po->fresh()->status)->toBe(InvoiceStatus::IN_PROGRESS);
});

it('cannot start a purchase order that is not accepted', function () {
    $po = makeFreelancerPo($this->invoiceService, $this->business, $this->supplier, $this->expenseAccount);

    expect(fn () => $this->invoiceService->startPurchaseOrderProgress($po))
        ->toThrow(DomainException::class, 'Only accepted purchase orders can be started.');
});

// ── Service: deliver ────────────────────────────────────────────────────────

it('marks an in-progress purchase order as delivered', function () {
    $po = makeFreelancerPo($this->invoiceService, $this->business, $this->supplier, $this->expenseAccount);
    $this->invoiceService->sendPurchaseOrder($po);
    $this->invoiceService->acceptPurchaseOrder($po->fresh());
    $this->invoiceService->startPurchaseOrderProgress($po->fresh());

    $this->invoiceService->deliverPurchaseOrder($po->fresh());

    expect($po->fresh()->status)->toBe(InvoiceStatus::DELIVERED);
});

it('cannot deliver a purchase order that is not in progress', function () {
    $po = makeFreelancerPo($this->invoiceService, $this->business, $this->supplier, $this->expenseAccount);

    expect(fn () => $this->invoiceService->deliverPurchaseOrder($po))
        ->toThrow(DomainException::class, 'Only in-progress purchase orders can be marked as delivered.');
});

// ── Full lifecycle ──────────────────────────────────────────────────────────

it('completes the full freelancer PO lifecycle: draft → sent → accepted → in_progress → delivered → invoiced', function () {
    $po = makeFreelancerPo($this->invoiceService, $this->business, $this->supplier, $this->expenseAccount);

    expect($po->status)->toBe(InvoiceStatus::DRAFT);

    $this->invoiceService->sendPurchaseOrder($po);
    expect($po->fresh()->status)->toBe(InvoiceStatus::SENT);

    $this->invoiceService->acceptPurchaseOrder($po->fresh());
    expect($po->fresh()->status)->toBe(InvoiceStatus::ACCEPTED);

    $this->invoiceService->startPurchaseOrderProgress($po->fresh());
    expect($po->fresh()->status)->toBe(InvoiceStatus::IN_PROGRESS);

    $this->invoiceService->deliverPurchaseOrder($po->fresh());
    expect($po->fresh()->status)->toBe(InvoiceStatus::DELIVERED);

    $invoice = $this->invoiceService->convertPurchaseOrderToInvoice($po->fresh());
    expect($po->fresh()->status)->toBe(InvoiceStatus::INVOICED)
        ->and($invoice->purchase_order_id)->toBe($po->id);
});

// ── Translation fields on lines ─────────────────────────────────────────────

it('stores language pair and service type on purchase order lines', function () {
    $po = $this->invoiceService->createPurchaseOrder($this->business, [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            [
                'account_id' => $this->expenseAccount->id,
                'description' => 'Translation EN→FR',
                'quantity' => 5000,
                'unit_price' => 0.08,
                'tax_code_id' => null,
                'language_pair_id' => $this->languagePair->id,
                'service_type_id' => $this->serviceType->id,
                'billing_unit' => 'word',
            ],
        ],
    ]);

    $line = $po->lines->first();

    expect($line->language_pair_id)->toBe($this->languagePair->id)
        ->and($line->service_type_id)->toBe($this->serviceType->id)
        ->and($line->billing_unit)->toBe('word');
});

it('loads language pair and service type relationships on lines', function () {
    $po = $this->invoiceService->createPurchaseOrder($this->business, [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            [
                'account_id' => $this->expenseAccount->id,
                'description' => 'Translation EN→FR',
                'quantity' => 1000,
                'unit_price' => 0.10,
                'tax_code_id' => null,
                'language_pair_id' => $this->languagePair->id,
                'service_type_id' => $this->serviceType->id,
                'billing_unit' => 'word',
            ],
        ],
    ]);

    $po->load(['lines.languagePair', 'lines.serviceType']);
    $line = $po->lines->first();

    expect($line->languagePair)->not->toBeNull()
        ->and($line->serviceType)->not->toBeNull()
        ->and($line->serviceType->name)->toBe('Translation');
});

it('allows null translation fields on lines', function () {
    $po = $this->invoiceService->createPurchaseOrder($this->business, [
        'contact_id' => $this->supplier->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            [
                'account_id' => $this->expenseAccount->id,
                'description' => 'General services',
                'quantity' => 1,
                'unit_price' => 200,
                'tax_code_id' => null,
            ],
        ],
    ]);

    $line = $po->lines->first();

    expect($line->language_pair_id)->toBeNull()
        ->and($line->service_type_id)->toBeNull()
        ->and($line->billing_unit)->toBeNull();
});

// ── HTTP: accept/start/deliver ──────────────────────────────────────────────

it('accepts a sent PO via POST /accept', function () {
    $po = makeFreelancerPo($this->invoiceService, $this->business, $this->supplier, $this->expenseAccount);
    $this->invoiceService->sendPurchaseOrder($po);

    $this->actingAs($this->user)
        ->post("/purchases/purchase-orders/{$po->id}/accept")
        ->assertRedirect();

    expect($po->fresh()->status)->toBe(InvoiceStatus::ACCEPTED);
});

it('starts a PO via POST /start', function () {
    $po = makeFreelancerPo($this->invoiceService, $this->business, $this->supplier, $this->expenseAccount);
    $this->invoiceService->sendPurchaseOrder($po);
    $this->invoiceService->acceptPurchaseOrder($po->fresh());

    $this->actingAs($this->user)
        ->post("/purchases/purchase-orders/{$po->id}/start")
        ->assertRedirect();

    expect($po->fresh()->status)->toBe(InvoiceStatus::IN_PROGRESS);
});

it('delivers a PO via POST /deliver', function () {
    $po = makeFreelancerPo($this->invoiceService, $this->business, $this->supplier, $this->expenseAccount);
    $this->invoiceService->sendPurchaseOrder($po);
    $this->invoiceService->acceptPurchaseOrder($po->fresh());
    $this->invoiceService->startPurchaseOrderProgress($po->fresh());

    $this->actingAs($this->user)
        ->post("/purchases/purchase-orders/{$po->id}/deliver")
        ->assertRedirect();

    expect($po->fresh()->status)->toBe(InvoiceStatus::DELIVERED);
});

it('shows the create form with language pairs and service types', function () {
    $this->actingAs($this->user)
        ->get('/purchases/purchase-orders/create')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('purchases/purchase-orders/create')
            ->has('languagePairs')
            ->has('serviceTypes')
        );
});
