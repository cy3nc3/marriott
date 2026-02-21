<?php

use App\Http\Controllers\Finance\CashierPanelController;
use App\Http\Controllers\Finance\DailyReportsController;
use App\Http\Controllers\Finance\DiscountManagerController;
use App\Http\Controllers\Finance\FeeStructureController;
use App\Http\Controllers\Finance\ProductInventoryController;
use App\Http\Controllers\Finance\StudentLedgersController;
use App\Http\Controllers\Finance\TransactionHistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:finance'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('/student-ledgers', [StudentLedgersController::class, 'index'])->name('student_ledgers');

    Route::get('/cashier-panel', [CashierPanelController::class, 'index'])->name('cashier_panel');
    Route::post('/cashier-panel/transactions', [CashierPanelController::class, 'storeTransaction'])->name('cashier_panel.store_transaction');

    Route::get('/transaction-history', [TransactionHistoryController::class, 'index'])->name('transaction_history');

    Route::get('/product-inventory', [ProductInventoryController::class, 'index'])->name('product_inventory');
    Route::post('/product-inventory', [ProductInventoryController::class, 'store'])->name('product_inventory.store');
    Route::patch('/product-inventory/{inventoryItem}', [ProductInventoryController::class, 'update'])->name('product_inventory.update');
    Route::delete('/product-inventory/{inventoryItem}', [ProductInventoryController::class, 'destroy'])->name('product_inventory.destroy');

    Route::get('/discount-manager', [DiscountManagerController::class, 'index'])->name('discount_manager');
    Route::post('/discount-manager', [DiscountManagerController::class, 'store'])->name('discount_manager.store');
    Route::patch('/discount-manager/{discount}', [DiscountManagerController::class, 'update'])->name('discount_manager.update');
    Route::delete('/discount-manager/{discount}', [DiscountManagerController::class, 'destroy'])->name('discount_manager.destroy');
    Route::post('/discount-manager/tag-student', [DiscountManagerController::class, 'tagStudent'])->name('discount_manager.tag_student');
    Route::delete('/discount-manager/tag-student/{studentDiscount}', [DiscountManagerController::class, 'untagStudent'])->name('discount_manager.untag_student');

    Route::get('/fee-structure', [FeeStructureController::class, 'index'])->name('fee_structure');
    Route::post('/fee-structure', [FeeStructureController::class, 'store'])->name('fee_structure.store');
    Route::patch('/fee-structure/{fee}', [FeeStructureController::class, 'update'])->name('fee_structure.update');
    Route::delete('/fee-structure/{fee}', [FeeStructureController::class, 'destroy'])->name('fee_structure.destroy');

    Route::get('/daily-reports', [DailyReportsController::class, 'index'])->name('daily_reports');
});
