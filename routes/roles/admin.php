<?php

use App\Http\Controllers\Admin\ClassListController;
use App\Http\Controllers\Admin\CurriculumController;
use App\Http\Controllers\Admin\GradeVerificationController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SchoolYearController;
use App\Http\Controllers\Admin\SectionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/academic-controls', [SchoolYearController::class, 'index'])->middleware('desktop_only')->name('academic_controls');
    Route::patch('/academic-controls/{academicYear}/dates', [SchoolYearController::class, 'updateDates'])->middleware('desktop_only')->name('academic_controls.update_dates');
    Route::post('/academic-controls/initialize', [SchoolYearController::class, 'initializeNext'])->middleware('desktop_only')->name('academic_controls.initialize');

    // Simulation routes
    Route::post('/academic-controls/{academicYear}/simulate-opening', [SchoolYearController::class, 'simulateOpening'])->middleware('desktop_only')->name('academic_controls.simulate_opening');
    Route::post('/academic-controls/{academicYear}/advance-quarter', [SchoolYearController::class, 'advanceQuarter'])->middleware('desktop_only')->name('academic_controls.advance_quarter');
    Route::post('/academic-controls/reset-simulation', [SchoolYearController::class, 'resetSimulation'])->middleware('desktop_only')->name('academic_controls.reset_simulation');

    Route::get('/curriculum-manager', [CurriculumController::class, 'index'])->middleware('desktop_only')->name('curriculum_manager');
    Route::post('/curriculum-manager', [CurriculumController::class, 'store'])->middleware('desktop_only')->name('curriculum_manager.store');
    Route::patch('/curriculum-manager/{subject}', [CurriculumController::class, 'update'])->middleware('desktop_only')->name('curriculum_manager.update');
    Route::delete('/curriculum-manager/{subject}', [CurriculumController::class, 'destroy'])->middleware('desktop_only')->name('curriculum_manager.destroy');
    Route::post('/curriculum-manager/{subject}/certify', [CurriculumController::class, 'certifyTeachers'])->middleware('desktop_only')->name('curriculum_manager.certify');

    Route::get('/section-manager', [SectionController::class, 'index'])->middleware('desktop_only')->name('section_manager');
    Route::post('/section-manager', [SectionController::class, 'store'])->middleware('desktop_only')->name('section_manager.store');
    Route::patch('/section-manager/{section}', [SectionController::class, 'update'])->middleware('desktop_only')->name('section_manager.update');
    Route::delete('/section-manager/{section}', [SectionController::class, 'destroy'])->middleware('desktop_only')->name('section_manager.destroy');

    Route::get('/schedule-builder', [ScheduleController::class, 'index'])->middleware('desktop_only')->name('schedule_builder');
    Route::post('/schedule-builder', [ScheduleController::class, 'store'])->middleware('desktop_only')->name('schedule_builder.store');
    Route::patch('/schedule-builder/{schedule}', [ScheduleController::class, 'update'])->middleware('desktop_only')->name('schedule_builder.update');
    Route::delete('/schedule-builder/{schedule}', [ScheduleController::class, 'destroy'])->middleware('desktop_only')->name('schedule_builder.destroy');

    Route::get('/class-lists', [ClassListController::class, 'index'])->middleware('desktop_only')->name('class_lists');
    Route::get('/grade-verification', [GradeVerificationController::class, 'index'])->name('grade_verification');
    Route::post('/grade-verification/deadline', [GradeVerificationController::class, 'updateDeadline'])->middleware('desktop_only')->name('grade_verification.update_deadline');
    Route::post('/grade-verification/{gradeSubmission}/verify', [GradeVerificationController::class, 'verify'])->middleware('desktop_only')->name('grade_verification.verify');
    Route::post('/grade-verification/{gradeSubmission}/return', [GradeVerificationController::class, 'returnSubmission'])->middleware('desktop_only')->name('grade_verification.return');

    Route::get('/deped-reports', function () {
        return Inertia::render('admin/deped-reports/index');
    })->middleware('desktop_only')->name('deped_reports');

    Route::get('/sf9-generator', function () {
        return Inertia::render('admin/sf9-generator/index');
    })->middleware('desktop_only')->name('sf9_generator');
});
