<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ImportFinanceTransactionsRequest;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\LedgerEntry;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Transaction;
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
            ->where('action', 'finance.transactions.imported')
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
                    'created_transactions' => (int) ($snapshot['created_transactions'] ?? 0),
                    'updated_transactions' => (int) ($snapshot['updated_transactions'] ?? 0),
                    'created_students' => (int) ($snapshot['created_students'] ?? 0),
                    'created_academic_years' => (int) ($snapshot['created_academic_years'] ?? 0),
                    'created_grade_levels' => (int) ($snapshot['created_grade_levels'] ?? 0),
                    'created_sections' => (int) ($snapshot['created_sections'] ?? 0),
                    'created_enrollments' => (int) ($snapshot['created_enrollments'] ?? 0),
                    'created_ledger_entries' => (int) ($snapshot['created_ledger_entries'] ?? 0),
                    'skipped_rows' => (int) ($snapshot['skipped_rows'] ?? 0),
                    'performed_by' => $performedBy !== '' ? $performedBy : ($log->user?->name ?? 'System'),
                ];
            })
            ->values();

        return Inertia::render('finance/data-import/index', [
            'imports' => $imports,
        ]);
    }

    public function import(
        ImportFinanceTransactionsRequest $request,
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
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->isCsvRowEmpty($row)) {
                continue;
            }

            $rows[] = $this->mapCsvRow($headers, $row);
        }

        fclose($handle);

        usort($rows, function (array $leftRow, array $rightRow): int {
            return $this->resolveRowSortTimestamp($leftRow) <=> $this->resolveRowSortTimestamp($rightRow);
        });

        $summary = [
            'processed_rows' => 0,
            'imported_rows' => 0,
            'created_transactions' => 0,
            'updated_transactions' => 0,
            'created_students' => 0,
            'created_academic_years' => 0,
            'created_grade_levels' => 0,
            'created_sections' => 0,
            'created_enrollments' => 0,
            'created_ledger_entries' => 0,
            'skipped_rows' => 0,
        ];

        foreach ($rows as $rowData) {
            $summary['processed_rows']++;
            if (! $this->importFinanceRow($rowData, $summary, (int) auth()->id())) {
                $summary['skipped_rows']++;
            }
        }

        Setting::set('finance_transactions_last_import_at', now()->toDateTimeString(), 'finance');
        Setting::set('finance_transactions_last_import_name', $file->getClientOriginalName(), 'finance');
        Setting::set('finance_transactions_last_import_summary', json_encode($summary), 'finance');

        $auditLogService->log('finance.transactions.imported', Transaction::class, null, [
            ...$summary,
            'file_name' => $file->getClientOriginalName(),
        ]);

        DashboardCacheService::bust();

        return back()->with(
            'success',
            "Import complete. Imported {$summary['imported_rows']} of {$summary['processed_rows']} rows ({$summary['skipped_rows']} skipped)."
        );
    }

    private function importFinanceRow(array $rowData, array &$summary, int $cashierId): bool
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
        $orNumber = $this->firstAvailable($rowData, [
            'or_number',
            'or_no',
            'receipt_no',
            'receipt_number',
        ]);
        $paymentAmount = $this->parseDecimal($this->firstAvailable($rowData, [
            'amount',
            'payment_amount',
            'total_amount',
        ]));

        if ($lrn === '' || ! $schoolYearPair || $orNumber === null || $paymentAmount === null || $paymentAmount <= 0) {
            return false;
        }

        try {
            [$startYear, $endYear] = $schoolYearPair;
            $academicYearName = "{$startYear}-{$endYear}";
            $transactionDate = $this->parseTransactionDate($this->firstAvailable($rowData, [
                'payment_date',
                'transaction_date',
                'posted_at',
                'date',
            ])) ?? now();
            $paymentMode = $this->normalizePaymentMode($this->firstAvailable($rowData, [
                'payment_mode',
                'payment_method',
                'method',
            ]));
            $referenceNo = $this->firstAvailable($rowData, ['reference_no', 'reference']);
            $remarks = $this->firstAvailable($rowData, ['remarks', 'notes']);
            $entryDescription = $this->firstAvailable($rowData, [
                'description',
                'entry_description',
                'payment_description',
            ]) ?: 'Imported Payment';

            [$parsedFirstName, $parsedLastName] = $this->parseNameParts(
                $this->firstAvailable($rowData, ['name', 'student_name', 'learner_name'])
            );

            $student = Student::query()->where('lrn', $lrn)->first();
            if (! $student) {
                $student = Student::query()->create([
                    'lrn' => $lrn,
                    'first_name' => $this->firstAvailable($rowData, ['first_name', 'firstname', 'given_name']) ?: ($parsedFirstName ?: 'Unknown'),
                    'last_name' => $this->firstAvailable($rowData, ['last_name', 'lastname', 'surname']) ?: ($parsedLastName ?: 'Student'),
                ]);
                $summary['created_students']++;
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

            $enrollment = Enrollment::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYear->id)
                ->first();

            if (! $enrollment) {
                $gradeLevelValue = $this->firstAvailable($rowData, ['grade_level', 'year_level']);
                $gradeLevelName = $this->normalizeGradeLevelName($gradeLevelValue);

                if ($gradeLevelName === null) {
                    return false;
                }

                $gradeLevel = GradeLevel::query()->firstOrCreate(
                    ['name' => $gradeLevelName],
                    ['level_order' => $this->resolveLevelOrder($gradeLevelName)]
                );
                if ($gradeLevel->wasRecentlyCreated) {
                    $summary['created_grade_levels']++;
                }

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

                $enrollment = Enrollment::query()->create([
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'grade_level_id' => $gradeLevel->id,
                    'section_id' => $section?->id,
                    'payment_term' => 'cash',
                    'downpayment' => 0,
                    'status' => 'enrolled',
                ]);
                $summary['created_enrollments']++;
            }

            $transaction = Transaction::query()->where('or_number', $orNumber)->first();

            if (! $transaction) {
                $transaction = Transaction::query()->create([
                    'or_number' => $orNumber,
                    'student_id' => $student->id,
                    'cashier_id' => $cashierId,
                    'total_amount' => $paymentAmount,
                    'payment_mode' => $paymentMode,
                    'reference_no' => $referenceNo,
                    'remarks' => $remarks,
                    'status' => 'posted',
                ]);
                $summary['created_transactions']++;
            } else {
                $transaction->update([
                    'student_id' => $student->id,
                    'cashier_id' => $cashierId,
                    'total_amount' => $paymentAmount,
                    'payment_mode' => $paymentMode,
                    'reference_no' => $referenceNo,
                    'remarks' => $remarks,
                    'status' => 'posted',
                ]);
                $summary['updated_transactions']++;
            }

            $transaction->created_at = $transactionDate;
            $transaction->updated_at = $transactionDate;
            $transaction->save();

            $transaction->items()->delete();
            $transaction->items()->create([
                'fee_id' => null,
                'inventory_item_id' => null,
                'description' => $entryDescription,
                'amount' => $paymentAmount,
            ]);

            LedgerEntry::query()
                ->where('reference_id', $transaction->id)
                ->where('description', 'like', 'Imported Payment%')
                ->delete();

            $previousRunningBalance = (float) (LedgerEntry::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $academicYear->id)
                ->latest('date')
                ->latest('id')
                ->value('running_balance') ?? 0.0);

            LedgerEntry::query()->create([
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'date' => $transactionDate->toDateString(),
                'description' => "Imported Payment ({$transaction->or_number})",
                'debit' => null,
                'credit' => $paymentAmount,
                'running_balance' => round($previousRunningBalance - $paymentAmount, 2),
                'reference_id' => $transaction->id,
            ]);
            $summary['created_ledger_entries']++;

            $summary['imported_rows']++;

            return true;
        } catch (\Throwable $throwable) {
            report($throwable);

            return false;
        }
    }

    private function resolveRowSortTimestamp(array $rowData): int
    {
        $date = $this->parseTransactionDate($this->firstAvailable($rowData, [
            'payment_date',
            'transaction_date',
            'posted_at',
            'date',
        ]));

        return $date?->timestamp ?? now()->timestamp;
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

    private function normalizePaymentMode(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '', 'cash', 'full' => 'cash',
            'gcash', 'e_wallet', 'e-wallet', 'ewallet', 'maya', 'paymaya' => 'gcash',
            'bank_transfer', 'bank transfer' => 'bank_transfer',
            default => str_replace(' ', '_', $normalized),
        };
    }

    private function parseTransactionDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $throwable) {
            return null;
        }
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
}
