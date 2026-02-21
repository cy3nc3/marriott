<?php

use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\ConductRating;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradedActivity;
use App\Models\GradeLevel;
use App\Models\LedgerEntry;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TeacherSubject;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->studentUser = User::factory()->student()->create([
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);

    $this->student = Student::query()->create([
        'user_id' => $this->studentUser->id,
        'lrn' => '911111111111',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);

    $this->actingAs($this->studentUser);
});

test('student schedule page renders class schedule from enrolled section', function () {
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

    ClassSchedule::query()->create([
        'section_id' => $section->id,
        'subject_assignment_id' => $assignment->id,
        'type' => 'academic',
        'label' => null,
        'day' => 'Monday',
        'start_time' => '08:00:00',
        'end_time' => '09:00:00',
    ]);

    ClassSchedule::query()->create([
        'section_id' => $section->id,
        'subject_assignment_id' => null,
        'type' => 'break',
        'label' => 'Recess',
        'day' => 'Monday',
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    Enrollment::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    $this->get('/student/schedule')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/schedule/index')
            ->has('schedule_items', 1)
            ->where('schedule_items.0.title', 'Mathematics 7')
            ->where('schedule_items.0.teacher', 'Arthur Santos')
            ->where('schedule_items.0.start', '08:00')
            ->where('schedule_items.0.end', '09:00')
            ->has('break_items', 1)
            ->where('break_items.0.label', 'Recess')
        );
});

test('student grades page renders computed subject rows and conduct ratings', function () {
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

    $math = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'MATH7',
        'subject_name' => 'Mathematics 7',
    ]);

    $science = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'SCI7',
        'subject_name' => 'Science 7',
    ]);

    $mathTeacher = TeacherSubject::query()->create([
        'teacher_id' => $adviser->id,
        'subject_id' => $math->id,
    ]);

    $scienceTeacher = TeacherSubject::query()->create([
        'teacher_id' => $adviser->id,
        'subject_id' => $science->id,
    ]);

    $mathAssignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $mathTeacher->id,
    ]);

    $scienceAssignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $scienceTeacher->id,
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    FinalGrade::query()->create([
        'enrollment_id' => $enrollment->id,
        'subject_assignment_id' => $mathAssignment->id,
        'quarter' => '1',
        'grade' => 86,
        'is_locked' => true,
    ]);

    FinalGrade::query()->create([
        'enrollment_id' => $enrollment->id,
        'subject_assignment_id' => $mathAssignment->id,
        'quarter' => '2',
        'grade' => 88,
        'is_locked' => true,
    ]);

    FinalGrade::query()->create([
        'enrollment_id' => $enrollment->id,
        'subject_assignment_id' => $scienceAssignment->id,
        'quarter' => '1',
        'grade' => 84,
        'is_locked' => true,
    ]);

    FinalGrade::query()->create([
        'enrollment_id' => $enrollment->id,
        'subject_assignment_id' => $scienceAssignment->id,
        'quarter' => '2',
        'grade' => 86,
        'is_locked' => true,
    ]);

    ConductRating::query()->create([
        'enrollment_id' => $enrollment->id,
        'quarter' => '1',
        'maka_diyos' => 'AO',
        'makatao' => 'AO',
        'makakalikasan' => 'SO',
        'makabansa' => 'AO',
        'remarks' => 'Good start',
        'is_locked' => true,
    ]);

    ConductRating::query()->create([
        'enrollment_id' => $enrollment->id,
        'quarter' => '2',
        'maka_diyos' => 'AO',
        'makatao' => 'AO',
        'makakalikasan' => 'AO',
        'makabansa' => 'AO',
        'remarks' => 'Improved performance',
        'is_locked' => true,
    ]);

    $this->get('/student/grades')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/grades/index')
            ->where('summary.general_average', '86.00')
            ->where('summary.trend_text', '+2.00 compared to previous quarter')
            ->where('context.school_year', '2025-2026')
            ->where('context.adviser_name', 'Arthur Santos')
            ->where('context.adviser_remarks', 'Improved performance')
            ->has('subject_rows', 2)
            ->where('subject_rows.0.subject', 'Mathematics 7')
            ->where('subject_rows.0.q1', '86.00')
            ->where('subject_rows.0.q2', '88.00')
            ->where('subject_rows.0.final', '87.00')
            ->where('conduct_rows.2.core_value', 'Makakalikasan')
            ->where('conduct_rows.2.q1', 'SO')
            ->where('conduct_rows.2.q2', 'AO')
        );
});

test('student dashboard is learning-focused and excludes billing metrics', function () {
    Carbon::setTestNow('2025-06-16 08:30:00');

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

    ClassSchedule::query()->create([
        'section_id' => $section->id,
        'subject_assignment_id' => $assignment->id,
        'type' => 'academic',
        'label' => null,
        'day' => 'Monday',
        'start_time' => '08:00:00',
        'end_time' => '09:00:00',
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

    LedgerEntry::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'date' => '2025-06-10',
        'description' => 'Tuition Charge',
        'debit' => 12000,
        'credit' => 0,
        'running_balance' => 12000,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'date' => '2025-06-12',
        'description' => 'Payment',
        'debit' => 0,
        'credit' => 3000,
        'running_balance' => 9000,
    ]);

    \App\Models\BillingSchedule::query()->create([
        'student_id' => $this->student->id,
        'academic_year_id' => $schoolYear->id,
        'description' => 'July Tuition',
        'due_date' => '2025-07-15',
        'amount_due' => 3000,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    $activity = GradedActivity::query()->create([
        'subject_assignment_id' => $assignment->id,
        'type' => 'WW',
        'quarter' => '1',
        'title' => 'Quiz 1',
        'max_score' => 20,
    ]);

    StudentScore::query()->create([
        'student_id' => $this->student->id,
        'graded_activity_id' => $activity->id,
        'score' => 18,
    ]);

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/dashboard')
            ->has('kpis', 4)
            ->has('alerts')
            ->has('trends', 2)
            ->has('action_links', 2)
            ->where('learning_summary.current_or_upcoming_class', 'Mathematics 7')
            ->where('learning_summary.latest_score', '18/20')
            ->where('learning_summary.recent_score_average', '90.00%')
            ->where('learning_summary.recent_score_records_count', 1)
            ->missing('account_summary')
            ->missing('learning_summary.attendance_risk_level')
            ->missing('learning_summary.attendance_risk_rate')
        );

    Carbon::setTestNow();
});

test('student dashboard renders safe fallback values when records are empty', function () {
    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/dashboard')
            ->where('learning_summary.general_average', '-')
            ->where('learning_summary.latest_score', '-')
            ->where('learning_summary.recent_score_average', '-')
            ->where('learning_summary.recent_score_trend_delta', null)
            ->where('learning_summary.recent_score_records_count', 0)
            ->where('learning_summary.upcoming_items_count', 0)
        );
});
