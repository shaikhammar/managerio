<?php

use App\Models\Account;
use App\Models\AccountBudget;
use App\Models\Business;
use App\Models\User;
use App\Services\Accounting\BudgetService;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->business = Business::factory()->create();
    $this->user = User::factory()->create();
    $this->business->users()->attach($this->user, ['role' => 'owner']);
    Auth::login($this->user);

    $this->service = app(BudgetService::class);

    $this->revenueAccount = Account::factory()->create([
        'business_id' => $this->business->id,
        'type' => 'revenue',
    ]);
});

it('saves monthly budget entries', function () {
    $this->service->saveBudget($this->business, 2026, [
        ['account_id' => $this->revenueAccount->id, 'month' => 1, 'amount' => 5000],
        ['account_id' => $this->revenueAccount->id, 'month' => 2, 'amount' => 6000],
    ]);

    $this->assertDatabaseHas('account_budgets', [
        'business_id' => $this->business->id,
        'account_id' => $this->revenueAccount->id,
        'year' => 2026,
        'month' => 1,
        'amount' => 5000,
    ]);

    $this->assertDatabaseHas('account_budgets', [
        'business_id' => $this->business->id,
        'account_id' => $this->revenueAccount->id,
        'year' => 2026,
        'month' => 2,
        'amount' => 6000,
    ]);
});

it('upserts budget entries without creating duplicates', function () {
    $this->service->saveBudget($this->business, 2026, [
        ['account_id' => $this->revenueAccount->id, 'month' => 1, 'amount' => 5000],
    ]);

    $this->service->saveBudget($this->business, 2026, [
        ['account_id' => $this->revenueAccount->id, 'month' => 1, 'amount' => 7500],
    ]);

    expect(AccountBudget::withoutGlobalScopes()
        ->where('business_id', $this->business->id)
        ->where('account_id', $this->revenueAccount->id)
        ->where('year', 2026)
        ->where('month', 1)
        ->count()
    )->toBe(1);

    $this->assertDatabaseHas('account_budgets', [
        'business_id' => $this->business->id,
        'account_id' => $this->revenueAccount->id,
        'year' => 2026,
        'month' => 1,
        'amount' => 7500,
    ]);
});

it('returns budget for year', function () {
    $this->service->saveBudget($this->business, 2026, [
        ['account_id' => $this->revenueAccount->id, 'month' => 3, 'amount' => 4000],
    ]);

    $budgets = $this->service->getBudgetForYear($this->business, 2026);

    expect($budgets)->toHaveCount(1)
        ->and($budgets->first()->amount)->toBe('4000.00');
});

it('getBudgetVsActual returns 12 months per account', function () {
    $result = $this->service->getBudgetVsActual($this->business, 2026);

    expect($result['year'])->toBe(2026);
    $accountRow = collect($result['accounts'])->first(fn ($r) => $r['account']->id === $this->revenueAccount->id);
    expect($accountRow)->not->toBeNull()
        ->and($accountRow['months'])->toHaveCount(12);
});
