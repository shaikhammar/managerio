<?php

use App\Models\Account;
use App\Models\User;
use App\Services\Business\BusinessSetupService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = app(BusinessSetupService::class)->createBusiness($this->user, [
        'name' => 'Source Business',
        'currency_code' => 'USD',
        'country' => 'US',
    ]);
    $this->otherBusiness = app(BusinessSetupService::class)->createBusiness($this->user, [
        'name' => 'Target Business',
        'currency_code' => 'USD',
        'country' => 'US',
    ]);

    $this->actingAs($this->user);
    session(['current_business_id' => $this->business->id]);
});

it('can load the intercompany index page', function () {
    $this->get(route('accounting.intercompany.index'))
        ->assertOk();
});

it('can load the intercompany create page', function () {
    $this->get(route('accounting.intercompany.create'))
        ->assertOk();
});

it('can record an intercompany transfer', function () {
    $sourceAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'asset',
        'sub_type' => 'bank',
    ]);

    $targetAccount = Account::factory()->create([
        'business_id' => $this->otherBusiness->id,
        'type' => 'asset',
        'sub_type' => 'bank',
    ]);

    $this->post(route('accounting.intercompany.store'), [
        'source_account_id' => $sourceAccount->id,
        'target_business_id' => $this->otherBusiness->id,
        'target_account_id' => $targetAccount->id,
        'amount' => 1500,
        'date' => '2026-01-10',
        'description' => 'Intercompany management fee',
    ])->assertRedirect();

    $this->assertDatabaseHas('intercompany_transactions', [
        'source_business_id' => $this->business->id,
        'target_business_id' => $this->otherBusiness->id,
        'description' => 'Intercompany management fee',
    ]);
});

it('cannot transfer to same business', function () {
    $sourceAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'asset',
        'sub_type' => 'bank',
    ]);

    $this->post(route('accounting.intercompany.store'), [
        'source_account_id' => $sourceAccount->id,
        'target_business_id' => $this->business->id,
        'target_account_id' => $sourceAccount->id,
        'amount' => 500,
        'date' => '2026-01-10',
        'description' => 'Self-transfer',
    ])->assertSessionHasErrors('target_business_id');
});

it('validates required fields', function () {
    $this->post(route('accounting.intercompany.store'), [])
        ->assertSessionHasErrors(['source_account_id', 'target_business_id', 'amount', 'date', 'description']);
});
