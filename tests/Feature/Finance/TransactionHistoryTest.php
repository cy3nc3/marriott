<?php

use App\Models\Student;
use App\Models\Transaction;
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
            ->has('transactions', 2)
            ->where('transactions.0.or_number', 'OR-1002')
            ->where('transactions.0.entry_label', 'Tuition Partial + 1 more')
            ->where('transactions.0.payment_mode_label', 'GCash')
            ->where('transactions.1.or_number', 'OR-1001')
            ->where('transactions.1.entry_label', 'Enrollment Downpayment')
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
            ->has('transactions', 1)
            ->where('transactions.0.or_number', 'OR-2002')
            ->where('transactions.0.student_name', 'Jose Rizal')
            ->where('summary.count', 1)
            ->where('summary.posted_amount', 2500)
            ->where('filters.search', 'Rizal')
            ->where('filters.payment_mode', 'gcash')
            ->where('filters.date_from', '2026-02-20')
            ->where('filters.date_to', '2026-02-20')
        );
});
