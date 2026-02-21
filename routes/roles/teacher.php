<?php

use App\Http\Controllers\Teacher\AdvisoryBoardController;
use App\Http\Controllers\Teacher\GradingSheetController;
use App\Http\Controllers\Teacher\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule');

    Route::get('/grading-sheet', [GradingSheetController::class, 'index'])->name('grading_sheet');
    Route::post('/grading-sheet/rubric', [GradingSheetController::class, 'updateRubric'])->name('grading_sheet.update_rubric');
    Route::post('/grading-sheet/assessments', [GradingSheetController::class, 'storeAssessment'])->name('grading_sheet.store_assessment');
    Route::post('/grading-sheet/scores', [GradingSheetController::class, 'storeScores'])->name('grading_sheet.store_scores');

    Route::get('/advisory-board', [AdvisoryBoardController::class, 'index'])->name('advisory_board');
    Route::post('/advisory-board/conduct', [AdvisoryBoardController::class, 'storeConduct'])->name('advisory_board.store_conduct');
});
