<?php

use App\Models\Account;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->business1 = Business::factory()->create();
    $this->business2 = Business::factory()->create();
    $this->user = User::factory()->create();
    $this->business1->users()->attach($this->user, ['role' => 'owner']);
});

it('only shows accounts for current business', function () {
    Account::factory()->create(['business_id' => $this->business1->id, 'name' => 'B1 Account']);
    Account::factory()->create(['business_id' => $this->business2->id, 'name' => 'B2 Account']);

    // Simulate session
    session(['current_business_id' => $this->business1->id]);

    $accounts = Account::all();
    expect($accounts)->toHaveCount(1)
        ->and($accounts->first()->name)->toBe('B1 Account');

    // Switch business
    session(['current_business_id' => $this->business2->id]);

    $accounts = Account::all();
    expect($accounts)->toHaveCount(1)
        ->and($accounts->first()->name)->toBe('B2 Account');
});

it('automatically sets business_id when creating models', function () {
    session(['current_business_id' => $this->business1->id]);

    $account = Account::create([
        'code' => '9999',
        'name' => 'Auto Business ID',
        'type' => 'asset',
    ]);

    expect($account->business_id)->toBe($this->business1->id);
});

it('blocks unauthorized business access via middleware', function () {
    Auth::login($this->user);
    session(['current_business_id' => $this->business2->id]); // User doesn't belong to business 2

    $this->get('/dashboard')
        ->assertRedirect(route('business.index'))
        ->assertSessionHas('error');
});

it('allows authorized business access via middleware', function () {
    Auth::login($this->user);
    session(['current_business_id' => $this->business1->id]);

    $this->get('/dashboard')
        ->assertSuccessful();
});
