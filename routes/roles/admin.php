<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/curriculum-manager', function () {
        return Inertia::render('admin/curriculum-manager/index');
    })->name('curriculum_manager');

    Route::get('/section-manager', function () {
        return Inertia::render('admin/section-manager/index');
    })->name('section_manager');

    Route::get('/schedule-builder', function () {
        return Inertia::render('admin/schedule-builder/index');
    })->name('schedule_builder');

    Route::get('/class-lists', function () {
        return Inertia::render('admin/class-lists/index');
    })->name('class_lists');

    Route::get('/deped-reports', function () {
        return Inertia::render('admin/deped-reports/index');
    })->name('deped_reports');

    Route::get('/sf9-generator', function () {
        return Inertia::render('admin/sf9-generator/index');
    })->name('sf9_generator');
});
