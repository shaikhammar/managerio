<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

test('security settings page is displayed', function () {
    $user = User::factory()->create();
    setupBusiness($user);

    $response = $this
        ->actingAs($user)
        ->get(route('security.edit'));

    $response->assertOk();
});

test('two factor authentication can be enabled', function () {
    $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

    $user = User::factory()->create();
    setupBusiness($user);

    $response = $this
        ->actingAs($user)
        ->post(route('two-factor.enable'));

    $response->assertRedirect();
    expect($user->refresh()->two_factor_secret)->not->toBeNull();
});

test('two factor authentication can be disabled', function () {
    $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

    $user = User::factory()->create();
    setupBusiness($user);

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $response = $this
        ->actingAs($user)
        ->delete(route('two-factor.enable'));

    $response->assertRedirect();
    expect($user->refresh()->two_factor_secret)->toBeNull();
});

test('password can be updated', function () {
    $user = User::factory()->create();
    setupBusiness($user);

    $response = $this
        ->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('security.edit'));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();
    setupBusiness($user);

    $response = $this
        ->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('security.edit'));
});
