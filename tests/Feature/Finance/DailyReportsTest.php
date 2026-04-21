<?php

use App\Models\AcademicYear;
use App\Models\Fee;
use App\Models\GradeLevel;
use App\Models\InventoryItem;
use App\Models\LedgerEntry;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

beforeEach(function () {
    $this->finance = User::factory()->finance()->create();
    $this->actingAs($this->finance);
});

test('finance daily reports page renders summary breakdown and transaction rows', function () {
    $cashierA = User::factory()->finance()->create([
        'first_name' => 'Cashier',
        'last_name' => 'A',
    ]);

    $cashierB = User::factory()->finance()->create([
        'first_name' => 'Cashier',
        'last_name' => 'B',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    $tuitionFee = Fee::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'type' => 'tuition',
        'name' => 'Tuition Fee',
        'amount' => 7500,
    ]);

    $uniform = InventoryItem::query()->create([
        'name' => 'School Uniform',
        'type' => 'uniform',
        'price' => 1350,
    ]);

    $studentOne = Student::query()->create([
        'lrn' => '731234567890',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);

    $studentTwo = Student::query()->create([
        'lrn' => '741234567890',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
    ]);

    $studentThree = Student::query()->create([
        'lrn' => '751234567890',
        'first_name' => 'Carlo',
        'last_name' => 'Reyes',
    ]);

    $downpaymentTransaction = Transaction::query()->create([
        'or_number' => 'OR-01021',
        'student_id' => $studentOne->id,
        'cashier_id' => $cashierA->id,
        'total_amount' => 5000,
        'payment_mode' => 'cash',
        'reference_no' => null,
        'remarks' => null,
    ]);

    $downpaymentTransaction->items()->create([
        'fee_id' => null,
        'inventory_item_id' => null,
        'description' => 'Enrollment Downpayment',
        'amount' => 5000,
    ]);

    $tuitionTransaction = Transaction::query()->create([
        'or_number' => 'OR-01022',
        'student_id' => $studentTwo->id,
        'cashier_id' => $cashierB->id,
        'total_amount' => 7500,
        'payment_mode' => 'gcash',
        'reference_no' => null,
        'remarks' => null,
    ]);

    $tuitionTransaction->items()->create([
        'fee_id' => $tuitionFee->id,
        'inventory_item_id' => null,
        'description' => 'Tuition Payment',
        'amount' => 7500,
    ]);

    $productTransaction = Transaction::query()->create([
        'or_number' => 'OR-01023',
        'student_id' => $studentThree->id,
        'cashier_id' => $cashierA->id,
        'total_amount' => 1350,
        'payment_mode' => 'cash',
        'reference_no' => null,
        'remarks' => null,
    ]);

    $productTransaction->items()->create([
        'fee_id' => null,
        'inventory_item_id' => $uniform->id,
        'description' => 'Uniform Purchase',
        'amount' => 1350,
    ]);

    Transaction::query()->whereKey($downpaymentTransaction->id)->update([
        'created_at' => '2026-02-20 08:25:00',
        'updated_at' => '2026-02-20 08:25:00',
    ]);

    Transaction::query()->whereKey($tuitionTransaction->id)->update([
        'created_at' => '2026-02-20 08:44:00',
        'updated_at' => '2026-02-20 08:44:00',
    ]);

    Transaction::query()->whereKey($productTransaction->id)->update([
        'created_at' => '2026-02-20 09:02:00',
        'updated_at' => '2026-02-20 09:02:00',
    ]);

    $this->get('/finance/daily-reports?date_from=2026-02-20&date_to=2026-02-20')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/daily-reports/index')
            ->has('cashiers', 3)
            ->where('summary.transaction_count', 3)
            ->where('summary.gross_collection', 13850)
            ->where('summary.cash_on_hand', 6350)
            ->where('summary.digital_collection', 7500)
            ->where('summary.void_adjustments', 0)
            ->has('breakdown_rows', 4)
            ->where('breakdown_rows.0.category', 'Tuition Fees')
            ->where('breakdown_rows.0.transaction_count', 1)
            ->where('breakdown_rows.0.total_amount', 7500)
            ->where('breakdown_rows.1.category', 'Enrollment Downpayment')
            ->where('breakdown_rows.1.total_amount', 5000)
            ->where('breakdown_rows.2.category', 'Products (Uniform/Books)')
            ->where('breakdown_rows.2.total_amount', 1350)
            ->has('transaction_rows.data', 3)
            ->where('transaction_rows.total', 3)
            ->where('transaction_rows.data.0.or_number', 'OR-01023')
            ->where('transaction_rows.data.1.or_number', 'OR-01022')
            ->where('transaction_rows.data.2.or_number', 'OR-01021')
        );
});

test('finance daily reports filters by cashier payment mode and date range', function () {
    $cashierA = User::factory()->finance()->create([
        'first_name' => 'Cashier',
        'last_name' => 'A',
    ]);

    $cashierB = User::factory()->finance()->create([
        'first_name' => 'Cashier',
        'last_name' => 'B',
    ]);

    $student = Student::query()->create([
        'lrn' => '761234567890',
        'first_name' => 'Lina',
        'last_name' => 'Garcia',
    ]);

    $transactionOne = Transaction::query()->create([
        'or_number' => 'OR-02001',
        'student_id' => $student->id,
        'cashier_id' => $cashierA->id,
        'total_amount' => 2200,
        'payment_mode' => 'cash',
        'reference_no' => null,
        'remarks' => null,
    ]);

    $transactionOne->items()->create([
        'fee_id' => null,
        'inventory_item_id' => null,
        'description' => 'Miscellaneous Payment',
        'amount' => 2200,
    ]);

    $transactionTwo = Transaction::query()->create([
        'or_number' => 'OR-02002',
        'student_id' => $student->id,
        'cashier_id' => $cashierB->id,
        'total_amount' => 3300,
        'payment_mode' => 'gcash',
        'reference_no' => null,
        'remarks' => null,
    ]);

    $transactionTwo->items()->create([
        'fee_id' => null,
        'inventory_item_id' => null,
        'description' => 'Enrollment Downpayment',
        'amount' => 3300,
    ]);

    $transactionThree = Transaction::query()->create([
        'or_number' => 'OR-02003',
        'student_id' => $student->id,
        'cashier_id' => $cashierB->id,
        'total_amount' => 4100,
        'payment_mode' => 'gcash',
        'reference_no' => null,
        'remarks' => null,
    ]);

    $transactionThree->items()->create([
        'fee_id' => null,
        'inventory_item_id' => null,
        'description' => 'Downpayment',
        'amount' => 4100,
    ]);

    Transaction::query()->whereKey($transactionOne->id)->update([
        'created_at' => '2026-02-20 07:30:00',
        'updated_at' => '2026-02-20 07:30:00',
    ]);

    Transaction::query()->whereKey($transactionTwo->id)->update([
        'created_at' => '2026-02-20 10:15:00',
        'updated_at' => '2026-02-20 10:15:00',
    ]);

    Transaction::query()->whereKey($transactionThree->id)->update([
        'created_at' => '2026-02-21 08:45:00',
        'updated_at' => '2026-02-21 08:45:00',
    ]);

    $this->get("/finance/daily-reports?cashier_id={$cashierB->id}&payment_mode=gcash&date_from=2026-02-20&date_to=2026-02-20")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/daily-reports/index')
            ->has('transaction_rows.data', 1)
            ->where('transaction_rows.total', 1)
            ->where('transaction_rows.data.0.or_number', 'OR-02002')
            ->where('summary.transaction_count', 1)
            ->where('summary.gross_collection', 3300)
            ->where('summary.cash_on_hand', 0)
            ->where('summary.digital_collection', 3300)
            ->where('filters.cashier_id', $cashierB->id)
            ->where('filters.payment_mode', 'gcash')
            ->where('filters.date_from', '2026-02-20')
            ->where('filters.date_to', '2026-02-20')
        );
});

test('finance daily reports filters by school year', function () {
    $schoolYearOne = AcademicYear::query()->create([
        'name' => '2024-2025',
        'start_date' => '2024-06-01',
        'end_date' => '2025-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);

    AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $student = Student::query()->create([
        'lrn' => '771234567890',
        'first_name' => 'Daily',
        'last_name' => 'Scope',
    ]);

    $transactionOne = Transaction::query()->create([
        'or_number' => 'OR-DAILY-SY-1',
        'student_id' => $student->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 1800,
        'payment_mode' => 'cash',
    ]);

    $transactionOne->items()->create([
        'description' => 'Assessment Fee',
        'amount' => 1800,
    ]);

    $transactionTwo = Transaction::query()->create([
        'or_number' => 'OR-DAILY-SY-2',
        'student_id' => $student->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 2600,
        'payment_mode' => 'gcash',
    ]);

    $transactionTwo->items()->create([
        'description' => 'Assessment Fee',
        'amount' => 2600,
    ]);

    Transaction::query()->whereKey($transactionOne->id)->update([
        'created_at' => '2024-10-10 09:00:00',
        'updated_at' => '2024-10-10 09:00:00',
    ]);
    Transaction::query()->whereKey($transactionTwo->id)->update([
        'created_at' => '2025-10-10 09:00:00',
        'updated_at' => '2025-10-10 09:00:00',
    ]);

    $this->get("/finance/daily-reports?academic_year_id={$schoolYearOne->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/daily-reports/index')
            ->where('selected_school_year_id', $schoolYearOne->id)
            ->where('summary.transaction_count', 1)
            ->where('summary.gross_collection', 1800)
            ->where('filters.academic_year_id', $schoolYearOne->id)
            ->has('transaction_rows.data', 1)
            ->where('transaction_rows.data.0.or_number', 'OR-DAILY-SY-1')
        );
});

test('finance daily reports school year filter includes transaction mapped by ledger year', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'upcoming',
        'current_quarter' => 'pre_opening',
    ]);

    $student = Student::query()->create([
        'lrn' => '781234567890',
        'first_name' => 'Sofia',
        'last_name' => 'Castro',
    ]);

    $transaction = Transaction::query()->create([
        'or_number' => 'OR-DAILY-LEDGER-1',
        'student_id' => $student->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 3200,
        'payment_mode' => 'cash',
    ]);

    $transaction->items()->create([
        'description' => 'Enrollment Downpayment',
        'amount' => 3200,
    ]);

    Transaction::query()->whereKey($transaction->id)->update([
        'created_at' => '2026-03-12 08:00:00',
        'updated_at' => '2026-03-12 08:00:00',
    ]);

    LedgerEntry::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $schoolYear->id,
        'date' => '2026-03-12',
        'description' => 'Payment (OR-DAILY-LEDGER-1)',
        'debit' => null,
        'credit' => 3200,
        'running_balance' => -3200,
        'reference_id' => $transaction->id,
    ]);

    $this->get("/finance/daily-reports?academic_year_id={$schoolYear->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/daily-reports/index')
            ->where('selected_school_year_id', $schoolYear->id)
            ->where('summary.transaction_count', 1)
            ->where('summary.gross_collection', 3200)
            ->where('filters.academic_year_id', $schoolYear->id)
            ->has('transaction_rows.data', 1)
            ->where('transaction_rows.data.0.or_number', 'OR-DAILY-LEDGER-1')
        );
});

test('finance daily reports handles school year filters without date bounds', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => null,
        'end_date' => null,
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $student = Student::query()->create([
        'lrn' => '791234567890',
        'first_name' => 'Null',
        'last_name' => 'Range',
    ]);

    $transaction = Transaction::query()->create([
        'or_number' => 'OR-DAILY-NULL-1',
        'student_id' => $student->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 2750,
        'payment_mode' => 'cash',
    ]);

    $transaction->items()->create([
        'description' => 'Assessment Fee',
        'amount' => 2750,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $schoolYear->id,
        'date' => '2026-08-15',
        'description' => 'Payment (OR-DAILY-NULL-1)',
        'debit' => null,
        'credit' => 2750,
        'running_balance' => -2750,
        'reference_id' => $transaction->id,
    ]);

    $this->get("/finance/daily-reports?academic_year_id={$schoolYear->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/daily-reports/index')
            ->where('selected_school_year_id', $schoolYear->id)
            ->where('summary.transaction_count', 1)
            ->where('summary.gross_collection', 2750)
            ->where('transaction_rows.data.0.or_number', 'OR-DAILY-NULL-1')
        );
});

test('finance daily reports can export xlsx workbook with structured summary and transaction sheets', function () {
    $schoolYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '3',
    ]);

    $cashier = User::factory()->finance()->create([
        'first_name' => 'Aira',
        'last_name' => 'Santos',
    ]);
    $cashierTwo = User::factory()->finance()->create([
        'first_name' => 'Noel',
        'last_name' => 'Garcia',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);

    $tuitionFee = Fee::query()->create([
        'grade_level_id' => $gradeLevel->id,
        'academic_year_id' => $schoolYear->id,
        'type' => 'tuition',
        'name' => 'Tuition Fee',
        'amount' => 5500,
    ]);

    $student = Student::query()->create([
        'lrn' => '801234567890',
        'first_name' => 'Mina',
        'last_name' => 'Lopez',
    ]);

    $transaction = Transaction::query()->create([
        'or_number' => 'OR-EXP-0001',
        'student_id' => $student->id,
        'cashier_id' => $cashier->id,
        'total_amount' => 5500,
        'payment_mode' => 'cash',
    ]);

    $transaction->items()->create([
        'fee_id' => $tuitionFee->id,
        'description' => 'Tuition Payment',
        'amount' => 5500,
    ]);

    $transactionTwo = Transaction::query()->create([
        'or_number' => 'OR-EXP-0002',
        'student_id' => $student->id,
        'cashier_id' => $cashierTwo->id,
        'total_amount' => 3500,
        'payment_mode' => 'gcash',
    ]);

    $transactionTwo->items()->create([
        'fee_id' => null,
        'description' => 'Enrollment Downpayment',
        'amount' => 3000,
    ]);
    $transactionTwo->items()->create([
        'fee_id' => null,
        'description' => 'Miscellaneous Payment',
        'amount' => 500,
    ]);

    $transactionThree = Transaction::query()->create([
        'or_number' => 'OR-EXP-0003',
        'student_id' => $student->id,
        'cashier_id' => $cashierTwo->id,
        'total_amount' => 1200,
        'payment_mode' => 'cash',
        'status' => 'voided',
    ]);

    $transactionThree->items()->create([
        'fee_id' => null,
        'description' => 'Miscellaneous Payment',
        'amount' => 1200,
    ]);

    Transaction::query()->whereKey($transaction->id)->update([
        'created_at' => '2025-07-15 10:30:00',
        'updated_at' => '2025-07-15 10:30:00',
    ]);
    Transaction::query()->whereKey($transactionTwo->id)->update([
        'created_at' => '2025-07-15 11:30:00',
        'updated_at' => '2025-07-15 11:30:00',
    ]);
    Transaction::query()->whereKey($transactionThree->id)->update([
        'created_at' => '2025-07-15 12:30:00',
        'updated_at' => '2025-07-15 12:30:00',
    ]);

    $response = $this->get("/finance/daily-reports/export?academic_year_id={$schoolYear->id}&date_from=2025-07-15&date_to=2025-07-15");

    $response->assertSuccessful();
    expect((string) $response->headers->get('content-disposition'))->toContain('daily-reports-');
    expect((string) $response->headers->get('content-disposition'))->toContain('.xlsx');

    $fileResponse = $response->baseResponse;
    expect($fileResponse)->toBeInstanceOf(BinaryFileResponse::class);

    $spreadsheet = IOFactory::load($fileResponse->getFile()->getPathname());
    $summarySheet = $spreadsheet->getSheetByName('Summary');
    $transactionsSheet = $spreadsheet->getSheetByName('Transactions');

    expect($summarySheet instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet)->toBeTrue();
    expect($transactionsSheet instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet)->toBeTrue();

    expect((string) $summarySheet?->getCell('A1')->getCalculatedValue())->toBe('Daily Collection Report');
    expect((string) $summarySheet?->getCell('B4')->getCalculatedValue())->toBe('2025-2026');
    expect((string) $summarySheet?->getCell('B5')->getCalculatedValue())->toBe('All Cashiers');
    expect((string) $summarySheet?->getCell('B6')->getCalculatedValue())->toBe('All Payment Modes');
    expect((string) $summarySheet?->getCell('B7')->getCalculatedValue())->toBe('2025-07-15');
    expect((string) $summarySheet?->getCell('B8')->getCalculatedValue())->toBe('2025-07-15');
    expect((float) $summarySheet?->getCell('B11')->getCalculatedValue())->toBe(3.0);
    expect((float) $summarySheet?->getCell('B12')->getCalculatedValue())->toBe(9000.0);
    expect((float) $summarySheet?->getCell('B13')->getCalculatedValue())->toBe(5500.0);
    expect((float) $summarySheet?->getCell('B14')->getCalculatedValue())->toBe(3500.0);
    expect((float) $summarySheet?->getCell('B15')->getCalculatedValue())->toBe(1200.0);
    expect((string) $summarySheet?->getCell('A20')->getCalculatedValue())->toBe('Tuition Fees');
    expect((float) $summarySheet?->getCell('C20')->getCalculatedValue())->toBe(5500.0);
    expect((string) $summarySheet?->getCell('A24')->getCalculatedValue())->toBe('Cashier Breakdown');
    expect((string) $summarySheet?->getCell('A25')->getCalculatedValue())->toBe('Cashier');
    expect((string) $summarySheet?->getCell('A26')->getCalculatedValue())->toBe('Aira Santos');
    expect((float) $summarySheet?->getCell('B26')->getCalculatedValue())->toBe(1.0);
    expect((float) $summarySheet?->getCell('C26')->getCalculatedValue())->toBe(5500.0);
    expect((float) $summarySheet?->getCell('D26')->getCalculatedValue())->toBe(5500.0);
    expect((float) $summarySheet?->getCell('E26')->getCalculatedValue())->toBe(0.0);
    expect((float) $summarySheet?->getCell('F26')->getCalculatedValue())->toBe(0.0);
    expect((float) $summarySheet?->getCell('G26')->getCalculatedValue())->toBe(5500.0);
    expect((string) $summarySheet?->getCell('A27')->getCalculatedValue())->toBe('Noel Garcia');
    expect((float) $summarySheet?->getCell('B27')->getCalculatedValue())->toBe(2.0);
    expect((float) $summarySheet?->getCell('C27')->getCalculatedValue())->toBe(3500.0);
    expect((float) $summarySheet?->getCell('D27')->getCalculatedValue())->toBe(0.0);
    expect((float) $summarySheet?->getCell('E27')->getCalculatedValue())->toBe(3500.0);
    expect((float) $summarySheet?->getCell('F27')->getCalculatedValue())->toBe(1200.0);
    expect((float) $summarySheet?->getCell('G27')->getCalculatedValue())->toBe(2300.0);

    expect((string) $transactionsSheet?->getCell('A1')->getCalculatedValue())->toBe('Transaction Details');
    expect((string) $transactionsSheet?->getCell('A3')->getCalculatedValue())->toBe('OR Number');
    expect((string) $transactionsSheet?->getCell('A4')->getCalculatedValue())->toBe('OR-EXP-0003');
    expect((string) $transactionsSheet?->getCell('B4')->getCalculatedValue())->toBe('Mina Lopez');
    expect((string) $transactionsSheet?->getCell('A5')->getCalculatedValue())->toBe('OR-EXP-0002');
    expect((string) $transactionsSheet?->getCell('C5')->getCalculatedValue())->toBe('Enrollment Downpayment');
    expect((float) $transactionsSheet?->getCell('F5')->getCalculatedValue())->toBe(3000.0);
    expect((string) $transactionsSheet?->getCell('A6')->getCalculatedValue())->toBe('OR-EXP-0002');
    expect((string) $transactionsSheet?->getCell('C6')->getCalculatedValue())->toBe('Miscellaneous Payment');
    expect((float) $transactionsSheet?->getCell('F6')->getCalculatedValue())->toBe(500.0);
    expect((float) $transactionsSheet?->getCell('F4')->getCalculatedValue())->toBe(1200.0);

    $airaSheet = $spreadsheet->getSheetByName('Aira Santos');
    $noelSheet = $spreadsheet->getSheetByName('Noel Garcia');

    expect($airaSheet instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet)->toBeTrue();
    expect($noelSheet instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet)->toBeTrue();
    expect((string) $airaSheet?->getCell('A4')->getCalculatedValue())->toBe('OR-EXP-0001');
    expect((string) $noelSheet?->getCell('A4')->getCalculatedValue())->toBe('OR-EXP-0003');
    expect((string) $noelSheet?->getCell('A5')->getCalculatedValue())->toBe('OR-EXP-0002');
    expect((string) $noelSheet?->getCell('A6')->getCalculatedValue())->toBe('OR-EXP-0002');
});
