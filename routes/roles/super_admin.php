<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:super_admin'])->prefix('super-admin')->name('super_admin.')->group(function () {
    Route::get('/user-manager', function () {
        return Inertia::render('super_admin/user-manager/index');
    })->name('user_manager');

    Route::get('/system-settings', function () {
        return Inertia::render('super_admin/system-settings/index');
    })->name('system_settings');
});
