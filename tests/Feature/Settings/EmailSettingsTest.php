<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = setupBusiness($this->user);
    $this->actingAs($this->user);
});

test('owner can view email settings page', function () {
    $this->get(route('email.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/email')
            ->has('business')
        );
});

test('owner can save smtp settings', function () {
    $this->patch(route('email.update'), [
        'smtp_host' => 'smtp.example.com',
        'smtp_port' => 587,
        'smtp_username' => 'user@example.com',
        'smtp_password' => 'secret',
        'smtp_encryption' => 'tls',
        'smtp_from_name' => 'Test Business',
        'smtp_from_email' => 'billing@example.com',
    ])->assertRedirect();

    $this->business->refresh();
    expect($this->business->smtp_host)->toBe('smtp.example.com');
    expect($this->business->smtp_port)->toBe(587);
    expect($this->business->smtp_from_email)->toBe('billing@example.com');
    expect($this->business->hasEmailConfigured())->toBeTrue();
});

test('blank password does not overwrite existing password', function () {
    $this->business->update(['smtp_password' => 'original-secret']);

    $this->patch(route('email.update'), [
        'smtp_host' => 'smtp.example.com',
        'smtp_port' => 587,
        'smtp_username' => 'user@example.com',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
        'smtp_from_name' => 'Test',
        'smtp_from_email' => 'test@test.com',
    ])->assertRedirect();

    $this->business->refresh();
    expect($this->business->smtp_password)->toBe('original-secret');
});

test('business without smtp configured returns hasEmailConfigured false', function () {
    expect($this->business->hasEmailConfigured())->toBeFalse();
});

test('smtp_from_email must be valid email', function () {
    $this->patch(route('email.update'), [
        'smtp_from_email' => 'not-an-email',
    ])->assertSessionHasErrors('smtp_from_email');
});
