<?php

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\BillingSchedule;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\GradedActivity;
use App\Models\LedgerEntry;
use App\Models\Section;
use App\Models\StudentScore;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\ProductionQuarterOneDayFifteenSeeder;

test('production quarter one day fifteen seeder creates a production-like snapshot', function () {
    $this->seed(ProductionQuarterOneDayFifteenSeeder::class);

    $academicYear = AcademicYear::query()->where('name', '2025-2026')->first();
    expect($academicYear)->not->toBeNull();
    expect($academicYear?->status)->toBe('ongoing');
    expect($academicYear?->current_quarter)->toBe('1');

    $sections = Section::query()
        ->with('gradeLevel:id,level_order')
        ->where('academic_year_id', $academicYear?->id)
        ->orderBy('grade_level_id')
        ->orderBy('name')
        ->get();

    expect($sections)->toHaveCount(5);
    expect($sections
        ->map(fn (Section $section): string => "{$section->gradeLevel?->level_order}|{$section->name}")
        ->all())
        ->toBe([
            '7|St. Paul',
            '8|St. Anthony',
            '9|St. Francis',
            '10|St. Anne',
            '10|St. John',
        ]);

    $enrollments = Enrollment::query()
        ->where('academic_year_id', $academicYear?->id)
        ->get();

    expect($enrollments)->toHaveCount(125);
    $sectionCounts = $enrollments
        ->groupBy('section_id')
        ->map(fn ($rows): int => $rows->count());
    expect($sectionCounts)->toHaveCount(5);
    expect($sectionCounts->unique()->values()->all())->toBe([25]);

    expect(User::query()->where('email', 'finance@marriott.edu')->exists())->toBeTrue();
    expect(User::query()->where('email', 'rowell.almonte@marriott.edu')->exists())->toBeTrue();

    $sectionIds = $sections->pluck('id');
    expect(ClassSchedule::query()->whereIn('section_id', $sectionIds)->count())->toBe(200);

    expect(Attendance::query()->count())->toBeGreaterThan(10000);
    expect(GradedActivity::query()->where('quarter', '1')->count())->toBeGreaterThan(0);
    expect(StudentScore::query()->count())->toBeGreaterThan(0);

    $transactions = Transaction::query()
        ->whereDate('created_at', '>=', '2025-06-02')
        ->get();

    expect($transactions)->toHaveCount(60);
    $transactionDays = $transactions
        ->groupBy(fn (Transaction $transaction): string => (string) $transaction->created_at?->toDateString());
    expect($transactionDays)->toHaveCount(15);
    expect($transactionDays->every(fn ($rows): bool => $rows->count() >= 1))->toBeTrue();

    expect(BillingSchedule::query()->where('academic_year_id', $academicYear?->id)->count())->toBeGreaterThan(0);
    expect(LedgerEntry::query()->where('academic_year_id', $academicYear?->id)->count())->toBeGreaterThan(125);
});
