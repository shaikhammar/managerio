<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $this->skipUnlessFortifyFeature(Features::registration());

    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $this->skipUnlessFortifyFeature(Features::registration());

    $response = $this->post(route('register'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $this->assertAuthenticated();
    $user = User::where('email', 'test@example.com')->first();
    setupBusiness($user); // Ensure the newly registered user has a business to avoid redirects
    
    $response->assertRedirect(route('dashboard', absolute: false));
});
