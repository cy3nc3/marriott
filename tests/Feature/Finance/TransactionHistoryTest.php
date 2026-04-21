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
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
            ->where('transactions.data.0.transaction_items.0.description', 'Tuition Partial')
            ->where('transactions.data.0.transaction_items.0.amount', 1000)
            ->where('transactions.data.0.transaction_items.1.description', 'School Uniform')
            ->where('transactions.data.0.transaction_items.1.amount', 400)
            ->where('transactions.data.0.payment_mode_label', 'GCash')
            ->where('transactions.data.1.or_number', 'OR-1001')
            ->where('transactions.data.1.entry_label', 'Enrollment Downpayment')
            ->where('transactions.data.1.transaction_items.0.description', 'Enrollment Downpayment')
            ->where('transactions.data.1.transaction_items.0.amount', 3000)
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

test('finance transaction history handles school year filters without date bounds', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => null,
        'end_date' => null,
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $student = Student::query()->create([
        'lrn' => '844455556666',
        'first_name' => 'Null',
        'last_name' => 'Bounds',
    ]);

    $transaction = Transaction::query()->create([
        'or_number' => 'OR-SY-NULL-1001',
        'student_id' => $student->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 1200,
        'payment_mode' => 'cash',
    ]);

    $transaction->items()->create([
        'description' => 'Assessment Fee',
        'amount' => 1200,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $schoolYear->id,
        'date' => '2026-08-12',
        'description' => 'Payment (OR-SY-NULL-1001)',
        'debit' => null,
        'credit' => 1200,
        'running_balance' => -1200,
        'reference_id' => $transaction->id,
    ]);

    $this->get("/finance/transaction-history?academic_year_id={$schoolYear->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/transaction-history/index')
            ->where('selected_school_year_id', $schoolYear->id)
            ->where('summary.count', 1)
            ->where('transactions.data.0.or_number', 'OR-SY-NULL-1001')
        );
});

test('finance can export transaction history workbook using preset date range with monthly segmented sheets', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '4',
    ]);

    $student = Student::query()->create([
        'lrn' => '955566667777',
        'first_name' => 'Mila',
        'last_name' => 'Santos',
    ]);

    $marchTransaction = Transaction::query()->create([
        'or_number' => 'OR-EXP-MAR-1',
        'student_id' => $student->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 1200,
        'payment_mode' => 'cash',
        'status' => 'posted',
    ]);
    $marchTransaction->items()->create([
        'description' => 'Assessment Fee',
        'amount' => 1200,
    ]);

    $aprilTransaction = Transaction::query()->create([
        'or_number' => 'OR-EXP-APR-1',
        'student_id' => $student->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 800,
        'payment_mode' => 'gcash',
        'status' => 'posted',
    ]);
    $aprilTransaction->items()->create([
        'description' => 'Books Payment',
        'amount' => 800,
    ]);

    Transaction::query()->whereKey($marchTransaction->id)->update([
        'created_at' => '2026-03-20 10:00:00',
        'updated_at' => '2026-03-20 10:00:00',
    ]);
    Transaction::query()->whereKey($aprilTransaction->id)->update([
        'created_at' => '2026-04-04 10:00:00',
        'updated_at' => '2026-04-04 10:00:00',
    ]);

    $this->travelTo('2026-04-09 12:00:00');

    $response = $this->get('/finance/transaction-history/export?export_range=all_time');

    $response->assertSuccessful();
    expect((string) $response->headers->get('content-disposition'))->toContain('transaction-history-');
    expect((string) $response->headers->get('content-disposition'))->toContain('.xlsx');

    $fileResponse = $response->baseResponse;
    expect($fileResponse)->toBeInstanceOf(BinaryFileResponse::class);

    $spreadsheet = IOFactory::load($fileResponse->getFile()->getPathname());

    $summarySheet = $spreadsheet->getSheetByName('Summary');
    $monthlyOverviewSheet = $spreadsheet->getSheetByName('Monthly Overview');
    $marchDetailSheet = $spreadsheet->getSheetByName('March 20-31, 2026');
    $aprilDetailSheet = $spreadsheet->getSheetByName('April 1-4, 2026');

    expect($summarySheet instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet)->toBeTrue();
    expect($monthlyOverviewSheet instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet)->toBeTrue();
    expect($marchDetailSheet instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet)->toBeTrue();
    expect($aprilDetailSheet instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet)->toBeTrue();

    expect((string) $summarySheet?->getCell('A1')->getCalculatedValue())->toBe('Transaction History Export');
    expect((string) $summarySheet?->getCell('B4')->getCalculatedValue())->toBe('All Time');
    expect((string) $summarySheet?->getCell('B5')->getCalculatedValue())->toBe('2026-03-20');
    expect((string) $summarySheet?->getCell('B6')->getCalculatedValue())->toBe('2026-04-04');
    expect((float) $summarySheet?->getCell('B9')->getCalculatedValue())->toBe(2.0);
    expect((float) $summarySheet?->getCell('B10')->getCalculatedValue())->toBe(2000.0);

    expect((string) $monthlyOverviewSheet?->getCell('A4')->getCalculatedValue())->toBe('March 20-31, 2026');
    expect((float) $monthlyOverviewSheet?->getCell('B4')->getCalculatedValue())->toBe(1.0);
    expect((float) $monthlyOverviewSheet?->getCell('C4')->getCalculatedValue())->toBe(1200.0);
    expect((string) $monthlyOverviewSheet?->getCell('A5')->getCalculatedValue())->toBe('April 1-4, 2026');
    expect((float) $monthlyOverviewSheet?->getCell('C5')->getCalculatedValue())->toBe(800.0);

    expect((string) $marchDetailSheet?->getCell('A4')->getCalculatedValue())->toBe('OR-EXP-MAR-1');
    expect((string) $aprilDetailSheet?->getCell('A4')->getCalculatedValue())->toBe('OR-EXP-APR-1');

    $this->travelBack();
});

test('finance export uses explicitly selected date range over preset', function () {
    AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '4',
    ]);

    $student = Student::query()->create([
        'lrn' => '955566667779',
        'first_name' => 'Luna',
        'last_name' => 'Dela Paz',
    ]);

    $marchTransaction = Transaction::query()->create([
        'or_number' => 'OR-EXP-MAR-2',
        'student_id' => $student->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 600,
        'payment_mode' => 'cash',
        'status' => 'posted',
    ]);
    $marchTransaction->items()->create([
        'description' => 'Assessment Fee',
        'amount' => 600,
    ]);

    $aprilTransaction = Transaction::query()->create([
        'or_number' => 'OR-EXP-APR-2',
        'student_id' => $student->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 900,
        'payment_mode' => 'gcash',
        'status' => 'posted',
    ]);
    $aprilTransaction->items()->create([
        'description' => 'Books Payment',
        'amount' => 900,
    ]);

    Transaction::query()->whereKey($marchTransaction->id)->update([
        'created_at' => '2026-03-22 10:00:00',
        'updated_at' => '2026-03-22 10:00:00',
    ]);
    Transaction::query()->whereKey($aprilTransaction->id)->update([
        'created_at' => '2026-04-05 10:00:00',
        'updated_at' => '2026-04-05 10:00:00',
    ]);

    $this->travelTo('2026-04-09 12:00:00');

    $response = $this->get('/finance/transaction-history/export?export_range=all_time&date_from=2026-03-22&date_to=2026-03-22');

    $response->assertSuccessful();
    $fileResponse = $response->baseResponse;
    expect($fileResponse)->toBeInstanceOf(BinaryFileResponse::class);

    $spreadsheet = IOFactory::load($fileResponse->getFile()->getPathname());

    $summarySheet = $spreadsheet->getSheetByName('Summary');
    $marchDetailSheet = $spreadsheet->getSheetByName('March 22-22, 2026');
    $aprilDetailSheet = $spreadsheet->getSheetByName('April 1-5, 2026');

    expect($summarySheet instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet)->toBeTrue();
    expect($marchDetailSheet instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet)->toBeTrue();
    expect($aprilDetailSheet)->toBeNull();
    expect((string) $summarySheet?->getCell('B5')->getCalculatedValue())->toBe('2026-03-22');
    expect((string) $summarySheet?->getCell('B6')->getCalculatedValue())->toBe('2026-03-22');
    expect((float) $summarySheet?->getCell('B9')->getCalculatedValue())->toBe(1.0);

    $this->travelBack();
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
    $expectedCorrectedByName = trim("{$this->finance->first_name} {$this->finance->last_name}");
    if ($expectedCorrectedByName === '') {
        $expectedCorrectedByName = (string) ($this->finance->name ?? '');
    }

    $this->get('/finance/transaction-history')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.posted_amount', 7000)
            ->where('summary.voided_amount', 7000)
            ->where('summary.corrected_amount', 7000)
            ->where('summary.net_amount', 0)
            ->where('transactions.data.0.status', 'voided')
            ->where('transactions.data.0.correction_reason', 'Duplicate posting')
            ->where('transactions.data.0.corrected_by_name', $expectedCorrectedByName)
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
