<?php

use App\Http\Controllers\Imports\ImportBatchRollbackController;
use App\Http\Controllers\Imports\StudentImportBatchController;
use App\Http\Controllers\Registrar\BatchPromotionController;
use App\Http\Controllers\Registrar\DataImportController;
use App\Http\Controllers\Registrar\EnrollmentController;
use App\Http\Controllers\Registrar\PermanentRecordsController;
use App\Http\Controllers\Registrar\RemedialEntryController;
use App\Http\Controllers\Registrar\StudentDepartureController;
use App\Http\Controllers\Registrar\StudentDirectoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:registrar'])->prefix('registrar')->name('registrar.')->group(function () {
    Route::get('/student-directory', [StudentDirectoryController::class, 'index'])->name('student_directory');
    Route::post('/student-directory/sf1-upload', [StudentDirectoryController::class, 'uploadSf1'])->middleware('desktop_only')->name('student_directory.sf1_upload');

    Route::get('/enrollment', [EnrollmentController::class, 'index'])->middleware('desktop_only')->name('enrollment');
    Route::get('/enrollment/lookup', [EnrollmentController::class, 'lookup'])->middleware('desktop_only')->name('enrollment.lookup');
    Route::get('/enrollment/export', [EnrollmentController::class, 'export'])->middleware('desktop_only')->name('enrollment.export');
    Route::get('/enrollment/{enrollment}/assessment', [EnrollmentController::class, 'printAssessment'])->middleware('desktop_only')->name('enrollment.assessment');
    Route::post('/enrollment/{enrollment}/regenerate-activation-codes', [EnrollmentController::class, 'regenerateAssessmentCredentials'])->middleware('desktop_only')->name('enrollment.regenerate_activation_codes');
    Route::post('/enrollment', [EnrollmentController::class, 'store'])->middleware('desktop_only')->name('enrollment.store');
    Route::patch('/enrollment/{enrollment}', [EnrollmentController::class, 'update'])->middleware('desktop_only')->name('enrollment.update');
    Route::delete('/enrollment/{enrollment}', [EnrollmentController::class, 'destroy'])->middleware('desktop_only')->name('enrollment.destroy');

    Route::get('/permanent-records', [PermanentRecordsController::class, 'index'])->middleware('desktop_only')->name('permanent_records');
    Route::get('/data-import', [DataImportController::class, 'index'])->middleware('desktop_only')->name('data_import');
    Route::post('/data-import/permanent-records', [DataImportController::class, 'import'])->middleware('desktop_only')->name('data_import.permanent_records');
    Route::post('/import-batches', [StudentImportBatchController::class, 'store'])->middleware('desktop_only')->name('import_batches.store');
    Route::post('/import-batches/{importBatch}/preview', [StudentImportBatchController::class, 'preview'])->middleware('desktop_only')->name('import_batches.preview');
    Route::patch('/import-batches/{importBatch}/rows/{importBatchRow}', [StudentImportBatchController::class, 'updateRow'])->middleware('desktop_only')->name('import_batches.rows.update');
    Route::post('/import-batches/{importBatch}/apply', [StudentImportBatchController::class, 'apply'])->middleware('desktop_only')->name('import_batches.apply');
    Route::post('/import-batches/{importBatch}/rollback', [ImportBatchRollbackController::class, 'store'])->middleware('desktop_only')->name('import_batches.rollback');

    Route::get('/batch-promotion', [BatchPromotionController::class, 'index'])->middleware('desktop_only')->name('batch_promotion');
    Route::post('/batch-promotion/review', [BatchPromotionController::class, 'resolveReviewCase'])->middleware('desktop_only')->name('batch_promotion.review');

    Route::get('/remedial-entry', [RemedialEntryController::class, 'index'])->middleware('desktop_only')->name('remedial_entry');
    Route::post('/remedial-entry/intake', [RemedialEntryController::class, 'storeIntake'])->middleware('desktop_only')->name('remedial_entry.intake');
    Route::post('/remedial-entry', [RemedialEntryController::class, 'store'])->middleware('desktop_only')->name('remedial_entry.store');

    Route::get('/student-departure', [StudentDepartureController::class, 'index'])->middleware('desktop_only')->name('student_departure');
    Route::post('/student-departure', [StudentDepartureController::class, 'store'])->middleware('desktop_only')->name('student_departure.store');
});
