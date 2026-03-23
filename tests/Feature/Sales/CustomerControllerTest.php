<?php

use App\Models\Business;
use App\Models\Contact;
use App\Models\User;
use App\Services\Business\BusinessSetupService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = app(BusinessSetupService::class)->createBusiness($this->user, [
        'name' => 'Test Business',
        'currency_code' => 'USD',
        'country' => 'US',
    ]);

    $this->actingAs($this->user);
    session(['current_business_id' => $this->business->id]);
});

it('can load the create customer page', function () {
    $this->get(route('sales.customers.create'))
        ->assertOk();
});

it('owner can create a customer', function () {
    $this->post(route('sales.customers.store'), [
        'name' => 'Acme Corp',
        'email' => 'acme@example.com',
    ])->assertRedirect(route('sales.customers.index'));

    expect(Contact::withoutGlobalScopes()->where('name', 'Acme Corp')->exists())->toBeTrue();
});

it('viewer cannot create a customer', function () {
    $viewer = User::factory()->create();
    $this->business->users()->attach($viewer, ['role' => 'viewer']);

    $this->actingAs($viewer);
    session(['current_business_id' => $this->business->id]);

    $this->post(route('sales.customers.store'), [
        'name' => 'Blocked Corp',
    ])->assertForbidden();
});
