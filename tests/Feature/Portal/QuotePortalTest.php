<?php

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = setupBusiness($this->user);
    $this->contact = Contact::factory()->create(['business_id' => $this->business->id]);
    $this->quote = Invoice::factory()->create([
        'business_id' => $this->business->id,
        'contact_id' => $this->contact->id,
        'type' => 'quote',
        'status' => 'sent',
        'number' => 'Q-001',
        'date' => now()->format('Y-m-d'),
        'subtotal' => 100,
        'tax_amount' => 10,
        'total' => 110,
        'balance_due' => 110,
        'amount_paid' => 0,
    ]);
});

it('returns 403 with an invalid signature', function () {
    $this->get('/portal/quotes/'.$this->quote->id)
        ->assertStatus(403);
});

it('shows the quote portal page with a valid signature', function () {
    $url = URL::signedRoute('portal.quotes.show', ['quote' => $this->quote->id]);

    $this->get($url)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('portal/quote-approval'));
});

it('approves a quote via the portal', function () {
    $url = URL::signedRoute('portal.quotes.approve', ['quote' => $this->quote->id]);

    $this->post($url, ['comment' => 'Approved!'])
        ->assertRedirect();

    expect($this->quote->fresh()->status->value)->toBe('approved');
    expect($this->quote->fresh()->portal_comment)->toBe('Approved!');
});

it('rejects a quote via the portal', function () {
    $url = URL::signedRoute('portal.quotes.reject', ['quote' => $this->quote->id]);

    $this->post($url, ['comment' => 'Too expensive'])
        ->assertRedirect();

    expect($this->quote->fresh()->status->value)->toBe('cancelled');
});

it('returns 403 when posting without a valid signature', function () {
    $this->post('/portal/quotes/'.$this->quote->id.'/approve', ['comment' => 'x'])
        ->assertStatus(403);
});
