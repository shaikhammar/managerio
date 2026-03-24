<?php

use App\Domain\Accounting\Enums\AccountSubType;
use App\Domain\Accounting\Enums\AccountType;
use App\Models\Business;
use App\Models\User;

beforeEach(function () {
    $this->business = Business::factory()->create();
    $this->user = User::factory()->create();
    $this->business->users()->attach($this->user, ['role' => 'owner']);
});

it('shows the bank accounts index', function () {
    $this->actingAs($this->user)
        ->get('/banking/accounts')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('banking/accounts/index'));
});

it('shows the create bank account form', function () {
    $this->actingAs($this->user)
        ->get('/banking/accounts/create')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('banking/accounts/create'));
});

it('creates a bank account', function () {
    $this->actingAs($this->user)
        ->post('/banking/accounts', [
            'name' => 'Business Checking',
            'code' => '1010',
            'bank_name' => 'Chase',
            'account_number' => '1234',
        ])
        ->assertRedirect('/banking/accounts');

    $this->assertDatabaseHas('accounts', [
        'business_id' => $this->business->id,
        'name' => 'Business Checking',
        'code' => '1010',
        'type' => AccountType::ASSET->value,
        'sub_type' => AccountSubType::BANK->value,
        'description' => 'Chase · Acct #1234',
    ]);
});

it('creates a bank account without optional fields', function () {
    $this->actingAs($this->user)
        ->post('/banking/accounts', [
            'name' => 'Savings',
            'code' => '1020',
        ])
        ->assertRedirect('/banking/accounts');

    $this->assertDatabaseHas('accounts', [
        'name' => 'Savings',
        'description' => null,
    ]);
});

it('validates required fields', function () {
    $this->actingAs($this->user)
        ->post('/banking/accounts', [])
        ->assertSessionHasErrors(['name', 'code']);
});
