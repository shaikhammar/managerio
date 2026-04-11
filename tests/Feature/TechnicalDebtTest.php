<?php

use App\Models\Business;
use App\Models\User;

// ── Branding ──────────────────────────────────────────────────────────────────

it('shows ManagerIO branding on the landing page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('ManagerIO');
});

it('shows login page without starter kit branding', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('Laravel Starter Kit')
        ->assertDontSee('laravel/react-starter-kit');
});

it('shows register page without starter kit branding', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertDontSee('Laravel Starter Kit');
});

it('shows getting-started page', function () {
    $this->get('/docs')
        ->assertOk()
        ->assertSee('ManagerIO');
});

it('landing page does not show stale coming-soon banner', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Translation-specific features coming soon');
});

// ── Invoice validation messages ───────────────────────────────────────────────

it('shows human-readable error when invoice is submitted with no customer', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();
    $business->users()->attach($user, ['role' => 'owner']);

    $this->actingAs($user)
        ->withSession(['current_business_id' => $business->id])
        ->post(route('sales.invoices.store'), [
            'contact_id' => '',
            'date' => now()->format('Y-m-d'),
            'lines' => [
                ['description' => 'Test', 'quantity' => 1, 'unit_price' => 100, 'account_id' => '', 'discount_percent' => 0, 'tax_code_id' => null],
            ],
        ])
        ->assertSessionHasErrors(['contact_id' => 'Please select a customer.']);
});

it('shows human-readable error when invoice line items are missing description', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();
    $business->users()->attach($user, ['role' => 'owner']);

    $this->actingAs($user)
        ->withSession(['current_business_id' => $business->id])
        ->post(route('sales.invoices.store'), [
            'contact_id' => 999,
            'date' => now()->format('Y-m-d'),
            'lines' => [
                ['description' => '', 'quantity' => 1, 'unit_price' => 100, 'account_id' => '', 'discount_percent' => 0, 'tax_code_id' => null],
            ],
        ])
        ->assertSessionHasErrors(['lines.0.description' => 'Please enter a description for each line item.']);
});

it('shows human-readable error when invoice has no line items', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();
    $business->users()->attach($user, ['role' => 'owner']);

    $this->actingAs($user)
        ->withSession(['current_business_id' => $business->id])
        ->post(route('sales.invoices.store'), [
            'contact_id' => 999,
            'date' => now()->format('Y-m-d'),
            'lines' => [],
        ])
        ->assertSessionHasErrors(['lines' => 'At least one line item is required.']);
});

// ── Purchase invoice validation messages ─────────────────────────────────────

it('shows human-readable error when purchase invoice is submitted with no supplier', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();
    $business->users()->attach($user, ['role' => 'owner']);

    $this->actingAs($user)
        ->withSession(['current_business_id' => $business->id])
        ->post(route('purchases.purchase-invoices.store'), [
            'contact_id' => '',
            'date' => now()->format('Y-m-d'),
            'lines' => [
                ['description' => 'Test', 'quantity' => 1, 'unit_price' => 100, 'account_id' => '', 'discount_percent' => 0, 'tax_code_id' => null],
            ],
        ])
        ->assertSessionHasErrors(['contact_id' => 'Please select a supplier.']);
});
