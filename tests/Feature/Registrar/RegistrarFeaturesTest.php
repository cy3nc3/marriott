<?php

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\RemedialRecord;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
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

test('registrar dashboard shows queue aging buckets and lis sync bar distribution', function () {
    $section = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Mabini',
    ]);

    $queueDefinitions = [
        ['status' => 'pending', 'days_ago' => 1, 'is_lis_synced' => true, 'sync_error_flag' => false],
        ['status' => 'pending_intake', 'days_ago' => 4, 'is_lis_synced' => false, 'sync_error_flag' => false],
        ['status' => 'for_cashier_payment', 'days_ago' => 10, 'is_lis_synced' => false, 'sync_error_flag' => true],
        ['status' => 'partial_payment', 'days_ago' => 20, 'is_lis_synced' => false, 'sync_error_flag' => false],
    ];

    $counter = 0;
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
            'payment_term' => 'monthly',
            'downpayment' => 1000,
            'status' => $definition['status'],
        ]);

        $enrollment->created_at = now()->subDays($definition['days_ago']);
        $enrollment->updated_at = now()->subDays($definition['days_ago']);
        $enrollment->save();
    }

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

    $this->get('/dashboard')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/dashboard')
            ->where('trends.0.id', 'queue-aging-buckets')
            ->where('trends.0.display', 'bar')
            ->where('trends.0.chart.rows', function ($rows): bool {
                $rowsByBucket = collect($rows)->keyBy('bucket');

                return count($rows) === 4
                    && (int) ($rowsByBucket['0-2 days']['records'] ?? -1) === 1
                    && (int) ($rowsByBucket['3-7 days']['records'] ?? -1) === 1
                    && (int) ($rowsByBucket['8-14 days']['records'] ?? -1) === 1
                    && (int) ($rowsByBucket['15+ days']['records'] ?? -1) === 1
                    && (int) collect($rows)->sum('records') === 4;
            })
            ->where('trends.1.id', 'lis-sync-distribution')
            ->where('trends.1.display', 'bar')
            ->where('kpis.2.id', 'lis-sync-rate')
            ->where('kpis.2.value', '40.00%')
        );
});

test('registrar enrollment intake supports create update and delete', function () {
    $lrn = '123123123123';

    $this->post('/registrar/enrollment', [
        'lrn' => $lrn,
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'emergency_contact' => '09171234567',
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

    expect(User::query()->where('email', "student.{$lrn}@marriott.edu")->exists())->toBeTrue();
    expect(User::query()->where('email', "parent.{$lrn}@marriott.edu")->exists())->toBeTrue();
    expect(User::query()->where('email', "student.{$lrn}@marriott.edu")->first()?->role?->value)->toBe(UserRole::STUDENT->value);
    expect(User::query()->where('email', "parent.{$lrn}@marriott.edu")->first()?->role?->value)->toBe(UserRole::PARENT->value);

    $this->patch("/registrar/enrollment/{$enrollment->id}", [
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'emergency_contact' => '09998887777',
        'payment_term' => 'quarterly',
        'downpayment' => 2500,
        'status' => 'for_cashier_payment',
    ])->assertRedirect();

    $enrollment->refresh();

    expect($enrollment->status)->toBe('for_cashier_payment');
    expect($enrollment->payment_term)->toBe('quarterly');
    expect((float) $enrollment->downpayment)->toBe(2500.0);
    expect($student->fresh()->last_name)->toBe('Reyes');

    $this->delete("/registrar/enrollment/{$enrollment->id}")
        ->assertRedirect();

    expect(Enrollment::query()->whereKey($enrollment->id)->exists())->toBeFalse();
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
