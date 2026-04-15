<?php

namespace App\Services\SchoolForms;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Drawing;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Sf2TemplateAdapter
{
    /**
     * @var list<string>
     */
    private array $attendanceColumns = [
        'F',
        'H',
        'I',
        'J',
        'K',
        'L',
        'N',
        'O',
        'P',
        'Q',
        'R',
        'T',
        'U',
        'V',
        'X',
        'Z',
        'AB',
        'AC',
        'AD',
        'AE',
        'AF',
        'AG',
        'AI',
        'AJ',
        'AK',
    ];

    /**
     * @param  array{school_id: string, school_year: string, report_month: string, school_name: string, grade_level: string, section: string}  $metadata
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function exportRows(string $templatePath, string $outputPath, array $metadata, array $rows): void
    {
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('F3', $metadata['school_id']);
        $sheet->setCellValue('M3', $metadata['school_year']);
        $sheet->setCellValue('S3', $metadata['report_month']);
        $sheet->setCellValue('F4', $metadata['school_name']);
        $sheet->setCellValue('AA4', $metadata['grade_level']);
        $sheet->setCellValue('AM4', $metadata['section']);

        $maleRow = 8;
        $femaleRow = 26;

        foreach ($rows as $row) {
            $targetRow = $this->isFemale((string) ($row['gender'] ?? '')) ? $femaleRow++ : $maleRow++;

            $sheet->setCellValue("C{$targetRow}", (string) ($row['name'] ?? ''));

            foreach (array_values((array) ($row['attendance'] ?? [])) as $index => $status) {
                if (! isset($this->attendanceColumns[$index])) {
                    break;
                }

                $this->applyAttendanceStatus($sheet, $this->attendanceColumns[$index].$targetRow, (string) $status);
            }

            $sheet->setCellValue("AM{$targetRow}", (string) ($row['total_absent'] ?? ''));
            $sheet->setCellValue("AO{$targetRow}", (string) ($row['total_present'] ?? ''));
            $sheet->setCellValue("AQ{$targetRow}", (string) ($row['remarks'] ?? ''));
        }

        $this->save($spreadsheet, $outputPath);
    }

    private function isFemale(string $gender): bool
    {
        return in_array(strtolower(trim($gender)), ['female', 'f'], true);
    }

    private function applyAttendanceStatus(Worksheet $sheet, string $coordinate, string $status): void
    {
        $normalizedStatus = strtolower(trim($status));

        $sheet->setCellValue($coordinate, '');
        $sheet->getStyle($coordinate)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_NONE,
            ],
            'borders' => [
                'diagonalDirection' => Borders::DIAGONAL_NONE,
                'diagonal' => [
                    'borderStyle' => Border::BORDER_NONE,
                ],
            ],
        ]);

        if (in_array($normalizedStatus, ['absent', 'x'], true)) {
            $this->applyAbsentMarker($sheet, $coordinate);

            return;
        }

        if (in_array($normalizedStatus, ['tardy_late_comer', 'late', 'l'], true)) {
            $this->applyTardyMarker($sheet, $coordinate, 'top');

            return;
        }

        if (in_array($normalizedStatus, ['tardy_cutting_classes', 'cutting', 'c'], true)) {
            $this->applyTardyMarker($sheet, $coordinate, 'bottom');
        }
    }

    private function applyTardyMarker(Worksheet $sheet, string $coordinate, string $trianglePosition): void
    {
        $cellWidth = $this->cellWidthPixels($sheet, $coordinate);
        $cellHeight = $this->cellHeightPixels($sheet, $coordinate);
        $drawing = new MemoryDrawing;
        $drawing->setName("sf2-{$trianglePosition}-marker");
        $drawing->setDescription("SF2 {$trianglePosition} triangle marker");
        $drawing->setImageResource($this->triangleImage($trianglePosition, $cellWidth, $cellHeight));
        $drawing->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
        $drawing->setMimeType(MemoryDrawing::MIMETYPE_PNG);
        $drawing->setCoordinates($coordinate);
        $drawingWidth = max((int) floor($cellWidth * 0.74), 1);
        $drawingHeight = max((int) floor($cellHeight * 0.74), 1);
        $drawing->setOffsetX(max((int) floor(($cellWidth - $drawingWidth) / 2), 0));
        $drawing->setOffsetY(max((int) floor(($cellHeight - $drawingHeight) / 2), 0));
        $drawing->setWidth($drawingWidth);
        $drawing->setHeight($drawingHeight);
        $drawing->setWorksheet($sheet);
    }

    private function applyAbsentMarker(Worksheet $sheet, string $coordinate): void
    {
        $styleTarget = $this->styleTarget($sheet, $coordinate);

        $sheet->setCellValue($coordinate, 'X');
        $sheet->getStyle($styleTarget)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'size' => 14,
                'color' => [
                    'rgb' => '000000',
                ],
            ],
        ]);
    }

    private function triangleImage(string $trianglePosition, int $width, int $height)
    {
        $image = imagecreatetruecolor($width, $height);
        imageantialias($image, true);
        $white = imagecolorallocate($image, 255, 255, 255);
        $fillColor = imagecolorallocate($image, 0, 0, 0);
        $lineColor = imagecolorallocate($image, 0, 0, 0);

        imagefill($image, 0, 0, $white);

        $points = $trianglePosition === 'top'
            ? [0, 0, $width - 1, 0, 0, $height - 1]
            : [$width - 1, 0, 0, $height - 1, $width - 1, $height - 1];

        imagefilledpolygon($image, $points, 3, $fillColor);
        imageline($image, 0, $height - 1, $width - 1, 0, $lineColor);

        return $image;
    }

    private function cellWidthPixels(Worksheet $sheet, string $coordinate): int
    {
        $mergeRange = $this->mergeRangeForCoordinate($sheet, $coordinate);

        if ($mergeRange === null) {
            preg_match('/[A-Z]+/', $coordinate, $matches);
            $column = $matches[0] ?? 'A';
            $width = $sheet->getColumnDimension($column)->getWidth();

            return max(Drawing::cellDimensionToPixels((float) $width, new Font(false)), 1);
        }

        [$startCoordinate, $endCoordinate] = explode(':', $mergeRange);
        [$startColumn] = Coordinate::coordinateFromString($startCoordinate);
        [$endColumn] = Coordinate::coordinateFromString($endCoordinate);
        $startIndex = Coordinate::columnIndexFromString($startColumn);
        $endIndex = Coordinate::columnIndexFromString($endColumn);
        $totalWidth = 0;

        for ($columnIndex = $startIndex; $columnIndex <= $endIndex; $columnIndex++) {
            $column = Coordinate::stringFromColumnIndex($columnIndex);
            $width = $sheet->getColumnDimension($column)->getWidth();
            $totalWidth += max(Drawing::cellDimensionToPixels((float) $width, new Font(false)), 1);
        }

        return max($totalWidth, 1);
    }

    private function styleTarget(Worksheet $sheet, string $coordinate): string
    {
        return $this->mergeRangeForCoordinate($sheet, $coordinate) ?? $coordinate;
    }

    private function mergeRangeForCoordinate(Worksheet $sheet, string $coordinate): ?string
    {
        foreach ($sheet->getMergeCells() as $mergeRange) {
            [$startCoordinate] = explode(':', $mergeRange);

            if ($startCoordinate === $coordinate) {
                return $mergeRange;
            }
        }

        return null;
    }

    private function cellHeightPixels(Worksheet $sheet, string $coordinate): int
    {
        preg_match('/\d+/', $coordinate, $matches);
        $row = (int) ($matches[0] ?? 1);
        $height = $sheet->getRowDimension($row)->getRowHeight();

        return max(Drawing::pointsToPixels($height > 0 ? $height : 15), 1);
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
