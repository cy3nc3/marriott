<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/schedule', function () {
        return Inertia::render('teacher/schedule/index');
    })->name('schedule');

    Route::get('/grading-sheet', function () {
        return Inertia::render('teacher/grading-sheet/index');
    })->name('grading_sheet');

    Route::get('/advisory-board', function () {
        return Inertia::render('teacher/advisory-board/index');
    })->name('advisory_board');
});
