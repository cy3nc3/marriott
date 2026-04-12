<?php

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ConductRating;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradedActivity;
use App\Models\GradeSubmission;
use App\Models\PermanentRecord;
use App\Models\RemedialCase;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\User;
use Database\Seeders\ProductionBaselineSeeder;
use Database\Seeders\ProductionEndOfYearStageSeeder;
use Database\Seeders\ProductionEnrollmentStageSeeder;
use Database\Seeders\ProductionOngoingClassesStageSeeder;
use Illuminate\Support\Facades\Hash;

test('production stage seeders cumulatively advance the demo school year without removing manual records', function () {
    $this->seed(ProductionBaselineSeeder::class);

    $completedYear = AcademicYear::query()->where('name', '2024-2025')->first();
    $upcomingYear = AcademicYear::query()->where('name', '2025-2026')->first();

    expect($completedYear?->status)->toBe('completed');
    expect($upcomingYear?->status)->toBe('upcoming');
    expect(Student::query()->where('lrn', '1000000001')->exists())->toBeTrue();
    expect(Enrollment::query()->where('academic_year_id', $completedYear?->id)->where('status', 'enrolled')->count())
        ->toBeGreaterThan(0);

    $manualUser = User::query()->create([
        'first_name' => 'Manual',
        'last_name' => 'Student',
        'name' => 'Manual Student',
        'email' => 'manual.student@marriott.edu',
        'password' => Hash::make('password'),
        'birthday' => '2010-01-01',
        'role' => UserRole::STUDENT,
    ]);
    Student::query()->create([
        'user_id' => $manualUser->id,
        'lrn' => '9999999999',
        'first_name' => 'Manual',
        'last_name' => 'Student',
        'gender' => 'Female',
        'birthdate' => '2010-01-01',
    ]);

    $this->seed(ProductionEnrollmentStageSeeder::class);

    $upcomingYear->refresh();
    expect($upcomingYear->status)->toBe('upcoming');
    expect(Student::query()->where('lrn', '9999999999')->exists())->toBeTrue();
    expect(Enrollment::query()->where('academic_year_id', $upcomingYear->id)->where('status', 'for_cashier_payment')->count())
        ->toBeGreaterThan(0);
    expect(Enrollment::query()->where('academic_year_id', $upcomingYear->id)->where('status', 'enrolled')->count())
        ->toBeGreaterThan(0);

    $this->seed(ProductionOngoingClassesStageSeeder::class);

    $upcomingYear->refresh();
    expect($upcomingYear->status)->toBe('ongoing');
    expect($upcomingYear->current_quarter)->toBe('2');
    expect(Student::query()->where('lrn', '9999999999')->exists())->toBeTrue();
    expect(Attendance::query()->count())->toBeGreaterThan(0);
    expect(GradedActivity::query()->count())->toBeGreaterThan(0);
    expect(StudentScore::query()->count())->toBeGreaterThan(0);
    expect(GradeSubmission::query()->where('academic_year_id', $upcomingYear->id)->count())->toBeGreaterThan(0);

    $this->seed(ProductionEndOfYearStageSeeder::class);

    $upcomingYear->refresh();
    expect($upcomingYear->status)->toBe('completed');
    expect($upcomingYear->current_quarter)->toBe('4');
    expect(Student::query()->where('lrn', '9999999999')->exists())->toBeTrue();
    expect(FinalGrade::query()->where('is_locked', true)->count())->toBeGreaterThan(0);
    expect(ConductRating::query()->where('is_locked', true)->count())->toBeGreaterThan(0);
    expect(PermanentRecord::query()->where('academic_year_id', $upcomingYear->id)->count())->toBeGreaterThan(0);
    expect(RemedialCase::query()->where('academic_year_id', $upcomingYear->id)->count())->toBeGreaterThan(0);
});
