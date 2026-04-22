<?php

use App\Services\Imports\DuplicateEngine;

uses(Tests\TestCase::class);

test('duplicate engine builds the primary payment duplicate key from lrn and or number', function (): void {
    $engine = app(DuplicateEngine::class);

    expect($engine->paymentDuplicateKey([
        'lrn' => '123456789012',
        'or_number' => 'OR-000123',
        'payment_date' => '2026-04-21',
        'amount' => 1500,
        'reference_no' => 'REF-001',
    ]))->toBe('123456789012|OR-000123');
});

test('duplicate engine falls back to payment metadata when or number is missing', function (): void {
    $engine = app(DuplicateEngine::class);

    expect($engine->paymentDuplicateKey([
        'lrn' => '123456789012',
        'payment_date' => '04/21/2026',
        'amount' => '1500.00',
        'reference_no' => 'REF-001',
    ]))->toBe('123456789012|2026-04-21|1500.00|REF-001');
});

test('duplicate engine rejects ambiguous dates in fallback duplicate keys', function (): void {
    $engine = app(DuplicateEngine::class);

    expect($engine->paymentDuplicateKey([
        'lrn' => '123456789012',
        'payment_date' => '20240314',
        'amount' => '1500.00',
        'reference_no' => 'REF-001',
    ]))->toBeNull();
});

test('duplicate engine accepts formatted currency amounts in fallback duplicate keys', function (): void {
    $engine = app(DuplicateEngine::class);

    expect($engine->paymentDuplicateKey([
        'lrn' => '123456789012',
        'payment_date' => '04/21/2026',
        'amount' => '₱1,234.50',
        'reference_no' => 'REF-001',
    ]))->toBe('123456789012|2026-04-21|1234.50|REF-001');
});

test('duplicate engine falls back to a later parseable amount alias when the first amount alias is invalid', function (): void {
    $engine = app(DuplicateEngine::class);

    expect($engine->paymentDuplicateKey([
        'lrn' => '123456789012',
        'payment_date' => '04/21/2026',
        'amount' => 'not-a-number',
        'payment_amount' => '$1,234.50',
        'reference_no' => 'REF-001',
    ]))->toBe('123456789012|2026-04-21|1234.50|REF-001');
});

test('duplicate engine falls back to a later parseable payment date alias when the first payment date alias is invalid', function (): void {
    $engine = app(DuplicateEngine::class);

    expect($engine->paymentDuplicateKey([
        'lrn' => '123456789012',
        'payment_date' => '20240314',
        'transaction_date' => '04/21/2026',
        'amount' => '1500.00',
        'reference_no' => 'REF-001',
    ]))->toBe('123456789012|2026-04-21|1500.00|REF-001');
});

test('duplicate engine normalizes duplicated internal whitespace in key parts', function (): void {
    $engine = app(DuplicateEngine::class);

    expect($engine->paymentDuplicateKey([
        'lrn' => '123456789012',
        'or_number' => 'OR-  000123',
        'payment_date' => '04/21/2026',
        'amount' => '1500.00',
        'reference_no' => 'REF  001',
    ]))->toBe('123456789012|OR- 000123');
});

test('duplicate engine returns null when the fallback key cannot be assembled', function (): void {
    $engine = app(DuplicateEngine::class);

    expect($engine->paymentDuplicateKey([
        'lrn' => '123456789012',
        'payment_date' => '04/21/2026',
        'amount' => '1500.00',
    ]))->toBeNull();
});
