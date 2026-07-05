<?php

namespace App\Services\SchoolForms;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
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
        $maleRowsWritten = 0;
        $femaleRowsWritten = 0;
        $statusSummary = [
            'promoted' => ['male' => 0, 'female' => 0],
            'conditional' => ['male' => 0, 'female' => 0],
            'retained' => ['male' => 0, 'female' => 0],
        ];

        // Clear learner row blocks so template placeholders do not remain visible.
        $this->clearRows($sheet, 14, 54);
        $this->clearRows($sheet, 58, 87);

        foreach ($rows as $row) {
            $isFemale = $this->isFemale((string) ($row['gender'] ?? ''));
            $targetRow = $isFemale ? $femaleRow++ : $maleRow++;

            if ($isFemale) {
                $femaleRowsWritten++;
            } else {
                $maleRowsWritten++;
            }

            $sheet->setCellValueExplicit("A{$targetRow}", (string) ($row['lrn'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("C{$targetRow}", (string) ($row['name'] ?? ''));
            $sheet->setCellValue("G{$targetRow}", (string) ($row['general_average'] ?? ''));
            $sheet->setCellValue("H{$targetRow}", (string) ($row['action_taken'] ?? ''));
            $sheet->setCellValue("J{$targetRow}", (string) ($row['learning_areas_not_met'] ?? ''));

            $action = strtolower(trim((string) ($row['action_taken'] ?? '')));
            $bucket = match (true) {
                str_contains($action, 'conditional') => 'conditional',
                str_contains($action, 'retain') => 'retained',
                default => 'promoted',
            };
            $statusSummary[$bucket][$isFemale ? 'female' : 'male']++;
        }

        $sheet->setCellValue('A54', $maleRowsWritten);
        $sheet->setCellValue('A87', $femaleRowsWritten);
        $sheet->setCellValue('A89', $maleRowsWritten + $femaleRowsWritten);

        $this->writeSummaryRow($sheet, 11, $statusSummary['promoted']['male'], $statusSummary['promoted']['female']);
        $this->writeSummaryRow($sheet, 13, $statusSummary['conditional']['male'], $statusSummary['conditional']['female']);
        $this->writeSummaryRow($sheet, 15, $statusSummary['retained']['male'], $statusSummary['retained']['female']);

        $this->save($spreadsheet, $outputPath);
    }

    private function clearRows(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $fromRow, int $toRow): void
    {
        for ($row = $fromRow; $row <= $toRow; $row++) {
            foreach (['A', 'C', 'G', 'H', 'J'] as $column) {
                $sheet->setCellValue("{$column}{$row}", '');
            }
        }
    }

    private function writeSummaryRow(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, int $male, int $female): void
    {
        $sheet->setCellValue("Q{$row}", $male);
        $sheet->setCellValue("T{$row}", $female);
        $sheet->setCellValue("U{$row}", $male + $female);
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
