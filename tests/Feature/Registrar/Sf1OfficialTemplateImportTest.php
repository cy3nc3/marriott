<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\SchoolForms\Sf1TemplateAdapter;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

test('registrar can import official sf1 workbook for historical student directory reconciliation', function () {
    $registrar = User::factory()->superAdmin()->create();
    $this->actingAs($registrar);

    $academicYear = AcademicYear::query()->create([
        'name' => '2025-2026',
        'start_date' => '2025-06-01',
        'end_date' => '2026-03-31',
        'status' => 'completed',
        'current_quarter' => '4',
    ]);
    $gradeLevel = GradeLevel::query()->create([
        'name' => 'Grade 7',
        'level_order' => 7,
    ]);
    $section = Section::query()->create([
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'name' => 'ST PAUL',
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
        'academic_year_id' => $academicYear->id,
        'grade_level_id' => $gradeLevel->id,
        'section_id' => $section->id,
        'payment_term' => 'cash',
        'downpayment' => 0,
        'status' => 'enrolled',
    ]);

    $workbook = IOFactory::load(base_path('templates/SF1_2025.xls'));
    $sheet = $workbook->getActiveSheet();
    $sheet->setCellValue('T4', '2025 - 2026');
    $sheet->setCellValue('AE4', 'Grade 7');
    $sheet->setCellValue('AM4', 'ST PAUL');
    $sheet->setCellValue('A7', '123456789012');
    $sheet->setCellValue('C7', 'Dela Cruz, Juan, Santos');
    $sheet->setCellValue('G7', 'M');
    $sheet->setCellValue('H7', Date::PHPToExcel(new DateTimeImmutable('2010-01-15')));
    $sheet->setCellValue('P7', '123 Sample Street');
    $sheet->setCellValue('R7', 'Bagumbayan');
    $sheet->setCellValue('U7', 'Quezon City');
    $sheet->setCellValue('W7', 'Metro Manila');
    $sheet->setCellValue('AK7', 'Maria Dela Cruz');
    $sheet->setCellValue('AP7', '09171234567');

    $path = storage_path('framework/testing/sf1-official-import.xls');
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }
    (new Xls($workbook))->save($path);

    $file = new UploadedFile(
        $path,
        'sf1-official-import.xls',
        'application/vnd.ms-excel',
        null,
        true
    );

    $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)')
        ->post('/registrar/student-directory/sf1-upload', [
            'sf1_file' => $file,
            'academic_year_id' => $academicYear->id,
        ])
        ->assertRedirect()
        ->assertSessionHas(
            'success',
            'SF1 processed. Matched 1 of 1 rows, updated 0 section assignments, with 0 discrepancies.'
        );

    $student->refresh();

    expect($student->is_lis_synced)->toBeTrue();
    expect($student->sync_error_flag)->toBeFalse();
    expect($student->first_name)->toBe('Juan');
    expect($student->last_name)->toBe('Dela Cruz');
    expect($student->gender)->toBe('Male');
    expect($student->birthdate?->toDateString())->toBe('2010-01-15');
    expect($student->address)->toBe('123 Sample Street, Bagumbayan, Quezon City, Metro Manila');
    expect($student->guardian_name)->toBe('Maria Dela Cruz');
    expect($student->contact_number)->toBe('09171234567');
    expect(Setting::get('registrar_sf1_last_upload_name'))->toBe('sf1-official-import.xls');
});

test('sf1 adapter exports normalized rows into the official workbook layout', function () {
    $path = storage_path('framework/testing/sf1-official-export.xls');
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }

    app(Sf1TemplateAdapter::class)->exportRows(
        base_path('templates/SF1_2025.xls'),
        $path,
        [
            'school_year' => '2025 - 2026',
            'grade_level' => 'Grade 7',
            'section' => 'ST PAUL',
        ],
        [
            [
                'lrn' => '123456789012',
                'last_name' => 'Dela Cruz',
                'first_name' => 'Juan',
                'middle_name' => 'Santos',
                'gender' => 'Male',
                'birthdate' => '2010-01-15',
                'address' => '123 Sample Street, Bagumbayan, Quezon City, Metro Manila',
                'guardian_name' => 'Maria Dela Cruz',
                'contact_number' => '09171234567',
            ],
        ]
    );

    $sheet = IOFactory::load($path)->getActiveSheet();

    expect(trim((string) $sheet->getCell('T4')->getCalculatedValue()))->toBe('2025 - 2026');
    expect(trim((string) $sheet->getCell('AE4')->getCalculatedValue()))->toBe('Grade 7');
    expect(trim((string) $sheet->getCell('AM4')->getCalculatedValue()))->toBe('ST PAUL');
    expect(trim((string) $sheet->getCell('A7')->getCalculatedValue()))->toBe('123456789012');
    expect(trim((string) $sheet->getCell('C7')->getCalculatedValue()))->toBe('Dela Cruz, Juan, Santos');
    expect(trim((string) $sheet->getCell('G7')->getCalculatedValue()))->toBe('M');
    expect(trim((string) $sheet->getCell('AK7')->getCalculatedValue()))->toBe('Maria Dela Cruz');
    expect(trim((string) $sheet->getCell('AP7')->getCalculatedValue()))->toBe('09171234567');
});
