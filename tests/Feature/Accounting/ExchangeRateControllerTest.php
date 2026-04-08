<?php

use App\Models\ExchangeRate;
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

it('can list exchange rates', function () {
    ExchangeRate::create([
        'business_id' => $this->business->id,
        'currency_code' => 'EUR',
        'rate' => 1.10,
        'date' => '2024-01-15',
    ]);

    $this->get(route('accounting.exchange-rates.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('accounting/exchange-rates/index')
            ->has('rates.data', 1)
        );
});

it('can render the create form', function () {
    $this->get(route('accounting.exchange-rates.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('accounting/exchange-rates/create'));
});

it('can create an exchange rate', function () {
    $this->post(route('accounting.exchange-rates.store'), [
        'currency_code' => 'EUR',
        'rate' => 1.085,
        'date' => '2024-01-15',
    ])->assertRedirect(route('accounting.exchange-rates.index'));

    $this->assertDatabaseHas('exchange_rates', [
        'business_id' => $this->business->id,
        'currency_code' => 'EUR',
        'date' => '2024-01-15',
    ]);
});

it('rejects duplicate currency/date combinations', function () {
    ExchangeRate::create([
        'business_id' => $this->business->id,
        'currency_code' => 'EUR',
        'rate' => 1.10,
        'date' => '2024-01-15',
    ]);

    $this->post(route('accounting.exchange-rates.store'), [
        'currency_code' => 'EUR',
        'rate' => 1.12,
        'date' => '2024-01-15',
    ])->assertSessionHasErrors('date');
});

it('can update an exchange rate', function () {
    $rate = ExchangeRate::create([
        'business_id' => $this->business->id,
        'currency_code' => 'EUR',
        'rate' => 1.10,
        'date' => '2024-01-15',
    ]);

    $this->put(route('accounting.exchange-rates.update', $rate), [
        'currency_code' => 'EUR',
        'rate' => 1.12,
        'date' => '2024-01-15',
    ])->assertRedirect(route('accounting.exchange-rates.index'));

    $this->assertDatabaseHas('exchange_rates', [
        'id' => $rate->id,
        'rate' => 1.12,
    ]);
});

it('can delete an exchange rate', function () {
    $rate = ExchangeRate::create([
        'business_id' => $this->business->id,
        'currency_code' => 'EUR',
        'rate' => 1.10,
        'date' => '2024-01-15',
    ]);

    $this->delete(route('accounting.exchange-rates.destroy', $rate))
        ->assertRedirect(route('accounting.exchange-rates.index'));

    $this->assertDatabaseMissing('exchange_rates', ['id' => $rate->id]);
});

it('validates required fields on create', function () {
    $this->post(route('accounting.exchange-rates.store'), [])
        ->assertSessionHasErrors(['currency_code', 'rate', 'date']);
});

it('requires minimum rate value', function () {
    $this->post(route('accounting.exchange-rates.store'), [
        'currency_code' => 'EUR',
        'rate' => 0,
        'date' => '2024-01-15',
    ])->assertSessionHasErrors('rate');
});

it('rejects the base currency as the foreign currency', function () {
    $this->post(route('accounting.exchange-rates.store'), [
        'currency_code' => 'USD', // same as business base currency
        'rate' => 1,
        'date' => '2024-01-15',
    ])->assertSessionHasErrors('currency_code');
});
