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
