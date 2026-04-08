<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SessionController;
use App\Http\Controllers\Settings\NotificationSettingsController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('settings/security', [PasswordController::class, 'edit'])->name('user-password.edit');
    Route::get('settings/password', function () {
        return redirect()->route('user-password.edit');
    });

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/account', [ProfileController::class, 'account'])->name('account.edit');

    Route::get('settings/notifications', [NotificationSettingsController::class, 'edit'])->name('notifications.edit');
    Route::patch('settings/notifications', [NotificationSettingsController::class, 'update'])->name('notifications.update');

    Route::delete('settings/sessions/{sessionId}', [SessionController::class, 'destroy'])->name('sessions.destroy');
    Route::delete('settings/sessions', [SessionController::class, 'destroyOthers'])->name('sessions.destroy-others');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance.edit');

    Route::get('settings/two-factor', function () {
        return redirect()->route('user-password.edit');
    })->name('two-factor.show');
});
