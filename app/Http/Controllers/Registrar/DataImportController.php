<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registrar\ImportPermanentRecordsRequest;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\PermanentRecord;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Services\AuditLogService;
use App\Services\DashboardCacheService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DataImportController extends Controller
{
    public function index(): Response
    {
        $imports = AuditLog::query()
            ->with('user:id,name,first_name,last_name')
            ->where('action', 'registrar.permanent_records.imported')
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(function (AuditLog $log): array {
                $snapshot = is_array($log->new_values) ? $log->new_values : [];
                $performedBy = trim((string) (($log->user?->first_name ?? '').' '.($log->user?->last_name ?? '')));

                return [
                    'id' => (int) $log->id,
                    'imported_at' => $log->created_at?->toDateTimeString(),
                    'file_name' => (string) ($snapshot['file_name'] ?? '-'),
                    'processed_rows' => (int) ($snapshot['processed_rows'] ?? 0),
                    'imported_rows' => (int) ($snapshot['imported_rows'] ?? 0),
                    'created_records' => (int) ($snapshot['created_records'] ?? 0),
                    'updated_records' => (int) ($snapshot['updated_records'] ?? 0),
                    'created_students' => (int) ($snapshot['created_students'] ?? 0),
                    'created_academic_years' => (int) ($snapshot['created_academic_years'] ?? 0),
                    'created_grade_levels' => (int) ($snapshot['created_grade_levels'] ?? 0),
                    'skipped_rows' => (int) ($snapshot['skipped_rows'] ?? 0),
                    'performed_by' => $performedBy !== '' ? $performedBy : ($log->user?->name ?? 'System'),
                ];
            })
            ->values();

        return Inertia::render('registrar/data-import/index', [
            'last_import' => [
                'at' => Setting::get('registrar_permanent_records_last_import_at'),
                'file_name' => Setting::get('registrar_permanent_records_last_import_name'),
                'summary' => $this->resolveLastImportSummary(),
            ],
            'imports' => $imports,
        ]);
    }

    public function import(
        ImportPermanentRecordsRequest $request,
        AuditLogService $auditLogService
    ): RedirectResponse {
        $validated = $request->validated();
        $file = $validated['import_file'];
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return back()->with('error', 'Unable to read import file.');
        }

        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);

            return back()->with('error', 'Import file is empty.');
        }

        $headers = $this->normalizeCsvHeaders($headerRow);

        $summary = [
            'processed_rows' => 0,
            'imported_rows' => 0,
            'created_records' => 0,
            'updated_records' => 0,
            'created_students' => 0,
            'created_academic_years' => 0,
            'created_grade_levels' => 0,
            'created_sections' => 0,
            'created_enrollments' => 0,
            'skipped_rows' => 0,
        ];

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->isCsvRowEmpty($row)) {
                continue;
            }

            $summary['processed_rows']++;
            $rowData = $this->mapCsvRow($headers, $row);

            if (! $this->importPermanentRecordRow($rowData, $summary)) {
                $summary['skipped_rows']++;
            }
        }

        fclose($handle);

        Setting::set('registrar_permanent_records_last_import_at', now()->toDateTimeString(), 'registrar');
        Setting::set('registrar_permanent_records_last_import_name', $file->getClientOriginalName(), 'registrar');
        Setting::set('registrar_permanent_records_last_import_summary', json_encode($summary), 'registrar');

        $auditLogService->log('registrar.permanent_records.imported', PermanentRecord::class, null, [
            ...$summary,
            'file_name' => $file->getClientOriginalName(),
        ]);

        DashboardCacheService::bust();

        return back()->with(
            'success',
            "Import complete. Imported {$summary['imported_rows']} of {$summary['processed_rows']} rows ({$summary['skipped_rows']} skipped)."
        );
    }

    private function importPermanentRecordRow(array $rowData, array &$summary): bool
    {
        $lrn = preg_replace('/\D/', '', (string) $this->firstAvailable($rowData, [
            'lrn',
            'learner_reference_number',
        ]));

        $schoolYearValue = $this->firstAvailable($rowData, [
            'school_year',
            'academic_year',
            'sy',
        ]);
        $schoolYearPair = $this->parseSchoolYear($schoolYearValue);
        $gradeLevelValue = $this->firstAvailable($rowData, ['grade_level', 'year_level']);
        $gradeLevelName = $this->normalizeGradeLevelName($gradeLevelValue);

        if ($lrn === '' || ! $schoolYearPair || $gradeLevelName === null) {
            return false;
        }

        try {
            [$startYear, $endYear] = $schoolYearPair;
            $academicYearName = "{$startYear}-{$endYear}";

            [$parsedFirstName, $parsedLastName] = $this->parseNameParts(
                $this->firstAvailable($rowData, ['name', 'student_name', 'learner_name'])
            );

            $firstName = $this->firstAvailable($rowData, ['first_name', 'firstname', 'given_name']) ?: $parsedFirstName;
            $lastName = $this->firstAvailable($rowData, ['last_name', 'lastname', 'surname']) ?: $parsedLastName;
            $gender = $this->normalizeGender($this->firstAvailable($rowData, ['gender', 'sex']));
            $birthdate = $this->parseBirthdate($this->firstAvailable($rowData, ['birthday', 'birthdate', 'date_of_birth']));

            $student = Student::query()->where('lrn', $lrn)->first();
            if (! $student) {
                $student = Student::query()->create([
                    'lrn' => $lrn,
                    'first_name' => $firstName ?: 'Unknown',
                    'last_name' => $lastName ?: 'Student',
                    'gender' => $gender,
                    'birthdate' => $birthdate,
                ]);
                $summary['created_students']++;
            } else {
                $studentUpdates = [];

                if ($firstName || $lastName) {
                    $studentUpdates['first_name'] = $firstName ?: $student->first_name;
                    $studentUpdates['last_name'] = $lastName ?: $student->last_name;
                }

                if ($gender !== null) {
                    $studentUpdates['gender'] = $gender;
                }

                if ($birthdate !== null) {
                    $studentUpdates['birthdate'] = $birthdate;
                }

                if ($studentUpdates !== []) {
                    $student->update($studentUpdates);
                }
            }

            $academicYear = AcademicYear::query()->firstOrCreate(
                ['name' => $academicYearName],
                [
                    'start_date' => "{$startYear}-06-01",
                    'end_date' => "{$endYear}-03-31",
                    'status' => $endYear < (int) now()->format('Y') ? 'completed' : 'upcoming',
                    'current_quarter' => $endYear < (int) now()->format('Y') ? '4' : '1',
                ]
            );
            if ($academicYear->wasRecentlyCreated) {
                $summary['created_academic_years']++;
            }

            $gradeLevel = GradeLevel::query()->firstOrCreate(
                ['name' => $gradeLevelName],
                ['level_order' => $this->resolveLevelOrder($gradeLevelName)]
            );
            if ($gradeLevel->wasRecentlyCreated) {
                $summary['created_grade_levels']++;
            }

            $generalAverage = $this->parseDecimal(
                $this->firstAvailable($rowData, ['grades', 'general_average', 'average', 'final_grade'])
            );
            $status = $this->resolveRecordStatusFromStudentData(
                $this->firstAvailable($rowData, ['status', 'record_status']),
                $generalAverage
            );
            $failedSubjectCount = $this->parseInteger(
                $this->firstAvailable($rowData, ['failed_subject_count', 'failed_subjects'])
            );
            $remarks = $this->firstAvailable($rowData, ['remarks', 'notes']);
            $sectionName = $this->firstAvailable($rowData, ['section', 'section_name']);
            $section = null;

            if ($sectionName !== null) {
                $section = Section::query()->firstOrCreate(
                    [
                        'academic_year_id' => $academicYear->id,
                        'grade_level_id' => $gradeLevel->id,
                        'name' => $sectionName,
                    ],
                    [
                        'adviser_id' => null,
                    ]
                );

                if ($section->wasRecentlyCreated) {
                    $summary['created_sections']++;
                }
            }

            $enrollment = Enrollment::query()->firstOrNew([
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
            ]);

            if (! $enrollment->exists) {
                $enrollment->payment_term = 'cash';
                $enrollment->downpayment = 0;
                $enrollment->status = 'enrolled';
                $summary['created_enrollments']++;
            }

            $enrollment->grade_level_id = $gradeLevel->id;
            $enrollment->section_id = $section?->id;
            $enrollment->save();

            $permanentRecord = PermanentRecord::query()->updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'school_name' => $this->firstAvailable($rowData, ['school_name']) ?: config('app.name', 'Marriott School'),
                    'grade_level_id' => $gradeLevel->id,
                    'general_average' => $generalAverage,
                    'status' => $status,
                    'failed_subject_count' => $failedSubjectCount ?? 0,
                    'remarks' => $remarks,
                    'conditional_resolved_at' => $status === 'conditional' ? null : now(),
                    'conditional_resolution_notes' => $status === 'conditional'
                        ? null
                        : ($this->firstAvailable($rowData, ['conditional_resolution_notes']) ?: 'Imported as resolved'),
                ]
            );

            if ($permanentRecord->wasRecentlyCreated) {
                $summary['created_records']++;
            } else {
                $summary['updated_records']++;
            }

            $summary['imported_rows']++;

            return true;
        } catch (\Throwable $throwable) {
            report($throwable);

            return false;
        }
    }

    /**
     * @param  array<int, string>  $headerRow
     * @return array<int, string>
     */
    private function normalizeCsvHeaders(array $headerRow): array
    {
        return array_map(function ($header): string {
            $value = strtolower(trim((string) $header));
            $value = str_replace([' ', '-'], '_', $value);

            return preg_replace('/[^a-z0-9_]/', '', $value) ?: '';
        }, $headerRow);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string|null>  $row
     * @return array<string, string>
     */
    private function mapCsvRow(array $headers, array $row): array
    {
        $rowData = [];

        foreach ($headers as $index => $header) {
            $rowData[$header] = trim((string) ($row[$index] ?? ''));
        }

        return $rowData;
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private function isCsvRowEmpty(array $row): bool
    {
        return count(array_filter(
            $row,
            fn ($value): bool => trim((string) $value) !== ''
        )) === 0;
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function parseSchoolYear(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        if (preg_match('/(\d{4})\D+(\d{4})/', $value, $matches) !== 1) {
            return null;
        }

        $startYear = (int) ($matches[1] ?? 0);
        $endYear = (int) ($matches[2] ?? 0);

        if ($startYear <= 0 || $endYear <= 0 || $endYear <= $startYear) {
            return null;
        }

        return [$startYear, $endYear];
    }

    private function normalizeGradeLevelName(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        if (preg_match('/(\d+)/', $value, $matches) === 1) {
            $grade = (int) $matches[1];
            if ($grade < 1 || $grade > 12) {
                return null;
            }

            return "Grade {$grade}";
        }

        return trim($value);
    }

    private function resolveLevelOrder(string $gradeLevelName): int
    {
        if (preg_match('/(\d+)/', $gradeLevelName, $matches) === 1) {
            return (int) $matches[1];
        }

        $maxLevelOrder = (int) GradeLevel::query()->max('level_order');

        return max($maxLevelOrder + 1, 1);
    }

    private function normalizeRecordStatus(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'conditional' => 'conditional',
            'retained', 'failed' => 'retained',
            'completed', 'terminal' => 'completed',
            default => 'promoted',
        };
    }

    private function resolveRecordStatusFromStudentData(?string $value, ?float $generalAverage): string
    {
        $explicit = $this->normalizeRecordStatus($value);
        if (trim((string) $value) !== '') {
            return $explicit;
        }

        if ($generalAverage !== null) {
            return $generalAverage < 75 ? 'retained' : 'promoted';
        }

        return 'completed';
    }

    private function parseDecimal(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $parsedValue = (float) $value;

        return $parsedValue >= 0 ? round($parsedValue, 2) : null;
    }

    private function parseInteger(?string $value): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return max((int) $value, 0);
    }

    private function firstAvailable(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }

            $value = trim((string) $row[$key]);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function parseNameParts(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [null, null];
        }

        $normalizedValue = trim($value);

        if (str_contains($normalizedValue, ',')) {
            [$lastNamePart, $firstNamePart] = array_map(
                fn (string $part): string => trim($part),
                explode(',', $normalizedValue, 2)
            );

            return [
                $firstNamePart !== '' ? $firstNamePart : null,
                $lastNamePart !== '' ? $lastNamePart : null,
            ];
        }

        $segments = preg_split('/\s+/', $normalizedValue) ?: [];
        if (count($segments) === 1) {
            return [$segments[0], null];
        }

        $lastName = array_pop($segments);
        $firstName = trim(implode(' ', $segments));

        return [
            $firstName !== '' ? $firstName : null,
            $lastName !== '' ? $lastName : null,
        ];
    }

    private function normalizeGender(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalizedValue = strtolower(trim($value));

        return match ($normalizedValue) {
            'm', 'male' => 'Male',
            'f', 'female' => 'Female',
            default => ucfirst($normalizedValue),
        };
    }

    private function parseBirthdate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $throwable) {
            return null;
        }
    }

    /**
     * @return array<string, int>|null
     */
    private function resolveLastImportSummary(): ?array
    {
        $rawSummary = Setting::get('registrar_permanent_records_last_import_summary');

        if (! is_string($rawSummary) || trim($rawSummary) === '') {
            return null;
        }

        $decoded = json_decode($rawSummary, true);

        if (! is_array($decoded)) {
            return null;
        }

        return [
            'processed_rows' => (int) ($decoded['processed_rows'] ?? 0),
            'imported_rows' => (int) ($decoded['imported_rows'] ?? 0),
            'created_records' => (int) ($decoded['created_records'] ?? 0),
            'updated_records' => (int) ($decoded['updated_records'] ?? 0),
            'created_students' => (int) ($decoded['created_students'] ?? 0),
            'created_academic_years' => (int) ($decoded['created_academic_years'] ?? 0),
            'created_grade_levels' => (int) ($decoded['created_grade_levels'] ?? 0),
            'created_sections' => (int) ($decoded['created_sections'] ?? 0),
            'created_enrollments' => (int) ($decoded['created_enrollments'] ?? 0),
            'skipped_rows' => (int) ($decoded['skipped_rows'] ?? 0),
        ];
    }
}
