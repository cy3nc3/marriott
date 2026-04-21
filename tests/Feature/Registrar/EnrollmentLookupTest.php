<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Student;
use App\Models\User;

beforeEach(function () {
    $this->registrar = User::factory()->registrar()->create();
    $this->actingAs($this->registrar);

    $this->ongoingYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);
});

test('lookup returns matched student with promoted grade level for returning learner', function () {
    $gradeSeven = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);
    $gradeEight = GradeLevel::query()->create([
        'name' => 'Grade 8',
        'level_order' => 8,
    ]);

    $student = Student::query()->create([
        'lrn' => '123456789012',
        'first_name' => 'Maria',
        'middle_name' => 'Dela',
        'last_name' => 'Cruz',
        'gender' => 'Female',
        'birthdate' => '2010-03-02',
        'guardian_name' => 'Ana Cruz',
        'contact_number' => '09171234567',
    ]);

    $previousYear = AcademicYear::query()->create([
        'name' => '2024-2025',
        'start_date' => '2024-06-01',
        'end_date' => '2025-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $previousYear->id,
        'grade_level_id' => $gradeSeven->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    $this->getJson('/registrar/enrollment/lookup?lrn=123456789012')
        ->assertSuccessful()
        ->assertJson([
            'matched' => true,
            'academic_year_id' => $this->ongoingYear->id,
            'student' => [
                'lrn' => '123456789012',
                'first_name' => 'Maria',
                'middle_name' => 'Dela',
                'last_name' => 'Cruz',
                'gender' => 'Female',
                'birthdate' => '2010-03-02',
                'guardian_name' => 'Ana Cruz',
                'guardian_contact_number' => '09171234567',
                'recommended_grade_level_id' => $gradeEight->id,
            ],
        ]);
});

test('lookup returns unmatched payload for unknown lrn', function () {
    GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    $this->getJson('/registrar/enrollment/lookup?lrn=999999999999')
        ->assertSuccessful()
        ->assertJson([
            'matched' => false,
            'academic_year_id' => $this->ongoingYear->id,
            'student' => null,
        ]);
});

test('lookup validates lrn format', function () {
    $this->getJson('/registrar/enrollment/lookup?lrn=123')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lrn']);
});

test('enrollment store defaults returning students to promoted grade level', function () {
    $gradeSeven = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);
    $gradeEight = GradeLevel::query()->create([
        'name' => 'Grade 8',
        'level_order' => 8,
    ]);

    $student = Student::query()->create([
        'lrn' => '234567890123',
        'first_name' => 'Liza',
        'last_name' => 'Rivera',
        'birthdate' => '2010-01-01',
    ]);

    $previousYear = AcademicYear::query()->create([
        'name' => '2024-2025',
        'start_date' => '2024-06-01',
        'end_date' => '2025-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $previousYear->id,
        'grade_level_id' => $gradeSeven->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    $this->post('/registrar/enrollment', [
        'lrn' => $student->lrn,
        'first_name' => 'Liza',
        'last_name' => 'Rivera',
        'birthdate' => '2010-01-01',
        'guardian_name' => 'Parent Rivera',
        'guardian_contact_number' => '09171234567',
        'payment_term' => 'monthly',
        'downpayment' => 1000,
    ])->assertRedirect();

    $intake = Enrollment::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->ongoingYear->id)
        ->first();

    expect($intake)->not->toBeNull();
    expect($intake?->grade_level_id)->toBe($gradeEight->id);
});
