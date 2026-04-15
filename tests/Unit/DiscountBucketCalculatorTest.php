<?php

use App\Models\AcademicYear;
use App\Models\Discount;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Services\Finance\DiscountBucketCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('discount bucket calculator groups discount amounts by export bucket with capped total', function () {
    $academicYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $student = Student::query()->create([
        'lrn' => '123456789012',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);

    $overallDiscount = Discount::query()->create([
        'name' => 'Scholarship Grant',
        'type' => 'percentage',
        'value' => 10,
        'export_bucket' => 'overall_discount',
    ]);

    $specialDiscount = Discount::query()->create([
        'name' => 'Voucher Credit',
        'type' => 'fixed',
        'value' => 700,
        'export_bucket' => 'special_discount',
    ]);

    $miscDiscount = Discount::query()->create([
        'name' => 'Miscellaneous Relief',
        'type' => 'fixed',
        'value' => 500,
        'export_bucket' => 'misc_discount',
    ]);

    StudentDiscount::query()->create([
        'student_id' => $student->id,
        'discount_id' => $overallDiscount->id,
        'academic_year_id' => $academicYear->id,
    ]);

    StudentDiscount::query()->create([
        'student_id' => $student->id,
        'discount_id' => $specialDiscount->id,
        'academic_year_id' => $academicYear->id,
    ]);

    StudentDiscount::query()->create([
        'student_id' => $student->id,
        'discount_id' => $miscDiscount->id,
        'academic_year_id' => $academicYear->id,
    ]);

    $summary = app(DiscountBucketCalculator::class)->summarizeForStudent(
        $student->id,
        $academicYear->id,
        1000
    );

    expect($summary['bucket_totals'])->toMatchArray([
        'overall_discount' => 100.0,
        'special_discount' => 700.0,
        'misc_discount' => 200.0,
        'misc_sibling_discount' => 0.0,
        'tuition_sibling_discount' => 0.0,
        'early_enrollment_discount' => 0.0,
        'fape' => 0.0,
        'fape_previous_year' => 0.0,
    ]);

    expect($summary['total_discount_amount'])->toBe(1000.0);
});
