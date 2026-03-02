<?php

use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\LedgerEntry;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\TransactionDueAllocation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->finance = User::factory()->finance()->create();
    $this->actingAs($this->finance);
});

test('finance transaction history page renders transaction rows and summary totals', function () {
    $studentOne = Student::query()->create([
        'lrn' => '223456789012',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);

    $studentTwo = Student::query()->create([
        'lrn' => '323456789012',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
    ]);

    $transactionOne = Transaction::query()->create([
        'or_number' => 'OR-1001',
        'student_id' => $studentOne->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 3000,
        'payment_mode' => 'cash',
        'reference_no' => null,
        'remarks' => null,
    ]);

    $transactionOne->items()->create([
        'fee_id' => null,
        'inventory_item_id' => null,
        'description' => 'Enrollment Downpayment',
        'amount' => 3000,
    ]);

    $transactionTwo = Transaction::query()->create([
        'or_number' => 'OR-1002',
        'student_id' => $studentTwo->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 1400,
        'payment_mode' => 'gcash',
        'reference_no' => 'GCASH-2026-001',
        'remarks' => null,
    ]);

    $transactionTwo->items()->create([
        'fee_id' => null,
        'inventory_item_id' => null,
        'description' => 'Tuition Partial',
        'amount' => 1000,
    ]);

    $transactionTwo->items()->create([
        'fee_id' => null,
        'inventory_item_id' => null,
        'description' => 'School Uniform',
        'amount' => 400,
    ]);

    Transaction::query()->whereKey($transactionOne->id)->update([
        'created_at' => '2026-02-15 08:30:00',
        'updated_at' => '2026-02-15 08:30:00',
    ]);

    Transaction::query()->whereKey($transactionTwo->id)->update([
        'created_at' => '2026-02-16 09:45:00',
        'updated_at' => '2026-02-16 09:45:00',
    ]);

    $this->get('/finance/transaction-history')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/transaction-history/index')
            ->has('transactions.data', 2)
            ->where('transactions.total', 2)
            ->where('transactions.data.0.or_number', 'OR-1002')
            ->where('transactions.data.0.entry_label', 'Tuition Partial + 1 more')
            ->where('transactions.data.0.payment_mode_label', 'GCash')
            ->where('transactions.data.1.or_number', 'OR-1001')
            ->where('transactions.data.1.entry_label', 'Enrollment Downpayment')
            ->where('summary.count', 2)
            ->where('summary.posted_amount', 4400)
            ->where('summary.voided_amount', 0)
            ->where('summary.net_amount', 4400)
        );
});

test('finance transaction history filters by search payment mode and date range', function () {
    $studentOne = Student::query()->create([
        'lrn' => '423456789012',
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
    ]);

    $studentTwo = Student::query()->create([
        'lrn' => '523456789012',
        'first_name' => 'Jose',
        'last_name' => 'Rizal',
    ]);

    $firstTransaction = Transaction::query()->create([
        'or_number' => 'OR-2001',
        'student_id' => $studentOne->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 800,
        'payment_mode' => 'cash',
        'reference_no' => null,
        'remarks' => null,
    ]);

    $firstTransaction->items()->create([
        'fee_id' => null,
        'inventory_item_id' => null,
        'description' => 'Books Payment',
        'amount' => 800,
    ]);

    $secondTransaction = Transaction::query()->create([
        'or_number' => 'OR-2002',
        'student_id' => $studentTwo->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 2500,
        'payment_mode' => 'gcash',
        'reference_no' => null,
        'remarks' => null,
    ]);

    $secondTransaction->items()->create([
        'fee_id' => null,
        'inventory_item_id' => null,
        'description' => 'Downpayment',
        'amount' => 2500,
    ]);

    Transaction::query()->whereKey($firstTransaction->id)->update([
        'created_at' => '2026-02-10 10:00:00',
        'updated_at' => '2026-02-10 10:00:00',
    ]);

    Transaction::query()->whereKey($secondTransaction->id)->update([
        'created_at' => '2026-02-20 11:00:00',
        'updated_at' => '2026-02-20 11:00:00',
    ]);

    $this->get('/finance/transaction-history?search=Rizal&payment_mode=gcash&date_from=2026-02-20&date_to=2026-02-20')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/transaction-history/index')
            ->has('transactions.data', 1)
            ->where('transactions.total', 1)
            ->where('transactions.data.0.or_number', 'OR-2002')
            ->where('transactions.data.0.student_name', 'Jose Rizal')
            ->where('summary.count', 1)
            ->where('summary.posted_amount', 2500)
            ->where('filters.search', 'Rizal')
            ->where('filters.payment_mode', 'gcash')
            ->where('filters.date_from', '2026-02-20')
            ->where('filters.date_to', '2026-02-20')
        );
});

test('finance transaction history filters by school year', function () {
    $schoolYearOne = AcademicYear::query()->create([
        'name' => '2024-2025',
        'start_date' => '2024-06-01',
        'end_date' => '2025-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);

    $schoolYearTwo = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $student = Student::query()->create([
        'lrn' => '993456789012',
        'first_name' => 'Filter',
        'last_name' => 'Target',
    ]);

    $transactionOne = Transaction::query()->create([
        'or_number' => 'OR-SY-1001',
        'student_id' => $student->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 1000,
        'payment_mode' => 'cash',
    ]);

    $transactionOne->items()->create([
        'description' => 'Assessment Fee',
        'amount' => 1000,
    ]);

    $transactionTwo = Transaction::query()->create([
        'or_number' => 'OR-SY-1002',
        'student_id' => $student->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 2000,
        'payment_mode' => 'cash',
    ]);

    $transactionTwo->items()->create([
        'description' => 'Assessment Fee',
        'amount' => 2000,
    ]);

    Transaction::query()->whereKey($transactionOne->id)->update([
        'created_at' => '2024-08-10 10:00:00',
        'updated_at' => '2024-08-10 10:00:00',
    ]);

    Transaction::query()->whereKey($transactionTwo->id)->update([
        'created_at' => '2025-08-10 10:00:00',
        'updated_at' => '2025-08-10 10:00:00',
    ]);

    $this->get("/finance/transaction-history?academic_year_id={$schoolYearOne->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/transaction-history/index')
            ->where('selected_school_year_id', $schoolYearOne->id)
            ->has('transactions.data', 1)
            ->where('transactions.data.0.or_number', 'OR-SY-1001')
            ->where('summary.count', 1)
            ->where('filters.academic_year_id', $schoolYearOne->id)
        );

    $this->get("/finance/transaction-history?academic_year_id={$schoolYearTwo->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/transaction-history/index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.or_number', 'OR-SY-1002')
            ->where('summary.count', 1)
            ->where('filters.academic_year_id', $schoolYearTwo->id)
        );
});

test('finance transaction history school year filter includes transaction mapped by ledger year', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'upcoming',
        'current_quarter' => 'pre_opening',
    ]);

    $student = Student::query()->create([
        'lrn' => '811122223333',
        'first_name' => 'Sofia',
        'last_name' => 'Castro',
    ]);

    $transaction = Transaction::query()->create([
        'or_number' => 'OR-SY-LEDGER-1001',
        'student_id' => $student->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 3000,
        'payment_mode' => 'cash',
    ]);

    $transaction->items()->create([
        'description' => 'Enrollment Downpayment',
        'amount' => 3000,
    ]);

    Transaction::query()->whereKey($transaction->id)->update([
        'created_at' => '2026-03-10 10:00:00',
        'updated_at' => '2026-03-10 10:00:00',
    ]);

    LedgerEntry::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $schoolYear->id,
        'date' => '2026-03-10',
        'description' => 'Payment (OR-SY-LEDGER-1001)',
        'debit' => null,
        'credit' => 3000,
        'running_balance' => -3000,
        'reference_id' => $transaction->id,
    ]);

    $this->get("/finance/transaction-history?academic_year_id={$schoolYear->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/transaction-history/index')
            ->where('selected_school_year_id', $schoolYear->id)
            ->has('transactions.data', 1)
            ->where('transactions.data.0.or_number', 'OR-SY-LEDGER-1001')
            ->where('summary.count', 1)
            ->where('filters.academic_year_id', $schoolYear->id)
        );
});

test('finance can void a transaction and rollback dues and ledger entries', function () {
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
        'lrn' => '623456789012',
        'first_name' => 'Carla',
        'last_name' => 'Montes',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 2000,
        'status' => 'enrolled',
    ]);

    $firstDue = BillingSchedule::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'July Installment',
        'due_date' => '2025-07-01',
        'amount_due' => 5000,
        'amount_paid' => 5000,
        'status' => 'paid',
    ]);

    $secondDue = BillingSchedule::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'August Installment',
        'due_date' => '2025-08-01',
        'amount_due' => 5000,
        'amount_paid' => 2000,
        'status' => 'partially_paid',
    ]);

    $transaction = Transaction::query()->create([
        'or_number' => 'OR-VOID-1001',
        'student_id' => $student->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 7000,
        'payment_mode' => 'cash',
        'reference_no' => null,
        'remarks' => null,
        'status' => 'posted',
    ]);

    $transaction->items()->create([
        'fee_id' => null,
        'inventory_item_id' => null,
        'description' => 'Assessment Fee',
        'amount' => 7000,
    ]);

    TransactionDueAllocation::query()->create([
        'transaction_id' => $transaction->id,
        'billing_schedule_id' => $firstDue->id,
        'amount' => 5000,
    ]);

    TransactionDueAllocation::query()->create([
        'transaction_id' => $transaction->id,
        'billing_schedule_id' => $secondDue->id,
        'amount' => 2000,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2025-07-02',
        'description' => 'Payment (OR-VOID-1001)',
        'debit' => null,
        'credit' => 7000,
        'running_balance' => 3000,
        'reference_id' => $transaction->id,
    ]);

    $this->post("/finance/transaction-history/{$transaction->id}/void", [
        'reason' => 'Duplicate posting',
    ])->assertRedirect();

    expect($transaction->fresh()->status)->toBe('voided');
    expect($transaction->fresh()->void_reason)->toBe('Duplicate posting');
    expect($transaction->fresh()->voided_by)->toBe($this->finance->id);

    expect((float) $firstDue->fresh()->amount_paid)->toBe(0.0);
    expect($firstDue->fresh()->status)->toBe('unpaid');
    expect((float) $secondDue->fresh()->amount_paid)->toBe(0.0);
    expect($secondDue->fresh()->status)->toBe('unpaid');

    $voidLedger = LedgerEntry::query()
        ->where('reference_id', $transaction->id)
        ->where('description', 'like', 'Transaction Void%')
        ->latest('id')
        ->first();

    expect($voidLedger)->not->toBeNull();
    expect((float) $voidLedger->debit)->toBe(7000.0);
    expect($voidLedger->credit)->toBeNull();
    expect((float) $voidLedger->running_balance)->toBe(10000.0);

    expect($enrollment->fresh()->status)->toBe('for_cashier_payment');

    $this->get('/finance/transaction-history')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.posted_amount', 7000)
            ->where('summary.voided_amount', 7000)
            ->where('summary.corrected_amount', 7000)
            ->where('summary.net_amount', 0)
            ->where('transactions.data.0.status', 'voided')
            ->where('transactions.data.0.can_void', false)
        );
});

test('finance can refund a transaction and rollback dues and ledger entries', function () {
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
        'lrn' => '733456789012',
        'first_name' => 'Diane',
        'last_name' => 'Cruz',
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 3000,
        'status' => 'enrolled',
    ]);

    $due = BillingSchedule::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'July Installment',
        'due_date' => '2025-07-01',
        'amount_due' => 5000,
        'amount_paid' => 3000,
        'status' => 'partially_paid',
    ]);

    $transaction = Transaction::query()->create([
        'or_number' => 'OR-REF-1001',
        'student_id' => $student->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 3000,
        'payment_mode' => 'cash',
        'status' => 'posted',
    ]);

    $transaction->items()->create([
        'description' => 'Assessment Fee',
        'amount' => 3000,
    ]);

    TransactionDueAllocation::query()->create([
        'transaction_id' => $transaction->id,
        'billing_schedule_id' => $due->id,
        'amount' => 3000,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2025-07-05',
        'description' => 'Payment (OR-REF-1001)',
        'debit' => null,
        'credit' => 3000,
        'running_balance' => 2000,
        'reference_id' => $transaction->id,
    ]);

    $this->post("/finance/transaction-history/{$transaction->id}/refund", [
        'reason' => 'Payment reversal',
    ])->assertRedirect();

    $transaction->refresh();
    $due->refresh();

    expect($transaction->status)->toBe('refunded');
    expect($transaction->refund_reason)->toBe('Payment reversal');
    expect($transaction->refunded_by)->toBe($this->finance->id);

    expect((float) $due->amount_paid)->toBe(0.0);
    expect($due->status)->toBe('unpaid');

    $refundLedger = LedgerEntry::query()
        ->where('reference_id', $transaction->id)
        ->where('description', 'like', 'Transaction Refund%')
        ->latest('id')
        ->first();

    expect($refundLedger)->not->toBeNull();
    expect((float) $refundLedger->debit)->toBe(3000.0);
    expect($refundLedger->credit)->toBeNull();
});

test('finance can reissue a transaction and create replacement posting', function () {
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
        'lrn' => '833456789012',
        'first_name' => 'Lea',
        'last_name' => 'Mendoza',
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 2000,
        'status' => 'enrolled',
    ]);

    $due = BillingSchedule::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'July Installment',
        'due_date' => '2025-07-01',
        'amount_due' => 4000,
        'amount_paid' => 2000,
        'status' => 'partially_paid',
    ]);

    $transaction = Transaction::query()->create([
        'or_number' => 'OR-REI-1001',
        'student_id' => $student->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 2000,
        'payment_mode' => 'cash',
        'status' => 'posted',
    ]);

    $transaction->items()->create([
        'description' => 'Assessment Fee',
        'amount' => 2000,
    ]);

    TransactionDueAllocation::query()->create([
        'transaction_id' => $transaction->id,
        'billing_schedule_id' => $due->id,
        'amount' => 2000,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2025-07-05',
        'description' => 'Payment (OR-REI-1001)',
        'debit' => null,
        'credit' => 2000,
        'running_balance' => 2000,
        'reference_id' => $transaction->id,
    ]);

    $this->post("/finance/transaction-history/{$transaction->id}/reissue", [
        'reason' => 'Incorrect OR encoding',
        'or_number' => 'OR-REI-2001',
        'payment_mode' => 'gcash',
        'reference_no' => 'GCASH-REISSUE-01',
        'remarks' => 'Reissued entry',
    ])->assertRedirect();

    $transaction->refresh();
    $due->refresh();

    $replacementTransaction = Transaction::query()
        ->where('or_number', 'OR-REI-2001')
        ->first();

    expect($replacementTransaction)->not->toBeNull();
    expect($transaction->status)->toBe('reissued');
    expect($transaction->reissue_reason)->toBe('Incorrect OR encoding');
    expect($transaction->reissued_transaction_id)->toBe($replacementTransaction->id);
    expect($replacementTransaction->payment_mode)->toBe('gcash');

    expect((float) $due->amount_paid)->toBe(2000.0);
    expect($due->status)->toBe('partially_paid');

    expect(TransactionDueAllocation::query()
        ->where('transaction_id', $replacementTransaction->id)
        ->count())->toBe(1);

    $reissueLedger = LedgerEntry::query()
        ->where('reference_id', $replacementTransaction->id)
        ->where('description', 'like', 'Payment Reissue%')
        ->latest('id')
        ->first();

    expect($reissueLedger)->not->toBeNull();
    expect((float) $reissueLedger->credit)->toBe(2000.0);
});
