<?php

use App\Models\Account;
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

it('can load the budget index page', function () {
    $this->get(route('accounting.budgets.index'))
        ->assertOk();
});

it('can load the budget edit page', function () {
    $this->get(route('accounting.budgets.edit'))
        ->assertOk();
});

it('can save a budget', function () {
    $account = Account::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'revenue',
    ]);

    $this->post(route('accounting.budgets.save'), [
        'year' => 2026,
        'entries' => [
            ['account_id' => $account->id, 'month' => 1, 'amount' => 5000],
            ['account_id' => $account->id, 'month' => 2, 'amount' => 6000],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('account_budgets', [
        'business_id' => $this->business->id,
        'account_id' => $account->id,
        'year' => 2026,
        'month' => 1,
        'amount' => 5000,
    ]);
});

it('cannot save budget with invalid year', function () {
    $this->post(route('accounting.budgets.save'), [
        'year' => 1999,
        'entries' => [],
    ])->assertSessionHasErrors('year');
});
