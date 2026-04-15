<?php

namespace App\Services\SchoolForms;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Sf5TemplateAdapter
{
    /**
     * @param  array{region: string, division: string, school_id: string, school_year: string, curriculum: string, school_name: string, grade_level: string, section: string}  $metadata
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function exportRows(string $templatePath, string $outputPath, array $metadata, array $rows): void
    {
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('E4', $metadata['region']);
        $sheet->setCellValue('G4', $metadata['division']);
        $sheet->setCellValue('E5', $metadata['school_id']);
        $sheet->setCellValue('I5', $metadata['school_year']);
        $sheet->setCellValue('L5', $metadata['curriculum']);
        $sheet->setCellValue('E6', $metadata['school_name']);
        $sheet->setCellValue('L6', $metadata['grade_level']);
        $sheet->setCellValue('S6', $metadata['section']);

        $maleRow = 14;
        $femaleRow = 58;

        foreach ($rows as $row) {
            $targetRow = $this->isFemale((string) ($row['gender'] ?? '')) ? $femaleRow++ : $maleRow++;

            $sheet->setCellValue("A{$targetRow}", (string) ($row['lrn'] ?? ''));
            $sheet->setCellValue("C{$targetRow}", (string) ($row['name'] ?? ''));
            $sheet->setCellValue("G{$targetRow}", (string) ($row['general_average'] ?? ''));
            $sheet->setCellValue("H{$targetRow}", (string) ($row['action_taken'] ?? ''));
            $sheet->setCellValue("J{$targetRow}", (string) ($row['learning_areas_not_met'] ?? ''));
        }

        $this->save($spreadsheet, $outputPath);
    }

    private function isFemale(string $gender): bool
    {
        return in_array(strtolower(trim($gender)), ['female', 'f'], true);
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
