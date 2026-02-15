<?php

use App\Http\Controllers\Admin\SchoolYearController;
use App\Http\Controllers\Admin\CurriculumController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ClassListController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/academic-controls', [SchoolYearController::class, 'index'])->name('academic_controls');
    Route::patch('/academic-controls/{academicYear}/dates', [SchoolYearController::class, 'updateDates'])->name('academic_controls.update_dates');
    Route::post('/academic-controls/initialize', [SchoolYearController::class, 'initializeNext'])->name('academic_controls.initialize');
    
    // Simulation routes
    Route::post('/academic-controls/{academicYear}/simulate-opening', [SchoolYearController::class, 'simulateOpening'])->name('academic_controls.simulate_opening');
    Route::post('/academic-controls/{academicYear}/advance-quarter', [SchoolYearController::class, 'advanceQuarter'])->name('academic_controls.advance_quarter');
    Route::post('/academic-controls/reset-simulation', [SchoolYearController::class, 'resetSimulation'])->name('academic_controls.reset_simulation');

    Route::get('/curriculum-manager', [CurriculumController::class, 'index'])->name('curriculum_manager');
    Route::post('/curriculum-manager', [CurriculumController::class, 'store'])->name('curriculum_manager.store');
    Route::patch('/curriculum-manager/{subject}', [CurriculumController::class, 'update'])->name('curriculum_manager.update');
    Route::delete('/curriculum-manager/{subject}', [CurriculumController::class, 'destroy'])->name('curriculum_manager.destroy');
    Route::post('/curriculum-manager/{subject}/certify', [CurriculumController::class, 'certifyTeachers'])->name('curriculum_manager.certify');

    Route::get('/section-manager', [SectionController::class, 'index'])->name('section_manager');
    Route::post('/section-manager', [SectionController::class, 'store'])->name('section_manager.store');
    Route::patch('/section-manager/{section}', [SectionController::class, 'update'])->name('section_manager.update');
    Route::delete('/section-manager/{section}', [SectionController::class, 'destroy'])->name('section_manager.destroy');

    Route::get('/schedule-builder', [ScheduleController::class, 'index'])->name('schedule_builder');
    Route::post('/schedule-builder', [ScheduleController::class, 'store'])->name('schedule_builder.store');
    Route::patch('/schedule-builder/{schedule}', [ScheduleController::class, 'update'])->name('schedule_builder.update');
    Route::delete('/schedule-builder/{schedule}', [ScheduleController::class, 'destroy'])->name('schedule_builder.destroy');

    Route::get('/class-lists', [ClassListController::class, 'index'])->name('class_lists');

    Route::get('/deped-reports', function () {
        return Inertia::render('admin/deped-reports/index');
    })->name('deped_reports');

    Route::get('/sf9-generator', function () {
        return Inertia::render('admin/sf9-generator/index');
    })->name('sf9_generator');
});
