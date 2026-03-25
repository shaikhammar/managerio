<?php

use App\Domain\Accounting\Enums\AssetStatus;
use App\Domain\Accounting\Enums\DepreciationMethod;
use App\Models\Account;
use App\Models\Business;
use App\Models\FixedAsset;
use App\Models\User;
use App\Services\Accounting\FixedAssetService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->business = Business::factory()->create();
    $this->user = User::factory()->create();
    $this->business->users()->attach($this->user, ['role' => 'owner']);
    Auth::login($this->user);

    $this->service = app(FixedAssetService::class);

    $this->assetAccount = Account::factory()->create(['business_id' => $this->business->id, 'type' => 'asset']);
    $this->accumDepAccount = Account::factory()->create(['business_id' => $this->business->id, 'type' => 'asset']);
    $this->depExpenseAccount = Account::factory()->create(['business_id' => $this->business->id, 'type' => 'expense']);
});

it('creates a fixed asset with correct attributes', function () {
    $asset = $this->service->create($this->business, [
        'name' => 'Office Laptop',
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'purchase_date' => '2026-01-01',
        'purchase_cost' => 3000.00,
        'salvage_value' => 200.00,
        'useful_life_months' => 36,
        'depreciation_method' => 'straight_line',
    ]);

    expect($asset)->toBeInstanceOf(FixedAsset::class)
        ->and($asset->name)->toBe('Office Laptop')
        ->and($asset->status)->toBe(AssetStatus::Active)
        ->and($asset->depreciation_method)->toBe(DepreciationMethod::StraightLine)
        ->and((float) $asset->purchase_cost)->toBe(3000.0)
        ->and((float) $asset->salvage_value)->toBe(200.0);

    $this->assertDatabaseHas('fixed_assets', [
        'id' => $asset->id,
        'business_id' => $this->business->id,
        'status' => 'active',
    ]);
});

it('calculates straight line depreciation schedule correctly', function () {
    $asset = FixedAsset::factory()->create([
        'business_id' => $this->business->id,
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'purchase_cost' => 12000.00,
        'salvage_value' => 0,
        'useful_life_months' => 12,
        'depreciation_method' => 'straight_line',
        'purchase_date' => now()->startOfMonth(),
        'created_by' => $this->user->id,
    ]);

    $schedule = $this->service->calculateSchedule($asset);

    expect($schedule)->toHaveCount(12);

    $firstRow = $schedule->first();
    expect((float) $firstRow['depreciation'])->toBe(1000.0)
        ->and((float) $firstRow['accumulated'])->toBe(1000.0)
        ->and((float) $firstRow['book_value'])->toBe(11000.0);

    $lastRow = $schedule->last();
    expect((float) $lastRow['book_value'])->toBe(0.0);
});

it('calculates declining balance depreciation schedule', function () {
    $asset = FixedAsset::factory()->create([
        'business_id' => $this->business->id,
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'purchase_cost' => 10000.00,
        'salvage_value' => 0,
        'useful_life_months' => 24,
        'depreciation_method' => 'declining_balance',
        'purchase_date' => now()->startOfMonth(),
        'created_by' => $this->user->id,
    ]);

    $schedule = $this->service->calculateSchedule($asset);

    // Each period depreciation should be decreasing (declining balance)
    $firstAmount = (float) $schedule->first()['depreciation'];
    $secondAmount = (float) $schedule->skip(1)->first()['depreciation'];

    expect($firstAmount)->toBeGreaterThan($secondAmount);
});

it('posts depreciation journal entries when running depreciation', function () {
    $asset = FixedAsset::factory()->create([
        'business_id' => $this->business->id,
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'purchase_cost' => 6000.00,
        'salvage_value' => 0,
        'useful_life_months' => 12,
        'depreciation_method' => 'straight_line',
        'purchase_date' => '2026-01-01',
        'status' => 'active',
        'created_by' => $this->user->id,
    ]);

    $period = Carbon::create(2026, 3, 1);
    $result = $this->service->runDepreciation($this->business, $period);

    expect($result['processed'])->toBe(1)
        ->and($result['skipped'])->toBe(0);

    $this->assertDatabaseHas('depreciation_entries', [
        'fixed_asset_id' => $asset->id,
        'business_id' => $this->business->id,
        'depreciation_amount' => 500.00,
    ]);

    $this->assertDatabaseHas('journal_entries', [
        'business_id' => $this->business->id,
        'source_type' => 'depreciation',
        'source_id' => $asset->id,
        'is_posted' => true,
    ]);
});

it('skips already-depreciated periods on re-run', function () {
    $asset = FixedAsset::factory()->create([
        'business_id' => $this->business->id,
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'purchase_cost' => 6000.00,
        'salvage_value' => 0,
        'useful_life_months' => 12,
        'depreciation_method' => 'straight_line',
        'purchase_date' => '2026-01-01',
        'status' => 'active',
        'created_by' => $this->user->id,
    ]);

    $period = Carbon::create(2026, 3, 1);

    $result1 = $this->service->runDepreciation($this->business, $period);
    $result2 = $this->service->runDepreciation($this->business, $period);

    expect($result1['processed'])->toBe(1)
        ->and($result2['processed'])->toBe(0)
        ->and($result2['skipped'])->toBe(1);
});

it('retires an active asset', function () {
    $asset = FixedAsset::factory()->create([
        'business_id' => $this->business->id,
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'status' => 'active',
        'created_by' => $this->user->id,
    ]);

    $retired = $this->service->retire($asset);

    expect($retired->status)->toBe(AssetStatus::Retired);
    $this->assertDatabaseHas('fixed_assets', ['id' => $asset->id, 'status' => 'retired']);
});

it('throws when retiring a non-active asset', function () {
    $asset = FixedAsset::factory()->retired()->create([
        'business_id' => $this->business->id,
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'created_by' => $this->user->id,
    ]);

    expect(fn () => $this->service->retire($asset))
        ->toThrow(DomainException::class, 'Only active assets can be retired.');
});

it('skips assets purchased after the depreciation period', function () {
    FixedAsset::factory()->create([
        'business_id' => $this->business->id,
        'asset_account_id' => $this->assetAccount->id,
        'accumulated_depreciation_account_id' => $this->accumDepAccount->id,
        'depreciation_expense_account_id' => $this->depExpenseAccount->id,
        'purchase_date' => '2026-06-01',
        'status' => 'active',
        'created_by' => $this->user->id,
    ]);

    $result = $this->service->runDepreciation($this->business, Carbon::create(2026, 3, 1));

    expect($result['processed'])->toBe(0)
        ->and($result['skipped'])->toBe(1);
});
