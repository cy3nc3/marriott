<?php

use App\Services\Imports\HeaderNormalizer;
use App\Services\Imports\MappingResolver;
use App\Services\Imports\ValueParser;
use Carbon\CarbonImmutable;

uses(Tests\TestCase::class);

test('header normalizer resolves common student and finance aliases to canonical fields', function (): void {
    $normalizer = app(HeaderNormalizer::class);

    expect($normalizer->normalize('  Learner Reference Number '))->toBe('learner_reference_number');
    expect($normalizer->canonicalize('  Learner Reference Number '))->toBe('lrn');
    expect($normalizer->canonicalize('Student ID'))->toBe('lrn');
    expect($normalizer->canonicalize('Full Name'))->toBe('name');
    expect($normalizer->canonicalize('Payment Amount'))->toBe('amount');
    expect($normalizer->canonicalize('OR No.'))->toBe('or_number');
    expect($normalizer->canonicalize('Date'))->toBe('payment_date');
    expect($normalizer->canonicalize('Description'))->toBe('description');
    expect($normalizer->canonicalize('Entry Description'))->toBe('description');
    expect($normalizer->canonicalize('Payment Description'))->toBe('description');
    expect($normalizer->canonicalize('Billing Description'))->toBe('due_description');
    expect($normalizer->canonicalize('Installment Description'))->toBe('due_description');
    expect($normalizer->canonicalize('Unexpected Header'))->toBe('unexpected_header');
});

test('value parser normalizes strings and parses safe decimal and date values', function (): void {
    $parser = app(ValueParser::class);

    expect($parser->normalizeString("  Juan   Dela Cruz \n"))->toBe('Juan Dela Cruz');
    expect($parser->normalizeString('   '))->toBeNull();
    expect($parser->parseDecimal(' ?1,234.50 '))->toBe(1234.5);
    expect($parser->parseDecimal('1.234,50'))->toBeNull();

    foreach ([
        '03/14/2024' => '2024-03-14',
        '2024-06-02 13:45' => '2024-06-02',
        '2024-06-02 13:45:00' => '2024-06-02',
        '2024-06-02T13:45:00' => '2024-06-02',
        '2024-06-02T13:45:00+08:00' => '2024-06-02',
    ] as $input => $expectedDate) {
        $parsedDate = $parser->parseDate($input);

        expect($parsedDate)->toBeInstanceOf(CarbonImmutable::class);
        expect($parsedDate?->toDateString())->toBe($expectedDate);
        expect($parsedDate?->toTimeString())->toBe('00:00:00');
    }

    foreach ([45292, 45292.0, '45292'] as $excelSerial) {
        $parsedDate = $parser->parseDate($excelSerial);

        expect($parsedDate)->toBeInstanceOf(CarbonImmutable::class);
        expect($parsedDate?->toDateString())->toBe('2024-01-01');
        expect($parsedDate?->toTimeString())->toBe('00:00:00');
    }

    expect($parser->parseDate('next friday'))->toBeNull();
    expect($parser->parseDate('2024-02-30'))->toBeNull();
    expect($parser->parseDate('14/03/2024'))->toBeNull();
});

test('mapping resolver maps selected headers and reports missing required fields', function (): void {
    $resolver = app(MappingResolver::class);

    $result = $resolver->resolve([
        'Student ID',
        'School Year',
        'Full Name',
        'Grade Level',
        'Section',
        'OR No.',
        'Payment Date',
        'Payment Amount',
        'Payment Method',
    ]);

    expect($result['mapping'])->toMatchArray([
        'lrn' => 'Student ID',
        'school_year' => 'School Year',
        'name' => 'Full Name',
        'grade_level' => 'Grade Level',
        'section' => 'Section',
        'or_number' => 'OR No.',
        'payment_date' => 'Payment Date',
        'amount' => 'Payment Amount',
        'payment_mode' => 'Payment Method',
    ]);

    expect($resolver->missingRequiredFields($result, [
        'lrn',
        'school_year',
        'or_number',
        'payment_date',
        'amount',
    ]))->toBe([]);

    expect($resolver->missingRequiredFields($result, [
        'lrn',
        'school_year',
        'or_number',
        'payment_date',
        'amount',
        'section',
        'guardian_name',
    ]))->toBe(['guardian_name']);
});

test('mapping resolver reports collisions when multiple source headers resolve to the same target', function (): void {
    $resolver = app(MappingResolver::class);

    $result = $resolver->resolve([
        'Date',
        'Payment Date',
        'Entry Description',
        'Payment Description',
        'Amount',
    ]);

    expect($result['mapping'])->toMatchArray([
        'payment_date' => 'Date',
        'description' => 'Entry Description',
        'amount' => 'Amount',
    ]);

    expect($result['collisions'])->toMatchArray([
        'payment_date' => [
            ['index' => 1, 'header' => 'Date'],
            ['index' => 2, 'header' => 'Payment Date'],
        ],
        'description' => [
            ['index' => 3, 'header' => 'Entry Description'],
            ['index' => 4, 'header' => 'Payment Description'],
        ],
    ]);

    $duplicateResult = $resolver->resolve([
        'Date',
        'Date',
        'Amount',
    ]);

    expect($duplicateResult['collisions'])->toMatchArray([
        'payment_date' => [
            ['index' => 1, 'header' => 'Date'],
            ['index' => 2, 'header' => 'Date'],
        ],
    ]);
});
