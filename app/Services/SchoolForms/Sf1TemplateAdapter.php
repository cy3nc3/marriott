<?php

namespace App\Services\SchoolForms;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Sf1TemplateAdapter
{
    /**
     * @return array<int, array{row_data: array<string, string>, lrn: string}>
     */
    public function parseRows(string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $gradeLevel = $this->normalizeGradeLevel((string) $sheet->getCell('AE4')->getCalculatedValue());
        $section = trim((string) $sheet->getCell('AM4')->getCalculatedValue());
        $rows = [];

        for ($rowNumber = 7; $rowNumber <= $sheet->getHighestRow(); $rowNumber++) {
            $lrn = preg_replace('/\D/', '', (string) $sheet->getCell("A{$rowNumber}")->getCalculatedValue()) ?: '';
            $learnerName = (string) $sheet->getCell("C{$rowNumber}")->getCalculatedValue();

            if (strlen($lrn) !== 12 || trim($learnerName) === '') {
                continue;
            }

            [$lastName, $firstName] = $this->parseLearnerName($learnerName);

            $rowData = [
                'lrn' => $lrn,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => $this->normalizeGender((string) $sheet->getCell("G{$rowNumber}")->getCalculatedValue()),
                'birthdate' => $this->parseBirthdate($sheet->getCell("H{$rowNumber}")->getValue()),
                'address' => $this->composeAddress([
                    (string) $sheet->getCell("P{$rowNumber}")->getCalculatedValue(),
                    (string) $sheet->getCell("R{$rowNumber}")->getCalculatedValue(),
                    (string) $sheet->getCell("U{$rowNumber}")->getCalculatedValue(),
                    (string) $sheet->getCell("W{$rowNumber}")->getCalculatedValue(),
                ]),
                'guardian_name' => trim((string) $sheet->getCell("AK{$rowNumber}")->getCalculatedValue()),
                'contact_number' => trim((string) $sheet->getCell("AP{$rowNumber}")->getCalculatedValue()),
                'section' => $section,
                'grade_level' => $gradeLevel,
            ];

            $rows[] = [
                'row_data' => $rowData,
                'lrn' => $lrn,
            ];
        }

        return $rows;
    }

    /**
     * @param  array{school_year: string, grade_level: string, section: string}  $metadata
     * @param  array<int, array<string, string>>  $rows
     */
    public function exportRows(string $templatePath, string $outputPath, array $metadata, array $rows): void
    {
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('T4', $metadata['school_year']);
        $sheet->setCellValue('AE4', $metadata['grade_level']);
        $sheet->setCellValue('AM4', $metadata['section']);

        foreach (array_values($rows) as $index => $row) {
            $rowNumber = 7 + $index;
            $addressParts = $this->splitAddress((string) ($row['address'] ?? ''));

            $sheet->setCellValue("A{$rowNumber}", (string) ($row['lrn'] ?? ''));
            $sheet->setCellValue("C{$rowNumber}", $this->formatLearnerName($row));
            $sheet->setCellValue("G{$rowNumber}", $this->formatGender((string) ($row['gender'] ?? '')));
            $sheet->setCellValue("H{$rowNumber}", (string) ($row['birthdate'] ?? ''));
            $sheet->setCellValue("P{$rowNumber}", $addressParts[0] ?? '');
            $sheet->setCellValue("R{$rowNumber}", $addressParts[1] ?? '');
            $sheet->setCellValue("U{$rowNumber}", $addressParts[2] ?? '');
            $sheet->setCellValue("W{$rowNumber}", $addressParts[3] ?? '');
            $sheet->setCellValue("AK{$rowNumber}", (string) ($row['guardian_name'] ?? ''));
            $sheet->setCellValue("AP{$rowNumber}", (string) ($row['contact_number'] ?? ''));
        }

        if (strtolower(pathinfo($outputPath, PATHINFO_EXTENSION)) === 'xlsx') {
            (new Xlsx($spreadsheet))->save($outputPath);

            return;
        }

        (new Xls($spreadsheet))->save($outputPath);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseLearnerName(string $value): array
    {
        $parts = array_map(
            fn (string $part): string => trim($part),
            explode(',', trim($value))
        );

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }

    private function normalizeGender(string $value): string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'm', 'male' => 'Male',
            'f', 'female' => 'Female',
            default => trim($value),
        };
    }

    private function parseBirthdate(mixed $value): string
    {
        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        if (trim((string) $value) === '') {
            return '';
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * @param  array<int, string>  $parts
     */
    private function composeAddress(array $parts): string
    {
        return implode(', ', array_values(array_filter(
            array_map(fn (string $part): string => trim($part), $parts),
            fn (string $part): bool => $part !== ''
        )));
    }

    private function normalizeGradeLevel(string $value): string
    {
        if (preg_match('/Grade\s*\d+/i', $value, $matches) === 1) {
            return trim($matches[0]);
        }

        return trim($value);
    }

    /**
     * @param  array<string, string>  $row
     */
    private function formatLearnerName(array $row): string
    {
        return implode(', ', array_values(array_filter([
            trim((string) ($row['last_name'] ?? '')),
            trim((string) ($row['first_name'] ?? '')),
            trim((string) ($row['middle_name'] ?? '')),
        ], fn (string $part): bool => $part !== '')));
    }

    private function formatGender(string $value): string
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'male', 'm' => 'M',
            'female', 'f' => 'F',
            default => trim($value),
        };
    }

    /**
     * @return array<int, string>
     */
    private function splitAddress(string $value): array
    {
        return array_map(
            fn (string $part): string => trim($part),
            array_slice(explode(',', $value), 0, 4)
        );
    }
}
