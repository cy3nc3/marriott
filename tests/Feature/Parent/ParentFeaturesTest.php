<?php

use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\ClassSchedule;
use App\Models\ConductRating;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradeLevel;
use App\Models\LedgerEntry;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TeacherSubject;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->parentUser = User::factory()->parent()->create([
        'first_name' => 'Ana',
        'last_name' => 'Dela Cruz',
    ]);

    $this->student = Student::query()->create([
        'lrn' => '922222222222',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);

    $this->parentUser->students()->attach($this->student->id);

    $this->actingAs($this->parentUser);
});

test('parent schedule page renders linked student schedule', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '2',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    $adviser = User::factory()->teacher()->create([
        'first_name' => 'Arthur',
        'last_name' => 'Santos',
    ]);

    $section = Section::query()->create([
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Rizal',
        'adviser_id' => $adviser->id,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'ENG7',
        'subject_name' => 'English 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $adviser->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    ClassSchedule::query()->create([
        'section_id' => $section->id,
        'subject_assignment_id' => $assignment->id,
        'type' => 'academic',
        'label' => null,
        'day' => 'Tuesday',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
    ]);

    Enrollment::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'enrolled',
    ]);

    $this->get('/parent/schedule')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('parent/schedule/index')
            ->where('student_name', 'Juan Dela Cruz')
            ->has('schedule_items', 1)
            ->where('schedule_items.0.title', 'English 7')
            ->where('schedule_items.0.teacher', 'Arthur Santos')
        );
});

test('parent grades page renders linked student grade and conduct data', function () {
    $schoolYear = AcademicYear::query()->create([
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

    $adviser = User::factory()->teacher()->create([
        'first_name' => 'Arthur',
        'last_name' => 'Santos',
    ]);

    $section = Section::query()->create([
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Rizal',
        'adviser_id' => $adviser->id,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'SCI7',
        'subject_name' => 'Science 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $adviser->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'enrolled',
    ]);

    FinalGrade::query()->create([
        'enrollment_id' => $enrollment->id,
        'subject_assignment_id' => $assignment->id,
        'quarter' => '1',
        'grade' => 89,
        'is_locked' => true,
    ]);

    ConductRating::query()->create([
        'enrollment_id' => $enrollment->id,
        'quarter' => '1',
        'maka_diyos' => 'AO',
        'makatao' => 'AO',
        'makakalikasan' => 'AO',
        'makabansa' => 'AO',
        'remarks' => 'Strong classroom behavior',
        'is_locked' => true,
    ]);

    $this->get('/parent/grades')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('parent/grades/index')
            ->where('context.student_name', 'Juan Dela Cruz')
            ->where('context.school_year', '2025-2026')
            ->where('context.adviser_name', 'Arthur Santos')
            ->where('context.adviser_remarks', 'Strong classroom behavior')
            ->where('context.is_verified', true)
            ->where('summary.general_average', '89.00')
            ->has('subject_rows', 1)
            ->where('subject_rows.0.subject', 'Science 7')
            ->where('conduct_rows.0.q1', 'AO')
        );
});

test('parent billing information page renders dues and recent payments', function () {
    $schoolYear = AcademicYear::query()->create([
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
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Rizal',
        'adviser_id' => null,
    ]);

    Enrollment::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'enrolled',
    ]);

    BillingSchedule::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'description' => 'Tuition - June',
        'due_date' => '2025-06-15',
        'amount_due' => 2500,
        'amount_paid' => 2500,
        'status' => 'paid',
    ]);

    BillingSchedule::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'description' => 'Tuition - July',
        'due_date' => '2025-07-15',
        'amount_due' => 2500,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    LedgerEntry::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'date' => '2025-06-10',
        'description' => 'Tuition Charge',
        'debit' => 10000,
        'credit' => 0,
        'running_balance' => 10000,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'date' => '2025-06-12',
        'description' => 'Payment',
        'debit' => 0,
        'credit' => 3000,
        'running_balance' => 7000,
    ]);

    $cashier = User::factory()->finance()->create();

    Transaction::query()->create([
        'or_number' => 'OR-1001',
        'student_id' => $this->student->id,
        'cashier_id' => $cashier->id,
        'total_amount' => 3000,
        'payment_mode' => 'cash',
        'reference_no' => null,
        'remarks' => null,
    ]);

    $this->get('/parent/billing-information')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('parent/billing-information/index')
            ->where('account_summary.student_name', 'Juan Dela Cruz')
            ->where('account_summary.payment_plan', 'monthly')
            ->where('account_summary.outstanding_balance', 'PHP 7,000.00')
            ->has('dues_by_plan.monthly', 2)
            ->where('dues_by_plan.monthly.1.status', 'Unpaid')
            ->has('recent_payments', 1)
            ->where('recent_payments.0.or_number', 'OR-1001')
        );
});

test('parent dashboard route renders linked child analytics', function () {
    $schoolYear = AcademicYear::query()->create([
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

    $adviser = User::factory()->teacher()->create([
        'first_name' => 'Arthur',
        'last_name' => 'Santos',
    ]);

    $section = Section::query()->create([
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Rizal',
        'adviser_id' => $adviser->id,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'MATH7',
        'subject_name' => 'Mathematics 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $adviser->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'enrolled',
    ]);

    FinalGrade::query()->create([
        'enrollment_id' => $enrollment->id,
        'subject_assignment_id' => $assignment->id,
        'quarter' => '1',
        'grade' => 90,
        'is_locked' => true,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'date' => '2025-06-10',
        'description' => 'Tuition Charge',
        'debit' => 10000,
        'credit' => 0,
        'running_balance' => 10000,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'date' => '2025-06-12',
        'description' => 'Payment',
        'debit' => 0,
        'credit' => 2500,
        'running_balance' => 7500,
    ]);

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('parent/dashboard')
            ->has('kpis', 4)
            ->has('alerts')
            ->has('trends', 2)
            ->has('action_links', 3)
            ->where('child_context.student_name', 'Juan Dela Cruz')
            ->where('child_context.section_label', 'Grade 7 - Rizal')
            ->where('child_context.adviser_name', 'Arthur Santos')
            ->where('child_context.next_due_label', 'No upcoming due')
            ->where('kpis.0.id', 'child-section')
            ->where('trends.1.id', 'upcoming-dues-timeline')
        );
});

test('parent dashboard isolates analytics to linked student records', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 8',
        'level_order' => 8,
    ]);

    $section = Section::query()->create([
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Mabini',
    ]);

    Enrollment::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 500,
        'status' => 'enrolled',
    ]);

    LedgerEntry::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'date' => '2025-06-10',
        'description' => 'Tuition Charge',
        'debit' => 5000,
        'credit' => 0,
        'running_balance' => 5000,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'date' => '2025-06-11',
        'description' => 'Payment',
        'debit' => 0,
        'credit' => 4000,
        'running_balance' => 1000,
    ]);

    $otherStudent = Student::query()->create([
        'lrn' => '933333333333',
        'first_name' => 'Other',
        'last_name' => 'Student',
    ]);

    Enrollment::query()->create([
        'student_id' => $otherStudent->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 500,
        'status' => 'enrolled',
    ]);

    LedgerEntry::query()->create([
        'student_id' => $otherStudent->id,
        'academic_year_id' => $schoolYear->id,
        'date' => '2025-06-10',
        'description' => 'Tuition Charge',
        'debit' => 20000,
        'credit' => 0,
        'running_balance' => 20000,
    ]);

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('parent/dashboard')
            ->where('child_context.student_name', 'Juan Dela Cruz')
            ->where('child_context.section_label', 'Grade 8 - Mabini')
            ->where('kpis.2.value', 'PHP 1,000.00')
        );
});
