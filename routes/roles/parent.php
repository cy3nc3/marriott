<?php

use App\Http\Controllers\ParentPortal\BillingInformationController;
use App\Http\Controllers\ParentPortal\GradesController;
use App\Http\Controllers\ParentPortal\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:parent'])->prefix('parent')->name('parent.')->group(function () {
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule');

    Route::get('/grades', [GradesController::class, 'index'])->name('grades');

    Route::get('/billing-information', [BillingInformationController::class, 'index'])->name('billing_information');
});
