<?php

use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\ConductRating;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradedActivity;
use App\Models\GradeLevel;
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
