<?php

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradeLevel;
use App\Models\PermanentRecord;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TeacherSubject;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('admin pages render successfully', function () {
    $this->get('/admin/academic-controls')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/academic-controls/index')
        );

    $this->get('/admin/curriculum-manager')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/curriculum-manager/index')
        );

    $this->get('/admin/section-manager')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/section-manager/index')
        );

    $this->get('/admin/schedule-builder')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/schedule-builder/index')
        );

    $this->get('/admin/class-lists')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/class-lists/index')
        );

    $this->get('/admin/grade-verification')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/grade-verification/index')
        );

    $this->get('/admin/deped-reports')->assertNotFound();
    $this->get('/admin/sf9-generator')->assertNotFound();
});

test('admin dashboard shows enrollment yoy growth and enrollment forecast trend', function () {
    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    $yearDefinitions = [
        ['name' => '2020-2021', 'status' => 'archived', 'start_date' => '2020-06-01', 'end_date' => '2021-03-31', 'count' => 8],
        ['name' => '2021-2022', 'status' => 'archived', 'start_date' => '2021-06-01', 'end_date' => '2022-03-31', 'count' => 9],
        ['name' => '2022-2023', 'status' => 'archived', 'start_date' => '2022-06-01', 'end_date' => '2023-03-31', 'count' => 10],
        ['name' => '2023-2024', 'status' => 'archived', 'start_date' => '2023-06-01', 'end_date' => '2024-03-31', 'count' => 11],
        ['name' => '2024-2025', 'status' => 'archived', 'start_date' => '2024-06-01', 'end_date' => '2025-03-31', 'count' => 12],
        ['name' => '2025-2026', 'status' => 'ongoing', 'start_date' => '2025-06-01', 'end_date' => '2026-03-31', 'count' => 13],
    ];

    $sequence = 0;
    foreach ($yearDefinitions as $definition) {
        $year = AcademicYear::query()->create([
            'name' => $definition['name'],
            'start_date' => $definition['start_date'],
            'end_date' => $definition['end_date'],
            'status' => $definition['status'],
            'current_quarter' => '1',
        ]);

        $section = Section::query()->create([
            'academic_year_id' => $year->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => "Section {$definition['name']}",
            'adviser_id' => null,
        ]);

        for ($index = 0; $index < $definition['count']; $index++) {
            $sequence++;

            $student = Student::query()->create([
                'lrn' => str_pad((string) (910000000000 + $sequence), 12, '0', STR_PAD_LEFT),
                'first_name' => "Student{$sequence}",
                'last_name' => 'Admin',
                'gender' => $sequence % 2 === 0 ? 'Male' : 'Female',
            ]);

            Enrollment::query()->create([
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'grade_level_id' => $gradeLevel->id,
                'section_id' => $section->id,
                'payment_term' => 'cash',
                'downpayment' => 0,
                'status' => 'enrolled',
            ]);
        }
    }

    Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'TLE7',
        'subject_name' => 'TLE 7',
    ]);

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->where('kpis.0.id', 'enrollment-yoy-growth')
            ->where('kpis.0.value', '+8.33%')
            ->where('kpis', function ($kpis): bool {
                return ! collect($kpis)->contains(function (array $kpi): bool {
                    return $kpi['id'] === 'enrollment-capacity';
                });
            })
            ->where('trends.0.id', 'grade-level-enrollment')
            ->where('trends.0.display', 'bar')
            ->where('trends.0.chart.series.0.key', 'male')
            ->where('trends.0.chart.series.1.key', 'female')
            ->where('trends.0.chart.rows.0.male', 6)
            ->where('trends.0.chart.rows.0.female', 7)
            ->where('trends.0.chart.rows.0.total', 13)
            ->where('trends.1.id', 'enrollment-forecast')
            ->where('trends.1.display', 'area')
            ->where('trends.1.chart.rows', function ($rows): bool {
                if (count($rows) !== 7) {
                    return false;
                }

                $forecastRow = collect($rows)->last();
                $lastActualRow = collect($rows)->slice(-2, 1)->first();

                return is_array($forecastRow)
                    && is_array($lastActualRow)
                    && ($lastActualRow['actual'] ?? null) !== null
                    && ($lastActualRow['forecast'] ?? null) === ($lastActualRow['actual'] ?? null)
                    && ($forecastRow['is_forecast'] ?? false) === true
                    && ($forecastRow['forecast'] ?? null) !== null;
            })
        );
});

test('admin dashboard forecast remains stable with partial history and zero baseline', function () {
    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 8',
        'level_order' => 8,
    ]);

    $previousYear = AcademicYear::query()->create([
        'name' => '2024-2025',
        'start_date' => '2024-06-01',
        'end_date' => '2025-03-31',
        'status' => 'archived',
        'current_quarter' => '4',
    ]);

    $activeYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $previousSection = Section::query()->create([
        'academic_year_id' => $previousYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Legacy Section',
    ]);

    $activeSection = Section::query()->create([
        'academic_year_id' => $activeYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Current Section',
    ]);

    ClassSchedule::query()->create([
        'section_id' => $previousSection->id,
        'subject_assignment_id' => null,
        'type' => 'break',
        'label' => 'Legacy Break',
        'day' => 'Monday',
        'start_time' => '09:00:00',
        'end_time' => '09:30:00',
    ]);

    ClassSchedule::query()->create([
        'section_id' => $activeSection->id,
        'subject_assignment_id' => null,
        'type' => 'break',
        'label' => 'Current Break',
        'day' => 'Monday',
        'start_time' => '09:00:00',
        'end_time' => '09:30:00',
    ]);

    for ($index = 1; $index <= 5; $index++) {
        $student = Student::query()->create([
            'lrn' => str_pad((string) (915000000000 + $index), 12, '0', STR_PAD_LEFT),
            'first_name' => "Partial{$index}",
            'last_name' => 'Admin',
            'gender' => $index % 2 === 0 ? 'Male' : 'Female',
        ]);

        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $activeYear->id,
            'grade_level_id' => $gradeLevel->id,
            'section_id' => $activeSection->id,
            'payment_term' => 'cash',
            'downpayment' => 0,
            'status' => 'enrolled',
        ]);
    }

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->where('kpis.0.id', 'enrollment-yoy-growth')
            ->where('kpis.0.value', '0.00%')
            ->where('trends.1.id', 'enrollment-forecast')
            ->where('trends.1.chart.rows', function ($rows): bool {
                if (count($rows) !== 3) {
                    return false;
                }

                $firstRow = $rows[0] ?? null;
                $secondRow = $rows[1] ?? null;
                $forecastRow = $rows[2] ?? null;

                return is_array($firstRow)
                    && is_array($secondRow)
                    && is_array($forecastRow)
                    && ($firstRow['actual'] ?? null) === 0
                    && ($firstRow['forecast'] ?? null) === null
                    && ($secondRow['actual'] ?? null) === 5
                    && ($secondRow['forecast'] ?? null) === 5
                    && ($forecastRow['is_forecast'] ?? null) === true
                    && ($forecastRow['forecast'] ?? null) === 5;
            })
        );
});

test('admin academic controls actions work', function () {
    GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);
    GradeLevel::query()->create([
        'name' => 'Grade 8',
        'level_order' => 8,
    ]);

    $this->post('/admin/academic-controls/initialize', [
        'name' => '2025-2026',
    ])->assertRedirect();

    $academicYear = AcademicYear::query()
        ->where('name', '2025-2026')
        ->first();

    expect($academicYear)->not->toBeNull();
    expect($academicYear->status)->toBe('upcoming');
    expect($academicYear->start_date)->toBeNull();
    expect($academicYear->end_date)->toBeNull();
    expect(Section::query()->where('academic_year_id', $academicYear->id)->count())->toBe(12);

    $this->patch("/admin/academic-controls/{$academicYear->id}/dates", [
        'start_date' => '2025-06-15',
        'end_date' => '2026-04-15',
    ])->assertRedirect();

    $academicYear->refresh();
    expect((string) $academicYear->start_date)->toBe('2025-06-15');
    expect((string) $academicYear->end_date)->toBe('2026-04-15');

    $this->post("/admin/academic-controls/{$academicYear->id}/simulate-opening")
        ->assertRedirect();

    expect($academicYear->fresh()->status)->toBe('ongoing');

    // Disable backup side effects for this action test.
    Setting::set('backup_on_quarter', '0', 'backup');
    Setting::set('backup_on_year_end', '0', 'backup');

    $this->post("/admin/academic-controls/{$academicYear->id}/advance-quarter")
        ->assertRedirect();

    expect($academicYear->fresh()->current_quarter)->toBe('2');

    $this->post('/admin/academic-controls/reset-simulation')
        ->assertRedirect();

    expect(AcademicYear::count())->toBe(0);
});

test('admin academic controls validation rejects duplicates and invalid date ranges', function () {
    $existingYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $this->post('/admin/academic-controls/initialize', [
        'name' => '2025-2026',
    ])->assertRedirect()
        ->assertSessionHasErrors(['name']);

    expect(AcademicYear::query()->count())->toBe(1);

    $this->patch("/admin/academic-controls/{$existingYear->id}/dates", [
        'start_date' => '2026-06-01',
        'end_date' => '2026-05-01',
    ])->assertRedirect()
        ->assertSessionHasErrors(['end_date']);
});

test('admin academic controls initialization requires consecutive school year name', function () {
    $this->post('/admin/academic-controls/initialize', [
        'name' => '2025-2027',
    ])->assertRedirect()
        ->assertSessionHasErrors(['name']);

    expect(AcademicYear::query()->count())->toBe(0);
});

test('upcoming school year remains pre-opening when dates are not set', function () {
    $upcomingYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => null,
        'end_date' => null,
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    $this->get('/admin/academic-controls')->assertSuccessful();

    expect($upcomingYear->fresh()->status)->toBe('upcoming');
});

test('upcoming school year auto-switches to first quarter on start date', function () {
    $upcomingYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addMonths(9)->toDateString(),
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    $this->get('/admin/academic-controls')->assertSuccessful();

    expect($upcomingYear->fresh()->status)->toBe('ongoing');
    expect($upcomingYear->fresh()->current_quarter)->toBe('1');
});

test('simulation actions are blocked in production mode', function () {
    $year = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    config(['app.env' => 'production']);

    $this->post("/admin/academic-controls/{$year->id}/simulate-opening")
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->post('/admin/academic-controls/reset-simulation')
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($year->fresh()->status)->toBe('upcoming');
    expect(AcademicYear::query()->count())->toBe(1);
});

test('admin academic control critical actions write audit logs', function () {
    $year = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    $this->post("/admin/academic-controls/{$year->id}/simulate-opening")
        ->assertRedirect();

    expect(AuditLog::query()
        ->where('action', 'academic_year.simulation_opened')
        ->where('model_type', AcademicYear::class)
        ->where('model_id', $year->id)
        ->exists())->toBeTrue();

    $this->post('/admin/academic-controls/reset-simulation')
        ->assertRedirect();

    expect(AuditLog::query()
        ->where('action', 'academic_year.simulation_reset')
        ->where('model_type', AcademicYear::class)
        ->exists())->toBeTrue();
});

test('admin year close runs batch promotion and creates next year enrollment', function () {
    $sourceYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '4',
    ]);

    $grade7 = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);
    $grade8 = GradeLevel::query()->create([
        'name' => 'Grade 8',
        'level_order' => 8,
    ]);

    $teacher = User::factory()->teacher()->create();

    $section = Section::query()->create([
        'academic_year_id' => $sourceYear->id,
        'grade_level_id' => $grade7->id,
        'name' => 'Rizal',
        'adviser_id' => $teacher->id,
    ]);

    $student = Student::query()->create([
        'lrn' => '944444444444',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
    ]);

    $sourceEnrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $sourceYear->id,
        'grade_level_id' => $grade7->id,
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 1500,
        'status' => 'enrolled',
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $grade7->id,
        'subject_code' => 'ENG7',
        'subject_name' => 'English 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    foreach (['1', '2', '3', '4'] as $quarter) {
        FinalGrade::query()->create([
            'enrollment_id' => $sourceEnrollment->id,
            'subject_assignment_id' => $assignment->id,
            'quarter' => $quarter,
            'grade' => 88,
            'is_locked' => true,
        ]);
    }

    Setting::set('backup_on_year_end', '0', 'backup');

    $this->post("/admin/academic-controls/{$sourceYear->id}/advance-quarter")
        ->assertRedirect();

    expect($sourceYear->fresh()->status)->toBe('completed');
    $targetYear = AcademicYear::query()
        ->where('name', '2026-2027')
        ->first();

    expect($targetYear)->not->toBeNull();
    expect($targetYear->status)->toBe('upcoming');
    expect(Section::query()->where('academic_year_id', $targetYear->id)->count())->toBeGreaterThan(0);

    $nextEnrollment = Enrollment::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $targetYear->id)
        ->first();

    expect($nextEnrollment)->not->toBeNull();
    expect($nextEnrollment->grade_level_id)->toBe($grade8->id);
    expect($nextEnrollment->status)->toBe('for_cashier_payment');
    expect($nextEnrollment->section_id)->toBeNull();
    expect((float) $nextEnrollment->downpayment)->toBe(0.0);

    $record = PermanentRecord::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $sourceYear->id)
        ->first();

    expect($record)->not->toBeNull();
    expect($record->status)->toBe('promoted');
    expect((int) $record->failed_subject_count)->toBe(0);
});

test('admin year close is blocked when annual grades are incomplete or unlocked', function () {
    $sourceYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '4',
    ]);

    $targetYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    $grade7 = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);
    GradeLevel::query()->create([
        'name' => 'Grade 8',
        'level_order' => 8,
    ]);

    $teacher = User::factory()->teacher()->create();

    $section = Section::query()->create([
        'academic_year_id' => $sourceYear->id,
        'grade_level_id' => $grade7->id,
        'name' => 'Mabini',
        'adviser_id' => $teacher->id,
    ]);

    $student = Student::query()->create([
        'lrn' => '955555555555',
        'first_name' => 'Carlo',
        'last_name' => 'Reyes',
    ]);

    $sourceEnrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $sourceYear->id,
        'grade_level_id' => $grade7->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $grade7->id,
        'subject_code' => 'SCI7',
        'subject_name' => 'Science 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    FinalGrade::query()->create([
        'enrollment_id' => $sourceEnrollment->id,
        'subject_assignment_id' => $assignment->id,
        'quarter' => '1',
        'grade' => 82,
        'is_locked' => true,
    ]);
    FinalGrade::query()->create([
        'enrollment_id' => $sourceEnrollment->id,
        'subject_assignment_id' => $assignment->id,
        'quarter' => '2',
        'grade' => 83,
        'is_locked' => true,
    ]);
    FinalGrade::query()->create([
        'enrollment_id' => $sourceEnrollment->id,
        'subject_assignment_id' => $assignment->id,
        'quarter' => '3',
        'grade' => 84,
        'is_locked' => false,
    ]);

    $this->post("/admin/academic-controls/{$sourceYear->id}/advance-quarter")
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($sourceYear->fresh()->status)->toBe('ongoing');

    expect(Enrollment::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $targetYear->id)
        ->exists())->toBeFalse();
});

test('admin curriculum manager actions work', function () {
    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);
    $teacher = User::factory()->teacher()->create();

    $this->post('/admin/curriculum-manager', [
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'MATH7',
        'subject_name' => 'Mathematics 7',
        'teacher_ids' => [$teacher->id],
    ])->assertRedirect();

    $subject = Subject::query()->where('subject_code', 'MATH7')->first();

    expect($subject)->not->toBeNull();
    expect($subject->teachers()->whereKey($teacher->id)->exists())->toBeTrue();

    $this->patch("/admin/curriculum-manager/{$subject->id}", [
        'subject_code' => 'MATH7A',
        'subject_name' => 'Mathematics 7 Advanced',
    ])->assertRedirect();

    expect($subject->fresh()->subject_code)->toBe('MATH7A');
    expect($subject->fresh()->subject_name)->toBe('Mathematics 7 Advanced');

    $this->post("/admin/curriculum-manager/{$subject->id}/certify", [
        'teacher_ids' => [$teacher->id],
    ])->assertRedirect();

    expect($subject->fresh()->teachers()->whereKey($teacher->id)->exists())->toBeTrue();

    $this->delete("/admin/curriculum-manager/{$subject->id}")
        ->assertRedirect();

    expect(Subject::query()->whereKey($subject->id)->exists())->toBeFalse();
});

test('admin section manager actions work', function () {
    $academicYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);
    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 8',
        'level_order' => 8,
    ]);
    $teacher = User::factory()->teacher()->create();

    $this->post('/admin/section-manager', [
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Section A',
        'adviser_id' => $teacher->id,
    ])->assertRedirect();

    $section = Section::query()->where('name', 'Section A')->first();

    expect($section)->not->toBeNull();
    expect($section->academic_year_id)->toBe($academicYear->id);
    expect($section->adviser_id)->toBe($teacher->id);

    $this->patch("/admin/section-manager/{$section->id}", [
        'name' => 'Section Alpha',
        'adviser_id' => null,
    ])->assertRedirect();

    expect($section->fresh()->name)->toBe('Section Alpha');
    expect($section->fresh()->adviser_id)->toBeNull();

    $this->delete("/admin/section-manager/{$section->id}")
        ->assertRedirect();

    expect(Section::query()->whereKey($section->id)->exists())->toBeFalse();
});

test('admin section manager prioritizes ongoing school year context', function () {
    $upcomingYear = AcademicYear::query()->create([
        'name' => '2027-2028',
        'start_date' => '2027-06-01',
        'end_date' => '2028-03-31',
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    $ongoingYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'ongoing',
        'current_quarter' => '2',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 8',
        'level_order' => 8,
    ]);

    Section::query()->create([
        'academic_year_id' => $upcomingYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Upcoming Section',
    ]);

    Section::query()->create([
        'academic_year_id' => $ongoingYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'St. Paul',
    ]);

    $this->post('/admin/section-manager', [
        'grade_level_id' => $gradeLevel->id,
        'name' => 'St. Anthony',
        'adviser_id' => null,
    ])->assertRedirect();

    $this->get('/admin/section-manager')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/section-manager/index')
            ->where('activeYear.id', $ongoingYear->id)
            ->has('gradeLevels.0.sections', 2)
            ->where('gradeLevels.0.sections.0.name', 'St. Paul')
            ->where('gradeLevels.0.sections.1.name', 'St. Anthony')
        );

    expect(Section::query()
        ->where('academic_year_id', $ongoingYear->id)
        ->where('name', 'St. Anthony')
        ->exists())->toBeTrue();
});

test('admin schedule builder actions work', function () {
    $academicYear = AcademicYear::query()->create([
        'name' => '2027-2028',
        'start_date' => '2027-06-01',
        'end_date' => '2028-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);
    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 9',
        'level_order' => 9,
    ]);
    $section = Section::query()->create([
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Section B',
    ]);
    $teacher = User::factory()->create([
        'role' => UserRole::TEACHER,
    ]);
    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'SCI9',
        'subject_name' => 'Science 9',
    ]);

    $this->post('/admin/schedule-builder', [
        'section_id' => $section->id,
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
        'type' => 'academic',
        'label' => null,
        'day' => 'Monday',
        'start_time' => '08:00',
        'end_time' => '09:00',
    ])->assertRedirect();

    $schedule = ClassSchedule::query()
        ->where('section_id', $section->id)
        ->where('day', 'Monday')
        ->latest('id')
        ->first();

    expect($schedule)->not->toBeNull();
    expect($schedule->subject_assignment_id)->not->toBeNull();

    $this->patch("/admin/schedule-builder/{$schedule->id}", [
        'subject_assignment_id' => $schedule->subject_assignment_id,
        'type' => 'break',
        'label' => 'Recess',
        'day' => 'Monday',
        'start_time' => '09:00',
        'end_time' => '09:30',
    ])->assertRedirect();

    $schedule->refresh();
    expect($schedule->type)->toBe('break');
    expect($schedule->label)->toBe('Recess');
    expect((string) $schedule->start_time)->toBe('09:00');
    expect((string) $schedule->end_time)->toBe('09:30');

    $this->delete("/admin/schedule-builder/{$schedule->id}")
        ->assertRedirect();

    expect(ClassSchedule::query()->whereKey($schedule->id)->exists())->toBeFalse();
});

test('admin schedule builder prioritizes ongoing school year context', function () {
    $upcomingYear = AcademicYear::query()->create([
        'name' => '2027-2028',
        'start_date' => '2027-06-01',
        'end_date' => '2028-03-31',
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    $ongoingYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'ongoing',
        'current_quarter' => '2',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 9',
        'level_order' => 9,
    ]);

    $upcomingSection = Section::query()->create([
        'academic_year_id' => $upcomingYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Mabini',
    ]);

    $ongoingSection = Section::query()->create([
        'academic_year_id' => $ongoingYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Bonifacio',
    ]);

    ClassSchedule::query()->create([
        'section_id' => $upcomingSection->id,
        'subject_assignment_id' => null,
        'type' => 'break',
        'label' => 'Upcoming Break',
        'day' => 'Monday',
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    ClassSchedule::query()->create([
        'section_id' => $ongoingSection->id,
        'subject_assignment_id' => null,
        'type' => 'break',
        'label' => 'Ongoing Break',
        'day' => 'Tuesday',
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    $this->get('/admin/schedule-builder')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/schedule-builder/index')
            ->where('activeYear.id', $ongoingYear->id)
            ->has('sectionSchedules', 1)
            ->where('sectionSchedules.0.section_id', $ongoingSection->id)
            ->has('gradeLevels.0.sections', 1)
            ->where('gradeLevels.0.sections.0.id', $ongoingSection->id)
        );
});

test('admin schedule builder validates end time is after start time', function () {
    $academicYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 8',
        'level_order' => 8,
    ]);

    $section = Section::query()->create([
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Rizal',
    ]);

    $this->post('/admin/schedule-builder', [
        'section_id' => $section->id,
        'type' => 'break',
        'label' => 'Recess',
        'day' => 'Monday',
        'start_time' => '10:00',
        'end_time' => '09:30',
    ])->assertSessionHasErrors('end_time');

    expect(ClassSchedule::query()->count())->toBe(0);
});
