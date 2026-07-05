<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\Setting;
use App\Services\AuditLogService;
use App\Services\SchoolForms\Sf9TemplateAdapter;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Process\Process;

class ReportCardExportController extends Controller
{
    public function __invoke(Request $request, Sf9TemplateAdapter $sf9TemplateAdapter, AuditLogService $auditLogService)
    {
        $format = strtolower((string) $request->query('format', 'xlsx'));
        if (! in_array($format, ['xlsx', 'pdf'], true)) {
            abort(422, 'Unsupported export format.');
        }

        $enrollmentId = $request->integer('enrollment_id');
        $enrollment = Enrollment::query()
            ->with(['student', 'academicYear', 'section.adviser', 'gradeLevel'])
            ->findOrFail($enrollmentId);

        $student = $enrollment->student;
        if (! $student) {
            abort(404, 'Student not found.');
        }

        $academicYear = $enrollment->academicYear;
        $section = $enrollment->section;

        $metadata = [
            'name' => mb_strtoupper(trim("{$student->last_name}, {$student->first_name} {$student->middle_name}")),
            'lrn' => $student->lrn,
            'age' => $student->birthdate ? \Carbon\Carbon::parse($student->birthdate)->age : '',
            'sex' => mb_strtoupper((string) $student->gender),
            'grade_level' => $enrollment->gradeLevel?->name ?? 'N/A',
            'section' => $section?->name ?? 'N/A',
            'school_year' => $academicYear?->name ?? 'N/A',
            'division' => Setting::get('division', 'Quezon City'),
            'district' => Setting::get('district', 'District 1'),
            'school' => Setting::get('school_name', 'Marriott School'),
            'adviser' => $section?->adviser?->name ?? 'N/A',
            'principal' => Setting::get('principal_name', 'Dr. Maria Santos'),
        ];

        $quarterGrades = FinalGrade::query()
            ->with('subjectAssignment.teacherSubject.subject:id,subject_name')
            ->where('enrollment_id', $enrollment->id)
            ->get();

        $learningAreas = $quarterGrades
            ->groupBy(fn (FinalGrade $g) => $g->subjectAssignment?->teacherSubject?->subject?->subject_name ?? 'Subject')
            ->map(function ($grades, string $subjectName): array {
                $gradeByQuarter = collect($grades)->keyBy(fn (FinalGrade $g) => (string) $g->quarter);
                $quarterValues = collect(['1', '2', '3', '4'])
                    ->map(fn (string $q) => $gradeByQuarter->get($q)?->grade)
                    ->filter(fn ($v) => $v !== null)
                    ->map(fn ($v) => (float) $v)
                    ->values();

                $finalAverage = $quarterValues->isNotEmpty() ? round($quarterValues->avg(), 2) : null;

                return [
                    'subject' => $subjectName,
                    'q1' => $this->formatGradeValue($gradeByQuarter->get('1')?->grade),
                    'q2' => $this->formatGradeValue($gradeByQuarter->get('2')?->grade),
                    'q3' => $this->formatGradeValue($gradeByQuarter->get('3')?->grade),
                    'q4' => $this->formatGradeValue($gradeByQuarter->get('4')?->grade),
                    'final' => $this->formatGradeValue($finalAverage),
                    'remarks' => $finalAverage !== null ? ($finalAverage >= 75 ? 'Passed' : 'Failed') : '',
                ];
            })
            ->values()
            ->all();

        // For attendance, we would ideally query the Attendance model. For now we use the template default logic.
        $attendance = [
            'school_days' => ['20', '22', '21', '21', '20', '20', '15', '20', '19', '20', '10'],
            'present' => ['20', '22', '21', '21', '20', '20', '15', '20', '19', '20', '10'],
            'absent' => ['0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0'],
        ];

        $outputDirectory = storage_path('app/private/exports');
        if (! is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0777, true);
        }
        $timestamp = now()->format('Ymd_His');
        $filename = sprintf('SF9_%s_%s.xlsx', $student->lrn, $timestamp);
        $outputPath = $outputDirectory.'/'.$filename;

        $sf9TemplateAdapter->exportRows(
            base_path('templates/SF9.xlsx'),
            $outputPath,
            $metadata,
            $learningAreas,
            $attendance
        );

        if ($format === 'pdf') {
            $spreadsheet = IOFactory::load($outputPath);
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $pageSetup = $sheet->getPageSetup();
                $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
                $pageSetup->setFitToPage(true);
                $pageSetup->setFitToWidth(1);
                $pageSetup->setFitToHeight(1);
                $pageSetup->setScale(null);
                $sheet->getPageMargins()
                    ->setTop(0.25)
                    ->setBottom(0.25)
                    ->setLeft(0.25)
                    ->setRight(0.25);
                $sheet->getPageSetup()->setHorizontalCentered(true);
            }
            $landscapeWriter = new Xlsx($spreadsheet);
            $landscapeWriter->setPreCalculateFormulas(false);
            $landscapeWriter->save($outputPath);
            $spreadsheet->disconnectWorksheets();

            $process = new Process([
                'soffice',
                '--headless',
                '--convert-to',
                'pdf:calc_pdf_Export',
                '--outdir',
                $outputDirectory,
                $outputPath,
            ]);
            $process->setTimeout(120);
            $process->run();

            if (! $process->isSuccessful()) {
                abort(500, 'Failed to convert SF9 workbook to PDF.');
            }

            $generatedPdfPath = $outputDirectory.'/'.pathinfo($outputPath, PATHINFO_FILENAME).'.pdf';
            if (! is_file($generatedPdfPath)) {
                abort(500, 'Generated SF9 PDF file was not found.');
            }

            $pdfDownloadName = sprintf('SF9_%s_%s.pdf', $student->lrn, $timestamp);
            $auditLogService->log('report_card.exported', $student, null, [
                'lrn' => $student->lrn,
                'enrollment_id' => $enrollment->id,
                'academic_year' => $academicYear?->name,
                'format' => 'pdf',
            ]);

            @unlink($outputPath);

            return response()->download($generatedPdfPath, $pdfDownloadName)->deleteFileAfterSend();
        }

        $auditLogService->log('report_card.exported', $student, null, [
            'lrn' => $student->lrn,
            'enrollment_id' => $enrollment->id,
            'academic_year' => $academicYear?->name,
            'format' => 'xlsx',
        ]);

        return response()->download($outputPath)->deleteFileAfterSend();
    }

    private function formatGradeValue(null|float|string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $numeric = (float) $value;

        if (fmod($numeric, 1.0) === 0.0) {
            return number_format($numeric, 0, '.', '');
        }

        return number_format($numeric, 2, '.', '');
    }
}
