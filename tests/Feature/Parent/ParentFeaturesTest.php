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
use Illuminate\Support\Carbon;
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

test('parent schedule page can switch school year history', function () {
    $currentYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '2',
    ]);

    $previousYear = AcademicYear::query()->create([
        'name' => '2024-2025',
        'start_date' => '2024-06-01',
        'end_date' => '2025-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    $teacher = User::factory()->teacher()->create([
        'first_name' => 'Arthur',
        'last_name' => 'Santos',
    ]);

    $currentSection = Section::query()->create([
        'academic_year_id' => $currentYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Rizal',
        'adviser_id' => $teacher->id,
    ]);

    $previousSection = Section::query()->create([
        'academic_year_id' => $previousYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Bonifacio',
        'adviser_id' => $teacher->id,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'SCI7',
        'subject_name' => 'Science 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
    ]);

    $currentAssignment = SubjectAssignment::query()->create([
        'section_id' => $currentSection->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $previousAssignment = SubjectAssignment::query()->create([
        'section_id' => $previousSection->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    ClassSchedule::query()->create([
        'section_id' => $currentSection->id,
        'subject_assignment_id' => $currentAssignment->id,
        'type' => 'academic',
        'day' => 'Monday',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
    ]);

    ClassSchedule::query()->create([
        'section_id' => $previousSection->id,
        'subject_assignment_id' => $previousAssignment->id,
        'type' => 'academic',
        'day' => 'Thursday',
        'start_time' => '13:00:00',
        'end_time' => '14:00:00',
    ]);

    Enrollment::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $currentYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $currentSection->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'enrolled',
    ]);

    Enrollment::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $previousYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $previousSection->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'enrolled',
    ]);

    $this->get("/parent/schedule?academic_year_id={$previousYear->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('parent/schedule/index')
            ->where('selected_school_year_id', $previousYear->id)
            ->has('school_year_options', 2)
            ->where('schedule_items.0.day', 'Thursday')
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

test('parent grades page can switch school year history', function () {
    $currentYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $previousYear = AcademicYear::query()->create([
        'name' => '2024-2025',
        'start_date' => '2024-06-01',
        'end_date' => '2025-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    $adviser = User::factory()->teacher()->create([
        'first_name' => 'Arthur',
        'last_name' => 'Santos',
    ]);

    $currentSection = Section::query()->create([
        'academic_year_id' => $currentYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Rizal',
        'adviser_id' => $adviser->id,
    ]);

    $previousSection = Section::query()->create([
        'academic_year_id' => $previousYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Bonifacio',
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

    $currentAssignment = SubjectAssignment::query()->create([
        'section_id' => $currentSection->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $previousAssignment = SubjectAssignment::query()->create([
        'section_id' => $previousSection->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $currentEnrollment = Enrollment::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $currentYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $currentSection->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'enrolled',
    ]);

    $previousEnrollment = Enrollment::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $previousYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $previousSection->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'enrolled',
    ]);

    FinalGrade::query()->create([
        'enrollment_id' => $currentEnrollment->id,
        'subject_assignment_id' => $currentAssignment->id,
        'quarter' => '1',
        'grade' => 90,
        'is_locked' => true,
    ]);

    FinalGrade::query()->create([
        'enrollment_id' => $previousEnrollment->id,
        'subject_assignment_id' => $previousAssignment->id,
        'quarter' => '1',
        'grade' => 84,
        'is_locked' => true,
    ]);

    $this->get("/parent/grades?academic_year_id={$previousYear->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('parent/grades/index')
            ->where('selected_school_year_id', $previousYear->id)
            ->where('context.school_year', '2024-2025')
            ->where('summary.general_average', '84.00')
            ->has('school_year_options', 2)
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
            ->has('dues_by_plan.monthly', 1)
            ->where('dues_by_plan.monthly.0.status', 'Unpaid')
            ->where('dues_by_plan.monthly.0.outstanding_amount', 'PHP 2,500.00')
            ->has('recent_payments', 1)
            ->where('recent_payments.0.or_number', 'OR-1001')
        );
});

test('parent billing information can switch school year history', function () {
    $currentYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $previousYear = AcademicYear::query()->create([
        'name' => '2024-2025',
        'start_date' => '2024-06-01',
        'end_date' => '2025-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    $currentSection = Section::query()->create([
        'academic_year_id' => $currentYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Rizal',
    ]);

    $previousSection = Section::query()->create([
        'academic_year_id' => $previousYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Bonifacio',
    ]);

    Enrollment::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $currentYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $currentSection->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'enrolled',
    ]);

    Enrollment::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $previousYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $previousSection->id,
        'payment_term' => 'quarterly',
        'downpayment' => 500,
        'status' => 'enrolled',
    ]);

    BillingSchedule::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $currentYear->id,
        'description' => 'Current Year Due',
        'due_date' => '2025-08-01',
        'amount_due' => 2000,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    BillingSchedule::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $previousYear->id,
        'description' => 'Previous Year Due',
        'due_date' => '2024-08-01',
        'amount_due' => 1800,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    $this->get("/parent/billing-information?academic_year_id={$previousYear->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('parent/billing-information/index')
            ->where('selected_school_year_id', $previousYear->id)
            ->where('dues_by_plan.quarterly.0.due_date', '08/01/2024')
            ->where('dues_by_plan.quarterly.0.amount', 'PHP 1,800.00')
            ->has('school_year_options', 2)
        );
});

test('parent billing page shows departed learner records as read only', function () {
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
    ]);

    Enrollment::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 500,
        'status' => 'dropped_out',
    ]);

    BillingSchedule::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'description' => 'Remaining Balance',
        'due_date' => '2025-08-15',
        'amount_due' => 2000,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    $this->get('/parent/billing-information')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('parent/billing-information/index')
            ->where('is_departed_read_only', true)
            ->where('account_summary.student_name', 'Juan Dela Cruz')
            ->has('dues_by_plan.monthly', 1)
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

test('parent dashboard caps upcoming dues timeline to the next four items', function () {
    Carbon::setTestNow('2026-02-20 09:00:00');

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
        'date' => '2026-02-01',
        'description' => 'Tuition Charge',
        'debit' => 10000,
        'credit' => 0,
        'running_balance' => 10000,
    ]);

    foreach ([
        ['2026-02-21', 1000],
        ['2026-02-25', 1200],
        ['2026-03-05', 1400],
        ['2026-03-10', 1600],
        ['2026-03-15', 1800],
        ['2026-03-20', 2000],
    ] as [$dueDate, $amount]) {
        BillingSchedule::query()->create([
            'student_id' => $this->student->id,
            'academic_year_id' => $schoolYear->id,
            'description' => "Due {$dueDate}",
            'due_date' => $dueDate,
            'amount_due' => $amount,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);
    }

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('parent/dashboard')
            ->where('kpis.3.id', 'next-due')
            ->where('kpis.3.value', 'Feb 21 · PHP 1,000.00')
            ->where('trends.1.id', 'upcoming-dues-timeline')
            ->where('trends.1.chart.rows', function ($rows): bool {
                if (count($rows) !== 4) {
                    return false;
                }

                $expected = [
                    ['due_date' => 'Feb 21', 'amount_outstanding' => 1000.0],
                    ['due_date' => 'Feb 25', 'amount_outstanding' => 1200.0],
                    ['due_date' => 'Mar 05', 'amount_outstanding' => 1400.0],
                    ['due_date' => 'Mar 10', 'amount_outstanding' => 1600.0],
                ];

                return collect($expected)->every(function (array $expectedRow, int $index) use ($rows): bool {
                    $actual = $rows[$index] ?? null;

                    return is_array($actual)
                        && ($actual['due_date'] ?? null) === $expectedRow['due_date']
                        && (float) ($actual['amount_outstanding'] ?? -1) === $expectedRow['amount_outstanding'];
                });
            })
        );

    Carbon::setTestNow();
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
