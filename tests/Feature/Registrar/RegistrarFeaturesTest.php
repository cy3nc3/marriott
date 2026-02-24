<?php

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\FinalGrade;
use App\Models\GradeLevel;
use App\Models\PermanentRecord;
use App\Models\RemedialRecord;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentDeparture;
use App\Models\StudentDiscount;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TeacherSubject;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->registrar = User::factory()->registrar()->create();
    $this->actingAs($this->registrar);

    $this->academicYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $this->gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);
});

test('registrar sf1 upload reconciles student directory by lrn', function () {
    $section = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Rizal',
    ]);

    $student = Student::query()->create([
        'lrn' => '123456789012',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'is_lis_synced' => false,
        'sync_error_flag' => false,
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'pending_intake',
    ]);

    $csvContent = "LRN,First Name,Last Name,Gender\n".
        "123456789012,Juanito,Dela Cruz,Male\n".
        "999999999999,Unknown,Student,Female\n";

    $file = UploadedFile::fake()->createWithContent('sf1.csv', $csvContent);

    $this->post('/registrar/student-directory/sf1-upload', [
        'sf1_file' => $file,
    ])->assertRedirect();

    $student->refresh();

    expect($student->is_lis_synced)->toBeTrue();
    expect($student->first_name)->toBe('Juanito');
    expect(Setting::get('registrar_sf1_last_upload_name'))->toBe('sf1.csv');

    $this->get('/registrar/student-directory')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/student-directory/index')
            ->where('summary.matched', 1)
            ->where('summary.pending', 0)
            ->where('summary.discrepancy', 0)
        );
});

test('registrar enrollment page filters queue by selected school year', function () {
    $completedYear = AcademicYear::query()->create([
        'name' => '2024-2025',
        'start_date' => '2024-06-01',
        'end_date' => '2025-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);

    $sectionCurrent = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Current',
    ]);

    $sectionCompleted = Section::query()->create([
        'academic_year_id' => $completedYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Completed',
    ]);

    $currentStudent = Student::query()->create([
        'lrn' => '111122223333',
        'first_name' => 'Current',
        'last_name' => 'Student',
    ]);

    $completedStudent = Student::query()->create([
        'lrn' => '444455556666',
        'first_name' => 'Completed',
        'last_name' => 'Student',
    ]);

    Enrollment::query()->create([
        'student_id' => $currentStudent->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $sectionCurrent->id,
        'payment_term' => 'monthly',
        'downpayment' => 0,
        'status' => 'pending_intake',
    ]);

    Enrollment::query()->create([
        'student_id' => $completedStudent->id,
        'academic_year_id' => $completedYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $sectionCompleted->id,
        'payment_term' => 'monthly',
        'downpayment' => 0,
        'status' => 'pending_intake',
    ]);

    $this->get("/registrar/enrollment?academic_year_id={$completedYear->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/enrollment/index')
            ->where('selected_school_year_id', $completedYear->id)
            ->where('filters.academic_year_id', $completedYear->id)
            ->has('enrollments', 1)
            ->where('enrollments.0.lrn', '444455556666')
        );
});

test('registrar student directory filters students by selected school year', function () {
    $completedYear = AcademicYear::query()->create([
        'name' => '2024-2025',
        'start_date' => '2024-06-01',
        'end_date' => '2025-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);

    $sectionCurrent = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Current',
    ]);

    $sectionCompleted = Section::query()->create([
        'academic_year_id' => $completedYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Completed',
    ]);

    $currentStudent = Student::query()->create([
        'lrn' => '777788889999',
        'first_name' => 'Current',
        'last_name' => 'Directory',
        'is_lis_synced' => true,
        'sync_error_flag' => false,
    ]);

    $completedStudent = Student::query()->create([
        'lrn' => '999900001111',
        'first_name' => 'Completed',
        'last_name' => 'Directory',
        'is_lis_synced' => false,
        'sync_error_flag' => false,
    ]);

    Enrollment::query()->create([
        'student_id' => $currentStudent->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $sectionCurrent->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    Enrollment::query()->create([
        'student_id' => $completedStudent->id,
        'academic_year_id' => $completedYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $sectionCompleted->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    $this->get("/registrar/student-directory?academic_year_id={$completedYear->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/student-directory/index')
            ->where('selected_school_year_id', $completedYear->id)
            ->has('students', 1)
            ->where('students.0.lrn', '999900001111')
            ->where('summary.matched', 0)
            ->where('summary.pending', 1)
        );
});

test('registrar dashboard shows lis sync pie and payment method trends', function () {
    $section = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Mabini',
    ]);

    $queueDefinitions = [
        ['status' => 'pending', 'payment_term' => 'monthly', 'days_ago' => 1, 'is_lis_synced' => true, 'sync_error_flag' => false],
        ['status' => 'pending_intake', 'payment_term' => 'quarterly', 'days_ago' => 4, 'is_lis_synced' => false, 'sync_error_flag' => false],
        ['status' => 'for_cashier_payment', 'payment_term' => 'full', 'days_ago' => 10, 'is_lis_synced' => false, 'sync_error_flag' => true],
        ['status' => 'partial_payment', 'payment_term' => 'monthly', 'days_ago' => 20, 'is_lis_synced' => false, 'sync_error_flag' => false],
    ];

    $counter = 0;
    $createdStudents = collect();
    foreach ($queueDefinitions as $definition) {
        $counter++;
        $student = Student::query()->create([
            'lrn' => str_pad((string) (930000000000 + $counter), 12, '0', STR_PAD_LEFT),
            'first_name' => "Queue{$counter}",
            'last_name' => 'Student',
            'is_lis_synced' => $definition['is_lis_synced'],
            'sync_error_flag' => $definition['sync_error_flag'],
        ]);

        $enrollment = Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $section->id,
            'payment_term' => $definition['payment_term'],
            'downpayment' => 1000,
            'status' => $definition['status'],
        ]);

        $enrollment->created_at = now()->subDays($definition['days_ago']);
        $enrollment->updated_at = now()->subDays($definition['days_ago']);
        $enrollment->save();

        $createdStudents->push($student);
    }

    $droppedOnlyStudent = Student::query()->create([
        'lrn' => '930000000111',
        'first_name' => 'Dropped',
        'last_name' => 'Only',
        'is_lis_synced' => false,
        'sync_error_flag' => false,
    ]);

    Enrollment::query()->create([
        'student_id' => $droppedOnlyStudent->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'semi-annual',
        'downpayment' => 0,
        'status' => 'dropped',
    ]);

    $enrolledStudent = Student::query()->create([
        'lrn' => '930000000099',
        'first_name' => 'Synced',
        'last_name' => 'Learner',
        'is_lis_synced' => true,
        'sync_error_flag' => false,
    ]);

    Enrollment::query()->create([
        'student_id' => $enrolledStudent->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    $cashier = User::factory()->finance()->create();

    Transaction::query()->create([
        'or_number' => 'REG-OR-1001',
        'student_id' => $createdStudents[0]->id,
        'cashier_id' => $cashier->id,
        'total_amount' => 1000,
        'payment_mode' => 'cash',
    ]);

    Transaction::query()->create([
        'or_number' => 'REG-OR-1002',
        'student_id' => $createdStudents[1]->id,
        'cashier_id' => $cashier->id,
        'total_amount' => 1500,
        'payment_mode' => 'gcash',
    ]);

    Transaction::query()->create([
        'or_number' => 'REG-OR-1003',
        'student_id' => $createdStudents[2]->id,
        'cashier_id' => $cashier->id,
        'total_amount' => 2000,
        'payment_mode' => 'bank_transfer',
    ]);

    Transaction::query()->create([
        'or_number' => 'REG-OR-1004',
        'student_id' => $createdStudents[3]->id,
        'cashier_id' => $cashier->id,
        'total_amount' => 1200,
        'payment_mode' => 'cash',
    ]);

    Transaction::query()->create([
        'or_number' => 'REG-OR-1005',
        'student_id' => $enrolledStudent->id,
        'cashier_id' => $cashier->id,
        'total_amount' => 900,
        'payment_mode' => 'check',
    ]);

    Transaction::query()->create([
        'or_number' => 'REG-OR-1006',
        'student_id' => $droppedOnlyStudent->id,
        'cashier_id' => $cashier->id,
        'total_amount' => 600,
        'payment_mode' => 'gcash',
    ]);

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/dashboard')
            ->where('trends.0.id', 'lis-sync-distribution')
            ->where('trends.0.display', 'pie')
            ->where('trends.0.chart.rows', function ($rows): bool {
                $rowsByStatus = collect($rows)->keyBy('status');

                return count($rows) === 3
                    && (int) ($rowsByStatus['Synced']['students'] ?? -1) === 2
                    && (int) ($rowsByStatus['Pending']['students'] ?? -1) === 3
                    && (int) ($rowsByStatus['Errors']['students'] ?? -1) === 1
                    && (int) collect($rows)->sum('students') === 6;
            })
            ->where('trends.1.id', 'payment-method-mix')
            ->where('trends.1.display', 'pie')
            ->where('trends.1.chart.rows', function ($rows): bool {
                return count($rows) === 5
                    && ($rows[0]['method'] ?? null) === 'Cash'
                    && (int) ($rows[0]['transactions'] ?? -1) === 2
                    && ($rows[1]['method'] ?? null) === 'E-Wallet'
                    && (int) ($rows[1]['transactions'] ?? -1) === 1
                    && ($rows[2]['method'] ?? null) === 'Bank Transfer'
                    && (int) ($rows[2]['transactions'] ?? -1) === 1
                    && ($rows[3]['method'] ?? null) === 'Check'
                    && (int) ($rows[3]['transactions'] ?? -1) === 1
                    && ($rows[4]['method'] ?? null) === 'Other'
                    && (int) ($rows[4]['transactions'] ?? -1) === 0
                    && (int) collect($rows)->sum('transactions') === 5;
            })
            ->where('kpis.2.id', 'lis-sync-rate')
            ->where('kpis.2.value', '33.33%')
        );
});

test('registrar dashboard renders empty chart-safe payloads with no queue or transactions', function () {
    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/dashboard')
            ->where('kpis.0.value', 0)
            ->where('kpis.1.value', 0)
            ->where('kpis.2.value', '0.00%')
            ->where('kpis.3.value', 0)
            ->where('alerts.0.id', 'lis-sync')
            ->where('alerts.0.severity', 'critical')
            ->where('trends.0.id', 'lis-sync-distribution')
            ->where('trends.0.chart.rows', function ($rows): bool {
                return count($rows) === 3
                    && (int) collect($rows)->sum('students') === 0;
            })
            ->where('trends.1.id', 'payment-method-mix')
            ->where('trends.1.chart.rows', function ($rows): bool {
                return count($rows) === 5
                    && (int) collect($rows)->sum('transactions') === 0;
            })
        );
});

test('registrar enrollment intake supports create update and delete', function () {
    $lrn = '123123123123';
    $firstSection = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Emerald',
    ]);
    $secondSection = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Ruby',
    ]);

    Fee::query()->create([
        'grade_level_id' => $this->gradeLevel->id,
        'type' => 'tuition',
        'name' => 'Tuition Fee',
        'amount' => 7000,
    ]);

    Fee::query()->create([
        'grade_level_id' => $this->gradeLevel->id,
        'type' => 'miscellaneous',
        'name' => 'Miscellaneous Fee',
        'amount' => 2000,
    ]);

    $this->post('/registrar/enrollment', [
        'lrn' => $lrn,
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'emergency_contact' => '09171234567',
        'section_id' => $firstSection->id,
        'payment_term' => 'monthly',
        'downpayment' => 1500,
    ])->assertRedirect();

    $student = Student::query()->where('lrn', $lrn)->first();

    expect($student)->not->toBeNull();
    expect($student->user_id)->not->toBeNull();

    $enrollment = Enrollment::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->first();

    expect($enrollment)->not->toBeNull();
    expect($enrollment->status)->toBe('pending_intake');
    expect((float) $enrollment->downpayment)->toBe(1500.0);
    expect($enrollment->section_id)->toBe($firstSection->id);
    expect($enrollment->grade_level_id)->toBe($firstSection->grade_level_id);

    $monthlySchedules = BillingSchedule::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->orderBy('due_date')
        ->get();

    expect($monthlySchedules)->toHaveCount(9);
    expect($monthlySchedules->first()?->due_date?->toDateString())->toBe('2025-07-01');
    expect($monthlySchedules->last()?->due_date?->toDateString())->toBe('2026-03-01');
    expect(round((float) $monthlySchedules->sum('amount_due'), 2))->toBe(7500.0);
    expect($monthlySchedules->every(fn (BillingSchedule $billingSchedule): bool => $billingSchedule->status === 'unpaid'))->toBeTrue();

    expect(User::query()->where('email', "student.{$lrn}@marriott.edu")->exists())->toBeTrue();
    expect(User::query()->where('email', "parent.{$lrn}@marriott.edu")->exists())->toBeTrue();
    expect(User::query()->where('email', "student.{$lrn}@marriott.edu")->first()?->role?->value)->toBe(UserRole::STUDENT->value);
    expect(User::query()->where('email', "parent.{$lrn}@marriott.edu")->first()?->role?->value)->toBe(UserRole::PARENT->value);

    $this->patch("/registrar/enrollment/{$enrollment->id}", [
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'emergency_contact' => '09998887777',
        'section_id' => $secondSection->id,
        'payment_term' => 'quarterly',
        'downpayment' => 2500,
        'status' => 'for_cashier_payment',
    ])->assertRedirect();

    $enrollment->refresh();

    expect($enrollment->status)->toBe('for_cashier_payment');
    expect($enrollment->payment_term)->toBe('quarterly');
    expect((float) $enrollment->downpayment)->toBe(2500.0);
    expect($enrollment->section_id)->toBe($secondSection->id);
    expect($student->fresh()->last_name)->toBe('Reyes');

    $quarterlySchedules = BillingSchedule::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->orderBy('due_date')
        ->get();

    expect($quarterlySchedules)->toHaveCount(4);
    expect($quarterlySchedules->pluck('due_date')->map(fn ($date) => $date?->toDateString())->all())->toBe([
        '2025-07-01',
        '2025-10-01',
        '2025-12-01',
        '2026-03-01',
    ]);
    expect(round((float) $quarterlySchedules->sum('amount_due'), 2))->toBe(6500.0);

    $this->patch("/registrar/enrollment/{$enrollment->id}", [
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'emergency_contact' => '09998887777',
        'section_id' => '',
        'payment_term' => 'semi-annual',
        'downpayment' => 3000,
        'status' => 'for_cashier_payment',
    ])->assertRedirect();

    $semiAnnualSchedules = BillingSchedule::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->orderBy('due_date')
        ->get();

    expect($semiAnnualSchedules)->toHaveCount(2);
    expect($semiAnnualSchedules->pluck('due_date')->map(fn ($date) => $date?->toDateString())->all())->toBe([
        '2025-07-01',
        '2026-03-01',
    ]);
    expect(round((float) $semiAnnualSchedules->sum('amount_due'), 2))->toBe(6000.0);

    $this->patch("/registrar/enrollment/{$enrollment->id}", [
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'emergency_contact' => '09998887777',
        'section_id' => '',
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'for_cashier_payment',
    ])->assertRedirect();

    expect(BillingSchedule::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->count())->toBe(0);

    $this->delete("/registrar/enrollment/{$enrollment->id}")
        ->assertRedirect();

    expect(Enrollment::query()->whereKey($enrollment->id)->exists())->toBeFalse();
});

test('registrar enrollment intake rejects already enrolled student in active year', function () {
    $student = Student::query()->create([
        'lrn' => '123456123456',
        'first_name' => 'Already',
        'last_name' => 'Enrolled',
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => null,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    $beforeCount = Enrollment::query()->count();

    $this->post('/registrar/enrollment', [
        'lrn' => $student->lrn,
        'first_name' => 'Already',
        'last_name' => 'Enrolled',
        'emergency_contact' => '09999999999',
        'payment_term' => 'cash',
        'downpayment' => 0,
    ])->assertRedirect()
        ->assertSessionHas('error');

    expect(Enrollment::query()->count())->toBe($beforeCount);
});

test('billing schedules are not regenerated when payment activity already exists', function () {
    Fee::query()->create([
        'grade_level_id' => $this->gradeLevel->id,
        'type' => 'tuition',
        'name' => 'Tuition Fee',
        'amount' => 9000,
    ]);

    $lrn = '888777666555';

    $this->post('/registrar/enrollment', [
        'lrn' => $lrn,
        'first_name' => 'Lara',
        'last_name' => 'Mercado',
        'emergency_contact' => '09170000000',
        'payment_term' => 'monthly',
        'downpayment' => 1000,
    ])->assertRedirect();

    $student = Student::query()->where('lrn', $lrn)->firstOrFail();
    $enrollment = Enrollment::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->firstOrFail();

    $beforeSchedules = BillingSchedule::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->orderBy('due_date')
        ->get();

    expect($beforeSchedules)->toHaveCount(9);

    $firstSchedule = $beforeSchedules->first();

    expect($firstSchedule)->not->toBeNull();

    $firstSchedule->update([
        'amount_paid' => 500,
        'status' => 'partially_paid',
    ]);

    $this->patch("/registrar/enrollment/{$enrollment->id}", [
        'first_name' => 'Lara',
        'last_name' => 'Mercado',
        'emergency_contact' => '09170000000',
        'payment_term' => 'quarterly',
        'downpayment' => 1500,
        'status' => 'for_cashier_payment',
    ])->assertRedirect();

    $afterSchedules = BillingSchedule::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->orderBy('due_date')
        ->get();

    expect($afterSchedules)->toHaveCount(9);
    expect($afterSchedules->first()?->id)->toBe($firstSchedule->id);
    expect((float) $afterSchedules->first()?->amount_paid)->toBe(500.0);
    expect($afterSchedules->first()?->status)->toBe('partially_paid');
    expect($afterSchedules->pluck('description')->contains('August Installment'))->toBeTrue();
});

test('billing schedules apply student discount on assessment total before downpayment', function () {
    $section = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Emerald',
    ]);

    Fee::query()->create([
        'grade_level_id' => $this->gradeLevel->id,
        'academic_year_id' => $this->academicYear->id,
        'type' => 'tuition',
        'name' => 'Tuition Fee',
        'amount' => 7000,
    ]);
    Fee::query()->create([
        'grade_level_id' => $this->gradeLevel->id,
        'academic_year_id' => $this->academicYear->id,
        'type' => 'miscellaneous',
        'name' => 'Miscellaneous Fee',
        'amount' => 2000,
    ]);
    Fee::query()->create([
        'grade_level_id' => $this->gradeLevel->id,
        'academic_year_id' => $this->academicYear->id,
        'type' => 'books_modules',
        'name' => 'Books Fee',
        'amount' => 500,
    ]);

    $this->post('/registrar/enrollment', [
        'lrn' => '899977766655',
        'first_name' => 'Nora',
        'last_name' => 'Castro',
        'emergency_contact' => '09171231234',
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
    ])->assertRedirect();

    $student = Student::query()->where('lrn', '899977766655')->firstOrFail();
    $enrollment = Enrollment::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->firstOrFail();

    $discount = Discount::query()->create([
        'name' => 'Academic Scholarship',
        'type' => 'percentage',
        'value' => 10,
    ]);

    StudentDiscount::query()->create([
        'student_id' => $student->id,
        'discount_id' => $discount->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    $this->patch("/registrar/enrollment/{$enrollment->id}", [
        'first_name' => 'Nora',
        'last_name' => 'Castro',
        'emergency_contact' => '09171231234',
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'for_cashier_payment',
    ])->assertRedirect();

    $discountedSchedules = BillingSchedule::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->get();

    expect($discountedSchedules)->toHaveCount(9);
    expect(round((float) $discountedSchedules->sum('amount_due'), 2))->toBe(7100.0);
});

test('registrar remedial entry stores recomputed grades and updates student flag', function () {
    $student = Student::query()->create([
        'lrn' => '321321321321',
        'first_name' => 'Carlo',
        'last_name' => 'Reyes',
        'is_for_remedial' => true,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $this->gradeLevel->id,
        'subject_code' => 'MATH7',
        'subject_name' => 'Mathematics 7',
    ]);

    $this->post('/registrar/remedial-entry', [
        'academic_year_id' => $this->academicYear->id,
        'student_id' => $student->id,
        'save_mode' => 'submitted',
        'records' => [
            [
                'subject_id' => $subject->id,
                'final_rating' => 70,
                'remedial_class_mark' => 80,
            ],
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('remedial_records', [
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'academic_year_id' => $this->academicYear->id,
        'status' => 'passed',
    ]);

    $record = RemedialRecord::query()
        ->where('student_id', $student->id)
        ->where('subject_id', $subject->id)
        ->first();

    expect((float) $record->recomputed_final_grade)->toBe(75.0);
    expect($student->fresh()->is_for_remedial)->toBeFalse();

    $this->get('/registrar/remedial-entry')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/remedial-entry/index')
        );
});

test('registrar batch promotion review resolves held conditional cases', function () {
    $pastYear = AcademicYear::query()->create([
        'name' => '2024-2025',
        'start_date' => '2024-06-01',
        'end_date' => '2025-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);

    $student = Student::query()->create([
        'lrn' => '566677778888',
        'first_name' => 'Jessa',
        'last_name' => 'Torres',
        'is_for_remedial' => true,
    ]);

    $record = PermanentRecord::query()->create([
        'student_id' => $student->id,
        'school_name' => 'Marriott School',
        'academic_year_id' => $pastYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'general_average' => 74.25,
        'status' => 'conditional',
        'failed_subject_count' => 1,
        'remarks' => 'Conditional from prior year',
    ]);

    $this->post('/registrar/batch-promotion/review', [
        'permanent_record_id' => $record->id,
        'decision' => 'promoted',
        'note' => 'Resolved after registrar review',
    ])->assertRedirect();

    $record->refresh();

    expect($record->status)->toBe('promoted');
    expect($record->conditional_resolved_at)->not->toBeNull();
    expect($record->conditional_resolution_notes)->toBe('Resolved after registrar review');
    expect($student->fresh()->is_for_remedial)->toBeFalse();
});

test('registrar batch promotion and student departure pages render server props', function () {
    $this->get('/registrar/batch-promotion')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/batch-promotion/index')
            ->has('run_summary')
            ->has('conditional_queue')
            ->has('held_for_review_queue')
            ->has('grade_completeness_issues')
        );

    $this->get('/registrar/student-departure')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/student-departure/index')
            ->has('student_lookup')
            ->has('recent_departures')
        );
});

test('registrar student departure stores transfer out and sets account expiry', function () {
    $nextYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    $studentUser = User::factory()->student()->create([
        'email' => 'student.transfer@example.com',
    ]);

    $student = Student::query()->create([
        'user_id' => $studentUser->id,
        'lrn' => '777788889999',
        'first_name' => 'Marco',
        'last_name' => 'Diaz',
    ]);

    $section = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Bonifacio',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 500,
        'status' => 'enrolled',
    ]);

    $this->post('/registrar/student-departure', [
        'student_id' => $student->id,
        'enrollment_id' => $enrollment->id,
        'reason' => 'transfer_out',
        'effective_date' => '2026-02-20',
        'remarks' => 'Transferred to another school.',
    ])->assertRedirect();

    expect(StudentDeparture::query()->where('student_id', $student->id)->exists())->toBeTrue();

    $departure = StudentDeparture::query()
        ->where('student_id', $student->id)
        ->latest('id')
        ->first();

    expect($departure->reason)->toBe('transfer_out');
    expect($enrollment->fresh()->status)->toBe('transferred_out');
    expect($studentUser->fresh()->access_expires_at?->toDateString())->toBe($nextYear->start_date);
});

test('reenrollment clears student account expiry and reactivates access', function () {
    $studentUser = User::factory()->student()->create([
        'email' => 'student.reactivate@example.com',
        'is_active' => false,
        'access_expires_at' => now()->subDay(),
    ]);

    $student = Student::query()->create([
        'user_id' => $studentUser->id,
        'lrn' => '888899990000',
        'first_name' => 'Lara',
        'last_name' => 'Cruz',
    ]);

    $this->post('/registrar/enrollment', [
        'lrn' => $student->lrn,
        'first_name' => 'Lara',
        'last_name' => 'Cruz',
        'emergency_contact' => '09170000000',
        'payment_term' => 'monthly',
        'downpayment' => 1000,
    ])->assertRedirect();

    $studentUser->refresh();

    expect($studentUser->is_active)->toBeTrue();
    expect($studentUser->access_expires_at)->toBeNull();
});

test('remedial submission resolves conditional status using annual failed subjects', function () {
    $teacher = User::factory()->teacher()->create();

    $section = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Rizal',
        'adviser_id' => $teacher->id,
    ]);

    $student = Student::query()->create([
        'lrn' => '900011112222',
        'first_name' => 'Nina',
        'last_name' => 'Reyes',
        'is_for_remedial' => true,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $this->gradeLevel->id,
        'subject_code' => 'MATH7',
        'subject_name' => 'Mathematics 7',
    ]);

    $teacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
    ]);

    $assignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $teacherSubject->id,
    ]);

    $enrollment = Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 1200,
        'status' => 'enrolled',
    ]);

    foreach (['1', '2', '3', '4'] as $quarter) {
        FinalGrade::query()->create([
            'enrollment_id' => $enrollment->id,
            'subject_assignment_id' => $assignment->id,
            'quarter' => $quarter,
            'grade' => 70,
            'is_locked' => true,
        ]);
    }

    PermanentRecord::query()->create([
        'student_id' => $student->id,
        'school_name' => 'Marriott School',
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'general_average' => 70,
        'status' => 'conditional',
        'failed_subject_count' => 1,
        'remarks' => 'Needs remedial',
    ]);

    $this->post('/registrar/remedial-entry', [
        'academic_year_id' => $this->academicYear->id,
        'student_id' => $student->id,
        'save_mode' => 'submitted',
        'records' => [
            [
                'subject_id' => $subject->id,
                'final_rating' => 70,
                'remedial_class_mark' => 82,
            ],
        ],
    ])->assertRedirect();

    $record = PermanentRecord::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->first();

    expect($record->status)->toBe('promoted');
    expect($record->conditional_resolved_at)->not->toBeNull();
    expect($student->fresh()->is_for_remedial)->toBeFalse();
});

test('registrar permanent records page renders', function () {
    $this->get('/registrar/permanent-records')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/permanent-records/index')
        );
});
