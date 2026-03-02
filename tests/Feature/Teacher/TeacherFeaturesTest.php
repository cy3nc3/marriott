<?php

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\ConductRating;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradedActivity;
use App\Models\GradeLevel;
use App\Models\GradeSubmission;
use App\Models\GradingRubric;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TeacherSubject;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->teacher = User::factory()->teacher()->create([
        'first_name' => 'Liza',
        'last_name' => 'Teacher',
    ]);

    $this->actingAs($this->teacher);
});

afterEach(function () {
    Carbon::setTestNow();
});

test('teacher schedule page renders class and advisory schedules assigned to the teacher', function () {
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

    $classSection = Section::query()->create([
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Bonifacio',
        'adviser_id' => null,
    ]);

    $advisorySection = Section::query()->create([
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Rizal',
        'adviser_id' => $this->teacher->id,
    ]);

    $math = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'MATH7',
        'subject_name' => 'Mathematics 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $math->id,
    ]);

    $subjectAssignment = SubjectAssignment::query()->create([
        'section_id' => $classSection->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    ClassSchedule::query()->create([
        'section_id' => $classSection->id,
        'subject_assignment_id' => $subjectAssignment->id,
        'type' => 'academic',
        'label' => null,
        'day' => 'Monday',
        'start_time' => '08:00:00',
        'end_time' => '09:00:00',
    ]);

    ClassSchedule::query()->create([
        'section_id' => $advisorySection->id,
        'subject_assignment_id' => null,
        'type' => 'advisory',
        'label' => 'Advisory',
        'day' => 'Friday',
        'start_time' => '13:00:00',
        'end_time' => '14:00:00',
    ]);

    ClassSchedule::query()->create([
        'section_id' => $classSection->id,
        'subject_assignment_id' => null,
        'type' => 'break',
        'label' => 'Lunch Break',
        'day' => 'Monday',
        'start_time' => '12:00:00',
        'end_time' => '13:00:00',
    ]);

    $otherTeacher = User::factory()->teacher()->create();
    $otherTeacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $otherTeacher->id,
        'subject_id' => $math->id,
    ]);

    $otherAssignment = SubjectAssignment::query()->create([
        'section_id' => $classSection->id,
        'teacher_subject_id' => $otherTeacherSubject->id,
    ]);

    ClassSchedule::query()->create([
        'section_id' => $classSection->id,
        'subject_assignment_id' => $otherAssignment->id,
        'type' => 'academic',
        'label' => null,
        'day' => 'Tuesday',
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
    ]);

    $this->get('/teacher/schedule')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/schedule/index')
            ->has('schedule_items', 2)
            ->where('schedule_items.0.title', 'Mathematics 7')
            ->where('schedule_items.0.section', 'Grade 7 - Bonifacio')
            ->where('schedule_items.0.type', 'class')
            ->where('schedule_items.1.title', 'Advisory')
            ->where('schedule_items.1.section', 'Grade 7 - Rizal')
            ->where('schedule_items.1.type', 'advisory')
            ->has('break_items', 1)
            ->where('break_items.0.label', 'Lunch Break')
            ->where('break_items.0.start', '12:00')
            ->where('break_items.0.end', '13:00')
        );
});

test('teacher schedule page is scoped to the active academic year', function () {
    $completedYear = AcademicYear::query()->create([
        'name' => '2024-2025',
        'start_date' => '2024-06-01',
        'end_date' => '2025-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);

    $ongoingYear = AcademicYear::query()->create([
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

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'SCI7',
        'subject_name' => 'Science 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $subject->id,
    ]);

    $completedSection = Section::query()->create([
        'academic_year_id' => $completedYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Mabini',
        'adviser_id' => $this->teacher->id,
    ]);

    $ongoingSection = Section::query()->create([
        'academic_year_id' => $ongoingYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Rizal',
        'adviser_id' => null,
    ]);

    $completedAssignment = SubjectAssignment::query()->create([
        'section_id' => $completedSection->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $ongoingAssignment = SubjectAssignment::query()->create([
        'section_id' => $ongoingSection->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    ClassSchedule::query()->create([
        'section_id' => $completedSection->id,
        'subject_assignment_id' => $completedAssignment->id,
        'type' => 'academic',
        'label' => null,
        'day' => 'Monday',
        'start_time' => '08:00:00',
        'end_time' => '09:00:00',
    ]);

    ClassSchedule::query()->create([
        'section_id' => $completedSection->id,
        'subject_assignment_id' => null,
        'type' => 'break',
        'label' => 'Completed Year Break',
        'day' => 'Monday',
        'start_time' => '10:00:00',
        'end_time' => '10:30:00',
    ]);

    ClassSchedule::query()->create([
        'section_id' => $ongoingSection->id,
        'subject_assignment_id' => $ongoingAssignment->id,
        'type' => 'academic',
        'label' => null,
        'day' => 'Tuesday',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
    ]);

    $this->get('/teacher/schedule')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/schedule/index')
            ->has('schedule_items', 1)
            ->where('schedule_items.0.title', 'Science 7')
            ->where('schedule_items.0.section', 'Grade 7 - Rizal')
            ->has('break_items', 2)
            ->where('break_items.0.label', 'Recess Break')
        );
});

test('teacher attendance page renders sf2 style attendance rows for subject class assignment', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '3',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    $section = Section::query()->create([
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Mabini',
        'adviser_id' => null,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'ENG7',
        'subject_name' => 'English 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $student = Student::query()->create([
        'lrn' => '973456789012',
        'first_name' => 'Luna',
        'last_name' => 'Garcia',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    Attendance::query()->create([
        'subject_assignment_id' => $assignment->id,
        'enrollment_id' => $enrollment->id,
        'date' => '2026-03-05',
        'status' => 'absent',
    ]);

    $this->get("/teacher/attendance?subject_assignment_id={$assignment->id}&month=2026-03")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/attendance/index')
            ->where('context.selected_subject_assignment_id', $assignment->id)
            ->where('context.selected_month', '2026-03')
            ->where('rows.0.student_name', 'Garcia, Luna')
            ->where('rows.0.statuses.2026-03-05', 'absent')
        );
});

test('teacher attendance is locked during pre-opening before first quarter starts', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => now()->addDays(7)->toDateString(),
        'end_date' => now()->addMonths(10)->toDateString(),
        'status' => 'upcoming',
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

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'ENG7',
        'subject_name' => 'English 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $this->get('/teacher/attendance')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/attendance/index')
            ->where('feature_lock.is_locked', true)
            ->where('context.selected_subject_assignment_id', $assignment->id)
            ->has('context.class_options', 1)
            ->has('rows', 0)
            ->where('feature_lock.message', function ($message): bool {
                return is_string($message) && str_contains(strtolower($message), 'pre-opening');
            })
        );
});

test('teacher attendance is editable for simulated first quarter even with future start date', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => now()->addDays(7)->toDateString(),
        'end_date' => now()->addMonths(10)->toDateString(),
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

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'ENG7',
        'subject_name' => 'English 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $this->get('/teacher/attendance')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/attendance/index')
            ->where('feature_lock.is_locked', false)
            ->where('context.selected_subject_assignment_id', $assignment->id)
            ->has('context.class_options', 1)
        );
});

test('teacher attendance month outside school year range is read only', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
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
        'name' => 'Mabini',
        'adviser_id' => null,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'SCI7',
        'subject_name' => 'Science 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $this->get('/teacher/attendance?month=2026-04')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/attendance/index')
            ->where('feature_lock.is_locked', false)
            ->where('month_scope.is_out_of_scope', true)
            ->where('context.selected_subject_assignment_id', $assignment->id)
            ->has('days', 0)
            ->where('month_scope.message', function ($message): bool {
                return is_string($message) && str_contains(strtolower($message), 'outside the school year range');
            })
        );
});

test('teacher can store sf2 attendance statuses and reset a mark back to present', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '3',
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

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'MATH7',
        'subject_name' => 'Mathematics 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $student = Student::query()->create([
        'lrn' => '983456789012',
        'first_name' => 'Mila',
        'last_name' => 'Reyes',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    $this->post('/teacher/attendance', [
        'subject_assignment_id' => $assignment->id,
        'month' => '2026-03',
        'entries' => [
            [
                'enrollment_id' => $enrollment->id,
                'date' => '2026-03-02',
                'status' => 'tardy_late_comer',
            ],
        ],
    ])->assertRedirect()
        ->assertSessionHas('success', 'Attendance saved.');

    $this->assertDatabaseHas('attendances', [
        'subject_assignment_id' => $assignment->id,
        'enrollment_id' => $enrollment->id,
        'date' => '2026-03-02',
        'status' => 'tardy_late_comer',
    ]);

    $this->post('/teacher/attendance', [
        'subject_assignment_id' => $assignment->id,
        'month' => '2026-03',
        'entries' => [
            [
                'enrollment_id' => $enrollment->id,
                'date' => '2026-03-02',
                'status' => 'present',
            ],
        ],
    ])->assertRedirect()
        ->assertSessionHas('success', 'Attendance saved.');

    $this->assertDatabaseMissing('attendances', [
        'subject_assignment_id' => $assignment->id,
        'enrollment_id' => $enrollment->id,
        'date' => '2026-03-02',
    ]);
});

test('teacher attendance mutation is blocked during pre-opening', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => now()->addDays(7)->toDateString(),
        'end_date' => now()->addMonths(10)->toDateString(),
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    $section = Section::query()->create([
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Mabini',
        'adviser_id' => null,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'MATH7',
        'subject_name' => 'Mathematics 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $student = Student::query()->create([
        'lrn' => '989999999999',
        'first_name' => 'Mila',
        'last_name' => 'Reyes',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    $this->post('/teacher/attendance', [
        'subject_assignment_id' => $assignment->id,
        'month' => now()->format('Y-m'),
        'entries' => [
            [
                'enrollment_id' => $enrollment->id,
                'date' => now()->format('Y-m-d'),
                'status' => 'absent',
            ],
        ],
    ])->assertRedirect()
        ->assertSessionHas('error');

    expect(Attendance::query()->count())->toBe(0);
});

test('teacher attendance mutation is blocked when date is outside school year range', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
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

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'MATH7',
        'subject_name' => 'Mathematics 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $student = Student::query()->create([
        'lrn' => '989999999998',
        'first_name' => 'Mika',
        'last_name' => 'Lopez',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    $this->post('/teacher/attendance', [
        'subject_assignment_id' => $assignment->id,
        'month' => '2026-04',
        'entries' => [
            [
                'enrollment_id' => $enrollment->id,
                'date' => '2026-04-15',
                'status' => 'absent',
            ],
        ],
    ])->assertRedirect()
        ->assertSessionHas('error', 'Attendance entry date is outside the configured school year range.');

    expect(Attendance::query()->count())->toBe(0);
});

test('teacher dashboard reports quarter grade completion by class based on locked final grades', function () {
    Carbon::setTestNow('2026-02-16 08:30:00');

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

    $sectionA = Section::query()->create([
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Rizal',
        'adviser_id' => $this->teacher->id,
    ]);

    $sectionB = Section::query()->create([
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Bonifacio',
        'adviser_id' => null,
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

    $teacherMath = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $math->id,
    ]);

    $teacherScience = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $science->id,
    ]);

    $assignmentA = SubjectAssignment::query()->create([
        'section_id' => $sectionA->id,
        'teacher_subject_id' => $teacherMath->id,
    ]);

    $assignmentB = SubjectAssignment::query()->create([
        'section_id' => $sectionB->id,
        'teacher_subject_id' => $teacherScience->id,
    ]);

    ClassSchedule::query()->create([
        'section_id' => $sectionA->id,
        'subject_assignment_id' => $assignmentA->id,
        'type' => 'academic',
        'label' => null,
        'day' => 'Monday',
        'start_time' => '08:00:00',
        'end_time' => '09:00:00',
    ]);

    ClassSchedule::query()->create([
        'section_id' => $sectionB->id,
        'subject_assignment_id' => $assignmentB->id,
        'type' => 'academic',
        'label' => null,
        'day' => 'Monday',
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
    ]);

    $otherTeacher = User::factory()->teacher()->create();
    $otherTeacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $otherTeacher->id,
        'subject_id' => $science->id,
    ]);

    $otherAssignment = SubjectAssignment::query()->create([
        'section_id' => $sectionB->id,
        'teacher_subject_id' => $otherTeacherSubject->id,
    ]);

    ClassSchedule::query()->create([
        'section_id' => $sectionB->id,
        'subject_assignment_id' => $otherAssignment->id,
        'type' => 'academic',
        'label' => null,
        'day' => 'Monday',
        'start_time' => '13:00:00',
        'end_time' => '14:00:00',
    ]);

    $studentOne = Student::query()->create([
        'lrn' => '941234567890',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);

    $studentTwo = Student::query()->create([
        'lrn' => '951234567890',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
    ]);

    $studentThree = Student::query()->create([
        'lrn' => '961234567890',
        'first_name' => 'Carlo',
        'last_name' => 'Reyes',
    ]);

    $enrollmentOne = Enrollment::query()->create([
        'student_id' => $studentOne->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $sectionA->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    Enrollment::query()->create([
        'student_id' => $studentTwo->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $sectionA->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    $enrollmentThree = Enrollment::query()->create([
        'student_id' => $studentThree->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $sectionB->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    FinalGrade::query()->create([
        'enrollment_id' => $enrollmentOne->id,
        'subject_assignment_id' => $assignmentA->id,
        'quarter' => '1',
        'grade' => 85,
        'is_locked' => false,
    ]);

    FinalGrade::query()->create([
        'enrollment_id' => $enrollmentThree->id,
        'subject_assignment_id' => $assignmentB->id,
        'quarter' => '1',
        'grade' => 88,
        'is_locked' => false,
    ]);

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/dashboard')
            ->has('kpis', 4)
            ->has('alerts')
            ->has('trends')
            ->where('quarter_grade_completion.total_classes', 2)
            ->where('quarter_grade_completion.finalized_classes', 0)
            ->where('quarter_grade_completion.unfinalized_classes', 2)
        );

});

test('teacher dashboard counts finalized classes only when quarter grades are locked', function () {
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

    $sectionA = Section::query()->create([
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Mabini',
        'adviser_id' => null,
    ]);

    $sectionB = Section::query()->create([
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Luna',
        'adviser_id' => null,
    ]);

    $math = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'MATH8',
        'subject_name' => 'Mathematics 8',
    ]);

    $science = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'SCI8',
        'subject_name' => 'Science 8',
    ]);

    $mathTeacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $math->id,
    ]);

    $scienceTeacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $science->id,
    ]);

    $mathAssignment = SubjectAssignment::query()->create([
        'section_id' => $sectionA->id,
        'teacher_subject_id' => $mathTeacherSubject->id,
    ]);

    $scienceAssignment = SubjectAssignment::query()->create([
        'section_id' => $sectionB->id,
        'teacher_subject_id' => $scienceTeacherSubject->id,
    ]);

    $studentA = Student::query()->create([
        'lrn' => '971234567890',
        'first_name' => 'Anna',
        'last_name' => 'Rivera',
    ]);

    $studentB = Student::query()->create([
        'lrn' => '981234567890',
        'first_name' => 'Paolo',
        'last_name' => 'Santos',
    ]);

    $enrollmentA = Enrollment::query()->create([
        'student_id' => $studentA->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $sectionA->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    $enrollmentB = Enrollment::query()->create([
        'student_id' => $studentB->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $sectionB->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    FinalGrade::query()->create([
        'enrollment_id' => $enrollmentA->id,
        'subject_assignment_id' => $mathAssignment->id,
        'quarter' => '1',
        'grade' => 86,
        'is_locked' => true,
    ]);

    FinalGrade::query()->create([
        'enrollment_id' => $enrollmentB->id,
        'subject_assignment_id' => $scienceAssignment->id,
        'quarter' => '1',
        'grade' => 84,
        'is_locked' => false,
    ]);

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/dashboard')
            ->where('quarter_grade_completion.total_classes', 2)
            ->where('quarter_grade_completion.finalized_classes', 1)
            ->where('quarter_grade_completion.unfinalized_classes', 1)
        );
});

test('teacher dashboard handles high-volume pending grade rows by class', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 9',
        'level_order' => 9,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'MATH9',
        'subject_name' => 'Mathematics 9',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $subject->id,
    ]);

    for ($classIndex = 1; $classIndex <= 6; $classIndex++) {
        $section = Section::query()->create([
            'academic_year_id' => $schoolYear->id,
            'grade_level_id' => $gradeLevel->id,
            'name' => "Section {$classIndex}",
            'adviser_id' => null,
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
            'start_time' => sprintf('%02d:00:00', 7 + $classIndex),
            'end_time' => sprintf('%02d:00:00', 8 + $classIndex),
        ]);

        for ($studentIndex = 1; $studentIndex <= 2; $studentIndex++) {
            $student = Student::query()->create([
                'lrn' => str_pad((string) (990000000000 + ($classIndex * 10) + $studentIndex), 12, '0', STR_PAD_LEFT),
                'first_name' => "Learner{$classIndex}{$studentIndex}",
                'last_name' => 'Teacher',
            ]);

            Enrollment::query()->create([
                'student_id' => $student->id,
                'academic_year_id' => $schoolYear->id,
                'grade_level_id' => $gradeLevel->id,
                'section_id' => $section->id,
                'payment_term' => 'cash',
                'downpayment' => 0,
                'status' => 'enrolled',
            ]);
        }
    }

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/dashboard')
            ->where('quarter_grade_completion.total_classes', 6)
            ->where('quarter_grade_completion.finalized_classes', 0)
            ->where('quarter_grade_completion.unfinalized_classes', 6)
            ->where('kpis.2.id', 'grade-rows-pending')
            ->where('kpis.2.value', 12)
            ->where('trends.1.id', 'pending-grade-rows-by-class')
            ->where('trends.1.chart.rows', function ($rows): bool {
                return count($rows) === 6
                    && (int) collect($rows)->sum('pending_rows') === 12
                    && collect($rows)->every(function (array $row): bool {
                        return (int) ($row['pending_rows'] ?? 0) === 2;
                    });
            })
        );
});

test('teacher grading sheet renders rubric assessments and computed grades', function () {
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
        'adviser_id' => $this->teacher->id,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'MATH7',
        'subject_name' => 'Mathematics 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    GradingRubric::query()->create([
        'subject_id' => $subject->id,
        'ww_weight' => 40,
        'pt_weight' => 40,
        'qa_weight' => 20,
    ]);

    $ww1 = GradedActivity::query()->create([
        'subject_assignment_id' => $assignment->id,
        'type' => 'WW',
        'quarter' => '1',
        'title' => 'Quiz 1',
        'max_score' => 20,
    ]);

    $ww2 = GradedActivity::query()->create([
        'subject_assignment_id' => $assignment->id,
        'type' => 'WW',
        'quarter' => '1',
        'title' => 'Seatwork 1',
        'max_score' => 15,
    ]);

    $ww3 = GradedActivity::query()->create([
        'subject_assignment_id' => $assignment->id,
        'type' => 'WW',
        'quarter' => '1',
        'title' => 'Assignment 1',
        'max_score' => 25,
    ]);

    $pt1 = GradedActivity::query()->create([
        'subject_assignment_id' => $assignment->id,
        'type' => 'PT',
        'quarter' => '1',
        'title' => 'Project 1',
        'max_score' => 50,
    ]);

    $pt2 = GradedActivity::query()->create([
        'subject_assignment_id' => $assignment->id,
        'type' => 'PT',
        'quarter' => '1',
        'title' => 'Lab Activity 1',
        'max_score' => 40,
    ]);

    $exam = GradedActivity::query()->create([
        'subject_assignment_id' => $assignment->id,
        'type' => 'QA',
        'quarter' => '1',
        'title' => 'Quarterly Exam',
        'max_score' => 100,
    ]);

    $studentOne = Student::query()->create([
        'lrn' => '911111111111',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);

    $studentTwo = Student::query()->create([
        'lrn' => '922222222222',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
    ]);

    Enrollment::query()->create([
        'student_id' => $studentOne->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    Enrollment::query()->create([
        'student_id' => $studentTwo->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    $scoreRows = [
        ['student' => $studentOne->id, 'activity' => $ww1->id, 'score' => 18],
        ['student' => $studentOne->id, 'activity' => $ww2->id, 'score' => 13],
        ['student' => $studentOne->id, 'activity' => $ww3->id, 'score' => 22],
        ['student' => $studentOne->id, 'activity' => $pt1->id, 'score' => 45],
        ['student' => $studentOne->id, 'activity' => $pt2->id, 'score' => 36],
        ['student' => $studentOne->id, 'activity' => $exam->id, 'score' => 85],
        ['student' => $studentTwo->id, 'activity' => $ww1->id, 'score' => 19],
        ['student' => $studentTwo->id, 'activity' => $ww2->id, 'score' => 14],
        ['student' => $studentTwo->id, 'activity' => $ww3->id, 'score' => 24],
        ['student' => $studentTwo->id, 'activity' => $pt1->id, 'score' => 48],
        ['student' => $studentTwo->id, 'activity' => $pt2->id, 'score' => 38],
        ['student' => $studentTwo->id, 'activity' => $exam->id, 'score' => 91],
    ];

    foreach ($scoreRows as $scoreRow) {
        StudentScore::query()->create([
            'student_id' => $scoreRow['student'],
            'graded_activity_id' => $scoreRow['activity'],
            'score' => $scoreRow['score'],
        ]);
    }

    $this->get("/teacher/grading-sheet?section_id={$section->id}&subject_id={$subject->id}&quarter=1")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/grading-sheet/index')
            ->where('context.selected_section_id', $section->id)
            ->where('context.selected_subject_id', $subject->id)
            ->where('context.selected_assignment_id', $assignment->id)
            ->where('rubric_weights.ww_weight', 40)
            ->where('rubric_weights.pt_weight', 40)
            ->where('rubric_weights.qa_weight', 20)
            ->has('grouped_assessments', 2)
            ->where('grouped_assessments.0.assessments.0.title', 'Quiz 1')
            ->where('grouped_assessments.1.assessments.1.title', 'Lab Activity 1')
            ->where('quarterly_exam_assessment.title', 'Quarterly Exam')
            ->has('students', 2)
            ->where('students.0.computed_grade', '88.33')
            ->where('students.1.computed_grade', '94.42')
        );
});

test('teacher grading sheet is locked during pre-opening before first quarter starts', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => now()->addDays(10)->toDateString(),
        'end_date' => now()->addMonths(10)->toDateString(),
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    $section = Section::query()->create([
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Bonifacio',
        'adviser_id' => $this->teacher->id,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'SCI7',
        'subject_name' => 'Science 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $this->get('/teacher/grading-sheet')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/grading-sheet/index')
            ->where('feature_lock.is_locked', true)
            ->where('context.has_assignment', true)
            ->where('can_edit', false)
            ->has('context.section_options', 1)
            ->where('context.selected_assignment_id', $assignment->id)
            ->has('students', 0)
            ->where('feature_lock.message', function ($message): bool {
                return is_string($message) && str_contains(strtolower($message), 'pre-opening');
            })
        );
});

test('teacher grading sheet is editable for simulated first quarter even with future start date', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => now()->addDays(10)->toDateString(),
        'end_date' => now()->addMonths(10)->toDateString(),
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
        'name' => 'Bonifacio',
        'adviser_id' => $this->teacher->id,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'SCI7',
        'subject_name' => 'Science 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $this->get('/teacher/grading-sheet')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/grading-sheet/index')
            ->where('feature_lock.is_locked', false)
            ->where('context.has_assignment', true)
            ->where('can_edit', true)
            ->where('context.selected_assignment_id', $assignment->id)
            ->has('context.section_options', 1)
        );
});

test('teacher grading sheet actions update rubric add assessments and submit grades', function () {
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
        'name' => 'Bonifacio',
        'adviser_id' => null,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'SCI7',
        'subject_name' => 'Science 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $student = Student::query()->create([
        'lrn' => '933333333333',
        'first_name' => 'Carlo',
        'last_name' => 'Reyes',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    $this->post('/teacher/grading-sheet/rubric', [
        'subject_id' => $subject->id,
        'ww_weight' => 100,
        'pt_weight' => 0,
        'qa_weight' => 0,
    ])->assertRedirect();

    $this->assertDatabaseHas('grading_rubrics', [
        'subject_id' => $subject->id,
        'ww_weight' => 100,
        'pt_weight' => 0,
        'qa_weight' => 0,
    ]);

    $this->post('/teacher/grading-sheet/assessments', [
        'subject_assignment_id' => $assignment->id,
        'quarter' => '1',
        'type' => 'WW',
        'title' => 'Quiz 1',
        'max_score' => 20,
    ])->assertRedirect();

    $activity = GradedActivity::query()
        ->where('subject_assignment_id', $assignment->id)
        ->where('title', 'Quiz 1')
        ->first();

    expect($activity)->not->toBeNull();

    $this->post('/teacher/grading-sheet/scores', [
        'subject_assignment_id' => $assignment->id,
        'quarter' => '1',
        'save_mode' => 'submitted',
        'scores' => [
            [
                'student_id' => $student->id,
                'graded_activity_id' => $activity?->id,
                'score' => 18,
            ],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('student_scores', [
        'student_id' => $student->id,
        'graded_activity_id' => $activity?->id,
        'score' => 18,
    ]);

    $this->assertDatabaseHas('final_grades', [
        'enrollment_id' => $enrollment->id,
        'subject_assignment_id' => $assignment->id,
        'quarter' => '1',
        'grade' => 90,
        'is_locked' => true,
    ]);

    $this->assertDatabaseHas('grade_submissions', [
        'academic_year_id' => $schoolYear->id,
        'subject_assignment_id' => $assignment->id,
        'quarter' => '1',
        'status' => GradeSubmission::STATUS_SUBMITTED,
        'submitted_by' => $this->teacher->id,
    ]);

    $this->post('/teacher/grading-sheet/scores', [
        'subject_assignment_id' => $assignment->id,
        'quarter' => '1',
        'save_mode' => 'draft',
        'scores' => [
            [
                'student_id' => $student->id,
                'graded_activity_id' => $activity?->id,
                'score' => 17,
            ],
        ],
    ])
        ->assertRedirect()
        ->assertSessionHas('error', 'This class-quarter is already finalized. Return it first before editing scores.');
});

test('teacher grading sheet assessment mutation is blocked during pre-opening', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => now()->addDays(10)->toDateString(),
        'end_date' => now()->addMonths(10)->toDateString(),
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    $section = Section::query()->create([
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'Mabini',
        'adviser_id' => null,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'subject_code' => 'SCI7',
        'subject_name' => 'Science 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $this->post('/teacher/grading-sheet/assessments', [
        'subject_assignment_id' => $assignment->id,
        'quarter' => '1',
        'type' => 'WW',
        'title' => 'Quiz 1',
        'max_score' => 20,
    ])->assertRedirect()
        ->assertSessionHas('error');

    expect(GradedActivity::query()->count())->toBe(0);
});

test('teacher advisory board renders advisory class grades and conduct rows from real records', function () {
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
        'adviser_id' => $this->teacher->id,
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

    $teacherMath = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $math->id,
    ]);

    $teacherScience = TeacherSubject::query()->create([
        'teacher_id' => $this->teacher->id,
        'subject_id' => $science->id,
    ]);

    $mathAssignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherMath->id,
    ]);

    $scienceAssignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherScience->id,
    ]);

    $student = Student::query()->create([
        'lrn' => '944444444444',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
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
        'grade' => 87,
        'is_locked' => true,
    ]);

    FinalGrade::query()->create([
        'enrollment_id' => $enrollment->id,
        'subject_assignment_id' => $scienceAssignment->id,
        'quarter' => '1',
        'grade' => 89,
        'is_locked' => true,
    ]);

    ConductRating::query()->create([
        'enrollment_id' => $enrollment->id,
        'quarter' => '1',
        'maka_diyos' => 'AO',
        'makatao' => 'SO',
        'makakalikasan' => 'AO',
        'makabansa' => 'AO',
        'remarks' => 'Good participation',
        'is_locked' => false,
    ]);

    $this->get("/teacher/advisory-board?section_id={$section->id}&quarter=1")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/advisory-board/index')
            ->where('context.selected_section_id', $section->id)
            ->where('context.selected_quarter', '1')
            ->has('grade_columns', 2)
            ->where('grade_rows.0.subject_grades.'.$math->id, '87.00')
            ->where('grade_rows.0.subject_grades.'.$science->id, '89.00')
            ->where('grade_rows.0.general_average', '88.00')
            ->where('conduct_rows.0.ratings.makatao', 'SO')
            ->where('conduct_rows.0.remarks', 'Good participation')
            ->where('status', 'draft')
        );
});

test('teacher advisory board conduct actions save draft then lock quarter', function () {
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
        'name' => 'Bonifacio',
        'adviser_id' => $this->teacher->id,
    ]);

    $student = Student::query()->create([
        'lrn' => '955555555555',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    $payloadRows = [
        [
            'enrollment_id' => $enrollment->id,
            'maka_diyos' => 'AO',
            'makatao' => 'SO',
            'makakalikasan' => 'AO',
            'makabansa' => 'RO',
            'remarks' => 'Needs improvement',
        ],
    ];

    $this->post('/teacher/advisory-board/conduct', [
        'section_id' => $section->id,
        'quarter' => '1',
        'save_mode' => 'draft',
        'rows' => $payloadRows,
    ])->assertRedirect();

    $this->assertDatabaseHas('conduct_ratings', [
        'enrollment_id' => $enrollment->id,
        'quarter' => '1',
        'maka_diyos' => 'AO',
        'makatao' => 'SO',
        'makakalikasan' => 'AO',
        'makabansa' => 'RO',
        'remarks' => 'Needs improvement',
        'is_locked' => false,
    ]);

    $this->post('/teacher/advisory-board/conduct', [
        'section_id' => $section->id,
        'quarter' => '1',
        'save_mode' => 'locked',
        'rows' => $payloadRows,
    ])->assertRedirect();

    $this->assertDatabaseHas('conduct_ratings', [
        'enrollment_id' => $enrollment->id,
        'quarter' => '1',
        'is_locked' => true,
    ]);

    $this->post('/teacher/advisory-board/conduct', [
        'section_id' => $section->id,
        'quarter' => '1',
        'save_mode' => 'draft',
        'rows' => [
            [
                'enrollment_id' => $enrollment->id,
                'maka_diyos' => 'NO',
                'makatao' => 'NO',
                'makakalikasan' => 'NO',
                'makabansa' => 'NO',
                'remarks' => 'Should not update',
            ],
        ],
    ])->assertRedirect();

    $this->assertDatabaseMissing('conduct_ratings', [
        'enrollment_id' => $enrollment->id,
        'quarter' => '1',
        'maka_diyos' => 'NO',
        'remarks' => 'Should not update',
    ]);
});
