<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registrar\ImportPermanentRecordsRequest;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\PermanentRecord;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Services\AuditLogService;
use App\Services\DashboardCacheService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (! in_array($extension, ['xls', 'xlsx'], true)) {
            return back()->with('error', 'Only official Excel workbook templates (.xlsx/.xls) are supported.');
        }

        $workbook = $this->parseWorkbookImport($file);
        if ($workbook === null) {
            return back()->with('error', 'Unable to read workbook.');
        }

        if (($workbook['missing_sheets'] ?? []) !== []) {
            return back()->with('error', 'Workbook is missing required sheets: '.implode(', ', $workbook['missing_sheets']).'.');
        }

        if (($workbook['invalid_sheets'] ?? []) !== []) {
            if (in_array('enrollment_export_missing_lrn_key', $workbook['invalid_sheets'], true)) {
                return back()->with(
                    'error',
                    'Enrollment workbook export cannot be imported directly because it does not include unique LRN keys per row.'
                );
            }

            return back()->with(
                'error',
                'Workbook headers do not match official template for sheets: '.implode(', ', $workbook['invalid_sheets']).'.'
            );
        }

        $summary = $this->importWorkbook($workbook['sheets']);

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

    public function preview(ImportPermanentRecordsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $file = $validated['import_file'];
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (! in_array($extension, ['xls', 'xlsx'], true)) {
            return back()->with('error', 'Only official Excel workbook templates (.xlsx/.xls) are supported.');
        }

        $workbook = $this->parseWorkbookImport($file);
        if ($workbook === null) {
            return back()->with('error', 'Unable to read import file.');
        }

        $sheetPreview = collect($workbook['sheets'])
            ->map(function (array $sheetData, string $sheetName): array {
                $headers = $sheetData['headers'] ?? [];
                $rows = $sheetData['rows'] ?? [];
                $expectedHeaders = $this->requiredHeadersBySheet()[$sheetName] ?? [];

                return [
                    'sheet' => $sheetName,
                    'processed_rows' => count($rows),
                    'missing_required_headers' => $expectedHeaders === $headers ? [] : $expectedHeaders,
                    'headers' => $headers,
                ];
            })
            ->values()
            ->all();

        return back()->with('import_preview', [
            'file_name' => $file->getClientOriginalName(),
            'detected_format' => strtoupper((string) $file->getClientOriginalExtension()),
            'mode' => 'workbook',
            'missing_sheets' => $workbook['missing_sheets'],
            'invalid_sheets' => $workbook['invalid_sheets'],
            'sheets' => $sheetPreview,
        ]);
    }

    public function downloadWorkbookTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        $sheetHeaders = [
            'students' => [
                'lrn',
                'last_name',
                'first_name',
                'middle_name',
                'gender',
                'birthdate',
                'student_email',
                'guardian_name',
                'guardian_contact_number',
                'guardian_email',
                'contact_email',
            ],
            'enrollment_history' => [
                'lrn',
                'school_year',
                'grade_level',
                'section',
                'status',
                'school_name',
                'general_average',
                'failed_subject_count',
                'remarks',
            ],
        ];

        foreach ($sheetHeaders as $sheetName => $headers) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($sheetName);
            $sheet->fromArray([$headers], null, 'A1');
        }

        $spreadsheet->setActiveSheetIndex(0);
        $fileName = 'registrar-import-template.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
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
    /**
     * @return array<string, array<int, string>>
     */
    private function requiredHeadersBySheet(): array
    {
        return [
            'students' => [
                'lrn',
                'last_name',
                'first_name',
                'middle_name',
                'gender',
                'birthdate',
                'student_email',
                'guardian_name',
                'guardian_contact_number',
                'guardian_email',
                'contact_email',
            ],
            'enrollment_history' => [
                'lrn',
                'school_year',
                'grade_level',
                'section',
                'status',
                'school_name',
                'general_average',
                'failed_subject_count',
                'remarks',
            ],
        ];
    }

    /**
     * @return array{
     *   sheets: array<string, array{headers: array<int, string>, rows: array<int, array<int, string>>}>,
     *   missing_sheets: array<int, string>,
     *   invalid_sheets: array<int, string>
     * }|null
     */
    private function parseWorkbookImport(UploadedFile $file): ?array
    {
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (\Throwable) {
            return null;
        }

        $sf1Compatibility = $this->parseSf1ExportWorkbook($spreadsheet);
        if ($sf1Compatibility !== null) {
            return $sf1Compatibility;
        }

        if ($this->isEnrollmentExportWorkbook($spreadsheet)) {
            return [
                'sheets' => [],
                'missing_sheets' => ['students', 'enrollment_history'],
                'invalid_sheets' => ['enrollment_export_missing_lrn_key'],
            ];
        }

        $requiredSheets = array_keys($this->requiredHeadersBySheet());
        $sheetMap = [];

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $normalizedName = strtolower(trim((string) $worksheet->getTitle()));
            $rows = $worksheet->toArray(null, true, true, false);
            if ($rows === [] || ! isset($rows[0]) || ! is_array($rows[0])) {
                continue;
            }

            $headers = $this->normalizeCsvHeaders($rows[0]);
            $dataRows = collect(array_slice($rows, 1))
                ->filter(fn (array $row): bool => ! $this->isCsvRowEmpty($row))
                ->map(fn (array $row): array => array_map(
                    fn ($value): string => trim((string) $value),
                    $row
                ))
                ->values()
                ->all();

            $sheetMap[$normalizedName] = [
                'headers' => $headers,
                'rows' => $dataRows,
            ];
        }

        $missingSheets = collect($requiredSheets)
            ->reject(fn (string $sheet): bool => array_key_exists($sheet, $sheetMap))
            ->values()
            ->all();

        $invalidSheets = [];
        foreach ($requiredSheets as $sheetName) {
            if (! isset($sheetMap[$sheetName])) {
                continue;
            }

            $headers = $sheetMap[$sheetName]['headers'];
            $requiredHeaders = $this->requiredHeadersBySheet()[$sheetName];
            if ($headers !== $requiredHeaders) {
                $invalidSheets[] = $sheetName;
            }
        }

        return [
            'sheets' => $sheetMap,
            'missing_sheets' => $missingSheets,
            'invalid_sheets' => $invalidSheets,
        ];
    }

    private function isEnrollmentExportWorkbook(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): bool
    {
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $title = strtolower(trim((string) $worksheet->getTitle()));
            if (! in_array($title, ['sy26-27', 'counter', 'per section'], true)) {
                continue;
            }

            $header = strtolower(trim((string) $worksheet->getCell('A5')->getCalculatedValue()));
            $nameHeader = strtolower(trim((string) $worksheet->getCell('B5')->getCalculatedValue()));
            if ($header === 'no.' && $nameHeader === 'name') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{
     *   sheets: array<string, array{headers: array<int, string>, rows: array<int, array<int, string>>}>,
     *   missing_sheets: array<int, string>,
     *   invalid_sheets: array<int, string>
     * }|null
     */
    private function parseSf1ExportWorkbook(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): ?array
    {
        $sf1Sheet = null;
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $title = strtolower(trim((string) $worksheet->getTitle()));
            if (str_contains($title, 'school_form_1')) {
                $sf1Sheet = $worksheet;
                break;
            }
        }

        if ($sf1Sheet === null) {
            return null;
        }

        $studentRows = [];
        for ($row = 7; $row <= 2000; $row++) {
            $lrn = preg_replace('/\D/', '', (string) $sf1Sheet->getCell("A{$row}")->getFormattedValue());
            $nameCell = trim((string) $sf1Sheet->getCell("C{$row}")->getFormattedValue());
            $sex = trim((string) $sf1Sheet->getCell("G{$row}")->getFormattedValue());
            $birthdate = trim((string) $sf1Sheet->getCell("H{$row}")->getFormattedValue());
            $addressStreet = trim((string) $sf1Sheet->getCell("P{$row}")->getFormattedValue());
            $addressBarangay = trim((string) $sf1Sheet->getCell("R{$row}")->getFormattedValue());
            $addressCity = trim((string) $sf1Sheet->getCell("U{$row}")->getFormattedValue());
            $addressProvince = trim((string) $sf1Sheet->getCell("W{$row}")->getFormattedValue());
            $father = trim((string) $sf1Sheet->getCell("AB{$row}")->getFormattedValue());
            $mother = trim((string) $sf1Sheet->getCell("AF{$row}")->getFormattedValue());

            if ($lrn === '' && $nameCell === '') {
                continue;
            }

            if ($lrn === '' || $nameCell === '') {
                continue;
            }

            [$lastName, $firstName, $middleName] = $this->parseSf1NameCell($nameCell);
            $guardianName = $father !== '' ? $father : $mother;
            $address = trim(implode(', ', array_filter([
                $addressStreet,
                $addressBarangay,
                $addressCity,
                $addressProvince,
            ])));

            $studentRows[] = [
                $lrn,
                $lastName,
                $firstName,
                $middleName,
                $sex,
                $birthdate,
                '',
                $guardianName,
                '',
                '',
                '',
            ];
        }

        return [
            'sheets' => [
                'students' => [
                    'headers' => $this->requiredHeadersBySheet()['students'],
                    'rows' => $studentRows,
                ],
                'enrollment_history' => [
                    'headers' => $this->requiredHeadersBySheet()['enrollment_history'],
                    'rows' => [],
                ],
            ],
            'missing_sheets' => [],
            'invalid_sheets' => [],
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function parseSf1NameCell(string $name): array
    {
        $parts = array_map('trim', explode(',', $name));
        $lastName = $parts[0] ?? '';
        $firstName = $parts[1] ?? '';
        $middleName = $parts[2] ?? '';

        return [$lastName, $firstName, $middleName];
    }

    /**
     * @param  array<string, array{headers: array<int, string>, rows: array<int, array<int, string>>}>  $sheets
     * @return array<string, int>
     */
    private function importWorkbook(array $sheets): array
    {
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

        DB::transaction(function () use ($sheets, &$summary): void {
            foreach (($sheets['students']['rows'] ?? []) as $row) {
                $summary['processed_rows']++;
                $rowData = $this->mapCsvRow($sheets['students']['headers'], $row);

                if ($this->importStudentSheetRow($rowData, $summary)) {
                    $summary['imported_rows']++;
                } else {
                    $summary['skipped_rows']++;
                }
            }

            foreach (($sheets['enrollment_history']['rows'] ?? []) as $row) {
                $summary['processed_rows']++;
                $rowData = $this->mapCsvRow($sheets['enrollment_history']['headers'], $row);

                if ($this->importPermanentRecordRow($rowData, $summary)) {
                    // importPermanentRecordRow already tracks imported_rows.
                } else {
                    $summary['skipped_rows']++;
                }
            }

        });

        return $summary;
    }

    private function importStudentSheetRow(array $rowData, array &$summary): bool
    {
        $lrn = preg_replace('/\D/', '', (string) ($rowData['lrn'] ?? ''));
        $firstName = trim((string) ($rowData['first_name'] ?? ''));
        $lastName = trim((string) ($rowData['last_name'] ?? ''));

        if ($lrn === '' || $firstName === '' || $lastName === '') {
            return false;
        }

        $student = Student::query()->where('lrn', $lrn)->first();

        if (! $student) {
            Student::query()->create([
                'lrn' => $lrn,
                'first_name' => $firstName,
                'middle_name' => ($rowData['middle_name'] ?? '') !== '' ? $rowData['middle_name'] : null,
                'last_name' => $lastName,
                'gender' => $this->normalizeGender($rowData['gender'] ?? null),
                'birthdate' => $this->parseBirthdate($rowData['birthdate'] ?? null),
            ]);
            $summary['created_students']++;

            return true;
        }

        $student->update([
            'first_name' => $firstName,
            'middle_name' => ($rowData['middle_name'] ?? '') !== '' ? $rowData['middle_name'] : null,
            'last_name' => $lastName,
            'gender' => $this->normalizeGender($rowData['gender'] ?? null),
            'birthdate' => $this->parseBirthdate($rowData['birthdate'] ?? null),
        ]);

        return true;
    }

    private function importDiscountDefinitionRow(array $rowData, array &$summary): bool
    {
        $name = trim((string) ($rowData['discount_name'] ?? ''));
        $type = strtolower(trim((string) ($rowData['discount_type'] ?? '')));
        $value = $this->parseDecimal($rowData['discount_value'] ?? null);

        if ($name === '' || ! in_array($type, ['fixed', 'percentage'], true) || $value === null) {
            return false;
        }

        $discount = Discount::query()->firstOrCreate(
            ['name' => $name],
            [
                'type' => $type,
                'value' => $value,
                'export_bucket' => $rowData['export_bucket'] ?? Discount::DEFAULT_EXPORT_BUCKET,
            ]
        );

        if (! $discount->wasRecentlyCreated) {
            $discount->update([
                'type' => $type,
                'value' => $value,
                'export_bucket' => $rowData['export_bucket'] ?? $discount->export_bucket,
            ]);
        } else {
            $summary['created_discounts']++;
        }

        return true;
    }

    private function importStudentDiscountTagRow(array $rowData, array &$summary): bool
    {
        $lrn = preg_replace('/\D/', '', (string) ($rowData['lrn'] ?? ''));
        $discountName = trim((string) ($rowData['discount_name'] ?? ''));
        $schoolYearPair = $this->parseSchoolYear($rowData['school_year'] ?? null);
        if ($lrn === '' || $discountName === '' || $schoolYearPair === null) {
            return false;
        }

        [$startYear, $endYear] = $schoolYearPair;
        $academicYearName = "{$startYear}-{$endYear}";

        $student = Student::query()->where('lrn', $lrn)->first();
        $discount = Discount::query()->where('name', $discountName)->first();
        $academicYear = AcademicYear::query()->where('name', $academicYearName)->first();

        if (! $student || ! $discount || ! $academicYear) {
            return false;
        }

        $tag = StudentDiscount::query()->firstOrCreate([
            'student_id' => $student->id,
            'discount_id' => $discount->id,
            'academic_year_id' => $academicYear->id,
        ]);

        if ($tag->wasRecentlyCreated) {
            $summary['tagged_student_discounts']++;
        }

        return true;
    }

    private function missingRequiredHeaders(array $headers): array
    {
        $requiredHeaders = ['lrn', 'school_year', 'grade_level'];

        return collect($requiredHeaders)
            ->reject(fn (string $header): bool => in_array($header, $headers, true))
            ->values()
            ->all();
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<int, string>>}|null
     */
    private function parseImportRows(UploadedFile $file): ?array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (in_array($extension, ['xls', 'xlsx'], true)) {
            try {
                $spreadsheet = IOFactory::load($file->getRealPath());
                $sheetRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            } catch (\Throwable) {
                return null;
            }

            if ($sheetRows === [] || ! isset($sheetRows[0]) || ! is_array($sheetRows[0])) {
                return null;
            }

            $headers = $this->normalizeCsvHeaders($sheetRows[0]);
            $rows = collect(array_slice($sheetRows, 1))
                ->filter(fn (array $row): bool => ! $this->isCsvRowEmpty($row))
                ->map(fn (array $row): array => array_map(
                    fn ($value): string => trim((string) $value),
                    $row
                ))
                ->values()
                ->all();

            return [
                'headers' => $headers,
                'rows' => $rows,
            ];
        }

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return null;
        }

        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);

            return null;
        }

        $headers = $this->normalizeCsvHeaders($headerRow);
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->isCsvRowEmpty($row)) {
                continue;
            }

            $rows[] = array_map(fn ($value): string => trim((string) $value), $row);
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
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
            $value = preg_replace('/[^a-z0-9_]/', '', $value) ?: '';

            return match ($value) {
                'learner_reference_number', 'lrn_no' => 'lrn',
                'academic_year', 'sy' => 'school_year',
                'year_level' => 'grade_level',
                default => $value,
            };
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
