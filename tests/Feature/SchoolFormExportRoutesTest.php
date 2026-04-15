<?php

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\BillingSchedule;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TeacherSubject;
use App\Models\Transaction;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

test('teacher can download live sf2 workbook for the selected class and month', function () {
    $teacher = User::factory()->teacher()->create();
    $this->actingAs($teacher);

    Setting::set('school_name', 'Marriott School', 'system');
    Setting::set('school_id', '482518', 'system');

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
        'teacher_id' => $teacher->id,
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

    Attendance::query()->create([
        'subject_assignment_id' => $assignment->id,
        'enrollment_id' => $enrollment->id,
        'date' => '2026-03-02',
        'status' => Attendance::STATUS_TARDY_LATE_COMER,
    ]);

    Attendance::query()->create([
        'subject_assignment_id' => $assignment->id,
        'enrollment_id' => $enrollment->id,
        'date' => '2026-03-03',
        'status' => Attendance::STATUS_TARDY_CUTTING_CLASSES,
    ]);

    $response = $this->get("/teacher/attendance/export-sf2?subject_assignment_id={$assignment->id}&month=2026-03");

    $response->assertSuccessful();
    expect((string) $response->headers->get('content-disposition'))->toContain('sf2-2026-03');

    $fileResponse = $response->baseResponse;

    expect($fileResponse)->toBeInstanceOf(BinaryFileResponse::class);

    $sheet = IOFactory::load($fileResponse->getFile()->getPathname())->getActiveSheet();

    expect(trim((string) $sheet->getCell('F3')->getCalculatedValue()))->toBe('482518');
    expect(trim((string) $sheet->getCell('F4')->getCalculatedValue()))->toBe('Marriott School');
    expect(trim((string) $sheet->getCell('AA4')->getCalculatedValue()))->toBe('Grade 7');
    expect(trim((string) $sheet->getCell('AM4')->getCalculatedValue()))->toBe('Rizal');
    expect(trim((string) $sheet->getCell('C8')->getCalculatedValue()))->toBe('Reyes, Mila');
    expect(count($sheet->getDrawingCollection()))->toBeGreaterThanOrEqual(2);
});

test('registrar can download live enrollment workbook for the selected school year', function () {
    $registrar = User::factory()->registrar()->create();
    $this->actingAs($registrar);

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

    $student = Student::query()->create([
        'lrn' => '123456789012',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'for_cashier_payment',
    ]);

    Fee::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'academic_year_id' => $schoolYear->id,
        'type' => 'miscellaneous',
        'name' => 'Miscellaneous Fee',
        'amount' => 2000,
    ]);

    Fee::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'academic_year_id' => $schoolYear->id,
        'type' => 'tuition',
        'name' => 'Tuition Fee',
        'amount' => 7000,
    ]);

    $discount = Discount::query()->create([
        'name' => 'Academic Scholarship',
        'type' => 'fixed',
        'value' => 500,
        'export_bucket' => 'overall_discount',
    ]);

    StudentDiscount::query()->create([
        'student_id' => $student->id,
        'discount_id' => $discount->id,
        'academic_year_id' => $schoolYear->id,
    ]);

    BillingSchedule::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $schoolYear->id,
        'description' => 'Upon Enrollment',
        'due_date' => '2025-06-01',
        'amount_due' => 1000,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    $cashier = User::factory()->finance()->create();

    Transaction::query()->create([
        'or_number' => 'OR-1001',
        'student_id' => $student->id,
        'cashier_id' => $cashier->id,
        'total_amount' => 1000,
        'payment_mode' => 'cash',
        'reference_no' => null,
        'remarks' => 'Initial payment',
        'status' => 'posted',
    ]);

    $response = $this->get("/registrar/enrollment/export?academic_year_id={$schoolYear->id}");

    $response->assertSuccessful();
    expect((string) $response->headers->get('content-disposition'))->toContain('enrollment-2025-2026');

    $fileResponse = $response->baseResponse;

    expect($fileResponse)->toBeInstanceOf(BinaryFileResponse::class);

    $sheet = IOFactory::load($fileResponse->getFile()->getPathname())->getSheetByName('SY26-27');

    expect($sheet instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet)->toBeTrue();
    expect(trim((string) $sheet->getCell('B6')->getCalculatedValue()))->toBe('Dela Cruz, Juan');
    expect(trim((string) $sheet->getCell('C6')->getCalculatedValue()))->toBe('Grade 7');
    expect(trim((string) $sheet->getCell('D6')->getCalculatedValue()))->toBe('Rizal');
    expect(trim((string) $sheet->getCell('E6')->getCalculatedValue()))->toBe('OR-1001');
    expect((float) $sheet->getCell('H6')->getCalculatedValue())->toBe(2000.0);
    expect((float) $sheet->getCell('L6')->getCalculatedValue())->toBe(7000.0);
    expect((float) $sheet->getCell('R6')->getCalculatedValue())->toBe(500.0);
    expect(trim((string) $sheet->getCell('V6')->getCalculatedValue()))->toBe('R');
});
