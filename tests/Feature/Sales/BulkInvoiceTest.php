<?php

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Sales\Enums\InvoiceStatus;
use App\Models\Account;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Sales\InvoiceService;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = setupBusiness($this->user);
    $this->actingAs($this->user);

    Account::factory()->create([
        'business_id' => $this->business->id,
        'sub_type' => AccountSubType::ACCOUNTS_RECEIVABLE,
        'code' => '1100',
    ]);

    Account::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'revenue',
        'code' => '4000',
    ]);

    $this->contact = Contact::factory()->create(['business_id' => $this->business->id]);

    Auth::login($this->user);
    $this->invoiceService = app(InvoiceService::class);
});

function createTestInvoice(InvoiceService $service, Business $business, Contact $contact, string $revenueAccountCode = '4000'): Invoice
{
    $account = Account::where('business_id', $business->id)->where('code', $revenueAccountCode)->first();

    return $service->create($business, [
        'contact_id' => $contact->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [[
            'account_id' => $account->id,
            'description' => 'Test service',
            'quantity' => 1,
            'unit_price' => 100,
            'discount_percent' => 0,
            'tax_code_id' => null,
        ]],
    ]);
}

test('can mark multiple sent invoices in bulk', function () {
    $invoice1 = createTestInvoice($this->invoiceService, $this->business, $this->contact);
    $invoice2 = createTestInvoice($this->invoiceService, $this->business, $this->contact);

    // Manually set to draft to test marking as sent
    $invoice1->update(['status' => InvoiceStatus::DRAFT]);
    $invoice2->update(['status' => InvoiceStatus::DRAFT]);

    $this->post(route('sales.invoices.bulk.mark-sent'), [
        'ids' => [$invoice1->id, $invoice2->id],
    ])->assertRedirect();

    expect($invoice1->fresh()->status)->toBe(InvoiceStatus::SENT);
    expect($invoice2->fresh()->status)->toBe(InvoiceStatus::SENT);
});

test('bulk mark sent only affects draft invoices', function () {
    $draft = createTestInvoice($this->invoiceService, $this->business, $this->contact);
    $draft->update(['status' => InvoiceStatus::DRAFT]);

    $sent = createTestInvoice($this->invoiceService, $this->business, $this->contact);
    $this->invoiceService->postInvoice($sent->fresh(['lines']));
    $sent = $sent->fresh();

    $this->post(route('sales.invoices.bulk.mark-sent'), [
        'ids' => [$draft->id, $sent->id],
    ])->assertRedirect();

    expect($draft->fresh()->status)->toBe(InvoiceStatus::SENT);
    // sent invoice status is unchanged by the bulk action since it's already not draft
    expect($sent->fresh()->status)->toBe(InvoiceStatus::SENT);
});

test('can bulk delete draft invoices', function () {
    $draft = createTestInvoice($this->invoiceService, $this->business, $this->contact);
    $draft->update(['status' => InvoiceStatus::DRAFT]);
    $draft->journalEntry?->lines()->delete();
    $draft->journalEntry()->dissociate()->save();

    $this->delete(route('sales.invoices.bulk.delete'), [
        'ids' => [$draft->id],
    ])->assertRedirect();

    expect(Invoice::find($draft->id))->toBeNull();
});

test('bulk delete does not delete non-draft invoices', function () {
    $sent = createTestInvoice($this->invoiceService, $this->business, $this->contact);
    $this->invoiceService->postInvoice($sent->fresh(['lines']));
    $sent = $sent->fresh();

    $this->delete(route('sales.invoices.bulk.delete'), [
        'ids' => [$sent->id],
    ])->assertRedirect();

    expect(Invoice::find($sent->id))->not->toBeNull();
});

test('bulk actions require valid ids', function () {
    $this->post(route('sales.invoices.bulk.mark-sent'), ['ids' => []])
        ->assertSessionHasErrors('ids');
});
