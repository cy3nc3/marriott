<?php

use App\Http\Controllers\Registrar\EnrollmentController;
use App\Http\Controllers\Registrar\RemedialEntryController;
use App\Http\Controllers\Registrar\StudentDirectoryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:registrar'])->prefix('registrar')->name('registrar.')->group(function () {
    Route::get('/student-directory', [StudentDirectoryController::class, 'index'])->name('student_directory');
    Route::post('/student-directory/sf1-upload', [StudentDirectoryController::class, 'uploadSf1'])->name('student_directory.sf1_upload');

    Route::get('/enrollment', [EnrollmentController::class, 'index'])->name('enrollment');
    Route::post('/enrollment', [EnrollmentController::class, 'store'])->name('enrollment.store');
    Route::patch('/enrollment/{enrollment}', [EnrollmentController::class, 'update'])->name('enrollment.update');
    Route::delete('/enrollment/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollment.destroy');

    Route::get('/permanent-records', function () {
        return Inertia::render('registrar/permanent-records/index');
    })->name('permanent_records');

    Route::get('/batch-promotion', function () {
        return Inertia::render('registrar/batch-promotion/index');
    })->name('batch_promotion');

    Route::get('/remedial-entry', [RemedialEntryController::class, 'index'])->name('remedial_entry');
    Route::post('/remedial-entry', [RemedialEntryController::class, 'store'])->name('remedial_entry.store');

    Route::get('/student-departure', function () {
        return Inertia::render('registrar/student-departure/index');
    })->name('student_departure');
});
