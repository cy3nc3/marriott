<?php

namespace App\Services\SchoolForms;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Sf9TemplateAdapter
{
    /**
     * @param  array{name: string, lrn: string, age: string, sex: string, grade_level: string, section: string, school_year: string, division: string, district: string, school: string, adviser: string}  $metadata
     * @param  array<int, array{subject: string, q1: string, q2: string, q3: string, q4: string, final: string, remarks: string}>  $learningAreas
     * @param  array{school_days: array<int, string>, present: array<int, string>, absent: array<int, string>}  $attendance
     */
    public function exportRows(string $templatePath, string $outputPath, array $metadata, array $learningAreas, array $attendance): void
    {
        $spreadsheet = IOFactory::load($templatePath);
        $learningSheet = $spreadsheet->getSheetByName('Sheet1') ?? $spreadsheet->getSheet(1);
        $attendanceSheet = $spreadsheet->getSheetByName('Sheet2') ?? $spreadsheet->getSheet(0);
        $this->shrinkSheetFonts($learningSheet, 8);
        $this->shrinkSheetFonts($attendanceSheet, 8);

        $this->fillLearnerHeader($attendanceSheet, $metadata);
        $this->fillLearningAreas($learningSheet, $learningAreas);
        $this->fillCoreValues($learningSheet);
        $this->fillAttendance($attendanceSheet, $attendance);

        $this->save($spreadsheet, $outputPath);
    }

    private function shrinkSheetFonts(Worksheet $sheet, int $fontSize): void
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
            ->getFont()
            ->setName('Arial')
            ->setSize($fontSize);
    }

    private function fillLearnerHeader(Worksheet $sheet, array $metadata): void
    {
        $sheet->setCellValue('Q9', sprintf('Division of %s', (string) ($metadata['division'] ?? '')));
        $sheet->setCellValue('Q12', sprintf('District: %s', (string) ($metadata['district'] ?? '')));
        $sheet->setCellValue('Q14', sprintf('School: %s', (string) ($metadata['school'] ?? '')));
        $sheet->setCellValue('P24', $this->underlinedLabeledValue('Name: ', (string) ($metadata['name'] ?? '')));
        $sheet->setCellValue('P26', $this->underlinedLabeledValue("Learner's Reference Number: ", (string) ($metadata['lrn'] ?? '')));

        $ageSex = new RichText();
        $ageSex->createText('Age: ');
        $ageRun = $ageSex->createTextRun((string) ($metadata['age'] ?? ''));
        $ageRun->getFont()->setUnderline(true);
        $ageSex->createText('   Sex: ');
        $sexRun = $ageSex->createTextRun((string) ($metadata['sex'] ?? ''));
        $sexRun->getFont()->setUnderline(true);
        $sheet->setCellValue('P28', $ageSex);

        $gradeSection = new RichText();
        $gradeSection->createText('Grade: ');
        $gradeRun = $gradeSection->createTextRun((string) ($metadata['grade_level'] ?? ''));
        $gradeRun->getFont()->setUnderline(true);
        $gradeSection->createText('   Section: ');
        $sectionRun = $gradeSection->createTextRun((string) ($metadata['section'] ?? ''));
        $sectionRun->getFont()->setUnderline(true);
        $sheet->setCellValue('P30', $gradeSection);

        $sheet->setCellValue('P32', $this->underlinedLabeledValue('School Year: ', (string) ($metadata['school_year'] ?? '')));
        $sheet->setCellValue('S42', $this->underlinedLabeledValue('', (string) ($metadata['adviser'] ?? '')));
    }

    /**
     * @param  array<int, array{subject: string, q1: string, q2: string, q3: string, q4: string, final: string, remarks: string}>  $learningAreas
     */
    private function fillLearningAreas(Worksheet $sheet, array $learningAreas): void
    {
        $rowBySubject = [
            'Filipino' => 9,
            'English' => 10,
            'Mathematics' => 11,
            'Science' => 13,
            'Araling Panlipunan (AP)' => 14,
            'Edukasyon sa Pagpapakatao (EsP)' => 15,
            'Edukasyong Pantahanan at Pangkabuhayan' => 17,
            'MAPEH Music Arts Physical Education Health' => 19,
        ];

        $finalValues = [];
        foreach ($learningAreas as $entry) {
            $subject = (string) ($entry['subject'] ?? '');
            if (! isset($rowBySubject[$subject])) {
                continue;
            }

            $row = $rowBySubject[$subject];
            $sheet->setCellValue("D{$row}", (string) ($entry['q1'] ?? ''));
            $sheet->setCellValue("E{$row}", (string) ($entry['q2'] ?? ''));
            $sheet->setCellValue("F{$row}", (string) ($entry['q3'] ?? ''));
            $sheet->setCellValue("G{$row}", (string) ($entry['q4'] ?? ''));
            $sheet->setCellValue("H{$row}", (string) ($entry['final'] ?? ''));
            $sheet->setCellValue("I{$row}", (string) ($entry['remarks'] ?? ''));

            $numeric = is_numeric((string) ($entry['final'] ?? null)) ? (float) $entry['final'] : null;
            if ($numeric !== null) {
                $finalValues[] = $numeric;
            }
        }

        if ($finalValues !== []) {
            $sheet->setCellValue('H24', number_format(array_sum($finalValues) / count($finalValues), 2, '.', ''));
        }

        $sheet
            ->getStyle('D9:I24')
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('D9:I24')->getFont()->setName('Arial')->setSize(8);
    }

    private function fillCoreValues(Worksheet $sheet): void
    {
        $behaviorRows = [9, 12, 15, 18, 20, 22];
        foreach ($behaviorRows as $row) {
            foreach (['O', 'P', 'Q', 'R'] as $column) {
                $sheet->setCellValue("{$column}{$row}", 'AO');
            }
        }

        $sheet
            ->getStyle('O9:R24')
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('M9:N24')->getFont()->setName('Arial')->setSize(7);
        $sheet->getStyle('M9:N24')->getAlignment()->setWrapText(true)->setShrinkToFit(true)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('O9:R24')->getFont()->setName('Arial')->setSize(8);
    }

    /**
     * @param  array{school_days: array<int, string>, present: array<int, string>, absent: array<int, string>}  $attendance
     */
    private function fillAttendance(Worksheet $sheet, array $attendance): void
    {
        $monthColumns = ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
        foreach ($monthColumns as $index => $column) {
            $sheet->setCellValue("{$column}9", (string) ($attendance['school_days'][$index] ?? ''));
            $sheet->setCellValue("{$column}11", (string) ($attendance['present'][$index] ?? ''));
            $sheet->setCellValue("{$column}14", (string) ($attendance['absent'][$index] ?? ''));
        }

        $sheet
            ->getStyle('B9:L14')
            ->getAlignment()
            ->setTextRotation(0)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B6:L6')->getFont()->setName('Arial')->setSize(7);
    }

    private function underlinedLabeledValue(string $label, string $value): RichText
    {
        $text = new RichText();
        if ($label !== '') {
            $text->createText($label);
        }
        $run = $text->createTextRun($value);
        $run->getFont()->setUnderline(true);

        return $text;
    }

    private function save(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, string $outputPath): void
    {
        if (strtolower(pathinfo($outputPath, PATHINFO_EXTENSION)) === 'xlsx') {
            $writer = new Xlsx($spreadsheet);
            $writer->setPreCalculateFormulas(false);
            $writer->save($outputPath);
            $spreadsheet->disconnectWorksheets();

            return;
        }

        $writer = new Xls($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($outputPath);
        $spreadsheet->disconnectWorksheets();
    }
}
