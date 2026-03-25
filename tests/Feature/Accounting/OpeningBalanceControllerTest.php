<?php

use App\Models\Account;
use App\Models\JournalEntry;
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

it('can load the opening balances page', function () {
    $this->get(route('accounting.opening-balances.create'))
        ->assertOk();
});

it('can post opening balances', function () {
    $assetAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'asset',
    ]);

    $liabilityAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'liability',
    ]);

    $this->post(route('accounting.opening-balances.store'), [
        'date' => '2026-01-01',
        'description' => 'Opening balances',
        'lines' => [
            ['account_id' => $assetAccount->id, 'balance' => 10000],
            ['account_id' => $liabilityAccount->id, 'balance' => 5000],
        ],
    ])->assertRedirect();

    expect(JournalEntry::withoutGlobalScopes()
        ->where('business_id', $this->business->id)
        ->where('source_type', 'opening_balance')
        ->exists()
    )->toBeTrue();
});

it('requires at least one balance line', function () {
    $this->post(route('accounting.opening-balances.store'), [
        'date' => '2026-01-01',
        'lines' => [],
    ])->assertSessionHasErrors('lines');
});
