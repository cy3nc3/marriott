<?php

use App\Models\AcademicYear;
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
        );
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

    $fee = Fee::query()->create([
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
                'type' => 'fee',
                'description' => 'Tuition Downpayment',
                'amount' => 2000,
                'fee_id' => $fee->id,
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
