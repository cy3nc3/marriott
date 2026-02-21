<?php

use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\LedgerEntry;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Carbon::setTestNow('2026-02-20 09:00:00');

    $this->finance = User::factory()->finance()->create();
    $this->actingAs($this->finance);
});

afterEach(function () {
    Carbon::setTestNow();
});

test('finance dashboard renders metrics from ledger transactions and billing schedules', function () {
    $academicYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '3',
    ]);

    $studentA = Student::query()->create([
        'lrn' => '101234567890',
        'first_name' => 'Ana',
        'last_name' => 'Lopez',
    ]);

    $studentB = Student::query()->create([
        'lrn' => '111234567890',
        'first_name' => 'Ben',
        'last_name' => 'Tan',
    ]);

    LedgerEntry::query()->create([
        'student_id' => $studentA->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2026-02-01',
        'description' => 'Tuition Assessment',
        'debit' => 20000,
        'credit' => null,
        'running_balance' => 20000,
        'reference_id' => null,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $studentB->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2026-02-01',
        'description' => 'Tuition Assessment',
        'debit' => 10000,
        'credit' => null,
        'running_balance' => 10000,
        'reference_id' => null,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $studentA->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2026-02-10',
        'description' => 'Payment OR-10001',
        'debit' => null,
        'credit' => 7500,
        'running_balance' => 12500,
        'reference_id' => null,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $studentB->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2026-02-12',
        'description' => 'Payment OR-10002',
        'debit' => null,
        'credit' => 2500,
        'running_balance' => 7500,
        'reference_id' => null,
    ]);

    $cashToday = Transaction::query()->create([
        'or_number' => 'OR-50001',
        'student_id' => $studentA->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 3200,
        'payment_mode' => 'cash',
        'reference_no' => null,
        'remarks' => null,
    ]);

    $gcashToday = Transaction::query()->create([
        'or_number' => 'OR-50002',
        'student_id' => $studentB->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 4100,
        'payment_mode' => 'gcash',
        'reference_no' => null,
        'remarks' => null,
    ]);

    $cashYesterday = Transaction::query()->create([
        'or_number' => 'OR-50003',
        'student_id' => $studentA->id,
        'cashier_id' => $this->finance->id,
        'total_amount' => 1200,
        'payment_mode' => 'cash',
        'reference_no' => null,
        'remarks' => null,
    ]);

    Transaction::query()->whereKey($cashToday->id)->update([
        'created_at' => '2026-02-20 08:10:00',
        'updated_at' => '2026-02-20 08:10:00',
    ]);

    Transaction::query()->whereKey($gcashToday->id)->update([
        'created_at' => '2026-02-20 08:20:00',
        'updated_at' => '2026-02-20 08:20:00',
    ]);

    Transaction::query()->whereKey($cashYesterday->id)->update([
        'created_at' => '2026-02-19 15:00:00',
        'updated_at' => '2026-02-19 15:00:00',
    ]);

    BillingSchedule::query()->create([
        'student_id' => $studentA->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'March Installment 1',
        'due_date' => '2026-03-10',
        'amount_due' => 5000,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    BillingSchedule::query()->create([
        'student_id' => $studentB->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'March Installment 2',
        'due_date' => '2026-03-20',
        'amount_due' => 7000,
        'amount_paid' => 2000,
        'status' => 'partially_paid',
    ]);

    BillingSchedule::query()->create([
        'student_id' => $studentA->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'March Installment 3',
        'due_date' => '2026-03-25',
        'amount_due' => 4000,
        'amount_paid' => 4000,
        'status' => 'paid',
    ]);

    BillingSchedule::query()->create([
        'student_id' => $studentB->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'February Installment',
        'due_date' => '2026-02-28',
        'amount_due' => 6000,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/dashboard')
            ->where('metrics.collection_efficiency_percent', 33.33)
            ->where('metrics.total_charges', 30000)
            ->where('metrics.total_payments', 10000)
            ->where('metrics.outstanding_balance', 20000)
            ->where('metrics.cash_in_drawer_today', 3200)
            ->where('metrics.revenue_forecast_next_month', 10000)
            ->where('metrics.forecast_month_label', 'March 2026')
        );
});
