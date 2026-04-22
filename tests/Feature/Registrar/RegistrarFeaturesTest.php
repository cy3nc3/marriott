<?php

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\AccountActivationCode;
use App\Models\BillingSchedule;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\FinalGrade;
use App\Models\GradeLevel;
use App\Models\LedgerEntry;
use App\Models\PermanentRecord;
use App\Models\RemedialCase;
use App\Models\RemedialRecord;
use App\Models\RemedialSubjectFee;
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
use App\Services\Auth\AccountActivationCodeManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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

test('registrar sf1 upload endpoint is disabled for inbound lis sync', function () {
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
        'status' => 'for_cashier_payment',
    ]);

    $csvContent = "LRN,First Name,Last Name,Gender,Section,Grade Level\n".
        "123456789012,Juanito,Dela Cruz,Male,Rizal,Grade 7\n".
        "999999999999,Unknown,Student,Female,Rizal,Grade 7\n";

    $file = UploadedFile::fake()->createWithContent('sf1.csv', $csvContent);

    $this->post('/registrar/student-directory/sf1-upload', [
        'sf1_file' => $file,
        'academic_year_id' => $this->academicYear->id,
    ])
        ->assertRedirect()
        ->assertSessionHas(
            'error',
            'Inbound SF1 sync is disabled. Use Enrollment > Export SF1 Reference for LIS enrollment.'
        );

    $student->refresh();

    expect($student->is_lis_synced)->toBeFalse();
    expect($student->first_name)->toBe('Juan');
    expect(Setting::get('registrar_sf1_last_upload_name'))->toBeNull();
});

test('registrar enrollment export downloads sf1 reference csv', function () {
    $firstSection = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Rizal',
    ]);

    $secondSection = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Bonifacio',
    ]);

    $student = Student::query()->create([
        'lrn' => '111122223334',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'is_lis_synced' => false,
        'sync_error_flag' => false,
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $firstSection->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'for_cashier_payment',
    ]);

    $otherStudent = Student::query()->create([
        'lrn' => '222233334445',
        'first_name' => 'Luna',
        'last_name' => 'Reyes',
    ]);

    Enrollment::query()->create([
        'student_id' => $otherStudent->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $secondSection->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'for_cashier_payment',
    ]);

    $response = $this->get(
        "/registrar/enrollment/export?academic_year_id={$this->academicYear->id}&section_ids[]={$firstSection->id}"
    );

    $response->assertOk();
    $response->assertDownload('sf1-reference-2025-2026.csv');

    $downloadedCsv = file_get_contents($response->baseResponse->getFile()->getPathname());
    $csvRows = array_values(array_filter(
        array_map(
            static fn (string $line): array => str_getcsv($line),
            preg_split('/\r\n|\n|\r/', (string) $downloadedCsv) ?: []
        ),
        static fn (array $row): bool => count($row) > 0 && ! (count($row) === 1 && trim((string) $row[0]) === '')
    ));

    expect($csvRows[0] ?? [])->toBe([
        'LRN',
        'First Name',
        'Middle Name',
        'Last Name',
        'Gender',
        'Birthdate',
        'Address',
        'Guardian Name',
        'Guardian Contact Number',
        'Grade Level',
        'Section',
        'Enrollment Status',
    ]);
    expect($csvRows[1][0] ?? null)->toBe('111122223334');
    expect($csvRows[1][1] ?? null)->toBe('Maria');
    expect($csvRows[1][3] ?? null)->toBe('Santos');
    expect($csvRows[1][9] ?? null)->toBe('Grade 7');
    expect($csvRows[1][10] ?? null)->toBe('Rizal');
    expect($csvRows[1][11] ?? null)->toBe('for_cashier_payment');
    expect(collect($csvRows)->flatten()->contains('Bonifacio'))->toBeFalse();
});

test('registrar student directory omits lis status fields from payload', function () {
    $section = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Rizal',
    ]);

    $student = Student::query()->create([
        'lrn' => '222233334444',
        'first_name' => 'Lian',
        'last_name' => 'Vergara',
        'is_lis_synced' => false,
        'sync_error_flag' => true,
        'sync_error_notes' => 'Unable to resolve section assignment from SF1 row.',
    ]);

    Enrollment::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'for_cashier_payment',
    ]);

    $this->get('/registrar/student-directory')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/student-directory/index')
            ->where('students.data.0.lrn', '222233334444')
            ->missing('students.data.0.lis_status')
            ->missing('students.data.0.lis_status_reason')
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
        'status' => 'for_cashier_payment',
    ]);

    Enrollment::query()->create([
        'student_id' => $completedStudent->id,
        'academic_year_id' => $completedYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $sectionCompleted->id,
        'payment_term' => 'monthly',
        'downpayment' => 0,
        'status' => 'for_cashier_payment',
    ]);

    $this->get("/registrar/enrollment?academic_year_id={$completedYear->id}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/enrollment/index')
            ->where('selected_school_year_id', $completedYear->id)
            ->where('filters.academic_year_id', $completedYear->id)
            ->has('grade_level_options', 1)
            ->where('grade_level_options.0.id', $this->gradeLevel->id)
            ->has('enrollments.data', 1)
            ->where('enrollments.data.0.lrn', '444455556666')
        );
});

test('registrar enrollment page paginates queue to 10 items per page', function () {
    foreach (range(1, 12) as $index) {
        $student = Student::query()->create([
            'lrn' => str_pad((string) $index, 12, '0', STR_PAD_LEFT),
            'first_name' => "Student{$index}",
            'last_name' => 'Queue',
        ]);

        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $this->gradeLevel->id,
            'payment_term' => 'monthly',
            'downpayment' => 1000,
            'status' => 'for_cashier_payment',
        ]);
    }

    $this->get('/registrar/enrollment')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/enrollment/index')
            ->where('enrollments.per_page', 10)
            ->where('enrollments.total', 12)
            ->has('enrollments.data', 10)
        );
});

test('registrar student directory lists all students and derives status from ongoing year enrollment', function () {
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

    $droppedStudent = Student::query()->create([
        'lrn' => '222200001111',
        'first_name' => 'Dropped',
        'last_name' => 'Directory',
    ]);

    Enrollment::query()->create([
        'student_id' => $droppedStudent->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $sectionCurrent->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'dropped',
    ]);

    $this->get('/registrar/student-directory')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/student-directory/index')
            ->where('ongoing_academic_year_id', $this->academicYear->id)
            ->has('students.data', 3)
            ->where('students.per_page', 15)
            ->where('students.data.0.lrn', '999900001111')
            ->where('students.data.0.status', 'not_currently_enrolled')
            ->where('students.data.1.lrn', '777788889999')
            ->where('students.data.1.status', 'enrolled')
            ->where('students.data.2.lrn', '222200001111')
            ->where('students.data.2.status', 'dropped')
        );
});

test('registrar student directory paginates to 15 entries per page', function () {
    $section = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Paginated',
    ]);

    foreach (range(1, 16) as $index) {
        $student = Student::query()->create([
            'lrn' => str_pad((string) (760000000000 + $index), 12, '0', STR_PAD_LEFT),
            'first_name' => "Student{$index}",
            'last_name' => "Directory{$index}",
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
            'status' => 'enrolled',
        ]);
    }

    $this->get('/registrar/student-directory')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/student-directory/index')
            ->has('students.data', 15)
            ->where('students.per_page', 15)
            ->where('students.from', 1)
            ->where('students.to', 15)
            ->where('students.total', 16)
        );
});

test('registrar student directory searches by student name and lrn', function () {
    $section = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Searchable',
    ]);

    $matchingStudent = Student::query()->create([
        'lrn' => '123456789012',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'is_lis_synced' => true,
        'sync_error_flag' => false,
    ]);

    $otherStudent = Student::query()->create([
        'lrn' => '999988887777',
        'first_name' => 'Paolo',
        'last_name' => 'Reyes',
        'is_lis_synced' => false,
        'sync_error_flag' => false,
    ]);

    foreach ([$matchingStudent, $otherStudent] as $student) {
        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $section->id,
            'payment_term' => 'cash',
            'downpayment' => 0,
            'status' => 'enrolled',
        ]);
    }

    $this->get('/registrar/student-directory?search=maria')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/student-directory/index')
            ->where('filters.search', 'maria')
            ->has('students.data', 1)
            ->where('students.data.0.lrn', '123456789012')
        );

    $this->get('/registrar/student-directory?search=MaRiA')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/student-directory/index')
            ->where('filters.search', 'MaRiA')
            ->has('students.data', 1)
            ->where('students.data.0.student_name', 'Maria Santos')
        );

    $this->get('/registrar/student-directory?search=999988887777')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/student-directory/index')
            ->where('filters.search', '999988887777')
            ->has('students.data', 1)
            ->where('students.data.0.student_name', 'Paolo Reyes')
        );
});

test('registrar dashboard shows lis sync pie and payment method trends', function () {
    $section = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Mabini',
    ]);

    $queueDefinitions = [
        ['status' => 'for_cashier_payment', 'payment_term' => 'monthly', 'days_ago' => 1, 'is_lis_synced' => true, 'sync_error_flag' => false],
        ['status' => 'for_cashier_payment', 'payment_term' => 'quarterly', 'days_ago' => 4, 'is_lis_synced' => false, 'sync_error_flag' => false],
        ['status' => 'for_cashier_payment', 'payment_term' => 'full', 'days_ago' => 10, 'is_lis_synced' => false, 'sync_error_flag' => true],
        ['status' => 'for_cashier_payment', 'payment_term' => 'monthly', 'days_ago' => 20, 'is_lis_synced' => false, 'sync_error_flag' => false],
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
        'middle_name' => 'Lopez',
        'last_name' => 'Santos',
        'gender' => 'Female',
        'birthdate' => '2011-05-12',
        'guardian_name' => 'Guardian Name',
        'guardian_contact_number' => '09171234567',
        'grade_level_id' => $firstSection->grade_level_id,
        'section_id' => $firstSection->id,
        'payment_term' => 'monthly',
        'downpayment' => 1500,
    ])->assertRedirect()
        ->assertSessionHas('assessment_print_url');

    $student = Student::query()->where('lrn', $lrn)->first();

    expect($student)->not->toBeNull();
    expect($student->user_id)->not->toBeNull();

    $enrollment = Enrollment::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->first();

    expect($enrollment)->not->toBeNull();
    expect($enrollment->status)->toBe('for_cashier_payment');
    expect((float) $enrollment->downpayment)->toBe(1500.0);
    expect($enrollment->section_id)->toBe($firstSection->id);
    expect($enrollment->grade_level_id)->toBe($firstSection->grade_level_id);
    expect($student->middle_name)->toBe('Lopez');
    expect($student->gender)->toBe('Female');
    expect($student->birthdate?->toDateString())->toBe('2011-05-12');
    expect($student->guardian_name)->toBe('Guardian Name');
    expect($student->contact_number)->toBe('+639171234567');

    $monthlySchedules = BillingSchedule::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->orderBy('due_date')
        ->get();

    expect($monthlySchedules)->toHaveCount(10);
    expect($monthlySchedules->first()?->due_date?->toDateString())->toBe('2025-06-01');
    expect($monthlySchedules->first()?->description)->toBe('Upon Enrollment');
    expect($monthlySchedules->last()?->due_date?->toDateString())->toBe('2026-03-01');
    expect(round((float) $monthlySchedules->sum('amount_due'), 2))->toBe(9000.0);
    expect($monthlySchedules->every(fn (BillingSchedule $billingSchedule): bool => $billingSchedule->status === 'unpaid'))->toBeTrue();

    expect(User::query()->where('email', "santos.{$lrn}@marriott.edu")->exists())->toBeTrue();
    expect(User::query()->where('email', "parent.{$lrn}@marriott.edu")->exists())->toBeTrue();
    $studentUser = User::query()->where('email', "santos.{$lrn}@marriott.edu")->first();
    $parentUser = User::query()->where('email', "parent.{$lrn}@marriott.edu")->first();
    expect($studentUser?->role?->value)->toBe(UserRole::STUDENT->value);
    expect(Hash::check('maria@05122011', (string) $studentUser?->password))->toBeFalse();
    expect($studentUser?->must_change_password)->toBeTrue();
    expect($parentUser?->role?->value)->toBe(UserRole::PARENT->value);
    expect($parentUser?->birthday?->toDateString())->toBe('1980-01-01');
    expect(Hash::check('maria@05122011', (string) $parentUser?->password))->toBeFalse();
    expect($parentUser?->must_change_password)->toBeTrue();
    expect(AccountActivationCode::query()->where('user_id', $studentUser?->id)->exists())->toBeTrue();
    expect(AccountActivationCode::query()->where('user_id', $parentUser?->id)->exists())->toBeTrue();

    $this->patch("/registrar/enrollment/{$enrollment->id}", [
        'first_name' => 'Maria',
        'middle_name' => 'Ramos',
        'last_name' => 'Reyes',
        'gender' => 'Female',
        'birthdate' => '2011-06-15',
        'guardian_name' => 'Updated Guardian',
        'guardian_contact_number' => '09998887777',
        'grade_level_id' => $secondSection->grade_level_id,
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
    expect($student->fresh()->middle_name)->toBe('Ramos');
    expect($student->fresh()->gender)->toBe('Female');
    expect($student->fresh()->birthdate?->toDateString())->toBe('2011-06-15');
    expect($student->fresh()->guardian_name)->toBe('Updated Guardian');
    expect($student->fresh()->contact_number)->toBe('+639998887777');

    $quarterlySchedules = BillingSchedule::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->orderBy('due_date')
        ->get();

    expect($quarterlySchedules)->toHaveCount(5);
    expect($quarterlySchedules->pluck('due_date')->map(fn ($date) => $date?->toDateString())->all())->toBe([
        '2025-06-01',
        '2025-07-01',
        '2025-10-01',
        '2025-12-01',
        '2026-03-01',
    ]);
    expect(round((float) $quarterlySchedules->sum('amount_due'), 2))->toBe(9000.0);

    $this->patch("/registrar/enrollment/{$enrollment->id}", [
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'birthdate' => '2011-06-15',
        'guardian_name' => 'Guardian Name',
        'guardian_contact_number' => '09998887777',
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

    expect($semiAnnualSchedules)->toHaveCount(3);
    expect($semiAnnualSchedules->pluck('due_date')->map(fn ($date) => $date?->toDateString())->all())->toBe([
        '2025-06-01',
        '2025-07-01',
        '2026-03-01',
    ]);
    expect(round((float) $semiAnnualSchedules->sum('amount_due'), 2))->toBe(9000.0);

    $this->patch("/registrar/enrollment/{$enrollment->id}", [
        'first_name' => 'Maria',
        'last_name' => 'Reyes',
        'birthdate' => '2011-06-15',
        'guardian_name' => 'Guardian Name',
        'guardian_contact_number' => '09998887777',
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

test('registrar enrollment intake validates lrn as exactly 12 digits', function () {
    $this->from('/registrar/enrollment')
        ->post('/registrar/enrollment', [
            'lrn' => '12345ABC',
            'first_name' => 'Invalid',
            'last_name' => 'Lrn',
            'guardian_name' => 'Guardian Name',
            'guardian_contact_number' => '09171234567',
            'payment_term' => 'cash',
            'downpayment' => 0,
        ])
        ->assertRedirect('/registrar/enrollment')
        ->assertSessionHasErrors(['lrn']);

    expect(Student::query()->where('first_name', 'Invalid')->exists())->toBeFalse();
});

test('registrar can open a printable registration assessment form for an intake', function () {
    $adviser = User::factory()->teacher()->create([
        'first_name' => 'Mina',
        'last_name' => 'Lopez',
    ]);

    $section = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Diamond',
        'adviser_id' => $adviser->id,
    ]);

    Fee::query()->create([
        'grade_level_id' => $this->gradeLevel->id,
        'type' => 'tuition',
        'name' => 'Tuition',
        'amount' => 8000,
    ]);

    Fee::query()->create([
        'grade_level_id' => $this->gradeLevel->id,
        'type' => 'miscellaneous',
        'name' => 'Miscellaneous',
        'amount' => 2000,
    ]);

    $this->post('/registrar/enrollment', [
        'lrn' => '909090909090',
        'first_name' => 'Print',
        'last_name' => 'Ready',
        'birthdate' => '2012-01-01',
        'guardian_name' => 'Guardian Name',
        'guardian_contact_number' => '09175551234',
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 1500,
    ])->assertRedirect();

    $enrollment = Enrollment::query()
        ->where('academic_year_id', $this->academicYear->id)
        ->latest('id')
        ->first();

    expect($enrollment)->not->toBeNull();

    $this->get("/registrar/enrollment/{$enrollment?->id}/assessment")
        ->assertSuccessful()
        ->assertSee('Registration Assessment Form')
        ->assertSee('909090909090')
        ->assertSee('Print Ready')
        ->assertSee('Grade 7')
        ->assertSee('Diamond')
        ->assertSee('Mina Lopez');
});

test('registrar enrollment issues activation code and does not use predictable default password', function () {
    $lrn = '111122223334';

    $this->post('/registrar/enrollment', [
        'lrn' => $lrn,
        'first_name' => 'John Mark',
        'last_name' => 'Cruz',
        'birthdate' => '2012-06-05',
        'guardian_name' => 'Guardian Name',
        'guardian_contact_number' => '09179998888',
        'payment_term' => 'cash',
        'downpayment' => 0,
    ])->assertRedirect();

    $studentUser = User::query()->where('email', "cruz.{$lrn}@marriott.edu")->first();

    expect($studentUser)->not->toBeNull();
    expect(Hash::check('john@06052012', (string) $studentUser?->password))->toBeFalse();
    expect($studentUser?->must_change_password)->toBeTrue();
    expect(AccountActivationCode::query()->where('user_id', $studentUser?->id)->exists())->toBeTrue();
});

test('registrar can regenerate activation codes and print assessment from student directory enrollment action', function () {
    $section = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Jade',
    ]);

    $this->post('/registrar/enrollment', [
        'lrn' => '222211110000',
        'first_name' => 'Aira',
        'last_name' => 'Mendoza',
        'birthdate' => '2011-10-10',
        'guardian_name' => 'Guardian Name',
        'guardian_contact_number' => '09175550000',
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 1200,
    ])->assertRedirect();

    $enrollment = Enrollment::query()
        ->where('academic_year_id', $this->academicYear->id)
        ->latest('id')
        ->first();

    expect($enrollment)->not->toBeNull();

    $student = $enrollment?->student;
    $studentUser = $student?->user;
    $parentUser = User::query()
        ->where('role', UserRole::PARENT->value)
        ->whereHas('students', function ($query) use ($student): void {
            $query->where('students.id', $student?->id);
        })
        ->first();

    expect($studentUser)->not->toBeNull();
    expect($parentUser)->not->toBeNull();

    app(AccountActivationCodeManager::class)->issueForUser($studentUser);
    app(AccountActivationCodeManager::class)->issueForUser($parentUser);

    $originalStudentHash = (string) AccountActivationCode::query()
        ->where('user_id', $studentUser?->id)
        ->value('code_hash');
    $originalParentHash = (string) AccountActivationCode::query()
        ->where('user_id', $parentUser?->id)
        ->value('code_hash');

    $this->post("/registrar/enrollment/{$enrollment?->id}/regenerate-activation-codes")
        ->assertRedirect()
        ->assertSessionHas('success', 'Activation codes regenerated.')
        ->assertSessionHas('assessment_print_url', function ($value) use ($enrollment) {
            if (! is_string($value)) {
                return false;
            }

            return str_contains($value, "/registrar/enrollment/{$enrollment?->id}/assessment")
                && str_contains($value, 'credential_token=');
        });

    $studentActivationCode = AccountActivationCode::query()
        ->where('user_id', $studentUser?->id)
        ->first();
    $parentActivationCode = AccountActivationCode::query()
        ->where('user_id', $parentUser?->id)
        ->first();

    expect($studentActivationCode)->not->toBeNull();
    expect($parentActivationCode)->not->toBeNull();
    expect((string) $studentActivationCode?->code_hash)->not->toBe($originalStudentHash);
    expect((string) $parentActivationCode?->code_hash)->not->toBe($originalParentHash);
    expect($studentActivationCode?->used_at)->toBeNull();
    expect($parentActivationCode?->used_at)->toBeNull();
});

test('registrar enrollment intake requires birthdate', function () {
    $this->from('/registrar/enrollment')
        ->post('/registrar/enrollment', [
            'lrn' => '123456789013',
            'first_name' => 'No',
            'last_name' => 'Birthdate',
            'guardian_name' => 'Guardian Name',
            'guardian_contact_number' => '09179997777',
            'payment_term' => 'cash',
            'downpayment' => 0,
        ])
        ->assertRedirect('/registrar/enrollment')
        ->assertSessionHasErrors(['birthdate']);
});

test('registrar enrollment intake validates guardian contact number as a valid PH mobile number', function () {
    $this->from('/registrar/enrollment')
        ->post('/registrar/enrollment', [
            'lrn' => '123456789012',
            'first_name' => 'Invalid',
            'last_name' => 'Contact',
            'birthdate' => '2010-01-01',
            'guardian_name' => 'Guardian Name',
            'guardian_contact_number' => '0917123456',
            'payment_term' => 'cash',
            'downpayment' => 0,
        ])
        ->assertRedirect('/registrar/enrollment')
        ->assertSessionHasErrors(['guardian_contact_number']);

    expect(Student::query()->where('lrn', '123456789012')->exists())->toBeFalse();
});

test('registrar enrollment intake normalizes multi-word and dashed surnames in student emails', function () {
    $intakeCases = [
        ['lrn' => '123450000001', 'last_name' => 'Dela Cruz'],
        ['lrn' => '123450000002', 'last_name' => 'Dela-Cruz'],
    ];

    foreach ($intakeCases as $intakeCase) {
        $this->post('/registrar/enrollment', [
            'lrn' => $intakeCase['lrn'],
            'first_name' => 'Student',
            'last_name' => $intakeCase['last_name'],
            'birthdate' => '2010-01-01',
            'guardian_name' => 'Guardian Name',
            'guardian_contact_number' => '09170000123',
            'payment_term' => 'cash',
            'downpayment' => 0,
        ])->assertRedirect();

        expect(User::query()->where('email', "delacruz.{$intakeCase['lrn']}@marriott.edu")->exists())->toBeTrue();
    }
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
        'birthdate' => '2010-01-01',
        'guardian_name' => 'Guardian Name',
        'guardian_contact_number' => '09999999999',
        'payment_term' => 'cash',
        'downpayment' => 0,
    ])->assertRedirect()
        ->assertSessionHas('error');

    expect(Enrollment::query()->count())->toBe($beforeCount);
});

test('registrar enrollment intake applies selected grade level without section and rejects section-grade mismatch', function () {
    $gradeEight = GradeLevel::query()->create([
        'name' => 'Grade 8',
        'level_order' => 8,
    ]);

    $gradeSevenSection = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Topaz',
    ]);

    $this->post('/registrar/enrollment', [
        'lrn' => '111100002222',
        'first_name' => 'No',
        'last_name' => 'Section',
        'birthdate' => '2010-02-02',
        'guardian_name' => 'Guardian Name',
        'guardian_contact_number' => '09170000111',
        'grade_level_id' => $gradeEight->id,
        'section_id' => '',
        'payment_term' => 'cash',
        'downpayment' => 0,
    ])->assertRedirect();

    $student = Student::query()->where('lrn', '111100002222')->firstOrFail();
    $enrollment = Enrollment::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->firstOrFail();

    expect($enrollment->grade_level_id)->toBe($gradeEight->id);
    expect($enrollment->section_id)->toBeNull();

    $this->post('/registrar/enrollment', [
        'lrn' => '333300004444',
        'first_name' => 'Mismatch',
        'last_name' => 'Case',
        'birthdate' => '2010-03-03',
        'guardian_name' => 'Guardian Name',
        'guardian_contact_number' => '09170000222',
        'grade_level_id' => $gradeEight->id,
        'section_id' => $gradeSevenSection->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
    ])->assertRedirect()
        ->assertSessionHas('error', 'Selected section does not match the selected grade level.');

    expect(Student::query()->where('lrn', '333300004444')->exists())->toBeFalse();
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
        'birthdate' => '2010-04-04',
        'guardian_name' => 'Guardian Name',
        'guardian_contact_number' => '09170000000',
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

    expect($beforeSchedules)->toHaveCount(10);

    $firstSchedule = $beforeSchedules->first();

    expect($firstSchedule)->not->toBeNull();

    $firstSchedule->update([
        'amount_paid' => 500,
        'status' => 'partially_paid',
    ]);

    $this->patch("/registrar/enrollment/{$enrollment->id}", [
        'first_name' => 'Lara',
        'last_name' => 'Mercado',
        'birthdate' => '2010-04-04',
        'guardian_name' => 'Guardian Name',
        'guardian_contact_number' => '09170000000',
        'payment_term' => 'quarterly',
        'downpayment' => 1500,
        'status' => 'for_cashier_payment',
    ])->assertRedirect();

    $afterSchedules = BillingSchedule::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->orderBy('due_date')
        ->get();

    expect($afterSchedules)->toHaveCount(10);
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
        'birthdate' => '2010-05-05',
        'guardian_name' => 'Guardian Name',
        'guardian_contact_number' => '09171231234',
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
        'birthdate' => '2010-05-05',
        'guardian_name' => 'Guardian Name',
        'guardian_contact_number' => '09171231234',
        'section_id' => $section->id,
        'payment_term' => 'monthly',
        'downpayment' => 1000,
        'status' => 'for_cashier_payment',
    ])->assertRedirect();

    $discountedSchedules = BillingSchedule::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->get();

    expect($discountedSchedules)->toHaveCount(10);
    expect(round((float) $discountedSchedules->sum('amount_due'), 2))->toBe(8100.0);
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

    RemedialCase::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
        'created_by' => $this->registrar->id,
        'failed_subject_count' => 1,
        'fee_per_subject' => 500,
        'total_amount' => 500,
        'amount_paid' => 500,
        'status' => 'paid',
        'paid_at' => now(),
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

test('registrar can create remedial intake from remedial entry context', function () {
    $teacher = User::factory()->teacher()->create();

    $section = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Rizal',
        'adviser_id' => $teacher->id,
    ]);

    $student = Student::query()->create([
        'lrn' => '300000000001',
        'first_name' => 'Intake',
        'last_name' => 'Learner',
        'is_for_remedial' => true,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $this->gradeLevel->id,
        'subject_code' => 'SCI7',
        'subject_name' => 'Science 7',
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

    $this->post('/registrar/remedial-entry/intake', [
        'academic_year_id' => $this->academicYear->id,
        'student_id' => $student->id,
    ])->assertRedirect()
        ->assertSessionHas('success');

    $remedialCase = RemedialCase::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->first();

    expect($remedialCase)->not->toBeNull();
    expect($remedialCase?->failed_subject_count)->toBe(1);
    expect((float) $remedialCase?->fee_per_subject)->toBe(500.0);
    expect((float) $remedialCase?->total_amount)->toBe(500.0);
    expect((float) $remedialCase?->amount_paid)->toBe(0.0);
    expect($remedialCase?->status)->toBe('for_cashier_payment');

    expect(LedgerEntry::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->where('description', "Remedial Intake Fee (Case {$remedialCase?->id})")
        ->exists())->toBeTrue();
});

test('registrar remedial intake applies custom subject fees when configured', function () {
    $teacher = User::factory()->teacher()->create();

    $section = Section::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Bonifacio',
        'adviser_id' => $teacher->id,
    ]);

    $student = Student::query()->create([
        'lrn' => '300000000019',
        'first_name' => 'Custom',
        'last_name' => 'Fee',
        'is_for_remedial' => true,
    ]);

    $math = Subject::query()->create([
        'grade_level_id' => $this->gradeLevel->id,
        'subject_code' => 'MATH7',
        'subject_name' => 'Mathematics 7',
    ]);
    $english = Subject::query()->create([
        'grade_level_id' => $this->gradeLevel->id,
        'subject_code' => 'ENG7A',
        'subject_name' => 'English 7',
    ]);

    $mathTeacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $math->id,
    ]);
    $englishTeacherSubject = TeacherSubject::query()->create([
        'teacher_id' => $teacher->id,
        'subject_id' => $english->id,
    ]);

    $mathAssignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $mathTeacherSubject->id,
    ]);
    $englishAssignment = SubjectAssignment::query()->create([
        'section_id' => $section->id,
        'teacher_subject_id' => $englishTeacherSubject->id,
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
            'subject_assignment_id' => $mathAssignment->id,
            'quarter' => $quarter,
            'grade' => 70,
            'is_locked' => true,
        ]);
        FinalGrade::query()->create([
            'enrollment_id' => $enrollment->id,
            'subject_assignment_id' => $englishAssignment->id,
            'quarter' => $quarter,
            'grade' => 72,
            'is_locked' => true,
        ]);
    }

    RemedialSubjectFee::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'subject_id' => $math->id,
        'amount' => 900,
    ]);
    RemedialSubjectFee::query()->create([
        'academic_year_id' => $this->academicYear->id,
        'subject_id' => $english->id,
        'amount' => 1100,
    ]);

    $this->post('/registrar/remedial-entry/intake', [
        'academic_year_id' => $this->academicYear->id,
        'student_id' => $student->id,
    ])->assertRedirect()
        ->assertSessionHas('success');

    $remedialCase = RemedialCase::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->first();

    expect($remedialCase)->not->toBeNull();
    expect($remedialCase?->failed_subject_count)->toBe(2);
    expect((float) $remedialCase?->fee_per_subject)->toBe(1000.0);
    expect((float) $remedialCase?->total_amount)->toBe(2000.0);
    expect($remedialCase?->status)->toBe('for_cashier_payment');
});

test('remedial submission is blocked when intake is not fully paid', function () {
    $student = Student::query()->create([
        'lrn' => '300000000002',
        'first_name' => 'Blocked',
        'last_name' => 'Submission',
        'is_for_remedial' => true,
    ]);

    $subject = Subject::query()->create([
        'grade_level_id' => $this->gradeLevel->id,
        'subject_code' => 'ENG7',
        'subject_name' => 'English 7',
    ]);

    RemedialCase::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
        'created_by' => $this->registrar->id,
        'failed_subject_count' => 1,
        'fee_per_subject' => 500,
        'total_amount' => 500,
        'amount_paid' => 0,
        'status' => 'for_cashier_payment',
    ]);

    $this->from('/registrar/remedial-entry')
        ->post('/registrar/remedial-entry', [
            'academic_year_id' => $this->academicYear->id,
            'student_id' => $student->id,
            'save_mode' => 'submitted',
            'records' => [
                [
                    'subject_id' => $subject->id,
                    'final_rating' => 70,
                    'remedial_class_mark' => 85,
                ],
            ],
        ])->assertRedirect('/registrar/remedial-entry')
        ->assertSessionHas('error', 'Remedial intake must be fully paid before submitting results.');

    expect(RemedialRecord::query()
        ->where('student_id', $student->id)
        ->where('academic_year_id', $this->academicYear->id)
        ->exists())->toBeFalse();
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

test('registrar batch promotion page renders even when source year start date is missing', function () {
    $this->academicYear->update([
        'start_date' => null,
        'end_date' => null,
    ]);

    $nextAcademicYear = AcademicYear::query()->create([
        'name' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    $this->get('/registrar/batch-promotion')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/batch-promotion/index')
            ->where('source_year.id', $this->academicYear->id)
            ->where('target_year.id', $nextAcademicYear->id)
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
        'birthdate' => '2010-06-06',
        'guardian_name' => 'Guardian Name',
        'guardian_contact_number' => '09170000000',
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

    RemedialCase::query()->create([
        'student_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
        'created_by' => $this->registrar->id,
        'failed_subject_count' => 1,
        'fee_per_subject' => 500,
        'total_amount' => 500,
        'amount_paid' => 500,
        'status' => 'paid',
        'paid_at' => now(),
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

test('registrar data import page renders', function () {
    $this->get('/registrar/data-import')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/data-import/index')
            ->has('imports')
        );
});

test('registrar can import past school year permanent records from csv', function () {
    $existingAcademicYear = AcademicYear::query()->create([
        'name' => '2023-2024',
        'start_date' => '2023-06-01',
        'end_date' => '2024-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);

    $existingStudent = Student::query()->create([
        'lrn' => '100000000001',
        'first_name' => 'Legacy',
        'last_name' => 'Student',
    ]);

    PermanentRecord::query()->create([
        'student_id' => $existingStudent->id,
        'school_name' => 'Legacy School',
        'academic_year_id' => $existingAcademicYear->id,
        'grade_level_id' => $this->gradeLevel->id,
        'general_average' => 80,
        'status' => 'conditional',
        'failed_subject_count' => 1,
        'remarks' => 'Legacy record',
    ]);

    $csvContent = implode("\n", [
        'LRN,First Name,Last Name,School Year,Grade Level,Status,General Average,Failed Subject Count,School Name,Remarks',
        '100000000001,Legacy,Student,2023-2024,Grade 7,promoted,88.5,0,Legacy School,Updated import record',
        '100000000002,New,Learner,2022-2023,Grade 8,retained,74,2,Transfer School,Imported historical row',
    ]);

    $file = UploadedFile::fake()->createWithContent('past-records.csv', $csvContent);

    $this->post('/registrar/data-import/permanent-records', [
        'import_file' => $file,
    ])->assertRedirect()
        ->assertSessionHas('success');

    $existingStudent->refresh();

    $updatedRecord = PermanentRecord::query()
        ->where('student_id', $existingStudent->id)
        ->where('academic_year_id', $existingAcademicYear->id)
        ->first();

    expect($updatedRecord)->not->toBeNull();
    expect((float) $updatedRecord?->general_average)->toBe(88.5);
    expect($updatedRecord?->status)->toBe('promoted');
    expect((int) $updatedRecord?->failed_subject_count)->toBe(0);
    expect($updatedRecord?->remarks)->toBe('Updated import record');

    $newAcademicYear = AcademicYear::query()->where('name', '2022-2023')->first();
    $newGradeLevel = GradeLevel::query()->where('name', 'Grade 8')->first();
    $newStudent = Student::query()->where('lrn', '100000000002')->first();

    expect($newAcademicYear)->not->toBeNull();
    expect($newAcademicYear?->status)->toBe('completed');
    expect($newGradeLevel)->not->toBeNull();
    expect($newStudent)->not->toBeNull();
    expect($newStudent?->first_name)->toBe('New');
    expect($newStudent?->last_name)->toBe('Learner');

    expect(PermanentRecord::query()
        ->where('student_id', $newStudent?->id)
        ->where('academic_year_id', $newAcademicYear?->id)
        ->where('grade_level_id', $newGradeLevel?->id)
        ->where('status', 'retained')
        ->where('failed_subject_count', 2)
        ->exists())->toBeTrue();

    expect(Setting::get('registrar_permanent_records_last_import_name'))->toBe('past-records.csv');

    $this->get('/registrar/data-import')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('registrar/data-import/index')
            ->has('imports', 1)
            ->where('imports.0.file_name', 'past-records.csv')
            ->where('imports.0.imported_rows', 2)
            ->where('imports.0.processed_rows', 2)
            ->where('imports.0.skipped_rows', 0)
        );
});

test('registrar import maps student record fields for historical records', function () {
    $csvContent = implode("\n", [
        'School Year,LRN,Name,Gender,Birthday,Grade Level,Section,Grades',
        '2023-2024,100000000077,"Reyes, Ana",Female,2010-04-03,Grade 8,Diamond,89.5',
    ]);

    $file = UploadedFile::fake()->createWithContent('student-records.csv', $csvContent);

    $this->post('/registrar/data-import/permanent-records', [
        'import_file' => $file,
    ])->assertRedirect()
        ->assertSessionHas('success');

    $student = Student::query()->where('lrn', '100000000077')->first();
    $academicYear = AcademicYear::query()->where('name', '2023-2024')->first();
    $gradeLevel = GradeLevel::query()->where('name', 'Grade 8')->first();
    $section = Section::query()
        ->where('academic_year_id', $academicYear?->id)
        ->where('grade_level_id', $gradeLevel?->id)
        ->where('name', 'Diamond')
        ->first();

    expect($student)->not->toBeNull();
    expect($academicYear)->not->toBeNull();
    expect($gradeLevel)->not->toBeNull();
    expect($section)->not->toBeNull();

    expect($student?->first_name)->toBe('Ana');
    expect($student?->last_name)->toBe('Reyes');
    expect($student?->gender)->toBe('Female');
    expect($student?->birthdate?->toDateString())->toBe('2010-04-03');

    expect(Enrollment::query()
        ->where('student_id', $student?->id)
        ->where('academic_year_id', $academicYear?->id)
        ->where('grade_level_id', $gradeLevel?->id)
        ->where('section_id', $section?->id)
        ->exists())->toBeTrue();

    expect(PermanentRecord::query()
        ->where('student_id', $student?->id)
        ->where('academic_year_id', $academicYear?->id)
        ->where('grade_level_id', $gradeLevel?->id)
        ->where('status', 'promoted')
        ->where('general_average', 89.50)
        ->exists())->toBeTrue();
});
