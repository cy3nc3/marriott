<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:finance'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('/student-ledgers', function () {
        return Inertia::render('finance/student-ledgers/index');
    })->name('student_ledgers');

    Route::get('/cashier-panel', function () {
        return Inertia::render('finance/cashier-panel/index');
    })->name('cashier_panel');

    Route::get('/transaction-history', function () {
        return Inertia::render('finance/transaction-history/index');
    })->name('transaction_history');

    Route::get('/product-inventory', function () {
        return Inertia::render('finance/product-inventory/index');
    })->name('product_inventory');

    Route::get('/discount-manager', function () {
        return Inertia::render('finance/discount-manager/index');
    })->name('discount_manager');

    Route::get('/fee-structure', function () {
        return Inertia::render('finance/fee-structure/index');
    })->name('fee_structure');

    Route::get('/daily-reports', function () {
        return Inertia::render('finance/daily-reports/index');
    })->name('daily_reports');
});
