<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = setupBusiness($this->user);
    $this->actingAs($this->user);
});

test('owner can view business settings page', function () {
    $this->get(route('business.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/business')
            ->has('business')
        );
});

test('owner can update business name and currency', function () {
    $this->patch(route('business.update'), [
        'name' => 'Updated Business Name',
        'country' => 'GB',
        'currency_code' => 'GBP',
        'fiscal_year_start' => 4,
    ])->assertRedirect();

    $this->business->refresh();
    expect($this->business->name)->toBe('Updated Business Name');
    expect($this->business->currency_code)->toBe('GBP');
    expect($this->business->fiscal_year_start)->toBe(4);
});

test('currency is shared in inertia props after update', function () {
    $this->patch(route('business.update'), [
        'name' => $this->business->name,
        'country' => 'GB',
        'currency_code' => 'EUR',
        'fiscal_year_start' => 1,
    ]);

    $this->business->refresh();
    expect($this->business->currency_code)->toBe('EUR');
});

test('viewer cannot update business settings', function () {
    $viewer = User::factory()->create();
    $this->business->users()->attach($viewer, ['role' => 'viewer']);

    $this->actingAs($viewer);

    $this->withSession(['current_business_id' => $this->business->id])
        ->patch(route('business.update'), [
            'name' => 'Hacked Name',
            'country' => 'US',
            'currency_code' => 'USD',
            'fiscal_year_start' => 1,
        ])->assertForbidden();
});

test('business name is required', function () {
    $this->patch(route('business.update'), [
        'name' => '',
        'country' => 'US',
        'currency_code' => 'USD',
        'fiscal_year_start' => 1,
    ])->assertSessionHasErrors('name');
});

test('currency code must be 3 characters', function () {
    $this->patch(route('business.update'), [
        'name' => 'Test',
        'country' => 'US',
        'currency_code' => 'US',
        'fiscal_year_start' => 1,
    ])->assertSessionHasErrors('currency_code');
});
