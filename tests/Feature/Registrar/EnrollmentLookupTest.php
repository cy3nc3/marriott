<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\PermanentRecord;
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
    PermanentRecord::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $previousYear->id,
        'grade_level_id' => $gradeSeven->id,
        'status' => 'promoted',
        'general_average' => 85,
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
            'grade_prefill_mode' => 'next_grade',
            'grade_guardrail' => [
                'allowed_exact_grade_level_id' => $gradeEight->id,
            ],
        ]);
});

test('lookup locks returning retained student to same grade for immediate previous year', function () {
    $gradeSeven = GradeLevel::query()->create(['name' => 'Grade 7', 'level_order' => 7]);
    GradeLevel::query()->create(['name' => 'Grade 8', 'level_order' => 8]);
    $student = Student::query()->create([
        'lrn' => '456789012345',
        'first_name' => 'Nina',
        'last_name' => 'Santos',
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
    PermanentRecord::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $previousYear->id,
        'grade_level_id' => $gradeSeven->id,
        'status' => 'retained',
        'general_average' => 73,
    ]);

    $this->getJson('/registrar/enrollment/lookup?lrn=456789012345')
        ->assertSuccessful()
        ->assertJson([
            'grade_prefill_mode' => 'same_grade',
            'grade_guardrail' => [
                'allowed_exact_grade_level_id' => $gradeSeven->id,
            ],
            'status_flags' => [
                'has_previous_year_retained' => true,
            ],
        ]);
});

test('lookup does not prefill older returning student and sets minimum grade order guardrail', function () {
    $gradeSeven = GradeLevel::query()->create(['name' => 'Grade 7', 'level_order' => 7]);
    GradeLevel::query()->create(['name' => 'Grade 8', 'level_order' => 8]);
    GradeLevel::query()->create(['name' => 'Grade 9', 'level_order' => 9]);
    $student = Student::query()->create([
        'lrn' => '567890123456',
        'first_name' => 'Older',
        'last_name' => 'Returner',
        'birthdate' => '2010-01-01',
    ]);
    $olderYear = AcademicYear::query()->create([
        'name' => '2023-2024',
        'start_date' => '2023-06-01',
        'end_date' => '2024-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);
    AcademicYear::query()->create([
        'name' => '2024-2025',
        'start_date' => '2024-06-01',
        'end_date' => '2025-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);
    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $olderYear->id,
        'grade_level_id' => $gradeSeven->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);
    PermanentRecord::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $olderYear->id,
        'grade_level_id' => $gradeSeven->id,
        'status' => 'promoted',
        'general_average' => 85,
    ]);

    $this->getJson('/registrar/enrollment/lookup?lrn=567890123456')
        ->assertSuccessful()
        ->assertJson([
            'grade_prefill_mode' => 'none',
            'student' => [
                'recommended_grade_level_id' => null,
            ],
            'grade_guardrail' => [
                'allowed_exact_grade_level_id' => null,
                'min_allowed_grade_level_order' => 7,
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
    PermanentRecord::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $previousYear->id,
        'grade_level_id' => $gradeSeven->id,
        'status' => 'promoted',
        'general_average' => 85,
    ]);

    $this->post('/registrar/enrollment', [
        'lrn' => $student->lrn,
        'first_name' => 'Liza',
        'last_name' => 'Rivera',
        'gender' => 'Female',
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

test('enrollment store rejects grade level that violates strict previous-year guardrail', function () {
    $gradeSeven = GradeLevel::query()->create(['name' => 'Grade 7', 'level_order' => 7]);
    $gradeEight = GradeLevel::query()->create(['name' => 'Grade 8', 'level_order' => 8]);
    GradeLevel::query()->create(['name' => 'Grade 9', 'level_order' => 9]);
    $student = Student::query()->create([
        'lrn' => '678901234567',
        'first_name' => 'Guard',
        'last_name' => 'Rail',
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
    PermanentRecord::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $previousYear->id,
        'grade_level_id' => $gradeSeven->id,
        'status' => 'promoted',
        'general_average' => 85,
    ]);

    $this->from('/registrar/enrollment')->post('/registrar/enrollment', [
        'lrn' => $student->lrn,
        'first_name' => 'Guard',
        'last_name' => 'Rail',
        'gender' => 'Male',
        'birthdate' => '2010-01-01',
        'guardian_name' => 'Parent Rail',
        'guardian_contact_number' => '09171234567',
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'grade_level_id' => $gradeSeven->id,
    ])->assertRedirect('/registrar/enrollment')
        ->assertSessionHasErrors(['grade_level_id']);

    expect(Enrollment::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->ongoingYear->id)
        ->exists())->toBeFalse();
});

test('enrollment store can resolve older conditional and retained records when confirmed', function () {
    $gradeSeven = GradeLevel::query()->create(['name' => 'Grade 7', 'level_order' => 7]);
    $gradeEight = GradeLevel::query()->create(['name' => 'Grade 8', 'level_order' => 8]);
    $student = Student::query()->create([
        'lrn' => '789012345678',
        'first_name' => 'Legacy',
        'last_name' => 'Status',
        'birthdate' => '2010-01-01',
    ]);
    $olderYear = AcademicYear::query()->create([
        'name' => '2023-2024',
        'start_date' => '2023-06-01',
        'end_date' => '2024-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
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
        'academic_year_id' => $olderYear->id,
        'grade_level_id' => $gradeSeven->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);
    PermanentRecord::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $olderYear->id,
        'grade_level_id' => $gradeSeven->id,
        'status' => 'conditional',
        'general_average' => 77,
        'conditional_resolved_at' => null,
    ]);
    PermanentRecord::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $previousYear->id,
        'grade_level_id' => $gradeEight->id,
        'status' => 'retained',
        'general_average' => 72,
        'retained_resolved_at' => null,
    ]);

    $this->post('/registrar/enrollment', [
        'lrn' => $student->lrn,
        'first_name' => 'Legacy',
        'last_name' => 'Status',
        'gender' => 'Male',
        'birthdate' => '2010-01-01',
        'guardian_name' => 'Parent Status',
        'guardian_contact_number' => '09171234567',
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'grade_level_id' => $gradeEight->id,
        'resolve_older_conditional' => true,
        'resolve_older_retained' => true,
    ])->assertRedirect();

    $conditional = PermanentRecord::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $olderYear->id)
        ->where('status', 'conditional')
        ->first();
    $retained = PermanentRecord::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $previousYear->id)
        ->where('status', 'retained')
        ->first();

    expect($conditional?->conditional_resolved_at)->not->toBeNull();
    expect($retained?->retained_resolved_at)->toBeNull();
});
