<?php

use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\LedgerEntry;
use App\Models\Student;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->finance = User::factory()->finance()->create();
    $this->actingAs($this->finance);
});

test('finance student ledgers page renders selected student profile dues and entries', function () {
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
        'lrn' => '923456789012',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'guardian_name' => 'Maria Dela Cruz',
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'monthly',
        'downpayment' => 3000,
        'status' => 'partial_payment',
    ]);

    BillingSchedule::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'August Installment',
        'due_date' => '2025-08-15',
        'amount_due' => 2500,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    BillingSchedule::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'July Installment',
        'due_date' => '2025-07-15',
        'amount_due' => 2500,
        'amount_paid' => 2500,
        'status' => 'paid',
    ]);

    LedgerEntry::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2025-06-10',
        'description' => 'Tuition Fee Assessment',
        'debit' => 20000,
        'credit' => null,
        'running_balance' => 20000,
        'reference_id' => null,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2025-06-12',
        'description' => 'Payment (OR-2026-0001)',
        'debit' => null,
        'credit' => 3000,
        'running_balance' => 17000,
        'reference_id' => null,
    ]);

    $this->get("/finance/student-ledgers?search=Juan&student_id={$student->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/student-ledgers/index')
            ->has('students', 1)
            ->where('selected_student.name', 'Juan Dela Cruz')
            ->where('selected_student.lrn', '923456789012')
            ->where('selected_student.payment_plan_label', 'Monthly')
            ->has('dues_schedule', 1)
            ->where('dues_schedule.0.description', 'August Installment')
            ->has('ledger_entries', 2)
            ->where('summary.total_charges', 20000)
            ->where('summary.total_payments', 3000)
            ->where('summary.outstanding_balance', 17000)
        );
});

test('finance student ledgers filters entries and can include paid dues', function () {
    $academicYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 8',
        'level_order' => 8,
    ]);

    $student = Student::query()->create([
        'lrn' => '823456789012',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'quarterly',
        'downpayment' => 5000,
        'status' => 'partial_payment',
    ]);

    BillingSchedule::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'First Quarter',
        'due_date' => '2026-06-15',
        'amount_due' => 7500,
        'amount_paid' => 7500,
        'status' => 'paid',
    ]);

    BillingSchedule::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'Second Quarter',
        'due_date' => '2026-09-15',
        'amount_due' => 7500,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    LedgerEntry::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2026-06-01',
        'description' => 'Tuition Assessment',
        'debit' => 18000,
        'credit' => null,
        'running_balance' => 18000,
        'reference_id' => null,
    ]);

    LedgerEntry::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'date' => '2026-06-15',
        'description' => 'Payment (OR-2026-0100)',
        'debit' => null,
        'credit' => 7500,
        'running_balance' => 10500,
        'reference_id' => null,
    ]);

    $this->get("/finance/student-ledgers?student_id={$student->id}&show_paid_dues=1&entry_type=payment&date_from=2026-06-10&date_to=2026-06-20")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('finance/student-ledgers/index')
            ->has('dues_schedule', 2)
            ->has('ledger_entries', 1)
            ->where('ledger_entries.0.entry_type', 'payment')
            ->where('summary.total_charges', 0)
            ->where('summary.total_payments', 7500)
            ->where('filters.show_paid_dues', true)
            ->where('filters.entry_type', 'payment')
            ->where('filters.date_from', '2026-06-10')
            ->where('filters.date_to', '2026-06-20')
        );
});
