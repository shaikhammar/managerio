<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

test('two factor challenge can be rendered', function () {
    $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'Password123!',
    ]);

    $this->get(route('two-factor.login'))
        ->assertOk();
});

test('two factor challenge can be authenticated', function () {
    $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

    $user = User::factory()->create();
    setupBusiness($user);

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    // Mock the 2FA provider to always return true for the code
    $mock = Mockery::mock(TwoFactorAuthenticationProvider::class);
    $mock->shouldReceive('verify')->andReturn(true);
    $this->app->instance(TwoFactorAuthenticationProvider::class, $mock);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'Password123!',
    ]);

    $response = $this->post(route('two-factor.login'), [
        'code' => '123456',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
});

test('two factor challenge cannot be authenticated with invalid code', function () {
    $this->skipUnlessFortifyFeature(Features::twoFactorAuthentication());

    $user = User::factory()->create();
    setupBusiness($user);

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    // Mock the 2FA provider to return false
    $mock = Mockery::mock(TwoFactorAuthenticationProvider::class);
    $mock->shouldReceive('verify')->andReturn(false);
    $this->app->instance(TwoFactorAuthenticationProvider::class, $mock);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'Password123!',
    ]);

    $response = $this->post(route('two-factor.login'), [
        'code' => '000000',
    ]);

    $response->assertSessionHasErrors();
    $this->assertGuest();
});
