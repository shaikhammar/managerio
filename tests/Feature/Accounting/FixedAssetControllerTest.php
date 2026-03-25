<?php

use App\Models\Account;
use App\Models\DepreciationEntry;
use App\Models\FixedAsset;
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

    $this->assetAccount = Account::factory()->create(['business_id' => $this->business->id, 'type' => 'asset', 'code' => '9001']);
    $this->accumDepAccount = Account::factory()->create(['business_id' => $this->business->id, 'type' => 'asset', 'code' => '9002']);
    $this->depExpenseAccount = Account::factory()->create(['business_id' => $this->business->id, 'type' => 'expense', 'code' => '9003']);
});

it('can load the fixed assets index page', function () {
    $this->get(route('accounting.fixed-assets.index'))
        ->assertOk();
});

it('can load the create fixed asset page', function () {
    $this->get(route('accounting.fixed-assets.create'))
        ->assertOk();
});

it('can create a fixed asset', function () {
    $this->post(route('accounting.fixed-assets.store'), [
        'name' => 'Company Vehicle',
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'purchase_date' => '2026-01-15',
        'purchase_cost' => 25000.00,
        'salvage_value' => 2000.00,
        'useful_life_months' => 60,
        'depreciation_method' => 'straight_line',
    ])->assertRedirect();

    $this->assertDatabaseHas('fixed_assets', [
        'name' => 'Company Vehicle',
        'business_id' => $this->business->id,
        'status' => 'active',
    ]);
});

it('validates required fields on create', function () {
    $this->post(route('accounting.fixed-assets.store'), [])
        ->assertSessionHasErrors(['name', 'asset_account_id', 'purchase_date', 'purchase_cost', 'useful_life_months', 'depreciation_method']);
});

it('can load the show page', function () {
    $asset = FixedAsset::factory()->create([
        'business_id' => $this->business->id,
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'created_by' => $this->user->id,
    ]);

    $this->get(route('accounting.fixed-assets.show', $asset))
        ->assertOk();
});

it('can load the edit page', function () {
    $asset = FixedAsset::factory()->create([
        'business_id' => $this->business->id,
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'created_by' => $this->user->id,
    ]);

    $this->get(route('accounting.fixed-assets.edit', $asset))
        ->assertOk();
});

it('can update a fixed asset', function () {
    $asset = FixedAsset::factory()->create([
        'business_id' => $this->business->id,
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'name' => 'Old Name',
        'created_by' => $this->user->id,
    ]);

    $this->put(route('accounting.fixed-assets.update', $asset), [
        'name' => 'Updated Name',
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'purchase_date' => $asset->purchase_date->toDateString(),
        'purchase_cost' => (float) $asset->purchase_cost,
        'salvage_value' => (float) $asset->salvage_value,
        'useful_life_months' => $asset->useful_life_months,
        'depreciation_method' => $asset->depreciation_method->value,
    ])->assertRedirect();

    $this->assertDatabaseHas('fixed_assets', [
        'id' => $asset->id,
        'name' => 'Updated Name',
    ]);
});

it('can retire a fixed asset', function () {
    $asset = FixedAsset::factory()->create([
        'business_id' => $this->business->id,
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'status' => 'active',
        'created_by' => $this->user->id,
    ]);

    $this->post(route('accounting.fixed-assets.retire', $asset))
        ->assertRedirect();

    $this->assertDatabaseHas('fixed_assets', [
        'id' => $asset->id,
        'status' => 'retired',
    ]);
});

it('can delete a fixed asset with no depreciation history', function () {
    $asset = FixedAsset::factory()->create([
        'business_id' => $this->business->id,
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'created_by' => $this->user->id,
    ]);

    $this->delete(route('accounting.fixed-assets.destroy', $asset))
        ->assertRedirect(route('accounting.fixed-assets.index'));

    $this->assertDatabaseMissing('fixed_assets', ['id' => $asset->id]);
});

it('cannot delete an asset with depreciation history', function () {
    $asset = FixedAsset::factory()->create([
        'business_id' => $this->business->id,
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'created_by' => $this->user->id,
    ]);

    DepreciationEntry::factory()->create([
        'business_id' => $this->business->id,
        'fixed_asset_id' => $asset->id,
        'created_by' => $this->user->id,
    ]);

    $this->delete(route('accounting.fixed-assets.destroy', $asset))
        ->assertRedirect();

    $this->assertDatabaseHas('fixed_assets', ['id' => $asset->id]);
});

it('can run depreciation for a period', function () {
    FixedAsset::factory()->create([
        'business_id' => $this->business->id,
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'purchase_cost' => 12000.00,
        'salvage_value' => 0,
        'useful_life_months' => 12,
        'depreciation_method' => 'straight_line',
        'purchase_date' => '2026-01-01',
        'status' => 'active',
        'created_by' => $this->user->id,
    ]);

    $this->post(route('accounting.fixed-assets.run-depreciation'), [
        'period' => '2026-03',
    ])->assertRedirect();

    $this->assertDatabaseHas('depreciation_entries', [
        'business_id' => $this->business->id,
    ]);
});
