<?php

namespace App\Services\SchoolForms;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Sf10TemplateAdapter
{
    /**
     * @param  array{last_name: string, first_name: string, middle_name: string, lrn: string, birthdate: string, sex: string}  $learner
     * @param  array<int, array{school: string, school_id: string, district: string, division: string, region: string, grade: string, section: string, school_year: string, adviser: string, subjects: array<int, array{name: string, q1: string, q2: string, q3: string, q4: string, final: string, remarks: string}>, general_average: string}>  $records
     */
    public function exportRows(string $templatePath, string $outputPath, array $learner, array $records): void
    {
        $spreadsheet = IOFactory::load($templatePath);
        $front = $spreadsheet->getSheetByName('front') ?? $spreadsheet->getSheet(0);
        $back = $spreadsheet->getSheetByName('back') ?? $spreadsheet->getSheet(1);

        $this->fillLearnerHeader($front, $learner);

        $blocks = [
            ['sheet' => $front, 'headerRow' => 20, 'classRow' => 21, 'subjectStartRow' => 25, 'generalAverageCell' => 'G39', 'quarterCols' => ['G', 'H', 'I', 'J'], 'finalCol' => 'K', 'remarksCol' => 'L'],
            ['sheet' => $front, 'headerRow' => 46, 'classRow' => 47, 'subjectStartRow' => 51, 'generalAverageCell' => 'G65', 'quarterCols' => ['G', 'H', 'I', 'J'], 'finalCol' => 'K', 'remarksCol' => 'L'],
            ['sheet' => $back, 'headerRow' => 3, 'classRow' => 4, 'subjectStartRow' => 8, 'generalAverageCell' => 'F21', 'quarterCols' => ['F', 'G', 'H', 'I'], 'finalCol' => 'J', 'remarksCol' => 'K'],
            ['sheet' => $back, 'headerRow' => 28, 'classRow' => 29, 'subjectStartRow' => 32, 'generalAverageCell' => 'F46', 'quarterCols' => ['F', 'G', 'H', 'I'], 'finalCol' => 'J', 'remarksCol' => 'K'],
            ['sheet' => $back, 'headerRow' => 53, 'classRow' => 54, 'subjectStartRow' => 58, 'generalAverageCell' => 'F72', 'quarterCols' => ['F', 'G', 'H', 'I'], 'finalCol' => 'J', 'remarksCol' => 'K'],
        ];

        foreach ($blocks as $index => $block) {
            if (! isset($records[$index])) {
                continue;
            }

            $this->fillScholasticBlock(
                $block['sheet'],
                (int) $block['headerRow'],
                (int) $block['classRow'],
                (int) $block['subjectStartRow'],
                (string) $block['generalAverageCell'],
                $block['quarterCols'],
                (string) $block['finalCol'],
                (string) $block['remarksCol'],
                $records[$index]
            );
        }

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($outputPath);
        $spreadsheet->disconnectWorksheets();
    }

    private function fillLearnerHeader(Worksheet $sheet, array $learner): void
    {
        $sheet->setCellValue('B7', sprintf('LAST NAME: %s', (string) ($learner['last_name'] ?? '')));
        $sheet->setCellValue('F7', sprintf('FIRST NAME: %s', (string) ($learner['first_name'] ?? '')));
        $sheet->setCellValue('L7', sprintf('MIDDLE NAME: %s', (string) ($learner['middle_name'] ?? '')));
        $sheet->setCellValue('B8', sprintf('Learner Reference Number (LRN): %s', (string) ($learner['lrn'] ?? '')));
        $sheet->setCellValue('G8', sprintf('Birthdate (mm/dd/yyyy): %s', (string) ($learner['birthdate'] ?? '')));
        $sheet->setCellValue('L8', sprintf('Sex: %s', (string) ($learner['sex'] ?? '')));
    }

    /**
     * @param  array{school: string, school_id: string, district: string, division: string, region: string, grade: string, section: string, school_year: string, adviser: string, subjects: array<int, array{name: string, q1: string, q2: string, q3: string, q4: string, final: string, remarks: string}>, general_average: string}  $record
     */
    private function fillScholasticBlock(
        Worksheet $sheet,
        int $headerRow,
        int $classRow,
        int $subjectStartRow,
        string $generalAverageCell,
        array $quarterCols,
        string $finalCol,
        string $remarksCol,
        array $record
    ): void {
        $sheet->setCellValue(
            "B{$headerRow}",
            sprintf(
                'School: %s  School ID: %s  District: %s  Division: %s  Region: %s',
                (string) ($record['school'] ?? ''),
                (string) ($record['school_id'] ?? ''),
                (string) ($record['district'] ?? ''),
                (string) ($record['division'] ?? ''),
                (string) ($record['region'] ?? '')
            )
        );
        $sheet->getStyle("B{$headerRow}")->getFont()->setUnderline(Font::UNDERLINE_SINGLE);

        $sheet->setCellValue(
            "B{$classRow}",
            sprintf(
                'Classified as Grade: %s  Section: %s  School Year: %s  Name of Adviser/Teacher: %s',
                (string) ($record['grade'] ?? ''),
                (string) ($record['section'] ?? ''),
                (string) ($record['school_year'] ?? ''),
                (string) ($record['adviser'] ?? '')
            )
        );
        $sheet->getStyle("B{$classRow}")->getFont()->setUnderline(Font::UNDERLINE_SINGLE);

        $subjectRowByName = [
            'Filipino' => $subjectStartRow,
            'English' => $subjectStartRow + 1,
            'Mathematics' => $subjectStartRow + 2,
            'Science' => $subjectStartRow + 3,
            'Araling Panlipunan (AP)' => $subjectStartRow + 4,
            'Edukasyon sa Pagpapakatao (EsP)' => $subjectStartRow + 5,
            'Technology and Livelihood Education (TLE)' => $subjectStartRow + 6,
            'MAPEH' => $subjectStartRow + 7,
            'Music' => $subjectStartRow + 8,
            'Arts' => $subjectStartRow + 9,
            'Physical Education' => $subjectStartRow + 10,
            'Health' => $subjectStartRow + 11,
        ];

        foreach (($record['subjects'] ?? []) as $subject) {
            $name = (string) ($subject['name'] ?? '');
            if (! isset($subjectRowByName[$name])) {
                continue;
            }

            $row = $subjectRowByName[$name];
            $sheet->setCellValue("{$quarterCols[0]}{$row}", (string) ($subject['q1'] ?? ''));
            $sheet->setCellValue("{$quarterCols[1]}{$row}", (string) ($subject['q2'] ?? ''));
            $sheet->setCellValue("{$quarterCols[2]}{$row}", (string) ($subject['q3'] ?? ''));
            $sheet->setCellValue("{$quarterCols[3]}{$row}", (string) ($subject['q4'] ?? ''));
            $sheet->setCellValue("{$finalCol}{$row}", (string) ($subject['final'] ?? ''));
            $sheet->setCellValue("{$remarksCol}{$row}", (string) ($subject['remarks'] ?? ''));
        }

        $sheet->setCellValue($generalAverageCell, (string) ($record['general_average'] ?? ''));
    }
}
