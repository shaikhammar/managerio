<?php

use App\Models\Account;
use App\Models\Business;
use App\Models\NumberSequence;
use App\Models\User;
use App\Services\Business\BusinessSetupService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->setupService = app(BusinessSetupService::class);
});

it('can setup a new business with defaults', function () {
    $data = [
        'name' => 'Test Business',
        'country' => 'GB',
        'currency_code' => 'GBP',
    ];

    $business = $this->setupService->createBusiness($this->user, $data);

    expect($business)->toBeInstanceOf(Business::class)
        ->and($business->name)->toBe('Test Business')
        ->and($business->country)->toBe('GB')
        ->and($business->currency_code)->toBe('GBP');

    // Check owner
    expect($business->users)->toHaveCount(1)
        ->and($business->users->first()->id)->toBe($this->user->id)
        ->and($business->users->first()->pivot->role)->toBe('owner');

    // Check default accounts
    $accountsCount = Account::withoutGlobalScopes()->where('business_id', $business->id)->count();
    expect($accountsCount)->toBeGreaterThan(20);

    // Check number sequences
    $sequencesCount = NumberSequence::withoutGlobalScopes()->where('business_id', $business->id)->count();
    expect($sequencesCount)->toBe(7);
});
