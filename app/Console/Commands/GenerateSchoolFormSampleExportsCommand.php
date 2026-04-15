<?php

namespace App\Console\Commands;

use App\Services\SchoolForms\EnrollmentTemplateAdapter;
use App\Services\SchoolForms\Sf1TemplateAdapter;
use App\Services\SchoolForms\Sf2TemplateAdapter;
use App\Services\SchoolForms\Sf5TemplateAdapter;
use Illuminate\Console\Command;

class GenerateSchoolFormSampleExportsCommand extends Command
{
    protected $signature = 'school-forms:sample-exports {--output=output/spreadsheet}';

    protected $description = 'Generate sample school form exports for template review.';

    public function handle(
        Sf1TemplateAdapter $sf1TemplateAdapter,
        Sf2TemplateAdapter $sf2TemplateAdapter,
        Sf5TemplateAdapter $sf5TemplateAdapter,
        EnrollmentTemplateAdapter $enrollmentTemplateAdapter,
    ): int {
        $outputDirectory = $this->resolveOutputDirectory();

        $sf1TemplateAdapter->exportRows(
            base_path('templates/SF1_2025.xls'),
            "{$outputDirectory}/sf1-sample-export.xls",
            [
                'school_year' => '2025 - 2026',
                'grade_level' => 'Grade 7',
                'section' => 'ST PAUL',
            ],
            $this->sampleSf1Rows()
        );

        $sf2TemplateAdapter->exportRows(
            base_path('templates/SF2_2025.xls'),
            "{$outputDirectory}/sf2-sample-export.xls",
            [
                'school_id' => '482518',
                'school_year' => '2025 - 2026',
                'report_month' => 'July 2025',
                'school_name' => 'Marriott School',
                'grade_level' => 'Grade 7 (Year I)',
                'section' => 'ST PAUL',
            ],
            $this->sampleSf2Rows()
        );

        $sf5TemplateAdapter->exportRows(
            base_path('templates/SF5.xlsx'),
            "{$outputDirectory}/sf5-sample-export.xlsx",
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
            $this->sampleSf5Rows()
        );

        $enrollmentTemplateAdapter->exportRows(
            base_path('templates/_SY 26-27 Enrolment.xlsx'),
            "{$outputDirectory}/enrollment-sample-export.xlsx",
            [
                'school_year_label' => 'SY 26-27',
                'as_of' => '2026-04-12',
            ],
            $this->sampleEnrollmentRows()
        );

        $this->info("Generated sample school form exports in {$outputDirectory}");

        return self::SUCCESS;
    }

    private function resolveOutputDirectory(): string
    {
        $configuredOutput = (string) $this->option('output');
        $outputDirectory = preg_match('/^(?:[A-Za-z]:[\\\\\\/]|[\\\\\\/])/', $configuredOutput) === 1
            ? $configuredOutput
            : base_path($configuredOutput);

        if (! is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0777, true);
        }

        return str_replace('\\', '/', $outputDirectory);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function sampleSf1Rows(): array
    {
        return [
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
            [
                'lrn' => '210987654321',
                'last_name' => 'Reyes',
                'first_name' => 'Ana',
                'middle_name' => 'Lopez',
                'gender' => 'Female',
                'birthdate' => '2011-08-03',
                'address' => '45 Mabini Avenue, San Isidro, Pasig City, Metro Manila',
                'guardian_name' => 'Ramon Reyes',
                'contact_number' => '09181234567',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sampleSf2Rows(): array
    {
        $students = [
            ['gender' => 'Male', 'name' => 'Dela Cruz, Juan, Santos', 'remark' => 'Late on July 4'],
            ['gender' => 'Male', 'name' => 'Santos, Marco, Reyes', 'remark' => 'Absent and late pattern sample'],
            ['gender' => 'Male', 'name' => 'Mendoza, Carlo, Flores', 'remark' => 'Cutting classes pattern sample'],
            ['gender' => 'Male', 'name' => 'Garcia, Paulo, Diaz', 'remark' => 'Attendance mix sample'],
            ['gender' => 'Male', 'name' => 'Lopez, Adrian, Cruz', 'remark' => 'Repeated late markers sample'],
            ['gender' => 'Male', 'name' => 'Rivera, Noel, Ramos', 'remark' => 'Repeated absent markers sample'],
            ['gender' => 'Female', 'name' => 'Reyes, Ana, Lopez', 'remark' => 'Cutting classes on July 5'],
            ['gender' => 'Female', 'name' => 'Torres, Mia, Santos', 'remark' => 'Late marker sample'],
            ['gender' => 'Female', 'name' => 'Villanueva, Bea, Cruz', 'remark' => 'Absent marker sample'],
            ['gender' => 'Female', 'name' => 'Aquino, Lara, Ramos', 'remark' => 'Mixed attendance sample'],
            ['gender' => 'Female', 'name' => 'Castro, Nina, Lim', 'remark' => 'Repeated cutting markers sample'],
            ['gender' => 'Female', 'name' => 'Bautista, Kara, Ong', 'remark' => 'Repeated present markers sample'],
        ];

        $patterns = [
            ['present', 'absent', 'present', 'tardy_late_comer', 'present', 'present', 'tardy_cutting_classes', 'present'],
            ['absent', 'present', 'tardy_late_comer', 'present', 'absent', 'present', 'present', 'tardy_late_comer'],
            ['present', 'present', 'tardy_cutting_classes', 'absent', 'present', 'tardy_cutting_classes', 'present', 'present'],
            ['tardy_late_comer', 'present', 'present', 'present', 'absent', 'present', 'tardy_cutting_classes', 'absent'],
            ['present', 'tardy_late_comer', 'present', 'tardy_late_comer', 'present', 'absent', 'present', 'present'],
            ['absent', 'absent', 'present', 'present', 'tardy_cutting_classes', 'present', 'present', 'absent'],
        ];

        return array_map(function (array $student, int $index) use ($patterns): array {
            $attendance = $patterns[$index % count($patterns)];
            $absences = count(array_filter($attendance, fn (string $status): bool => $status === 'absent'));

            return [
                'gender' => $student['gender'],
                'name' => $student['name'],
                'attendance' => $attendance,
                'total_absent' => $absences,
                'total_present' => count($attendance) - $absences,
                'remarks' => $student['remark'],
            ];
        }, $students, array_keys($students));
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function sampleSf5Rows(): array
    {
        return [
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
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sampleEnrollmentRows(): array
    {
        return [
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
        ];
    }
}
