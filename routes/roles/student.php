<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/schedule', function () {
        return Inertia::render('student/schedule/index');
    })->name('schedule');

    Route::get('/grades', function () {
        return Inertia::render('student/grades/index');
    })->name('grades');
});
