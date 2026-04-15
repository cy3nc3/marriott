<?php

use App\Services\SchoolForms\EnrollmentTemplateAdapter;
use App\Services\SchoolForms\Sf2TemplateAdapter;
use App\Services\SchoolForms\Sf5TemplateAdapter;
use Illuminate\Support\Facades\Artisan;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

ini_set('memory_limit', '512M');

function schoolFormOutputPath(string $fileName): string
{
    $path = storage_path("framework/testing/school-form-exports/{$fileName}");

    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }

    return $path;
}

test('sf2 adapter exports daily attendance rows into the official workbook layout', function () {
    $path = schoolFormOutputPath('sf2-sample-export.xls');

    app(Sf2TemplateAdapter::class)->exportRows(
        base_path('templates/SF2_2025.xls'),
        $path,
        [
            'school_id' => '482518',
            'school_year' => '2025 - 2026',
            'report_month' => 'July 2025',
            'school_name' => 'Marriott School',
            'grade_level' => 'Grade 7 (Year I)',
            'section' => 'ST PAUL',
        ],
        [
            [
                'gender' => 'Male',
                'name' => 'Dela Cruz, Juan, Santos',
                'attendance' => ['present', 'absent', 'present', 'tardy_late_comer', 'present'],
                'total_absent' => 1,
                'total_present' => 4,
                'remarks' => 'Late on July 4',
            ],
            [
                'gender' => 'Female',
                'name' => 'Reyes, Ana, Lopez',
                'attendance' => ['present', 'present', 'absent', 'present', 'tardy_cutting_classes'],
                'total_absent' => 1,
                'total_present' => 4,
                'remarks' => 'Cutting classes on July 5',
            ],
        ]
    );

    $sheet = IOFactory::load($path)->getActiveSheet();

    expect(trim((string) $sheet->getCell('F3')->getCalculatedValue()))->toBe('482518');
    expect(trim((string) $sheet->getCell('M3')->getCalculatedValue()))->toBe('2025 - 2026');
    expect(trim((string) $sheet->getCell('S3')->getCalculatedValue()))->toBe('July 2025');
    expect(trim((string) $sheet->getCell('F4')->getCalculatedValue()))->toBe('Marriott School');
    expect(trim((string) $sheet->getCell('AA4')->getCalculatedValue()))->toBe('Grade 7 (Year I)');
    expect(trim((string) $sheet->getCell('AM4')->getCalculatedValue()))->toBe('ST PAUL');

    expect(trim((string) $sheet->getCell('C8')->getCalculatedValue()))->toBe('Dela Cruz, Juan, Santos');
    expect(trim((string) $sheet->getCell('F8')->getCalculatedValue()))->toBe('');
    expect(trim((string) $sheet->getCell('H8')->getCalculatedValue()))->toBe('X');
    expect($sheet->getStyle('H8')->getFont()->getSize())->toBe(14.0);
    expect(trim((string) $sheet->getCell('J8')->getCalculatedValue()))->toBe('');
    expect(trim((string) $sheet->getCell('AM8')->getCalculatedValue()))->toBe('1');
    expect(trim((string) $sheet->getCell('AO8')->getCalculatedValue()))->toBe('4');
    expect(trim((string) $sheet->getCell('AQ8')->getCalculatedValue()))->toBe('Late on July 4');

    expect(trim((string) $sheet->getCell('C26')->getCalculatedValue()))->toBe('Reyes, Ana, Lopez');
    expect(trim((string) $sheet->getCell('I26')->getCalculatedValue()))->toBe('X');
    expect(trim((string) $sheet->getCell('K26')->getCalculatedValue()))->toBe('');
    expect(trim((string) $sheet->getCell('AM26')->getCalculatedValue()))->toBe('1');
    expect(trim((string) $sheet->getCell('AO26')->getCalculatedValue()))->toBe('4');
    expect(trim((string) $sheet->getCell('AQ26')->getCalculatedValue()))->toBe('Cutting classes on July 5');
    expect(count($sheet->getDrawingCollection()))->toBeGreaterThanOrEqual(2);
});

test('sf5 adapter exports promotion rows and summary metadata into the official workbook layout', function () {
    $path = schoolFormOutputPath('sf5-sample-export.xlsx');

    app(Sf5TemplateAdapter::class)->exportRows(
        base_path('templates/SF5.xlsx'),
        $path,
        [
            'region' => 'NCR',
            'division' => 'Quezon City',
            'school_id' => '482518',
            'school_year' => '2025 - 2026',
            'curriculum' => 'K to 12',
            'school_name' => 'Marriott School',
            'grade_level' => 'Grade 10 (Year IV)',
            'section' => 'ST JOHN',
        ],
        [
            [
                'gender' => 'Male',
                'lrn' => '123456789012',
                'name' => 'Dela Cruz, Juan, Santos',
                'general_average' => '91',
                'action_taken' => 'PROMOTED',
                'learning_areas_not_met' => '',
            ],
            [
                'gender' => 'Female',
                'lrn' => '210987654321',
                'name' => 'Reyes, Ana, Lopez',
                'general_average' => '74',
                'action_taken' => 'CONDITIONAL',
                'learning_areas_not_met' => 'Mathematics',
            ],
        ]
    );

    $sheet = IOFactory::load($path)->getActiveSheet();

    expect(trim((string) $sheet->getCell('E4')->getCalculatedValue()))->toBe('NCR');
    expect(trim((string) $sheet->getCell('G4')->getCalculatedValue()))->toBe('Quezon City');
    expect(trim((string) $sheet->getCell('E5')->getCalculatedValue()))->toBe('482518');
    expect(trim((string) $sheet->getCell('I5')->getCalculatedValue()))->toBe('2025 - 2026');
    expect(trim((string) $sheet->getCell('L5')->getCalculatedValue()))->toBe('K to 12');
    expect(trim((string) $sheet->getCell('E6')->getCalculatedValue()))->toBe('Marriott School');
    expect(trim((string) $sheet->getCell('L6')->getCalculatedValue()))->toBe('Grade 10 (Year IV)');
    expect(trim((string) $sheet->getCell('S6')->getCalculatedValue()))->toBe('ST JOHN');

    expect(trim((string) $sheet->getCell('A14')->getCalculatedValue()))->toBe('123456789012');
    expect(trim((string) $sheet->getCell('C14')->getCalculatedValue()))->toBe('Dela Cruz, Juan, Santos');
    expect(trim((string) $sheet->getCell('G14')->getCalculatedValue()))->toBe('91');
    expect(trim((string) $sheet->getCell('H14')->getCalculatedValue()))->toBe('PROMOTED');

    expect(trim((string) $sheet->getCell('A58')->getCalculatedValue()))->toBe('210987654321');
    expect(trim((string) $sheet->getCell('C58')->getCalculatedValue()))->toBe('Reyes, Ana, Lopez');
    expect(trim((string) $sheet->getCell('G58')->getCalculatedValue()))->toBe('74');
    expect(trim((string) $sheet->getCell('H58')->getCalculatedValue()))->toBe('CONDITIONAL');
    expect(trim((string) $sheet->getCell('J58')->getCalculatedValue()))->toBe('Mathematics');
});

test('enrollment adapter exports collection rows into the enrollment workbook layout', function () {
    $path = schoolFormOutputPath('enrollment-sample-export.xlsx');

    app(EnrollmentTemplateAdapter::class)->exportRows(
        base_path('templates/_SY 26-27 Enrolment.xlsx'),
        $path,
        [
            'school_year_label' => 'SY 26-27',
            'as_of' => '2026-04-12',
        ],
        [
            [
                'name' => 'Dela Cruz, Juan Santos',
                'grade_level' => '7',
                'section' => 'ST PAUL',
                'or_number' => 'OR-1001',
                'date' => '2026-04-12',
                'total' => 15000,
                'misc' => 5000,
                'misc_discount' => 250,
                'misc_sibling_discount' => 100,
                'misc_mode' => 'Cash',
                'tuition' => 10000,
                'tuition_sibling_discount' => 500,
                'tuition_mode' => 'Monthly',
                'payment_plan' => 'M',
                'early_enrollment_discount' => 300,
                'fape' => 1000,
                'fape_previous_year' => 200,
                'overall_discount' => 750,
                'special_discount' => 125,
                'balance' => 11250,
                'overpayment' => 0,
                'reservation_status' => 'R',
                'old_new_status' => 'O',
                'remarks' => 'Sample enrollment export',
            ],
        ]
    );

    $reader = new Xlsx;
    $reader->setReadDataOnly(false);
    $reader->setLoadSheetsOnly(['SY26-27']);
    $reader->setReadFilter(new class implements IReadFilter
    {
        public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
        {
            if ($worksheetName !== 'SY26-27') {
                return false;
            }

            if (in_array($row, [1, 2, 3, 4], true) && in_array($columnAddress, ['G', 'H', 'L', 'W'], true)) {
                return true;
            }

            if ($row === 2) {
                return $columnAddress === 'A';
            }

            if ($row === 4) {
                return $columnAddress === 'B';
            }

            return $row === 6 && in_array($columnAddress, [
                'A',
                'B',
                'C',
                'D',
                'E',
                'F',
                'G',
                'H',
                'K',
                'L',
                'N',
                'V',
                'W',
                'AA',
            ], true);
        }
    });

    $sheet = $reader->load($path)->getSheetByName('SY26-27');

    expect(trim((string) $sheet->getCell('A2')->getCalculatedValue()))->toBe('SY 26-27');
    expect($sheet->getCell('B4')->getValue())->toBe('2026-04-12');
    expect((float) $sheet->getCell('G1')->getCalculatedValue())->toBe(0.0);
    expect((float) $sheet->getCell('G2')->getCalculatedValue())->toBe(15000.0);
    expect((float) $sheet->getCell('G3')->getCalculatedValue())->toBe(0.0);
    expect((float) $sheet->getCell('G4')->getCalculatedValue())->toBe(0.0);
    expect((float) $sheet->getCell('H2')->getCalculatedValue())->toBe(5000.0);
    expect((float) $sheet->getCell('L2')->getCalculatedValue())->toBe(10000.0);
    expect($sheet->getStyle('G2')->getNumberFormat()->getFormatCode())->toBe('_(* #,##0_);_(* \(#,##0\);_(* "-"??_);_(@_)');
    expect((string) $sheet->getCell('W1')->getValue())->toStartWith('=COUNTIF');
    expect((string) $sheet->getCell('W2')->getValue())->toStartWith('=COUNTIF');
    expect((string) $sheet->getCell('W3')->getValue())->toStartWith('=COUNTIF');
    expect((string) $sheet->getCell('W4')->getValue())->toStartWith('=COUNTIF');
    expect(trim((string) $sheet->getCell('A6')->getCalculatedValue()))->toBe('1');
    expect(trim((string) $sheet->getCell('B6')->getCalculatedValue()))->toBe('Dela Cruz, Juan Santos');
    expect(trim((string) $sheet->getCell('C6')->getCalculatedValue()))->toBe('7');
    expect(trim((string) $sheet->getCell('D6')->getCalculatedValue()))->toBe('ST PAUL');
    expect(trim((string) $sheet->getCell('E6')->getCalculatedValue()))->toBe('OR-1001');
    expect($sheet->getCell('F6')->getValue())->toBe('2026-04-12');
    expect((float) $sheet->getCell('G6')->getCalculatedValue())->toBe(15000.0);
    expect((float) $sheet->getCell('H6')->getCalculatedValue())->toBe(5000.0);
    expect(trim((string) $sheet->getCell('K6')->getCalculatedValue()))->toBe('Cash');
    expect((float) $sheet->getCell('L6')->getCalculatedValue())->toBe(10000.0);
    expect(trim((string) $sheet->getCell('N6')->getCalculatedValue()))->toBe('Monthly');
    expect(trim((string) $sheet->getCell('V6')->getCalculatedValue()))->toBe('R');
    expect(trim((string) $sheet->getCell('W6')->getCalculatedValue()))->toBe('O');
    expect(trim((string) $sheet->getCell('AA6')->getCalculatedValue()))->toBe('Sample enrollment export');
});

test('school form sample export command generates reviewable sample workbooks', function () {
    $outputDirectory = storage_path('framework/testing/school-form-samples');

    Artisan::call('school-forms:sample-exports', [
        '--output' => $outputDirectory,
    ]);

    expect(Artisan::output())->toContain('Generated sample school form exports');
    expect("{$outputDirectory}/sf1-sample-export.xls")->toBeFile();
    expect("{$outputDirectory}/sf2-sample-export.xls")->toBeFile();
    expect("{$outputDirectory}/sf5-sample-export.xlsx")->toBeFile();
    expect("{$outputDirectory}/enrollment-sample-export.xlsx")->toBeFile();

    $sf2Sheet = IOFactory::load("{$outputDirectory}/sf2-sample-export.xls")->getActiveSheet();

    expect(trim((string) $sf2Sheet->getCell('C8')->getCalculatedValue()))->toBe('Dela Cruz, Juan, Santos');
    expect(trim((string) $sf2Sheet->getCell('C13')->getCalculatedValue()))->toBe('Rivera, Noel, Ramos');
    expect(trim((string) $sf2Sheet->getCell('C26')->getCalculatedValue()))->toBe('Reyes, Ana, Lopez');
    expect(trim((string) $sf2Sheet->getCell('C31')->getCalculatedValue()))->toBe('Bautista, Kara, Ong');
    expect(count($sf2Sheet->getDrawingCollection()))->toBeGreaterThan(10);
});
