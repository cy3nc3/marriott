<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:registrar'])->prefix('registrar')->name('registrar.')->group(function () {
    Route::get('/student-directory', function () {
        return Inertia::render('registrar/student-directory/index');
    })->name('student_directory');

    Route::get('/enrollment', function () {
        return Inertia::render('registrar/enrollment/index');
    })->name('enrollment');

    Route::get('/permanent-records', function () {
        return Inertia::render('registrar/permanent-records/index');
    })->name('permanent_records');

    Route::get('/batch-promotion', function () {
        return Inertia::render('registrar/batch-promotion/index');
    })->name('batch_promotion');

    Route::get('/remedial-entry', function () {
        return Inertia::render('registrar/remedial-entry/index');
    })->name('remedial_entry');

    Route::get('/student-departure', function () {
        return Inertia::render('registrar/student-departure/index');
    })->name('student_departure');
});
