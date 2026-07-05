<?php

namespace App\Console\Commands;

use App\Services\SchoolForms\EnrollmentTemplateAdapter;
use App\Services\SchoolForms\Sf10TemplateAdapter;
use App\Services\SchoolForms\Sf1TemplateAdapter;
use App\Services\SchoolForms\Sf2TemplateAdapter;
use App\Services\SchoolForms\Sf5TemplateAdapter;
use App\Services\SchoolForms\Sf9TemplateAdapter;
use Illuminate\Console\Command;

class GenerateSchoolFormSampleExportsCommand extends Command
{
    protected $signature = 'school-forms:sample-exports {--output=output/spreadsheet}';

    protected $description = 'Generate sample school form exports for template review.';

    public function handle(
        Sf1TemplateAdapter $sf1TemplateAdapter,
        Sf2TemplateAdapter $sf2TemplateAdapter,
        Sf5TemplateAdapter $sf5TemplateAdapter,
        Sf9TemplateAdapter $sf9TemplateAdapter,
        Sf10TemplateAdapter $sf10TemplateAdapter,
        EnrollmentTemplateAdapter $enrollmentTemplateAdapter,
    ): int {
        $outputDirectory = $this->resolveOutputDirectory();

        $sf1TemplateAdapter->exportRows(
            base_path('templates/SF1_2025.xls'),
            "{$outputDirectory}/sf1-export.xls",
            [
                'school_year' => '2025 - 2026',
                'grade_level' => 'Grade 7',
                'section' => 'ST PAUL',
            ],
            $this->sampleSf1Rows()
        );

        $sf2TemplateAdapter->exportRows(
            base_path('templates/SF2_2025.xls'),
            "{$outputDirectory}/sf2-export.xls",
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
            "{$outputDirectory}/sf5-export.xlsx",
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

        $sf9TemplateAdapter->exportRows(
            base_path('templates/SF9.xlsx'),
            "{$outputDirectory}/sf9-export.xlsx",
            [
                'name' => 'Dela Cruz, Juan Santos',
                'lrn' => '232007000001',
                'age' => '14',
                'sex' => 'Male',
                'grade_level' => 'Grade 9',
                'section' => 'St. Paul',
                'school_year' => '2025 - 2026',
                'division' => 'Quezon City',
                'district' => 'District 1',
                'school' => 'Marriott School',
                'adviser' => 'Ma. Nimfa Guinacaran',
            ],
            $this->sampleSf9LearningRows(),
            $this->sampleSf9Attendance()
        );

        $sf10TemplateAdapter->exportRows(
            base_path('templates/SF10.xlsx'),
            "{$outputDirectory}/sf10-export.xlsx",
            [
                'last_name' => 'Dela Cruz',
                'first_name' => 'Juan',
                'middle_name' => 'Santos',
                'lrn' => '232007000001',
                'birthdate' => '08/14/2011',
                'sex' => 'Male',
            ],
            $this->sampleSf10Records()
        );

        $enrollmentTemplateAdapter->exportRows(
            base_path('templates/_SY 26-27 Enrolment.xlsx'),
            "{$outputDirectory}/enrollment-export.xlsx",
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
        $rows = [
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

        foreach ($this->sampleStudents() as $index => $student) {
            $rows[] = [
                'lrn' => (string) (232007000003 + $index),
                'last_name' => $student['last_name'],
                'first_name' => $student['first_name'],
                'middle_name' => $student['middle_name'],
                'gender' => $student['gender'],
                'birthdate' => sprintf('2011-%02d-%02d', ($index % 9) + 1, ($index % 24) + 1),
                'address' => $student['address'],
                'guardian_name' => $student['guardian_name'],
                'contact_number' => '09'.str_pad((string) (181230000 + $index), 9, '0', STR_PAD_LEFT),
            ];
        }

        return array_slice($rows, 0, 20);
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

        foreach ($this->sampleStudents() as $student) {
            $students[] = [
                'gender' => $student['gender'],
                'name' => "{$student['last_name']}, {$student['first_name']}, {$student['middle_name']}",
                'remark' => $student['remark'],
            ];
        }

        $students = array_slice($students, 0, 20);

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
        $rows = [
            [
                'gender' => 'Male',
                'lrn' => '123456789012',
                'name' => 'Dela Cruz, Juan, Santos',
                'general_average' => '91',
                'action_taken' => 'PROMOTED',
                'learning_areas_not_met' => '',
            ],
            [
                'gender' => 'Male',
                'lrn' => '123456789013',
                'name' => 'Santos, Marco, Reyes',
                'general_average' => '88',
                'action_taken' => 'PROMOTED',
                'learning_areas_not_met' => '',
            ],
            [
                'gender' => 'Male',
                'lrn' => '123456789014',
                'name' => 'Mendoza, Carlo, Flores',
                'general_average' => '74',
                'action_taken' => 'RETAINED',
                'learning_areas_not_met' => 'Mathematics',
            ],
            [
                'gender' => 'Female',
                'lrn' => '210987654321',
                'name' => 'Reyes, Ana, Lopez',
                'general_average' => '74',
                'action_taken' => 'CONDITIONAL',
                'learning_areas_not_met' => 'Mathematics',
            ],
            [
                'gender' => 'Female',
                'lrn' => '210987654322',
                'name' => 'Torres, Mia, Santos',
                'general_average' => '89',
                'action_taken' => 'PROMOTED',
                'learning_areas_not_met' => '',
            ],
            [
                'gender' => 'Female',
                'lrn' => '210987654323',
                'name' => 'Villanueva, Bea, Cruz',
                'general_average' => '73',
                'action_taken' => 'RETAINED',
                'learning_areas_not_met' => 'Science',
            ],
        ];

        $actions = [
            ['average' => '92', 'action' => 'PROMOTED', 'not_met' => ''],
            ['average' => '86', 'action' => 'PROMOTED', 'not_met' => ''],
            ['average' => '74', 'action' => 'CONDITIONAL', 'not_met' => 'Mathematics'],
            ['average' => '73', 'action' => 'RETAINED', 'not_met' => 'Science, English'],
        ];

        foreach ($this->sampleStudents() as $index => $student) {
            $action = $actions[$index % count($actions)];
            $rows[] = [
                'gender' => $student['gender'],
                'lrn' => (string) (232010000007 + $index),
                'name' => "{$student['last_name']}, {$student['first_name']}, {$student['middle_name']}",
                'general_average' => $action['average'],
                'action_taken' => $action['action'],
                'learning_areas_not_met' => $action['not_met'],
            ];
        }

        return array_slice($rows, 0, 20);
    }

    /**
     * @return array<int, array{subject: string, q1: string, q2: string, q3: string, q4: string, final: string, remarks: string}>
     */
    private function sampleSf9LearningRows(): array
    {
        return [
            ['subject' => 'Filipino', 'q1' => '90', 'q2' => '91', 'q3' => '90', 'q4' => '92', 'final' => '91', 'remarks' => 'Passed'],
            ['subject' => 'English', 'q1' => '89', 'q2' => '90', 'q3' => '91', 'q4' => '90', 'final' => '90', 'remarks' => 'Passed'],
            ['subject' => 'Mathematics', 'q1' => '88', 'q2' => '87', 'q3' => '89', 'q4' => '90', 'final' => '89', 'remarks' => 'Passed'],
            ['subject' => 'Science', 'q1' => '90', 'q2' => '89', 'q3' => '90', 'q4' => '91', 'final' => '90', 'remarks' => 'Passed'],
            ['subject' => 'Araling Panlipunan (AP)', 'q1' => '91', 'q2' => '90', 'q3' => '92', 'q4' => '91', 'final' => '91', 'remarks' => 'Passed'],
            ['subject' => 'Edukasyon sa Pagpapakatao (EsP)', 'q1' => '92', 'q2' => '92', 'q3' => '93', 'q4' => '92', 'final' => '92', 'remarks' => 'Passed'],
            ['subject' => 'Edukasyong Pantahanan at Pangkabuhayan', 'q1' => '89', 'q2' => '90', 'q3' => '89', 'q4' => '90', 'final' => '90', 'remarks' => 'Passed'],
            ['subject' => 'MAPEH Music Arts Physical Education Health', 'q1' => '93', 'q2' => '92', 'q3' => '93', 'q4' => '94', 'final' => '93', 'remarks' => 'Passed'],
        ];
    }

    /**
     * @return array{school_days: array<int, string>, present: array<int, string>, absent: array<int, string>}
     */
    private function sampleSf9Attendance(): array
    {
        return [
            'school_days' => ['20', '22', '21', '21', '20', '20', '15', '20', '19', '20', '10'],
            'present' => ['19', '21', '20', '20', '19', '20', '15', '19', '19', '19', '10'],
            'absent' => ['1', '1', '1', '1', '1', '0', '0', '1', '0', '1', '0'],
        ];
    }

    /**
     * @return array<int, array{school: string, school_id: string, district: string, division: string, region: string, grade: string, section: string, school_year: string, adviser: string, subjects: array<int, array{name: string, q1: string, q2: string, q3: string, q4: string, final: string, remarks: string}>, general_average: string}>
     */
    private function sampleSf10Records(): array
    {
        $subjects = [
            ['name' => 'Filipino', 'q1' => '88', 'q2' => '89', 'q3' => '90', 'q4' => '91', 'final' => '90', 'remarks' => 'Passed'],
            ['name' => 'English', 'q1' => '87', 'q2' => '88', 'q3' => '89', 'q4' => '90', 'final' => '89', 'remarks' => 'Passed'],
            ['name' => 'Mathematics', 'q1' => '86', 'q2' => '87', 'q3' => '88', 'q4' => '89', 'final' => '88', 'remarks' => 'Passed'],
            ['name' => 'Science', 'q1' => '89', 'q2' => '90', 'q3' => '90', 'q4' => '91', 'final' => '90', 'remarks' => 'Passed'],
            ['name' => 'Araling Panlipunan (AP)', 'q1' => '90', 'q2' => '90', 'q3' => '91', 'q4' => '91', 'final' => '91', 'remarks' => 'Passed'],
            ['name' => 'Edukasyon sa Pagpapakatao (EsP)', 'q1' => '91', 'q2' => '91', 'q3' => '92', 'q4' => '92', 'final' => '92', 'remarks' => 'Passed'],
            ['name' => 'Technology and Livelihood Education (TLE)', 'q1' => '88', 'q2' => '89', 'q3' => '90', 'q4' => '90', 'final' => '89', 'remarks' => 'Passed'],
            ['name' => 'MAPEH', 'q1' => '92', 'q2' => '92', 'q3' => '93', 'q4' => '93', 'final' => '93', 'remarks' => 'Passed'],
            ['name' => 'Music', 'q1' => '93', 'q2' => '93', 'q3' => '94', 'q4' => '94', 'final' => '94', 'remarks' => 'Passed'],
            ['name' => 'Arts', 'q1' => '92', 'q2' => '92', 'q3' => '93', 'q4' => '93', 'final' => '93', 'remarks' => 'Passed'],
            ['name' => 'Physical Education', 'q1' => '94', 'q2' => '94', 'q3' => '95', 'q4' => '95', 'final' => '95', 'remarks' => 'Passed'],
            ['name' => 'Health', 'q1' => '92', 'q2' => '92', 'q3' => '92', 'q4' => '93', 'final' => '92', 'remarks' => 'Passed'],
        ];

        return [
            [
                'school' => 'Marriott School',
                'school_id' => '482518',
                'district' => 'District 1',
                'division' => 'Quezon City',
                'region' => 'NCR',
                'grade' => '7',
                'section' => 'St. Matthew',
                'school_year' => '2023 - 2024',
                'adviser' => 'Ma. Nimfa Guinacaran',
                'subjects' => $subjects,
                'general_average' => '91',
            ],
            [
                'school' => 'Marriott School',
                'school_id' => '482518',
                'district' => 'District 1',
                'division' => 'Quezon City',
                'region' => 'NCR',
                'grade' => '8',
                'section' => 'St. Paul',
                'school_year' => '2024 - 2025',
                'adviser' => 'Leo Mendoza',
                'subjects' => $subjects,
                'general_average' => '92',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sampleEnrollmentRows(): array
    {
        $rows = [
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

        $paymentPlans = [
            ['plan' => 'M', 'mode' => 'Monthly', 'balance' => 11250, 'status' => 'R', 'remarks' => 'Monthly plan with sibling discount'],
            ['plan' => 'Q', 'mode' => 'Quarterly', 'balance' => 8500, 'status' => 'R', 'remarks' => 'Quarterly plan with early discount'],
            ['plan' => 'C', 'mode' => 'Cash', 'balance' => 0, 'status' => 'E', 'remarks' => 'Fully paid upon enrollment'],
            ['plan' => 'S', 'mode' => 'Semi-Annual', 'balance' => 6200, 'status' => 'R', 'remarks' => 'Semi-annual installment plan'],
        ];

        foreach ($this->sampleStudents() as $index => $student) {
            $plan = $paymentPlans[$index % count($paymentPlans)];
            $tuition = 10000 + (($index % 4) * 750);
            $misc = 5000 + (($index % 3) * 250);

            $rows[] = [
                'name' => "{$student['last_name']}, {$student['first_name']} {$student['middle_name']}",
                'grade_level' => (string) (($index % 4) + 7),
                'section' => ['ST PAUL', 'ST MATTHEW', 'ST JOHN', 'ST LUKE'][$index % 4],
                'or_number' => 'OR-'.(1002 + $index),
                'date' => '2026-04-'.str_pad((string) (($index % 18) + 1), 2, '0', STR_PAD_LEFT),
                'total' => $tuition + $misc,
                'misc' => $misc,
                'misc_discount' => ($index % 4) * 100,
                'misc_sibling_discount' => ($index % 2) * 100,
                'misc_mode' => $plan['mode'],
                'tuition' => $tuition,
                'tuition_sibling_discount' => ($index % 3) * 250,
                'tuition_mode' => $plan['mode'],
                'payment_plan' => $plan['plan'],
                'early_enrollment_discount' => ($index % 2) * 300,
                'fape' => ($index % 4) * 500,
                'fape_previous_year' => ($index % 3) * 100,
                'overall_discount' => ($index % 5) * 150,
                'special_discount' => ($index % 2) * 125,
                'balance' => $plan['balance'],
                'overpayment' => $plan['balance'] === 0 ? ($index % 2) * 250 : 0,
                'reservation_status' => $plan['status'],
                'old_new_status' => $index % 3 === 0 ? 'N' : 'O',
                'remarks' => $plan['remarks'],
            ];
        }

        return array_slice($rows, 0, 20);
    }

    /**
     * @return array<int, array{last_name: string, first_name: string, middle_name: string, gender: string, address: string, guardian_name: string, remark: string}>
     */
    private function sampleStudents(): array
    {
        return [
            ['last_name' => 'Abad', 'first_name' => 'Mikael', 'middle_name' => 'Santos', 'gender' => 'Male', 'address' => '14 Sampaguita Street, Novaliches, Quezon City', 'guardian_name' => 'Lorna Abad', 'remark' => 'Perfect attendance sample'],
            ['last_name' => 'Basilio', 'first_name' => 'Leah', 'middle_name' => 'Navarro', 'gender' => 'Female', 'address' => '22 Narra Avenue, Fairview, Quezon City', 'guardian_name' => 'Rogelio Basilio', 'remark' => 'One excused absence sample'],
            ['last_name' => 'Cabangon', 'first_name' => 'Noel', 'middle_name' => 'Ramos', 'gender' => 'Male', 'address' => '88 Mabini Road, Cubao, Quezon City', 'guardian_name' => 'Mylene Cabangon', 'remark' => 'Late pattern sample'],
            ['last_name' => 'Dizon', 'first_name' => 'Aira', 'middle_name' => 'Flores', 'gender' => 'Female', 'address' => '61 Banaba Lane, Project 8, Quezon City', 'guardian_name' => 'Ernesto Dizon', 'remark' => 'Absent and late sample'],
            ['last_name' => 'Escueta', 'first_name' => 'Paolo', 'middle_name' => 'Mendoza', 'gender' => 'Male', 'address' => '31 Molave Street, Tandang Sora, Quezon City', 'guardian_name' => 'Carina Escueta', 'remark' => 'Cutting classes marker sample'],
            ['last_name' => 'Ferrer', 'first_name' => 'Bianca', 'middle_name' => 'Castillo', 'gender' => 'Female', 'address' => '12 Kamias Road, Quezon City', 'guardian_name' => 'Nestor Ferrer', 'remark' => 'Medical absence sample'],
            ['last_name' => 'Gatdula', 'first_name' => 'Rafael', 'middle_name' => 'Torres', 'gender' => 'Male', 'address' => '73 Maligaya Drive, Quezon City', 'guardian_name' => 'Arlene Gatdula', 'remark' => 'Regular attendance sample'],
            ['last_name' => 'Hernando', 'first_name' => 'Janelle', 'middle_name' => 'Pascual', 'gender' => 'Female', 'address' => '19 Ilang-Ilang Street, Quezon City', 'guardian_name' => 'Victor Hernando', 'remark' => 'Two late marks sample'],
            ['last_name' => 'Ignacio', 'first_name' => 'Cedric', 'middle_name' => 'Soriano', 'gender' => 'Male', 'address' => '45 Scout Rallos, Quezon City', 'guardian_name' => 'Elisa Ignacio', 'remark' => 'One unexcused absence sample'],
            ['last_name' => 'Javier', 'first_name' => 'Trisha', 'middle_name' => 'Valdez', 'gender' => 'Female', 'address' => '27 Anonas Extension, Quezon City', 'guardian_name' => 'Dennis Javier', 'remark' => 'Consistent present sample'],
            ['last_name' => 'Lacson', 'first_name' => 'Miguel', 'middle_name' => 'Reyes', 'gender' => 'Male', 'address' => '52 Road 20, Project 8, Quezon City', 'guardian_name' => 'Rhea Lacson', 'remark' => 'Late and excused sample'],
            ['last_name' => 'Mallari', 'first_name' => 'Sofia', 'middle_name' => 'Bautista', 'gender' => 'Female', 'address' => '6 Mahogany Street, Quezon City', 'guardian_name' => 'Ariel Mallari', 'remark' => 'Attendance complete sample'],
            ['last_name' => 'Natividad', 'first_name' => 'Enzo', 'middle_name' => 'Garcia', 'gender' => 'Male', 'address' => '33 Commonwealth Avenue, Quezon City', 'guardian_name' => 'Mara Natividad', 'remark' => 'Repeated late sample'],
            ['last_name' => 'Ocampo', 'first_name' => 'Rina', 'middle_name' => 'Lopez', 'gender' => 'Female', 'address' => '24 Mindanao Avenue, Quezon City', 'guardian_name' => 'Gilbert Ocampo', 'remark' => 'Mixed attendance sample'],
            ['last_name' => 'Palma', 'first_name' => 'Karlo', 'middle_name' => 'Aquino', 'gender' => 'Male', 'address' => '81 Visayas Avenue, Quezon City', 'guardian_name' => 'Janice Palma', 'remark' => 'One cutting marker sample'],
            ['last_name' => 'Quinto', 'first_name' => 'Elaine', 'middle_name' => 'Rivera', 'gender' => 'Female', 'address' => '9 Katipunan Avenue, Quezon City', 'guardian_name' => 'Rolando Quinto', 'remark' => 'Recovered attendance sample'],
            ['last_name' => 'Roldan', 'first_name' => 'Nathan', 'middle_name' => 'Cruz', 'gender' => 'Male', 'address' => '40 Aurora Boulevard, Quezon City', 'guardian_name' => 'Gemma Roldan', 'remark' => 'Present with one tardy sample'],
            ['last_name' => 'Salcedo', 'first_name' => 'Mara', 'middle_name' => 'Domingo', 'gender' => 'Female', 'address' => '16 Kalayaan Avenue, Quezon City', 'guardian_name' => 'Oscar Salcedo', 'remark' => 'Excused absence sample'],
            ['last_name' => 'Tuazon', 'first_name' => 'Andre', 'middle_name' => 'Lim', 'gender' => 'Male', 'address' => '67 West Avenue, Quezon City', 'guardian_name' => 'Cecilia Tuazon', 'remark' => 'Regular attendance sample'],
        ];
    }
}
