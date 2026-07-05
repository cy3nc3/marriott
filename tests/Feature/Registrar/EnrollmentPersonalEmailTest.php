<?php

use App\Enums\UserRole;
use App\Models\AccountClaimToken;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Notifications\EnrollmentSingleAccountClaimNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('registrar enrollment saves student personal email to student user and contact email to parent user', function () {
    $registrar = User::factory()->registrar()->create();
    $this->actingAs($registrar);

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
        'name' => 'Rizal',
    ]);

    $this->post('/registrar/enrollment', [
        'lrn' => '123456789012',
        'first_name' => 'Maria',
        'middle_name' => 'Reyes',
        'last_name' => 'Santos',
        'gender' => 'Female',
        'birthdate' => '2011-05-12',
        'guardian_name' => 'Guardian Santos',
        'guardian_contact_number' => '09171234567',
        'email' => 'guardian@example.com',
        'student_personal_email' => 'student.personal@example.com',
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 1500,
    ])->assertRedirect();

    $student = Student::query()->where('lrn', '123456789012')->firstOrFail();
    $studentUser = $student->user()->firstOrFail();
    $parentUser = $student->parents()->where('role', UserRole::PARENT->value)->firstOrFail();

    expect($studentUser->email)->toBe('santos.123456789012@marriott.edu');
    expect($studentUser->personal_email)->toBe('student.personal@example.com');
    expect($parentUser->personal_email)->toBe('guardian@example.com');
});

test('registrar can force resend claim emails from student directory for unclaimed accounts', function () {
    Notification::fake();
    config()->set('services.enrollment_claim_mail.enabled', true);

    $registrar = User::factory()->registrar()->create();
    $this->actingAs($registrar);

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
        'name' => 'Rizal',
    ]);
    $studentUser = User::factory()->student()->create([
        'email' => 'santos.123456789013@marriott.edu',
        'personal_email' => 'student.personal@example.com',
        'must_change_password' => true,
    ]);
    $parentUser = User::factory()->parent()->create([
        'email' => 'parent.santos@marriott.edu',
        'personal_email' => 'guardian@example.com',
        'must_change_password' => true,
    ]);
    $student = Student::query()->create([
        'user_id' => $studentUser->id,
        'lrn' => '123456789013',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'contact_number' => '+639171234567',
    ]);
    $student->parents()->attach($parentUser->id);
    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'email' => 'guardian@example.com',
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 1500,
        'status' => 'enrolled',
    ]);

    $this->post("/registrar/student-directory/{$student->id}/resend-claim-email")
        ->assertRedirect()
        ->assertSessionHas('success', 'Account-claim emails resent.');

    expect(AccountClaimToken::query()->where('enrollment_id', $enrollment->id)->count())->toBe(2);
    Notification::assertSentOnDemandTimes(EnrollmentSingleAccountClaimNotification::class, 2);
});
