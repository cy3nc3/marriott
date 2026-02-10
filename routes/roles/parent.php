<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/schedule', function () {
        return Inertia::render('parent/schedule/index');
    })->name('schedule');

    Route::get('/grades', function () {
        return Inertia::render('parent/grades/index');
    })->name('grades');

    Route::get('/billing-information', function () {
        return Inertia::render('parent/billing-information/index');
    })->name('billing_information');
});
