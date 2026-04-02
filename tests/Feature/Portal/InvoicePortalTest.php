<?php

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Sales\InvoiceService;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $user = User::factory()->create();
    $this->business = setupBusiness($user);
    $contact = Contact::factory()->create(['business_id' => $this->business->id]);
    $this->invoice = Invoice::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $contact->id,
        'type' => 'invoice',
        'status' => 'sent',
        'number' => 'INV-001',
        'date' => now()->format('Y-m-d'),
        'subtotal' => 500,
        'tax_amount' => 50,
        'total' => 550,
        'amount_paid' => 0,
        'balance_due' => 550,
    ]);
    $this->invoiceService = app(InvoiceService::class);
});

it('returns 403 with invalid signature on invoice portal', function () {
    $this->get('/portal/invoices/'.$this->invoice->id)->assertStatus(403);
});

it('shows the invoice portal page with a valid signature', function () {
    $url = URL::signedRoute('portal.invoices.show', ['invoice' => $this->invoice->id]);

    $this->get($url)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('portal/invoice-view'));
});

it('generates a valid signed portal URL for an invoice', function () {
    $url = $this->invoiceService->generatePortalUrl($this->invoice);

    expect($url)->toContain('/portal/invoices/'.$this->invoice->id);
});

it('streams a PDF for a valid invoice portal link', function () {
    $url = URL::signedRoute('portal.invoices.pdf', ['invoice' => $this->invoice->id]);

    $this->get($url)
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});
