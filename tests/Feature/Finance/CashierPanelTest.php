<?php

use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\GradeLevel;
use App\Models\InventoryItem;
use App\Models\LedgerEntry;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->finance = User::factory()->finance()->create();
    $this->actingAs($this->finance);
});

test('cashier panel renders selected student profile and payment options', function () {
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

    $student = Student::query()->create([
        'lrn' => '123456789012',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 3000,
        'status' => 'for_cashier_payment',
    ]);

    Fee::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'type' => 'tuition',
        'name' => 'Tuition Downpayment',
        'amount' => 3000,
    ]);

    InventoryItem::query()->create([
        'name' => 'School Uniform',
        'type' => 'uniform',
        'price' => 650,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'date' => now()->toDateString(),
        'description' => 'Opening charge',
        'debit' => 20000,
        'credit' => null,
        'running_balance' => 20000,
        'reference_id' => null,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'date' => now()->toDateString(),
        'description' => 'Existing payment',
        'debit' => null,
        'credit' => 3000,
        'running_balance' => 17000,
        'reference_id' => null,
    ]);

    $this->get("/finance/cashier-panel?search=Juan&student_id={$student->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/cashier-panel/index')
            ->has('students', 1)
            ->where('selected_student.lrn', '123456789012')
            ->where('selected_student.grade_and_section', 'Grade 7')
            ->where('selected_student.payment_plan', 'monthly')
            ->where('selected_student.remaining_balance', 17000)
            ->has('fee_options', 1)
            ->has('inventory_options', 1)
            ->where('pending_intakes_count', 1)
            ->has('pending_intakes', 1)
            ->where('pending_intakes.0.student_id', $student->id)
        );
});

test('cashier panel pending intakes include active year for cashier payment statuses only', function () {
    $activeYear = AcademicYear::query()->create([
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
        'name' => 'Grade 8',
        'level_order' => 8,
    ]);

    $includedStudent = Student::query()->create([
        'lrn' => '765432109876',
        'first_name' => 'Ari',
        'last_name' => 'Lopez',
    ]);

    $excludedCurrentYearStudent = Student::query()->create([
        'lrn' => '876543210987',
        'first_name' => 'Ben',
        'last_name' => 'Tan',
    ]);

    $excludedPreviousYearStudent = Student::query()->create([
        'lrn' => '987650123456',
        'first_name' => 'Cara',
        'last_name' => 'Sy',
    ]);

    Enrollment::query()->create([
        'student_id' => $includedStudent->id,
        'academic_year_id' => $activeYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 3500,
        'status' => 'for_cashier_payment',
    ]);

    $includedSecondEligibleStudent = Student::query()->create([
        'lrn' => '654321098765',
        'first_name' => 'Dana',
        'last_name' => 'Reyes',
    ]);

    Enrollment::query()->create([
        'student_id' => $includedSecondEligibleStudent->id,
        'academic_year_id' => $activeYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'quarterly',
        'downpayment' => 2500,
        'status' => 'for_cashier_payment',
    ]);

    Enrollment::query()->create([
        'student_id' => $excludedCurrentYearStudent->id,
        'academic_year_id' => $activeYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 3500,
        'status' => 'partial_payment',
    ]);

    Enrollment::query()->create([
        'student_id' => $excludedPreviousYearStudent->id,
        'academic_year_id' => $previousYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 3500,
        'status' => 'for_cashier_payment',
    ]);

    $this->get('/finance/cashier-panel')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/cashier-panel/index')
            ->where('pending_intakes_count', 2)
            ->has('pending_intakes', 2)
            ->where('pending_intakes.0.student_id', $includedSecondEligibleStudent->id)
            ->where('pending_intakes.0.student_name', 'Dana Reyes')
            ->where('pending_intakes.1.student_id', $includedStudent->id)
            ->where('pending_intakes.1.student_name', 'Ari Lopez')
        );
});

test('cashier panel can search student by lrn', function () {
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

    $matchingStudent = Student::query()->create([
        'lrn' => '987654321098',
        'first_name' => 'Lina',
        'last_name' => 'Mendoza',
    ]);

    Enrollment::query()->create([
        'student_id' => $matchingStudent->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 3000,
        'status' => 'for_cashier_payment',
    ]);

    Student::query()->create([
        'lrn' => '111122223333',
        'first_name' => 'Other',
        'last_name' => 'Student',
    ]);

    $this->get('/finance/cashier-panel?search=987654321098')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/cashier-panel/index')
            ->has('students', 1)
            ->where('students.0.id', $matchingStudent->id)
            ->where('students.0.lrn', '987654321098')
        );
});

test('cashier panel search is case insensitive and does not auto select without student id', function () {
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

    $matchingStudent = Student::query()->create([
        'lrn' => '456789012345',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);

    Enrollment::query()->create([
        'student_id' => $matchingStudent->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 3000,
        'status' => 'for_cashier_payment',
    ]);

    Student::query()->create([
        'lrn' => '999988887777',
        'first_name' => 'Lina',
        'last_name' => 'Mendoza',
    ]);

    $this->get('/finance/cashier-panel?search=DELA')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/cashier-panel/index')
            ->has('students', 1)
            ->where('students.0.id', $matchingStudent->id)
            ->where('selected_student', null)
        );
});

test('cashier panel suggestions endpoint returns latest case insensitive matches', function () {
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

    $firstMatch = Student::query()->create([
        'lrn' => '112233445566',
        'first_name' => 'Ana',
        'last_name' => 'Dela Cruz',
    ]);

    Enrollment::query()->create([
        'student_id' => $firstMatch->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 1500,
        'status' => 'for_cashier_payment',
    ]);

    $secondMatch = Student::query()->create([
        'lrn' => '223344556677',
        'first_name' => 'Ben',
        'last_name' => 'Dela Rosa',
    ]);

    Enrollment::query()->create([
        'student_id' => $secondMatch->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 1500,
        'status' => 'for_cashier_payment',
    ]);

    Student::query()->create([
        'lrn' => '998877665544',
        'first_name' => 'Carl',
        'last_name' => 'Mendoza',
    ]);

    $this->getJson('/finance/cashier-panel/student-suggestions?search=DELA')
        ->assertSuccessful()
        ->assertJsonCount(2, 'students')
        ->assertJsonPath('students.0.id', $firstMatch->id)
        ->assertJsonPath('students.1.id', $secondMatch->id);
});

test('cashier can post transaction and update enrollment status to partial payment', function () {
    $academicYear = AcademicYear::query()->create([
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

    $student = Student::query()->create([
        'lrn' => '234567890123',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 3000,
        'status' => 'for_cashier_payment',
    ]);

    Fee::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'type' => 'tuition',
        'name' => 'Tuition Downpayment',
        'amount' => 2000,
    ]);

    $uniform = InventoryItem::query()->create([
        'name' => 'School Uniform',
        'type' => 'uniform',
        'price' => 500,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'date' => now()->toDateString(),
        'description' => 'Opening charge',
        'debit' => 18000,
        'credit' => null,
        'running_balance' => 18000,
        'reference_id' => null,
    ]);

    $this->post('/finance/cashier-panel/transactions', [
        'student_id' => $student->id,
        'or_number' => 'OR-2026-0001',
        'payment_mode' => 'gcash',
        'reference_no' => 'GCASH-REF-01',
        'remarks' => 'Enrollment payment',
        'tendered_amount' => 2500,
        'items' => [
            [
                'type' => 'assessment_fee',
                'description' => 'Assessment Fee',
                'amount' => 2000,
            ],
            [
                'type' => 'inventory',
                'description' => 'School Uniform',
                'amount' => 500,
                'inventory_item_id' => $uniform->id,
            ],
        ],
    ])->assertRedirect();

    $transaction = Transaction::query()
        ->where('or_number', 'OR-2026-0001')
        ->first();

    expect($transaction)->not->toBeNull();
    expect($transaction->student_id)->toBe($student->id);
    expect($transaction->cashier_id)->toBe($this->finance->id);
    expect((float) $transaction->total_amount)->toBe(2500.0);
    expect($transaction->items()->count())->toBe(2);

    $paymentLedgerEntry = LedgerEntry::query()
        ->where('student_id', $student->id)
        ->where('description', 'Payment (OR-2026-0001)')
        ->first();

    expect($paymentLedgerEntry)->not->toBeNull();
    expect((float) $paymentLedgerEntry->credit)->toBe(2500.0);

    $enrollment->refresh();
    expect($enrollment->status)->toBe('partial_payment');
});

test('cashier transaction marks non cash enrollment as enrolled when downpayment is reached', function () {
    $academicYear = AcademicYear::query()->create([
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

    $student = Student::query()->create([
        'lrn' => '345678901234',
        'first_name' => 'Jose',
        'last_name' => 'Rizal',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 3000,
        'status' => 'for_cashier_payment',
    ]);

    $this->post('/finance/cashier-panel/transactions', [
        'student_id' => $student->id,
        'or_number' => 'OR-2026-0002',
        'payment_mode' => 'cash',
        'reference_no' => null,
        'remarks' => null,
        'tendered_amount' => 3000,
        'items' => [
            [
                'type' => 'custom',
                'description' => 'Enrollment Downpayment',
                'amount' => 3000,
            ],
        ],
    ])->assertRedirect();

    $enrollment->refresh();
    expect($enrollment->status)->toBe('enrolled');
});

test('cashier transaction validates tendered amount', function () {
    $academicYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 10',
        'level_order' => 10,
    ]);

    $student = Student::query()->create([
        'lrn' => '456789012345',
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'for_cashier_payment',
    ]);

    $this->from('/finance/cashier-panel')
        ->post('/finance/cashier-panel/transactions', [
            'student_id' => $student->id,
            'or_number' => 'OR-2026-0003',
            'payment_mode' => 'cash',
            'tendered_amount' => 500,
            'items' => [
                [
                    'type' => 'custom',
                    'description' => 'Partial payment',
                    'amount' => 1000,
                ],
            ],
        ])
        ->assertRedirect('/finance/cashier-panel')
        ->assertSessionHasErrors(['tendered_amount']);

    expect(Transaction::query()->where('or_number', 'OR-2026-0003')->exists())->toBeFalse();
});

test('cashier payment allocates to oldest dues first and carries partial balance forward', function () {
    $academicYear = AcademicYear::query()->create([
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

    $student = Student::query()->create([
        'lrn' => '567890123456',
        'first_name' => 'Paolo',
        'last_name' => 'Garcia',
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 12000,
        'status' => 'for_cashier_payment',
    ]);

    Fee::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'type' => 'tuition',
        'name' => 'Tuition',
        'amount' => 10000,
    ]);

    $augustDue = BillingSchedule::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'August Installment',
        'due_date' => '2025-08-01',
        'amount_due' => 5000,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    $septemberDue = BillingSchedule::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'September Installment',
        'due_date' => '2025-09-01',
        'amount_due' => 5000,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    $this->post('/finance/cashier-panel/transactions', [
        'student_id' => $student->id,
        'or_number' => 'OR-2026-1001',
        'payment_mode' => 'cash',
        'reference_no' => null,
        'remarks' => 'First partial monthly payment',
        'tendered_amount' => 3000,
        'items' => [
            [
                'type' => 'assessment_fee',
                'description' => 'Assessment Fee',
                'amount' => 3000,
            ],
        ],
    ])->assertRedirect();

    $augustDue->refresh();
    $septemberDue->refresh();

    expect((float) $augustDue->amount_paid)->toBe(3000.0);
    expect($augustDue->status)->toBe('partially_paid');
    expect((float) $septemberDue->amount_paid)->toBe(0.0);
    expect($septemberDue->status)->toBe('unpaid');

    $this->post('/finance/cashier-panel/transactions', [
        'student_id' => $student->id,
        'or_number' => 'OR-2026-1002',
        'payment_mode' => 'gcash',
        'reference_no' => 'GCASH-SECOND',
        'remarks' => 'Second monthly payment',
        'tendered_amount' => 5000,
        'items' => [
            [
                'type' => 'assessment_fee',
                'description' => 'Assessment Fee',
                'amount' => 5000,
            ],
        ],
    ])->assertRedirect();

    $augustDue->refresh();
    $septemberDue->refresh();

    expect((float) $augustDue->amount_paid)->toBe(5000.0);
    expect($augustDue->status)->toBe('paid');
    expect((float) $septemberDue->amount_paid)->toBe(3000.0);
    expect($septemberDue->status)->toBe('partially_paid');
});

test('cashier custom line items do not settle dues schedule', function () {
    $academicYear = AcademicYear::query()->create([
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

    $student = Student::query()->create([
        'lrn' => '678901234567',
        'first_name' => 'Nina',
        'last_name' => 'Lopez',
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 0,
        'status' => 'for_cashier_payment',
    ]);

    $due = BillingSchedule::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'August Installment',
        'due_date' => '2025-08-01',
        'amount_due' => 5000,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    $this->post('/finance/cashier-panel/transactions', [
        'student_id' => $student->id,
        'or_number' => 'OR-2026-1003',
        'payment_mode' => 'cash',
        'reference_no' => null,
        'remarks' => 'ID replacement payment',
        'tendered_amount' => 500,
        'items' => [
            [
                'type' => 'custom',
                'description' => 'ID Replacement',
                'amount' => 500,
            ],
        ],
    ])->assertRedirect();

    $due->refresh();

    expect((float) $due->amount_paid)->toBe(0.0);
    expect($due->status)->toBe('unpaid');
});
