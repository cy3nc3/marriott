<?php

use App\Http\Controllers\Finance\CashierPanelController;
use App\Http\Controllers\Finance\DailyReportsController;
use App\Http\Controllers\Finance\DataImportController;
use App\Http\Controllers\Finance\DiscountManagerController;
use App\Http\Controllers\Finance\DueReminderSettingsController;
use App\Http\Controllers\Finance\FeeStructureController;
use App\Http\Controllers\Finance\ProductInventoryController;
use App\Http\Controllers\Finance\StudentLedgersController;
use App\Http\Controllers\Finance\TransactionHistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:finance'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('/student-ledgers', [StudentLedgersController::class, 'index'])->name('student_ledgers');

    Route::get('/cashier-panel', [CashierPanelController::class, 'index'])->middleware('desktop_only')->name('cashier_panel');
    Route::get('/cashier-panel/student-suggestions', [CashierPanelController::class, 'studentSuggestions'])->middleware('desktop_only')->name('cashier_panel.student_suggestions');
    Route::post('/cashier-panel/or-number-reservations', [CashierPanelController::class, 'reserveOrNumber'])->middleware('desktop_only')->name('cashier_panel.reserve_or_number');
    Route::delete('/cashier-panel/or-number-reservations/{token}', [CashierPanelController::class, 'releaseOrNumber'])->middleware('desktop_only')->name('cashier_panel.release_or_number');
    Route::post('/cashier-panel/transactions', [CashierPanelController::class, 'storeTransaction'])->middleware('desktop_only')->name('cashier_panel.store_transaction');

    Route::get('/transaction-history', [TransactionHistoryController::class, 'index'])->middleware('desktop_only')->name('transaction_history');
    Route::get('/transaction-history/export', [TransactionHistoryController::class, 'export'])->middleware('desktop_only')->name('transaction_history.export');
    Route::post('/transaction-history/{transaction}/void', [TransactionHistoryController::class, 'void'])->middleware('desktop_only')->name('transaction_history.void');
    Route::post('/transaction-history/{transaction}/refund', [TransactionHistoryController::class, 'refund'])->middleware('desktop_only')->name('transaction_history.refund');
    Route::post('/transaction-history/{transaction}/reissue', [TransactionHistoryController::class, 'reissue'])->middleware('desktop_only')->name('transaction_history.reissue');
    Route::get('/data-import', [DataImportController::class, 'index'])->middleware('desktop_only')->name('data_import');
    Route::post('/data-import/transactions', [DataImportController::class, 'import'])->middleware('desktop_only')->name('data_import.transactions');

    Route::get('/product-inventory', [ProductInventoryController::class, 'index'])->middleware('desktop_only')->name('product_inventory');
    Route::post('/product-inventory', [ProductInventoryController::class, 'store'])->middleware('desktop_only')->name('product_inventory.store');
    Route::patch('/product-inventory/{inventoryItem}', [ProductInventoryController::class, 'update'])->middleware('desktop_only')->name('product_inventory.update');
    Route::delete('/product-inventory/{inventoryItem}', [ProductInventoryController::class, 'destroy'])->middleware('desktop_only')->name('product_inventory.destroy');

    Route::get('/discount-manager', [DiscountManagerController::class, 'index'])->middleware('desktop_only')->name('discount_manager');
    Route::post('/discount-manager', [DiscountManagerController::class, 'store'])->middleware('desktop_only')->name('discount_manager.store');
    Route::patch('/discount-manager/{discount}', [DiscountManagerController::class, 'update'])->middleware('desktop_only')->name('discount_manager.update');
    Route::delete('/discount-manager/{discount}', [DiscountManagerController::class, 'destroy'])->middleware('desktop_only')->name('discount_manager.destroy');
    Route::post('/discount-manager/tag-student', [DiscountManagerController::class, 'tagStudent'])->middleware('desktop_only')->name('discount_manager.tag_student');
    Route::delete('/discount-manager/tag-student/{studentDiscount}', [DiscountManagerController::class, 'untagStudent'])->middleware('desktop_only')->name('discount_manager.untag_student');

    Route::get('/fee-structure', [FeeStructureController::class, 'index'])->middleware('desktop_only')->name('fee_structure');
    Route::post('/fee-structure', [FeeStructureController::class, 'store'])->middleware('desktop_only')->name('fee_structure.store');
    Route::patch('/fee-structure/remedial-subject-fee', [FeeStructureController::class, 'updateRemedialSubjectFee'])->middleware('desktop_only')->name('fee_structure.update_remedial_subject_fee');
    Route::patch('/fee-structure/{fee}', [FeeStructureController::class, 'update'])->middleware('desktop_only')->name('fee_structure.update');
    Route::delete('/fee-structure/{fee}', [FeeStructureController::class, 'destroy'])->middleware('desktop_only')->name('fee_structure.destroy');

    Route::get('/daily-reports', [DailyReportsController::class, 'index'])->name('daily_reports');
    Route::get('/daily-reports/export', [DailyReportsController::class, 'export'])->name('daily_reports.export');

    Route::get('/due-reminder-settings', [DueReminderSettingsController::class, 'index'])->middleware('desktop_only')->name('due_reminder_settings');
    Route::post('/due-reminder-settings', [DueReminderSettingsController::class, 'store'])->middleware('desktop_only')->name('due_reminder_settings.store');
    Route::patch('/due-reminder-settings/automation', [DueReminderSettingsController::class, 'updateAutomation'])->middleware('desktop_only')->name('due_reminder_settings.update_automation');
    Route::patch('/due-reminder-settings/{financeDueReminderRule}', [DueReminderSettingsController::class, 'update'])->middleware('desktop_only')->name('due_reminder_settings.update');
    Route::delete('/due-reminder-settings/{financeDueReminderRule}', [DueReminderSettingsController::class, 'destroy'])->middleware('desktop_only')->name('due_reminder_settings.destroy');
});
