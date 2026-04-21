<?php

use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\GradeSubmission;
use App\Models\InventoryItem;
use App\Models\Section;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\ProductionThreeYearSnapshotSeeder;

test('production three year snapshot seeder builds a deterministic q1-only current year dataset', function () {
    $this->seed(ProductionThreeYearSnapshotSeeder::class);

    $schoolYear2023 = AcademicYear::query()->where('name', '2023-2024')->first();
    $schoolYear2024 = AcademicYear::query()->where('name', '2024-2025')->first();
    $schoolYear2025 = AcademicYear::query()->where('name', '2025-2026')->first();

    expect($schoolYear2023?->status)->toBe('completed');
    expect($schoolYear2024?->status)->toBe('completed');
    expect($schoolYear2025?->status)->toBe('ongoing');
    expect($schoolYear2025?->current_quarter)->toBe('1');

    $admin = User::query()->where('email', 'admin@marriott.edu')->first();
    $registrar = User::query()->where('email', 'registrar@marriott.edu')->first();
    $finance = User::query()->where('email', 'finance@marriott.edu')->first();

    expect($admin?->name)->toBe('Alex Avellanosa');
    expect($registrar?->name)->toBe('Jocelyn Cleofe');
    expect($finance?->name)->toBe('Corrine Avellanosa');

    $currentYearSections = Section::query()
        ->with('gradeLevel:id,level_order')
        ->where('academic_year_id', $schoolYear2025?->id)
        ->get()
        ->map(fn (Section $section): string => "{$section->gradeLevel?->level_order}|{$section->name}")
        ->sort()
        ->values()
        ->all();

    expect($currentYearSections)->toBe([
        '10|St. Anne',
        '10|St. John',
        '7|St. Paul',
        '8|St. Anthony',
        '9|St. Francis',
    ]);

    $currentYearEnrollments = Enrollment::query()
        ->where('academic_year_id', $schoolYear2025?->id)
        ->get();

    expect($currentYearEnrollments->count())->toBe(125);
    expect(
        $currentYearEnrollments
            ->groupBy('section_id')
            ->map(fn ($rows): int => $rows->count())
            ->unique()
            ->values()
            ->all()
    )->toBe([25]);

    $allStudentLrnsAreTwelveDigits = Student::query()
        ->pluck('lrn')
        ->every(fn (?string $lrn): bool => is_string($lrn) && preg_match('/^\d{12}$/', $lrn) === 1);
    expect($allStudentLrnsAreTwelveDigits)->toBeTrue();

    expect(Fee::query()->where('academic_year_id', $schoolYear2025?->id)->count())->toBeGreaterThan(0);
    expect(BillingSchedule::query()->where('academic_year_id', $schoolYear2025?->id)->count())->toBeGreaterThan(0);
    expect(Transaction::query()->count())->toBeGreaterThan(0);
    expect(Discount::query()->count())->toBeGreaterThan(0);
    expect(InventoryItem::query()->count())->toBeGreaterThan(0);

    expect(GradeSubmission::query()->where('academic_year_id', $schoolYear2025?->id)->count())->toBeGreaterThan(0);
    expect(
        GradeSubmission::query()
            ->where('academic_year_id', $schoolYear2025?->id)
            ->where('quarter', '!=', '1')
            ->count()
    )->toBe(0);
});
