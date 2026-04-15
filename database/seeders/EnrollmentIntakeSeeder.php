<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\Finance\BillingScheduleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EnrollmentIntakeSeeder extends Seeder
{
    private const DEFAULT_PARENT_BIRTHDAY = '1980-01-01';

    public function run(): void
    {
        $this->call([
            GradeLevelSeeder::class,
            SectionSeeder::class,
        ]);

        $activeAcademicYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->orderByDesc('start_date')
            ->first();

        if (! $activeAcademicYear instanceof AcademicYear) {
            $activeAcademicYear = AcademicYear::query()
                ->where('status', 'upcoming')
                ->orderByDesc('created_at')
                ->first();
        }

        if (! $activeAcademicYear instanceof AcademicYear) {
            $this->command?->warn('No school year found with status ongoing or upcoming. Set one first, then run EnrollmentIntakeSeeder again.');

            return;
        }

        $completedAcademicYear = AcademicYear::query()
            ->where('status', 'completed')
            ->orderByDesc('end_date')
            ->first();

        $sf1Rows = $this->readCsvRows(base_path('tests/Fixtures/imports/registrar_sf1_sample.csv'));
        $permanentRecordRows = $this->readCsvRows(base_path('tests/Fixtures/imports/registrar_permanent_records_sample.csv'));

        $permanentRecordLrns = [];
        foreach ($permanentRecordRows as $row) {
            $lrn = $this->normalizeLrn($row['lrn'] ?? null);
            if ($lrn === null) {
                continue;
            }

            $permanentRecordLrns[$lrn] = true;
        }

        $oldStudentRows = [];
        $newStudentRows = [];

        foreach ($sf1Rows as $row) {
            $lrn = $this->normalizeLrn($row['lrn'] ?? null);
            if ($lrn === null) {
                continue;
            }

            $normalizedRow = [
                'lrn' => $lrn,
                'first_name' => trim((string) ($row['first_name'] ?? '')),
                'last_name' => trim((string) ($row['last_name'] ?? '')),
                'gender' => trim((string) ($row['gender'] ?? '')),
                'birthdate' => trim((string) ($row['birthdate'] ?? '')),
                'address' => trim((string) ($row['address'] ?? '')),
                'guardian_name' => trim((string) ($row['guardian_name'] ?? '')),
                'contact_number' => trim((string) ($row['contact_number'] ?? '')),
                'grade_level' => trim((string) ($row['grade_level'] ?? '')),
                'section' => trim((string) ($row['section'] ?? '')),
            ];

            if (isset($permanentRecordLrns[$lrn])) {
                $oldStudentRows[] = $normalizedRow;
            } else {
                $newStudentRows[] = $normalizedRow;
            }
        }

        if ($oldStudentRows === [] || $newStudentRows === []) {
            return;
        }

        $leftOutOldStudent = $oldStudentRows[0];
        $leftOutNewStudent = $newStudentRows[0];

        $profileOnlyRows = array_values(array_filter([
            $oldStudentRows[1] ?? null,
            $newStudentRows[1] ?? null,
            $oldStudentRows[2] ?? null,
            $newStudentRows[2] ?? null,
        ]));

        $pastYearOnlyRows = array_values(array_filter([
            $oldStudentRows[3] ?? null,
            $newStudentRows[3] ?? null,
            $oldStudentRows[4] ?? null,
            $newStudentRows[4] ?? null,
        ]));

        $leftOutLrns = [
            $leftOutOldStudent['lrn'] => true,
            $leftOutNewStudent['lrn'] => true,
        ];

        $profileOnlyLrns = [];
        foreach ($profileOnlyRows as $row) {
            $profileOnlyLrns[$row['lrn']] = true;
        }

        $pastYearOnlyLrns = [];
        foreach ($pastYearOnlyRows as $row) {
            $pastYearOnlyLrns[$row['lrn']] = true;
        }

        $paymentTerms = ['cash', 'monthly', 'quarterly', 'semi-annual'];
        $nonCashDeposits = [1500.0, 2500.0, 3200.0, 4200.0, 5000.0];
        $billingScheduleService = app(BillingScheduleService::class);
        $activeYearSections = Section::query()
            ->where('academic_year_id', $activeAcademicYear->id)
            ->orderBy('id')
            ->get();
        $completedYearSections = $completedAcademicYear instanceof AcademicYear
            ? Section::query()
                ->where('academic_year_id', $completedAcademicYear->id)
                ->orderBy('id')
                ->get()
            : collect();

        $activeIntakeIndex = 0;
        $pastYearIntakeIndex = 0;
        foreach ($sf1Rows as $row) {
            $normalizedRow = $this->normalizeSf1Row($row);
            if (! $normalizedRow) {
                continue;
            }

            $lrn = $normalizedRow['lrn'];
            if (isset($leftOutLrns[$lrn])) {
                continue;
            }

            $student = $this->upsertStudentWithAccounts($normalizedRow);

            if (isset($profileOnlyLrns[$lrn])) {
                continue;
            }

            if (isset($pastYearOnlyLrns[$lrn])) {
                if (
                    $completedAcademicYear instanceof AcademicYear
                    && (int) $completedAcademicYear->id !== (int) $activeAcademicYear->id
                ) {
                    $pastPaymentTerm = $paymentTerms[$pastYearIntakeIndex % count($paymentTerms)];
                    $pastDownpayment = $pastPaymentTerm === 'cash'
                        ? 0.0
                        : $nonCashDeposits[$pastYearIntakeIndex % count($nonCashDeposits)];

                    $pastSection = $this->resolveSectionAssignment(
                        $completedYearSections,
                        "{$lrn}|{$completedAcademicYear->id}|past"
                    );
                    if ($pastSection instanceof Section) {
                        $pastEnrollment = $this->upsertEnrollmentForYear(
                            $student,
                            $completedAcademicYear,
                            $pastSection,
                            $pastPaymentTerm,
                            $pastDownpayment,
                            'enrolled'
                        );

                        if ($pastEnrollment instanceof Enrollment) {
                            $billingScheduleService->syncForEnrollment($pastEnrollment);
                        }
                    }

                    $pastYearIntakeIndex++;
                }

                continue;
            }

            $paymentTerm = $paymentTerms[$activeIntakeIndex % count($paymentTerms)];
            $downpayment = $paymentTerm === 'cash'
                ? 0.0
                : $nonCashDeposits[$activeIntakeIndex % count($nonCashDeposits)];

            $activeSection = $this->resolveSectionAssignment(
                $activeYearSections,
                "{$lrn}|{$activeAcademicYear->id}|active"
            );
            if (! $activeSection instanceof Section) {
                continue;
            }

            $activeEnrollment = $this->upsertEnrollmentForYear(
                $student,
                $activeAcademicYear,
                $activeSection,
                $paymentTerm,
                $downpayment,
                'for_cashier_payment'
            );

            if ($activeEnrollment instanceof Enrollment) {
                $billingScheduleService->syncForEnrollment($activeEnrollment);
            }

            $activeIntakeIndex++;
        }

        $this->command?->info('Enrollment intake seeding completed.');
        $this->command?->line('Left out for manual enrollment testing:');
        $this->command?->line(
            "OLD STUDENT: {$leftOutOldStudent['lrn']} | {$leftOutOldStudent['first_name']} {$leftOutOldStudent['last_name']} | {$leftOutOldStudent['grade_level']} - {$leftOutOldStudent['section']}"
        );
        $this->command?->line(
            "NEW STUDENT: {$leftOutNewStudent['lrn']} | {$leftOutNewStudent['first_name']} {$leftOutNewStudent['last_name']} | {$leftOutNewStudent['grade_level']} - {$leftOutNewStudent['section']}"
        );
        $this->command?->line(
            'Discrepancy scenarios seeded: student-not-found (2), no-active-enrollment ('.(count($profileOnlyRows) + count($pastYearOnlyRows)).').'
        );
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readCsvRows(string $path): array
    {
        if (! file_exists($path)) {
            return [];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);

            return [];
        }

        $headers = array_map(function (?string $value): string {
            $normalized = strtolower(trim((string) $value));
            $normalized = str_replace([' ', '-'], '_', $normalized);

            return preg_replace('/[^a-z0-9_]/', '', $normalized) ?: '';
        }, $headerRow);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn ($value): bool => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $mappedRow = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $mappedRow[$header] = trim((string) ($row[$index] ?? ''));
            }

            $rows[] = $mappedRow;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<string, string>  $row
     * @return array{lrn: string, first_name: string, last_name: string, gender: string, birthdate: string, address: string, guardian_name: string, contact_number: string, grade_level: string, section: string}|null
     */
    private function normalizeSf1Row(array $row): ?array
    {
        $lrn = $this->normalizeLrn($row['lrn'] ?? null);
        if (! $lrn) {
            return null;
        }

        return [
            'lrn' => $lrn,
            'first_name' => trim((string) ($row['first_name'] ?? '')),
            'last_name' => trim((string) ($row['last_name'] ?? '')),
            'gender' => trim((string) ($row['gender'] ?? '')),
            'birthdate' => trim((string) ($row['birthdate'] ?? '')),
            'address' => trim((string) ($row['address'] ?? '')),
            'guardian_name' => trim((string) ($row['guardian_name'] ?? '')),
            'contact_number' => trim((string) ($row['contact_number'] ?? '')),
            'grade_level' => trim((string) ($row['grade_level'] ?? '')),
            'section' => trim((string) ($row['section'] ?? '')),
        ];
    }

    private function normalizeLrn(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits !== '' ? $digits : null;
    }

    /**
     * @param  array{lrn: string, first_name: string, last_name: string, gender: string, birthdate: string, address: string, guardian_name: string, contact_number: string, grade_level: string, section: string}  $row
     */
    private function upsertStudentWithAccounts(array $row): Student
    {
        $student = Student::query()->firstOrNew(['lrn' => $row['lrn']]);

        $student->fill([
            'first_name' => $row['first_name'],
            'middle_name' => null,
            'last_name' => $row['last_name'],
            'gender' => $this->normalizeGender($row['gender']),
            'birthdate' => $this->normalizeBirthdate($row['birthdate'])?->toDateString(),
            'address' => $row['address'],
            'guardian_name' => $row['guardian_name'] !== '' ? $row['guardian_name'] : "Guardian {$row['last_name']}",
            'contact_number' => $this->normalizeContactNumber($row['contact_number']),
            'is_lis_synced' => false,
            'sync_error_flag' => false,
            'sync_error_notes' => null,
        ]);
        $student->save();

        $studentEmail = $this->buildStudentEmail($student);
        $studentPassword = $this->buildStudentDefaultPassword($student);

        $studentUser = $student->user;
        if (! $studentUser) {
            $studentUser = User::query()->firstOrCreate(
                ['email' => $studentEmail],
                [
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'name' => trim("{$student->first_name} {$student->last_name}"),
                    'birthday' => $student->birthdate,
                    'role' => UserRole::STUDENT,
                    'is_active' => true,
                    'password' => Hash::make($studentPassword),
                    'must_change_password' => true,
                ]
            );
        }

        $studentUser->update([
            'email' => $studentEmail,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'name' => trim("{$student->first_name} {$student->last_name}"),
            'birthday' => $student->birthdate,
            'role' => UserRole::STUDENT,
            'is_active' => true,
            'access_expires_at' => null,
            'must_change_password' => true,
        ]);

        if ($student->user_id !== $studentUser->id) {
            $student->update(['user_id' => $studentUser->id]);
        }

        $parentUser = User::query()->firstOrCreate(
            ['email' => "parent.{$student->lrn}@marriott.edu"],
            [
                'first_name' => 'Parent',
                'last_name' => $student->last_name,
                'name' => "Parent {$student->last_name}",
                'birthday' => self::DEFAULT_PARENT_BIRTHDAY,
                'role' => UserRole::PARENT,
                'is_active' => true,
                'password' => Hash::make($this->buildStudentDefaultPassword($student)),
                'must_change_password' => true,
            ]
        );

        $parentUser->update([
            'first_name' => 'Parent',
            'last_name' => $student->last_name,
            'name' => "Parent {$student->last_name}",
            'birthday' => self::DEFAULT_PARENT_BIRTHDAY,
            'role' => UserRole::PARENT,
            'is_active' => true,
            'access_expires_at' => null,
            'must_change_password' => true,
        ]);

        $linkExists = DB::table('parent_student')
            ->where('parent_id', $parentUser->id)
            ->where('student_id', $student->id)
            ->exists();

        if ($linkExists) {
            DB::table('parent_student')
                ->where('parent_id', $parentUser->id)
                ->where('student_id', $student->id)
                ->update(['updated_at' => now()]);
        } else {
            DB::table('parent_student')->insert([
                'parent_id' => $parentUser->id,
                'student_id' => $student->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $student->fresh();
    }

    private function upsertEnrollmentForYear(
        Student $student,
        AcademicYear $academicYear,
        Section $section,
        string $paymentTerm,
        float $downpayment,
        string $status
    ): Enrollment {
        return Enrollment::query()->updateOrCreate(
            [
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
            ],
            [
                'grade_level_id' => (int) $section->grade_level_id,
                'section_id' => $section->id,
                'payment_term' => $paymentTerm,
                'downpayment' => $paymentTerm === 'cash' ? 0 : round($downpayment, 2),
                'status' => $status,
            ]
        );
    }

    private function resolveSectionAssignment(Collection $sections, string $seed): ?Section
    {
        if ($sections->isEmpty()) {
            return null;
        }

        $index = (int) sprintf('%u', crc32($seed)) % $sections->count();
        $section = $sections->values()->get($index);

        return $section instanceof Section ? $section : null;
    }

    private function normalizeGender(string $value): string
    {
        $normalized = strtolower(trim($value));
        if ($normalized === 'female') {
            return 'Female';
        }

        return 'Male';
    }

    private function normalizeBirthdate(string $value): Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable $exception) {
            return Carbon::create(2010, 1, 1);
        }
    }

    private function normalizeContactNumber(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';
        if (strlen($digits) >= 11) {
            return substr($digits, 0, 11);
        }

        return str_pad($digits, 11, '0');
    }

    private function buildStudentEmail(Student $student): string
    {
        $normalizedSurname = strtolower((string) preg_replace('/[^a-z0-9]/i', '', (string) $student->last_name));
        if ($normalizedSurname === '') {
            $normalizedSurname = 'student';
        }

        return "{$normalizedSurname}.{$student->lrn}@marriott.edu";
    }

    private function buildStudentDefaultPassword(Student $student): string
    {
        $birthdate = $student->birthdate instanceof Carbon
            ? $student->birthdate
            : Carbon::parse((string) $student->birthdate);

        return $this->buildDefaultPassword((string) $student->first_name, $birthdate->toDateString());
    }

    private function buildDefaultPassword(string $rawFirstName, string $birthday): string
    {
        $firstToken = Str::of($rawFirstName)
            ->trim()
            ->explode(' ')
            ->map(fn (string $value): string => trim($value))
            ->filter(fn (string $value): bool => $value !== '')
            ->first() ?? 'user';

        $normalizedToken = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $firstToken));
        if ($normalizedToken === '') {
            $normalizedToken = 'user';
        }

        $birthdaySegment = Carbon::parse($birthday)->format('mdY');

        return "{$normalizedToken}@{$birthdaySegment}";
    }
}
