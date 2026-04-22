<?php

use App\Services\Imports\FinanceReconciliationService;

uses(Tests\TestCase::class);

test('finance reconciliation service reports a valid match when the net matches the expected delta', function (): void {
    $service = app(FinanceReconciliationService::class);

    expect($service->reconcile(
        [
            ['amount_due' => 1250.00],
            ['amount_due' => 250.00],
        ],
        [
            ['amount' => 1000.00],
            ['amount' => 100.00],
        ],
        400.00
    ))->toBe([
        'net' => 400.00,
        'expected_delta' => 400.00,
        'valid' => true,
    ]);
});

test('finance reconciliation service reports an invalid match when the expected delta differs', function (): void {
    $service = app(FinanceReconciliationService::class);

    expect($service->reconcile(
        [
            ['amount_due' => 1250.00],
            ['amount_due' => 250.00],
        ],
        [
            ['amount' => 1000.00],
            ['amount' => 100.00],
        ],
        350.00
    ))->toBe([
        'net' => 400.00,
        'expected_delta' => 350.00,
        'valid' => false,
    ]);
});

test('finance reconciliation service parses formatted currency strings', function (): void {
    $service = app(FinanceReconciliationService::class);

    expect($service->reconcile(
        [
            ['amount_due' => '₱1,250.00'],
        ],
        [
            ['amount' => '₱100.50'],
        ],
        1149.50
    ))->toBe([
        'net' => 1149.50,
        'expected_delta' => 1149.50,
        'valid' => true,
    ]);
});

test('finance reconciliation service resolves payment and installment amount aliases', function (): void {
    $service = app(FinanceReconciliationService::class);

    expect($service->reconcile(
        [
            ['payment_amount' => '₱1,000.00'],
        ],
        [
            ['installment_amount' => '₱250.00'],
        ],
        750.00
    ))->toBe([
        'net' => 750.00,
        'expected_delta' => 750.00,
        'valid' => true,
    ]);
});
