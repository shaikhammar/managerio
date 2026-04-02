<?php

use App\Domain\Sales\Enums\InvoiceStatus;
use App\Domain\Sales\Enums\InvoiceType;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\TaxCode;
use App\Models\User;
use App\Services\Sales\QuoteService;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = setupBusiness($this->user);
    Auth::login($this->user);

    $this->quoteService = app(QuoteService::class);

    $this->contact = Contact::factory()->create(['business_id' => $this->business->id]);
    $this->taxCode = TaxCode::factory()->create(['business_id' => $this->business->id, 'rate' => 10]);
});

it('can create a sales quote without generating journal entries', function () {
    $data = [
        'contact_id' => $this->contact->id,
        'date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(15)->format('Y-m-d'),
        'lines' => [
            [
                'description' => 'Service Quote',
                'quantity' => 1,
                'unit_price' => 500,
                'tax_code_id' => $this->taxCode->id,
            ],
        ],
    ];

    $quote = $this->quoteService->create($this->business, $data);

    expect($quote)->toBeInstanceOf(Invoice::class)
        ->and($quote->type)->toBe(InvoiceType::QUOTE)
        ->and($quote->total)->toBe('550.00')
        ->and($quote->journal_entry_id)->toBeNull(); // Crucial: Quotes have no accounting impact
});

it('can convert a quote to a draft invoice without auto-posting', function () {
    $quote = $this->quoteService->create($this->business, [
        'contact_id' => $this->contact->id,
        'date' => now()->format('Y-m-d'),
        'lines' => [
            ['description' => 'Test', 'quantity' => 1, 'unit_price' => 1000],
        ],
    ]);

    $invoice = $this->quoteService->convertToInvoice($quote);

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->type)->toBe(InvoiceType::INVOICE)
        ->and($invoice->status)->toBe(InvoiceStatus::DRAFT)
        ->and($invoice->total)->toBe('1000.00')
        ->and($invoice->journal_entry_id)->toBeNull(); // Not posted yet — user must add accounts and post

    // Verify Quote status changed to approved/closed
    expect($quote->fresh()->status)->toBe(InvoiceStatus::APPROVED);
});

it('generates a valid signed portal URL for a quote', function () {
    $quote = Invoice::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->contact->id,
        'type' => 'quote',
        'status' => 'sent',
        'number' => 'Q-P01',
        'date' => now()->format('Y-m-d'),
        'subtotal' => 0,
        'tax_amount' => 0,
        'total' => 0,
        'balance_due' => 0,
        'amount_paid' => 0,
    ]);

    $url = $this->quoteService->generatePortalUrl($quote);

    expect($url)->toContain('/portal/quotes/'.$quote->id);
});

it('approves a sent quote from the portal', function () {
    $quote = Invoice::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->contact->id,
        'type' => 'quote',
        'status' => 'sent',
        'number' => 'Q-P02',
        'date' => now()->format('Y-m-d'),
        'subtotal' => 0,
        'tax_amount' => 0,
        'total' => 0,
        'balance_due' => 0,
        'amount_paid' => 0,
    ]);

    $this->quoteService->approveFromPortal($quote, 'Looks great!');

    expect($quote->fresh()->status->value)->toBe('approved');
    expect($quote->fresh()->portal_comment)->toBe('Looks great!');
});

it('rejects a sent quote from the portal', function () {
    $quote = Invoice::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->contact->id,
        'type' => 'quote',
        'status' => 'sent',
        'number' => 'Q-P03',
        'date' => now()->format('Y-m-d'),
        'subtotal' => 0,
        'tax_amount' => 0,
        'total' => 0,
        'balance_due' => 0,
        'amount_paid' => 0,
    ]);

    $this->quoteService->rejectFromPortal($quote, 'Price too high.');

    expect($quote->fresh()->status->value)->toBe('cancelled');
    expect($quote->fresh()->portal_comment)->toBe('Price too high.');
});

it('throws a DomainException when approving an already-responded quote', function () {
    $quote = Invoice::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->contact->id,
        'type' => 'quote',
        'status' => 'approved',
        'number' => 'Q-P04',
        'date' => now()->format('Y-m-d'),
        'subtotal' => 0,
        'tax_amount' => 0,
        'total' => 0,
        'balance_due' => 0,
        'amount_paid' => 0,
    ]);

    expect(fn () => $this->quoteService->approveFromPortal($quote, null))
        ->toThrow(DomainException::class, 'This quote has already been responded to.');
});
