<?php

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;

beforeEach(function () {
    $academicYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    $section = Section::query()->create([
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Mabini',
    ]);

    $student = Student::query()->create([
        'lrn' => (string) random_int(100000000000, 999999999999),
        'first_name' => 'Attendance',
        'last_name' => 'Learner',
    ]);

    $this->enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'status' => 'enrolled',
    ]);
});

test('attendance exposes sf2 attendance statuses', function () {
    expect(Attendance::STATUSES)->toBe([
        Attendance::STATUS_PRESENT,
        Attendance::STATUS_ABSENT,
        Attendance::STATUS_TARDY_LATE_COMER,
        Attendance::STATUS_TARDY_CUTTING_CLASSES,
    ]);
});

test('attendance can store both sf2 tardy variants', function () {
    Attendance::query()->create([
        'enrollment_id' => $this->enrollment->id,
        'date' => '2026-03-01',
        'status' => Attendance::STATUS_TARDY_LATE_COMER,
    ]);

    Attendance::query()->create([
        'enrollment_id' => $this->enrollment->id,
        'date' => '2026-03-02',
        'status' => Attendance::STATUS_TARDY_CUTTING_CLASSES,
    ]);

    $this->assertDatabaseHas('attendances', [
        'enrollment_id' => $this->enrollment->id,
        'date' => '2026-03-01',
        'status' => Attendance::STATUS_TARDY_LATE_COMER,
    ]);

    $this->assertDatabaseHas('attendances', [
        'enrollment_id' => $this->enrollment->id,
        'date' => '2026-03-02',
        'status' => Attendance::STATUS_TARDY_CUTTING_CLASSES,
    ]);
});
