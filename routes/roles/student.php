<?php

use App\Http\Controllers\Student\GradesController;
use App\Http\Controllers\Student\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule');

    Route::get('/grades', [GradesController::class, 'index'])->name('grades');
});
