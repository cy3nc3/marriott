<?php

namespace App\Services\Finance;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DailyReportsWorkbookExporter
{
    /**
     * @param  array{
     *     generated_at: string,
     *     school_year: string,
     *     cashier: string,
     *     payment_mode: string,
     *     date_from: string,
     *     date_to: string
     * }  $metadata
     * @param  array{
     *     transaction_count: int,
     *     gross_collection: float,
     *     cash_on_hand: float,
     *     digital_collection: float,
     *     void_adjustments: float
     * }  $summary
     * @param  array<int, array{category: string, transaction_count: int, total_amount: float}>  $breakdownRows
     * @param  array<int, array{
     *     or_number: string,
     *     student_name: string,
     *     payment_type: string,
     *     payment_mode: string,
     *     payment_mode_label: string,
     *     status: string,
     *     amount: float,
     *     cashier_name: string,
     *     posted_at: string
     * }>  $transactionRows
     */
    public function export(
        string $outputPath,
        array $metadata,
        array $summary,
        array $breakdownRows,
        array $transactionRows,
    ): void {
        $spreadsheet = new Spreadsheet;
        $cashierGroups = $this->groupTransactionsByCashier($transactionRows);
        $cashierSummaries = $this->buildCashierSummaries($cashierGroups);
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Summary');
        $this->buildSummarySheet($summarySheet, $metadata, $summary, $breakdownRows, $cashierSummaries);

        $transactionsSheet = $spreadsheet->createSheet();
        $transactionsSheet->setTitle('Transactions');
        $this->buildTransactionsSheet($transactionsSheet, $transactionRows);

        foreach ($cashierGroups as $cashierName => $rows) {
            $cashierSheet = $spreadsheet->createSheet();
            $cashierSheet->setTitle($this->sheetNameForCashier($spreadsheet, $cashierName));
            $this->buildTransactionsSheet($cashierSheet, $rows, "Transactions - {$cashierName}");
        }

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save($outputPath);
        $spreadsheet->disconnectWorksheets();
    }

    /**
     * @param  array{
     *     generated_at: string,
     *     school_year: string,
     *     cashier: string,
     *     payment_mode: string,
     *     date_from: string,
     *     date_to: string
     * }  $metadata
     * @param  array{
     *     transaction_count: int,
     *     gross_collection: float,
     *     cash_on_hand: float,
     *     digital_collection: float,
     *     void_adjustments: float
     * }  $summary
     * @param  array<int, array{category: string, transaction_count: int, total_amount: float}>  $breakdownRows
     * @param  array<int, array{
     *     cashier: string,
     *     transaction_count: int,
     *     gross_collection: float,
     *     cash_collection: float,
     *     digital_collection: float,
     *     void_adjustments: float,
     *     net_collection: float
     * }>  $cashierSummaries
     */
    private function buildSummarySheet(
        Worksheet $sheet,
        array $metadata,
        array $summary,
        array $breakdownRows,
        array $cashierSummaries,
    ): void {
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'Daily Collection Report');
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
                'startColor' => ['rgb' => '1D4ED8'],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->setCellValue('A3', 'Generated At');
        $sheet->setCellValue('B3', $metadata['generated_at']);
        $sheet->setCellValue('A4', 'School Year');
        $sheet->setCellValue('B4', $metadata['school_year']);
        $sheet->setCellValue('A5', 'Cashier');
        $sheet->setCellValue('B5', $metadata['cashier']);
        $sheet->setCellValue('A6', 'Payment Mode');
        $sheet->setCellValue('B6', $metadata['payment_mode']);
        $sheet->setCellValue('A7', 'Date From');
        $sheet->setCellValue('B7', $metadata['date_from']);
        $sheet->setCellValue('A8', 'Date To');
        $sheet->setCellValue('B8', $metadata['date_to']);

        $sheet->getStyle('A3:A8')->getFont()->setBold(true);
        $sheet->getStyle('A3:B8')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        $sheet->setCellValue('A10', 'Summary Totals');
        $sheet->mergeCells('A10:D10');
        $sheet->getStyle('A10')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'],
            ],
        ]);

        $summaryRows = [
            ['Transaction Count', (float) $summary['transaction_count'], false],
            ['Gross Collection', (float) $summary['gross_collection'], true],
            ['Cash on Hand', (float) $summary['cash_on_hand'], true],
            ['Digital Collection', (float) $summary['digital_collection'], true],
            ['Void Adjustments', (float) $summary['void_adjustments'], true],
        ];

        $summaryStartRow = 11;
        foreach ($summaryRows as $index => [$label, $amount, $isCurrency]) {
            $row = $summaryStartRow + $index;
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}", $amount);
            if ($isCurrency) {
                $sheet->getStyle("B{$row}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');
            } else {
                $sheet->getStyle("B{$row}")
                    ->getNumberFormat()
                    ->setFormatCode('0');
            }
        }

        $sheet->getStyle("A{$summaryStartRow}:B".($summaryStartRow + count($summaryRows) - 1))
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB'],
                    ],
                ],
            ]);
        $sheet->getStyle("A{$summaryStartRow}:A".($summaryStartRow + count($summaryRows) - 1))
            ->getFont()
            ->setBold(true);

        $breakdownHeaderRow = $summaryStartRow + count($summaryRows) + 2;
        $sheet->setCellValue("A{$breakdownHeaderRow}", 'Collection Breakdown');
        $sheet->mergeCells("A{$breakdownHeaderRow}:D{$breakdownHeaderRow}");
        $sheet->getStyle("A{$breakdownHeaderRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '7C3AED'],
            ],
        ]);

        $columnHeaderRow = $breakdownHeaderRow + 1;
        $sheet->setCellValue("A{$columnHeaderRow}", 'Category');
        $sheet->setCellValue("B{$columnHeaderRow}", 'Transactions');
        $sheet->setCellValue("C{$columnHeaderRow}", 'Total Amount');
        $sheet->getStyle("A{$columnHeaderRow}:C{$columnHeaderRow}")->applyFromArray([
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

        $breakdownDataStartRow = $columnHeaderRow + 1;
        foreach (array_values($breakdownRows) as $index => $rowData) {
            $row = $breakdownDataStartRow + $index;
            $sheet->setCellValue("A{$row}", $rowData['category']);
            $sheet->setCellValue("B{$row}", $rowData['transaction_count']);
            $sheet->setCellValue("C{$row}", (float) $rowData['total_amount']);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        }

        $breakdownDataEndRow = max($breakdownDataStartRow, $breakdownDataStartRow + count($breakdownRows) - 1);
        $sheet->getStyle("A{$columnHeaderRow}:C{$breakdownDataEndRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        $sheet->getStyle("B{$breakdownDataStartRow}:B{$breakdownDataEndRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$breakdownDataStartRow}:C{$breakdownDataEndRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getColumnDimension('A')->setWidth(32);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(4);

        $cashierHeaderRow = $breakdownDataEndRow + 1;
        $sheet->setCellValue("A{$cashierHeaderRow}", 'Cashier Breakdown');
        $sheet->mergeCells("A{$cashierHeaderRow}:G{$cashierHeaderRow}");
        $sheet->getStyle("A{$cashierHeaderRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E40AF'],
            ],
        ]);

        $cashierColumnsRow = $cashierHeaderRow + 1;
        $sheet->setCellValue("A{$cashierColumnsRow}", 'Cashier');
        $sheet->setCellValue("B{$cashierColumnsRow}", 'Transactions');
        $sheet->setCellValue("C{$cashierColumnsRow}", 'Gross Collection');
        $sheet->setCellValue("D{$cashierColumnsRow}", 'Cash Collection');
        $sheet->setCellValue("E{$cashierColumnsRow}", 'Digital Collection');
        $sheet->setCellValue("F{$cashierColumnsRow}", 'Void Adjustments');
        $sheet->setCellValue("G{$cashierColumnsRow}", 'Net Collection');
        $sheet->getStyle("A{$cashierColumnsRow}:G{$cashierColumnsRow}")->applyFromArray([
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

        $cashierDataStartRow = $cashierColumnsRow + 1;
        foreach (array_values($cashierSummaries) as $index => $cashierSummary) {
            $row = $cashierDataStartRow + $index;
            $sheet->setCellValue("A{$row}", $cashierSummary['cashier']);
            $sheet->setCellValue("B{$row}", $cashierSummary['transaction_count']);
            $sheet->setCellValue("C{$row}", $cashierSummary['gross_collection']);
            $sheet->setCellValue("D{$row}", $cashierSummary['cash_collection']);
            $sheet->setCellValue("E{$row}", $cashierSummary['digital_collection']);
            $sheet->setCellValue("F{$row}", $cashierSummary['void_adjustments']);
            $sheet->setCellValue("G{$row}", $cashierSummary['net_collection']);
            $sheet->getStyle("C{$row}:G{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        }

        $cashierDataEndRow = max($cashierDataStartRow, $cashierDataStartRow + count($cashierSummaries) - 1);
        $sheet->getStyle("A{$cashierColumnsRow}:G{$cashierDataEndRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);
        $sheet->getStyle("B{$cashierDataStartRow}:B{$cashierDataEndRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$cashierDataStartRow}:G{$cashierDataEndRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(16);
    }

    /**
     * @param  array<int, array{
     *     or_number: string,
     *     student_name: string,
     *     payment_type: string,
     *     payment_mode: string,
     *     payment_mode_label: string,
     *     status: string,
     *     amount: float,
     *     cashier_name: string,
     *     posted_at: string
     * }>  $transactionRows
     */
    private function buildTransactionsSheet(
        Worksheet $sheet,
        array $transactionRows,
        string $title = 'Transaction Details',
    ): void {
        $headers = [
            'OR Number',
            'Student',
            'Type',
            'Mode',
            'Status',
            'Amount',
            'Cashier',
            'Posted At',
        ];

        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F172A'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $headerRow = 3;
        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue("{$column}{$headerRow}", $header);
        }

        $sheet->getStyle("A{$headerRow}:H{$headerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F2937'],
            ],
        ]);

        $dataStartRow = $headerRow + 1;
        foreach (array_values($transactionRows) as $index => $row) {
            $targetRow = $dataStartRow + $index;
            $sheet->setCellValue("A{$targetRow}", $row['or_number']);
            $sheet->setCellValue("B{$targetRow}", $row['student_name']);
            $sheet->setCellValue("C{$targetRow}", $row['payment_type']);
            $sheet->setCellValue("D{$targetRow}", $row['payment_mode_label']);
            $sheet->setCellValue("E{$targetRow}", strtoupper($row['status']));
            $sheet->setCellValue("F{$targetRow}", (float) $row['amount']);
            $sheet->setCellValue("G{$targetRow}", $row['cashier_name']);
            $sheet->setCellValue("H{$targetRow}", $row['posted_at']);
        }

        $dataEndRow = max($dataStartRow, $dataStartRow + count($transactionRows) - 1);
        $sheet->getStyle("A{$headerRow}:H{$dataEndRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        $sheet->getStyle("F{$dataStartRow}:F{$dataEndRow}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');
        $sheet->getStyle("F{$dataStartRow}:F{$dataEndRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("E{$dataStartRow}:E{$dataEndRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(32);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(24);
        $sheet->getColumnDimension('H')->setWidth(22);

        $sheet->freezePane('A4');
        $sheet->setAutoFilter("A{$headerRow}:H{$headerRow}");
    }

    /**
     * @param  array<int, array{
     *     or_number: string,
     *     student_name: string,
     *     payment_type: string,
     *     payment_mode: string,
     *     payment_mode_label: string,
     *     status: string,
     *     amount: float,
     *     cashier_name: string,
     *     posted_at: string
     * }>  $transactionRows
     * @return array<string, array<int, array{
     *     or_number: string,
     *     student_name: string,
     *     payment_type: string,
     *     payment_mode: string,
     *     payment_mode_label: string,
     *     status: string,
     *     amount: float,
     *     cashier_name: string,
     *     posted_at: string
     * }>>
     */
    private function groupTransactionsByCashier(array $transactionRows): array
    {
        $grouped = [];

        foreach ($transactionRows as $row) {
            $cashier = trim((string) $row['cashier_name']) !== ''
                ? (string) $row['cashier_name']
                : 'Unassigned Cashier';
            if (! array_key_exists($cashier, $grouped)) {
                $grouped[$cashier] = [];
            }

            $grouped[$cashier][] = $row;
        }

        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);

        return $grouped;
    }

    /**
     * @param  array<string, array<int, array{
     *     or_number: string,
     *     student_name: string,
     *     payment_type: string,
     *     payment_mode: string,
     *     payment_mode_label: string,
     *     status: string,
     *     amount: float,
     *     cashier_name: string,
     *     posted_at: string
     * }>>  $cashierGroups
     * @return array<int, array{
     *     cashier: string,
     *     transaction_count: int,
     *     gross_collection: float,
     *     cash_collection: float,
     *     digital_collection: float,
     *     void_adjustments: float,
     *     net_collection: float
     * }>
     */
    private function buildCashierSummaries(array $cashierGroups): array
    {
        $correctedStatuses = ['voided', 'refunded', 'reissued'];
        $summaries = [];

        foreach ($cashierGroups as $cashier => $rows) {
            $transactionCount = count(array_unique(array_map(
                fn (array $row): string => (string) $row['or_number'],
                $rows
            )));
            $grossCollection = 0.0;
            $cashCollection = 0.0;
            $digitalCollection = 0.0;
            $voidAdjustments = 0.0;

            foreach ($rows as $row) {
                $status = strtolower((string) $row['status']);
                $amount = (float) $row['amount'];
                $paymentMode = strtolower((string) $row['payment_mode']);

                if (in_array($status, $correctedStatuses, true)) {
                    $voidAdjustments += $amount;

                    continue;
                }

                $grossCollection += $amount;

                if ($paymentMode === 'cash') {
                    $cashCollection += $amount;
                } else {
                    $digitalCollection += $amount;
                }
            }

            $summaries[] = [
                'cashier' => $cashier,
                'transaction_count' => $transactionCount,
                'gross_collection' => round($grossCollection, 2),
                'cash_collection' => round($cashCollection, 2),
                'digital_collection' => round($digitalCollection, 2),
                'void_adjustments' => round($voidAdjustments, 2),
                'net_collection' => round($grossCollection - $voidAdjustments, 2),
            ];
        }

        return $summaries;
    }

    private function sheetNameForCashier(Spreadsheet $spreadsheet, string $cashierName): string
    {
        $cleanName = preg_replace('/[\\\\\\/?*:\\[\\]]/', '', trim($cashierName));
        $cleanName = $cleanName !== '' ? $cleanName : 'Unassigned Cashier';
        $maxLength = 31;
        $base = mb_substr($cleanName, 0, $maxLength);
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
