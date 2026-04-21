<?php

namespace App\Services\Finance;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TransactionHistoryWorkbookExporter
{
    /**
     * @param  array{
     *     generated_at: string,
     *     range_preset: string,
     *     date_from: string,
     *     date_to: string,
     *     school_year: string,
     *     payment_mode: string,
     *     search: string
     * }  $metadata
     * @param  array{
     *     count: int,
     *     posted_amount: float,
     *     corrected_amount: float,
     *     net_amount: float
     * }  $summary
     * @param  array<int, array{
     *     label: string,
     *     count: int,
     *     posted_amount: float,
     *     corrected_amount: float,
     *     net_amount: float
     * }>  $monthlyOverviewRows
     * @param  array<string, array<int, array{
     *     or_number: string,
     *     student_name: string,
     *     payment_mode_label: string,
     *     status_label: string,
     *     posted_at: string,
     *     cashier_name: string,
     *     amount: float,
     *     correction_reason: string,
     *     corrected_by_name: string
     * }>>  $monthlyDetails
     */
    public function export(
        string $outputPath,
        array $metadata,
        array $summary,
        array $monthlyOverviewRows,
        array $monthlyDetails,
    ): void {
        $spreadsheet = new Spreadsheet;
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Summary');
        $this->buildSummarySheet($summarySheet, $metadata, $summary);

        $overviewSheet = $spreadsheet->createSheet();
        $overviewSheet->setTitle('Monthly Overview');
        $this->buildMonthlyOverviewSheet($overviewSheet, $monthlyOverviewRows);

        foreach ($monthlyDetails as $label => $rows) {
            $detailSheet = $spreadsheet->createSheet();
            $detailSheet->setTitle($this->sheetNameForLabel($spreadsheet, $label));
            $this->buildMonthlyDetailSheet($detailSheet, $label, $rows);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($outputPath);
        $spreadsheet->disconnectWorksheets();
    }

    /**
     * @param  array{
     *     generated_at: string,
     *     range_preset: string,
     *     date_from: string,
     *     date_to: string,
     *     school_year: string,
     *     payment_mode: string,
     *     search: string
     * }  $metadata
     * @param  array{
     *     count: int,
     *     posted_amount: float,
     *     corrected_amount: float,
     *     net_amount: float
     * }  $summary
     */
    private function buildSummarySheet(Worksheet $sheet, array $metadata, array $summary): void
    {
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'Transaction History Export');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F172A'],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->setCellValue('A3', 'Generated At');
        $sheet->setCellValue('B3', $metadata['generated_at']);
        $sheet->setCellValue('A4', 'Preset');
        $sheet->setCellValue('B4', $metadata['range_preset']);
        $sheet->setCellValue('A5', 'Date From');
        $sheet->setCellValue('B5', $metadata['date_from']);
        $sheet->setCellValue('A6', 'Date To');
        $sheet->setCellValue('B6', $metadata['date_to']);
        $sheet->setCellValue('A7', 'School Year');
        $sheet->setCellValue('B7', $metadata['school_year']);
        $sheet->setCellValue('A8', 'Payment Mode');
        $sheet->setCellValue('B8', $metadata['payment_mode']);
        $sheet->setCellValue('C7', 'Search');
        $sheet->setCellValue('D7', $metadata['search']);

        $sheet->getStyle('A3:A8')->getFont()->setBold(true);
        $sheet->getStyle('C7')->getFont()->setBold(true);
        $sheet->getStyle('A3:D8')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        $sheet->setCellValue('A9', 'Transactions');
        $sheet->setCellValue('B9', (int) $summary['count']);
        $sheet->setCellValue('A10', 'Posted Amount');
        $sheet->setCellValue('B10', (float) $summary['posted_amount']);
        $sheet->setCellValue('A11', 'Corrected Amount');
        $sheet->setCellValue('B11', (float) $summary['corrected_amount']);
        $sheet->setCellValue('A12', 'Net Amount');
        $sheet->setCellValue('B12', (float) $summary['net_amount']);

        $sheet->getStyle('A9:A12')->getFont()->setBold(true);
        $sheet->getStyle('A9:B12')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);
        $sheet->getStyle('B10:B12')->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(24);
    }

    /**
     * @param  array<int, array{
     *     label: string,
     *     count: int,
     *     posted_amount: float,
     *     corrected_amount: float,
     *     net_amount: float
     * }>  $monthlyOverviewRows
     */
    private function buildMonthlyOverviewSheet(Worksheet $sheet, array $monthlyOverviewRows): void
    {
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'Monthly Overview');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'],
            ],
        ]);

        $sheet->setCellValue('A3', 'Range');
        $sheet->setCellValue('B3', 'Transactions');
        $sheet->setCellValue('C3', 'Posted Amount');
        $sheet->setCellValue('D3', 'Corrected Amount');
        $sheet->setCellValue('E3', 'Net Amount');
        $sheet->getStyle('A3:E3')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '111827'],
            ],
        ]);

        $startRow = 4;
        foreach (array_values($monthlyOverviewRows) as $index => $row) {
            $rowNumber = $startRow + $index;
            $sheet->setCellValue("A{$rowNumber}", $row['label']);
            $sheet->setCellValue("B{$rowNumber}", (int) $row['count']);
            $sheet->setCellValue("C{$rowNumber}", (float) $row['posted_amount']);
            $sheet->setCellValue("D{$rowNumber}", (float) $row['corrected_amount']);
            $sheet->setCellValue("E{$rowNumber}", (float) $row['net_amount']);
        }

        $endRow = max($startRow, $startRow + count($monthlyOverviewRows) - 1);
        $sheet->getStyle("A3:E{$endRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);
        $sheet->getStyle("C{$startRow}:E{$endRow}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');
        $sheet->getStyle("B{$startRow}:B{$endRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimension('A')->setWidth(24);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(16);
    }

    /**
     * @param  array<int, array{
     *     or_number: string,
     *     student_name: string,
     *     payment_mode_label: string,
     *     status_label: string,
     *     posted_at: string,
     *     cashier_name: string,
     *     amount: float,
     *     correction_reason: string,
     *     corrected_by_name: string
     * }>  $rows
     */
    private function buildMonthlyDetailSheet(Worksheet $sheet, string $label, array $rows): void
    {
        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', $label);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 13,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '334155'],
            ],
        ]);

        $headers = [
            'OR Number',
            'Student',
            'Payment Mode',
            'Status',
            'Posted On',
            'Cashier',
            'Amount',
            'Corrected By',
            'Correction Reason',
        ];

        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue("{$column}3", $header);
        }

        $sheet->getStyle('A3:I3')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '111827'],
            ],
        ]);

        $startRow = 4;
        foreach (array_values($rows) as $index => $row) {
            $rowNumber = $startRow + $index;
            $sheet->setCellValue("A{$rowNumber}", $row['or_number']);
            $sheet->setCellValue("B{$rowNumber}", $row['student_name']);
            $sheet->setCellValue("C{$rowNumber}", $row['payment_mode_label']);
            $sheet->setCellValue("D{$rowNumber}", $row['status_label']);
            $sheet->setCellValue("E{$rowNumber}", $row['posted_at']);
            $sheet->setCellValue("F{$rowNumber}", $row['cashier_name']);
            $sheet->setCellValue("G{$rowNumber}", (float) $row['amount']);
            $sheet->setCellValue("H{$rowNumber}", $row['corrected_by_name']);
            $sheet->setCellValue("I{$rowNumber}", $row['correction_reason']);
        }

        $endRow = max($startRow, $startRow + count($rows) - 1);
        $sheet->getStyle("A3:I{$endRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);
        $sheet->getStyle("G{$startRow}:G{$endRow}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');
        $sheet->getStyle("G{$startRow}:G{$endRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(28);
        $sheet->freezePane('A4');
        $sheet->setAutoFilter('A3:I3');
    }

    private function sheetNameForLabel(Spreadsheet $spreadsheet, string $label): string
    {
        $clean = preg_replace('/[\\\\\\/?*:\\[\\]]/', '', trim($label));
        $clean = $clean !== '' ? $clean : 'Details';
        $maxLength = 31;
        $base = mb_substr($clean, 0, $maxLength);
        $candidate = $base;
        $counter = 2;

        while ($spreadsheet->sheetNameExists($candidate)) {
            $suffix = " ({$counter})";
            $candidate = mb_substr($base, 0, $maxLength - mb_strlen($suffix)).$suffix;
            $counter++;
        }

        return $candidate;
    }
}
