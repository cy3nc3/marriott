<?php

namespace App\Services\SchoolForms;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EnrollmentTemplateAdapter
{
    private const PAYMENT_TOTAL_NUMBER_FORMAT = '_(* #,##0_);_(* \(#,##0\);_(* "-"??_);_(@_)';

    /**
     * @param  array{school_year_label: string, as_of: string}  $metadata
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function exportRows(string $templatePath, string $outputPath, array $metadata, array $rows): void
    {
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getSheetByName('SY26-27') ?? $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A2', $metadata['school_year_label']);
        $sheet->setCellValue('B4', $metadata['as_of']);

        $paymentPlanTotals = [
            1 => $this->emptyPaymentPlanTotals(),
            2 => $this->emptyPaymentPlanTotals(),
            3 => $this->emptyPaymentPlanTotals(),
            4 => $this->emptyPaymentPlanTotals(),
        ];

        foreach (array_values($rows) as $index => $row) {
            $targetRow = 6 + $index;
            $paymentPlanRow = $this->paymentPlanSummaryRow((string) ($row['payment_plan'] ?? $row['tuition_mode'] ?? ''));

            if ($paymentPlanRow !== null) {
                foreach ($this->paymentPlanTotalColumns($row) as $column => $amount) {
                    $paymentPlanTotals[$paymentPlanRow][$column] += $amount;
                }
            }

            $sheet->setCellValue("A{$targetRow}", (string) ($index + 1));
            $sheet->setCellValue("B{$targetRow}", (string) ($row['name'] ?? ''));
            $sheet->setCellValue("C{$targetRow}", (string) ($row['grade_level'] ?? ''));
            $sheet->setCellValue("D{$targetRow}", (string) ($row['section'] ?? ''));
            $sheet->setCellValue("E{$targetRow}", (string) ($row['or_number'] ?? ''));
            $sheet->setCellValue("F{$targetRow}", (string) ($row['date'] ?? ''));
            $sheet->setCellValue("G{$targetRow}", (float) ($row['total'] ?? 0));
            $sheet->setCellValue("H{$targetRow}", (float) ($row['misc'] ?? 0));
            $sheet->setCellValue("I{$targetRow}", (float) ($row['misc_discount'] ?? 0));
            $sheet->setCellValue("J{$targetRow}", (float) ($row['misc_sibling_discount'] ?? 0));
            $sheet->setCellValue("K{$targetRow}", (string) ($row['misc_mode'] ?? ''));
            $sheet->setCellValue("L{$targetRow}", (float) ($row['tuition'] ?? 0));
            $sheet->setCellValue("M{$targetRow}", (float) ($row['tuition_sibling_discount'] ?? 0));
            $sheet->setCellValue("N{$targetRow}", (string) ($row['tuition_mode'] ?? ''));
            $sheet->setCellValue("O{$targetRow}", (float) ($row['early_enrollment_discount'] ?? 0));
            $sheet->setCellValue("P{$targetRow}", (float) ($row['fape'] ?? 0));
            $sheet->setCellValue("Q{$targetRow}", (float) ($row['fape_previous_year'] ?? 0));
            $sheet->setCellValue("R{$targetRow}", (float) ($row['overall_discount'] ?? 0));
            $sheet->setCellValue("S{$targetRow}", (float) ($row['special_discount'] ?? 0));
            $sheet->setCellValue("T{$targetRow}", (float) ($row['balance'] ?? 0));
            $sheet->setCellValue("U{$targetRow}", (float) ($row['overpayment'] ?? 0));
            $sheet->setCellValue("V{$targetRow}", (string) ($row['reservation_status'] ?? ''));
            $sheet->setCellValue("W{$targetRow}", (string) ($row['old_new_status'] ?? ''));
            $sheet->setCellValue("AA{$targetRow}", (string) ($row['remarks'] ?? ''));
        }

        foreach ($paymentPlanTotals as $row => $totals) {
            foreach ($totals as $column => $amount) {
                $sheet->setCellValue("{$column}{$row}", $amount);
                $sheet->getStyle("{$column}{$row}")
                    ->getNumberFormat()
                    ->setFormatCode(self::PAYMENT_TOTAL_NUMBER_FORMAT);
            }
        }

        $this->save($spreadsheet, $outputPath);
    }

    /**
     * @return array<string, float>
     */
    private function emptyPaymentPlanTotals(): array
    {
        return array_fill_keys(array_keys($this->paymentPlanTotalColumns([])), 0.0);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, float>
     */
    private function paymentPlanTotalColumns(array $row): array
    {
        return [
            'G' => (float) ($row['total'] ?? 0),
            'H' => (float) ($row['misc'] ?? 0),
            'I' => (float) ($row['misc_discount'] ?? 0),
            'J' => (float) ($row['misc_sibling_discount'] ?? 0),
            'L' => (float) ($row['tuition'] ?? 0),
            'M' => (float) ($row['tuition_sibling_discount'] ?? 0),
            'O' => (float) ($row['early_enrollment_discount'] ?? 0),
            'P' => (float) ($row['fape'] ?? 0),
            'Q' => (float) ($row['fape_previous_year'] ?? 0),
            'R' => (float) ($row['overall_discount'] ?? 0),
            'S' => (float) ($row['special_discount'] ?? 0),
            'T' => (float) ($row['balance'] ?? 0),
            'U' => (float) ($row['overpayment'] ?? 0),
        ];
    }

    private function paymentPlanSummaryRow(string $paymentPlan): ?int
    {
        return match (strtolower(trim($paymentPlan))) {
            'q', 'quarterly' => 1,
            'm', 'monthly' => 2,
            's', 'semestral', 'semiannual', 'semi-annual', 'semi annual' => 3,
            'a', 'annual', 'annually' => 4,
            default => null,
        };
    }

    private function save(Spreadsheet $spreadsheet, string $outputPath): void
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
