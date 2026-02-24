<?php

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\BillingSchedule;
use App\Models\FinanceDueReminderDispatch;
use App\Models\FinanceDueReminderRule;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;

test('finance due reminder command sends announcement to parent users', function () {
    Carbon::setTestNow('2026-03-01 07:00:00');

    $finance = User::factory()->finance()->create();
    $parent = User::factory()->create([
        'role' => UserRole::PARENT,
        'is_active' => true,
    ]);

    $academicYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $student = Student::query()->create([
        'lrn' => '901234567891',
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
    ]);

    $parent->students()->attach($student->id);

    BillingSchedule::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'March Installment',
        'due_date' => '2026-03-04',
        'amount_due' => 2500,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    FinanceDueReminderRule::query()->create([
        'days_before_due' => 3,
        'is_active' => true,
        'created_by' => $finance->id,
        'updated_by' => $finance->id,
    ]);

    $this->artisan('finance:send-due-reminders', [
        '--date' => '2026-03-01',
    ])->assertSuccessful();

    expect(Announcement::query()->count())->toBe(1);
    expect(FinanceDueReminderDispatch::query()->count())->toBe(1);

    $announcement = Announcement::query()->first();

    expect($announcement)->not()->toBeNull();
    expect($announcement?->target_roles)->toBe([UserRole::PARENT->value]);
    expect($announcement?->target_user_ids)->toBe([$parent->id]);

    Carbon::setTestNow();
});

test('finance due reminder command does not send duplicate dispatches for same date', function () {
    Carbon::setTestNow('2026-03-01 07:00:00');

    $finance = User::factory()->finance()->create();
    $parent = User::factory()->create([
        'role' => UserRole::PARENT,
        'is_active' => true,
    ]);

    $academicYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $student = Student::query()->create([
        'lrn' => '901234567892',
        'first_name' => 'Mark',
        'last_name' => 'Santos',
    ]);

    $parent->students()->attach($student->id);

    BillingSchedule::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'description' => 'March Installment',
        'due_date' => '2026-03-04',
        'amount_due' => 2500,
        'amount_paid' => 0,
        'status' => 'unpaid',
    ]);

    FinanceDueReminderRule::query()->create([
        'days_before_due' => 3,
        'is_active' => true,
        'created_by' => $finance->id,
        'updated_by' => $finance->id,
    ]);

    $this->artisan('finance:send-due-reminders', [
        '--date' => '2026-03-01',
    ])->assertSuccessful();

    $this->artisan('finance:send-due-reminders', [
        '--date' => '2026-03-01',
    ])->assertSuccessful();

    expect(Announcement::query()->count())->toBe(1);
    expect(FinanceDueReminderDispatch::query()->count())->toBe(1);

    Carbon::setTestNow();
});
