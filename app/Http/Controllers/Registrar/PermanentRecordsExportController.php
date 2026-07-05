<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradeRelease;
use App\Models\PermanentRecord;
use App\Models\Setting;
use App\Models\Student;
use App\Services\AuditLogService;
use App\Services\SchoolForms\Sf10TemplateAdapter;
use Illuminate\Http\Request;

class PermanentRecordsExportController extends Controller
{
    public function __invoke(Request $request, Sf10TemplateAdapter $sf10TemplateAdapter, AuditLogService $auditLogService)
    {
        $studentId = $request->integer('student_id');
        $student = Student::query()->findOrFail($studentId);

        $records = PermanentRecord::query()
            ->with(['academicYear:id,name', 'gradeLevel:id,name'])
            ->where('student_id', $student->id)
            ->orderByDesc('academic_year_id')
            ->orderByDesc('id')
            ->get();

        $formattedRecords = $records->map(function (PermanentRecord $record) use ($student): array {
            $enrollment = Enrollment::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $record->academic_year_id)
                ->latest('id')
                ->first();

            $sectionName = $enrollment?->section?->name ?? 'N/A';
            $adviserName = $enrollment?->section?->adviser?->name ?? 'N/A';

            $subjects = [];
            if ($enrollment) {
                $releasedQuarters = GradeRelease::query()
                    ->where('academic_year_id', $record->academic_year_id)
                    ->where('section_id', $enrollment->section_id)
                    ->pluck('quarter')
                    ->map(fn ($q) => (string) $q)
                    ->all();

                if ($releasedQuarters === []) {
                    $academicYearStatus = AcademicYear::query()->whereKey($record->academic_year_id)->value('status');
                    if ($academicYearStatus !== 'ongoing') {
                        $releasedQuarters = ['1', '2', '3', '4'];
                    }
                }

                $quarterGrades = FinalGrade::query()
                    ->with('subjectAssignment.teacherSubject.subject:id,subject_name')
                    ->where('enrollment_id', $enrollment->id)
                    ->whereIn('quarter', $releasedQuarters)
                    ->get();

                $subjects = $quarterGrades
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
                            'name' => $subjectName,
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
            }

            return [
                'school' => $record->school_name ?: Setting::get('school_name', 'Marriott School'),
                'school_id' => Setting::get('school_id', '482518'),
                'district' => Setting::get('district', 'District 1'),
                'division' => Setting::get('division', 'Quezon City'),
                'region' => 'NCR',
                'grade' => $record->gradeLevel?->name ?? 'N/A',
                'section' => $sectionName,
                'school_year' => $record->academicYear?->name ?? 'N/A',
                'adviser' => $adviserName,
                'subjects' => $subjects,
                'general_average' => $this->formatGradeValue($record->general_average),
            ];
        })->toArray();

        $learner = [
            'lrn' => $student->lrn,
            'name' => mb_strtoupper(trim("{$student->last_name}, {$student->first_name} {$student->middle_name}")),
            'sex' => mb_strtoupper((string) $student->gender),
            'birthdate' => $student->birthdate ? \Carbon\Carbon::parse($student->birthdate)->format('m/d/Y') : '',
        ];

        $outputDirectory = storage_path('app/private/exports');
        if (! is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0777, true);
        }

        $filename = sprintf('SF10_%s_%s.xlsx', $student->lrn, now()->format('Ymd_His'));
        $outputPath = $outputDirectory.'/'.$filename;

        $sf10TemplateAdapter->exportRows(
            base_path('templates/SF10.xlsx'),
            $outputPath,
            $learner,
            $formattedRecords
        );

        $auditLogService->log('permanent_record.exported', $student, null, [
            'lrn' => $student->lrn,
            'academic_history_count' => count($formattedRecords),
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
