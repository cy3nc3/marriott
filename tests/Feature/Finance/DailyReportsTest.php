<?php

use App\Models\Fee;
use App\Models\GradeLevel;
use App\Models\InventoryItem;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

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
            ->has('transaction_rows', 3)
            ->where('transaction_rows.0.or_number', 'OR-01023')
            ->where('transaction_rows.1.or_number', 'OR-01022')
            ->where('transaction_rows.2.or_number', 'OR-01021')
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
            ->has('transaction_rows', 1)
            ->where('transaction_rows.0.or_number', 'OR-02002')
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
