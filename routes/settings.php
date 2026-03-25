<?php

use App\Http\Controllers\Settings\BusinessSettingsController;
use App\Http\Controllers\Settings\EmailSettingsController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    Route::middleware(['business.set', 'business.access'])->group(function () {
        Route::get('settings/business', [BusinessSettingsController::class, 'edit'])->name('business.edit');
        Route::patch('settings/business', [BusinessSettingsController::class, 'update'])->name('business.update');
        Route::get('settings/email', [EmailSettingsController::class, 'edit'])->name('email.edit');
        Route::patch('settings/email', [EmailSettingsController::class, 'update'])->name('email.update');
    });
});
