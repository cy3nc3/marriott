<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\EnrollmentIntakeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('enrollment intake seeder creates intake records with accounts and leaves two manual-enrollment students unseeded', function () {
    AcademicYear::query()->create([
        'name' => '2030-2031',
        'start_date' => '2030-06-01',
        'end_date' => '2031-03-31',
        'status' => 'ongoing',
        'current_quarter' => '1',
    ]);

    $this->seed(EnrollmentIntakeSeeder::class);

    $sf1Rows = enrollmentIntakeReadCsvRows(base_path('tests/Fixtures/imports/registrar_sf1_sample.csv'));
    $permanentRows = enrollmentIntakeReadCsvRows(base_path('tests/Fixtures/imports/registrar_permanent_records_sample.csv'));

    $permanentLrns = [];
    foreach ($permanentRows as $row) {
        $lrn = enrollmentIntakeNormalizeLrn($row['lrn'] ?? null);
        if ($lrn !== null) {
            $permanentLrns[$lrn] = true;
        }
    }

    $oldRows = [];
    $newRows = [];
    foreach ($sf1Rows as $row) {
        $lrn = enrollmentIntakeNormalizeLrn($row['lrn'] ?? null);
        if ($lrn === null) {
            continue;
        }

        $normalizedRow = [
            'lrn' => $lrn,
            'first_name' => trim((string) ($row['first_name'] ?? '')),
            'last_name' => trim((string) ($row['last_name'] ?? '')),
            'birthdate' => trim((string) ($row['birthdate'] ?? '')),
        ];

        if (isset($permanentLrns[$lrn])) {
            $oldRows[] = $normalizedRow;
        } else {
            $newRows[] = $normalizedRow;
        }
    }

    expect($oldRows)->not->toBeEmpty();
    expect($newRows)->not->toBeEmpty();

    $leftOutOld = $oldRows[0];
    $leftOutNew = $newRows[0];
    $profileOnly = [$oldRows[1], $newRows[1], $oldRows[2], $newRows[2]];
    $pastYearOnly = [$oldRows[3], $newRows[3], $oldRows[4], $newRows[4]];
    $activeSeeded = $oldRows[5];

    expect(Student::query()->where('lrn', $leftOutOld['lrn'])->exists())->toBeFalse();
    expect(Student::query()->where('lrn', $leftOutNew['lrn'])->exists())->toBeFalse();

    $activeAcademicYear = AcademicYear::query()
        ->where('status', 'ongoing')
        ->orderByDesc('start_date')
        ->first();
    $completedAcademicYear = AcademicYear::query()
        ->where('status', 'completed')
        ->orderByDesc('end_date')
        ->first();

    expect($activeAcademicYear)->not->toBeNull();

    $activeStudent = Student::query()
        ->where('lrn', $activeSeeded['lrn'])
        ->first();

    expect($activeStudent)->not->toBeNull();
    expect($activeStudent?->user_id)->not->toBeNull();

    $expectedStudentEmail = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $activeSeeded['last_name']))
        .".{$activeSeeded['lrn']}@marriott.edu";
    $expectedPassword = strtolower((string) preg_replace('/[^a-z0-9]/i', '', explode(' ', $activeSeeded['first_name'])[0]))
        .'@'.Carbon::parse($activeSeeded['birthdate'])->format('mdY');

    $activeStudentUser = User::query()->find($activeStudent?->user_id);
    $activeParentUser = User::query()->where('email', "parent.{$activeSeeded['lrn']}@marriott.edu")->first();
    expect($activeStudentUser)->not->toBeNull();
    expect($activeParentUser)->not->toBeNull();
    expect($activeStudentUser?->email)->toBe($expectedStudentEmail);
    expect(Hash::check($expectedPassword, (string) $activeStudentUser?->password))->toBeTrue();
    expect($activeParentUser?->birthday?->toDateString())->toBe('1980-01-01');
    expect(Hash::check($expectedPassword, (string) $activeParentUser?->password))->toBeTrue();

    $activeEnrollment = Enrollment::query()
        ->where('student_id', $activeStudent?->id)
        ->where('academic_year_id', $activeAcademicYear?->id)
        ->first();

    expect($activeEnrollment)->not->toBeNull();
    expect($activeEnrollment?->status)->toBe('for_cashier_payment');
    expect((string) $activeEnrollment?->payment_term)->toBeIn(['cash', 'monthly', 'quarterly', 'semi-annual']);

    $activeYearPaymentTerms = Enrollment::query()
        ->where('academic_year_id', $activeAcademicYear?->id)
        ->where('status', 'for_cashier_payment')
        ->distinct()
        ->pluck('payment_term')
        ->all();

    expect($activeYearPaymentTerms)->toContain('cash');
    expect($activeYearPaymentTerms)->toContain('monthly');
    expect($activeYearPaymentTerms)->toContain('quarterly');
    expect($activeYearPaymentTerms)->toContain('semi-annual');

    foreach ($profileOnly as $profileRow) {
        $student = Student::query()
            ->where('lrn', $profileRow['lrn'])
            ->first();

        expect($student)->not->toBeNull();
        expect(
            Enrollment::query()
                ->where('student_id', $student?->id)
                ->where('academic_year_id', $activeAcademicYear?->id)
                ->exists()
        )->toBeFalse();
    }

    foreach ($pastYearOnly as $pastRow) {
        $student = Student::query()
            ->where('lrn', $pastRow['lrn'])
            ->first();

        expect($student)->not->toBeNull();
        expect(
            Enrollment::query()
                ->where('student_id', $student?->id)
                ->where('academic_year_id', $activeAcademicYear?->id)
                ->exists()
        )->toBeFalse();

        if ($completedAcademicYear instanceof AcademicYear) {
            expect(
                Enrollment::query()
                    ->where('student_id', $student?->id)
                    ->where('academic_year_id', $completedAcademicYear->id)
                    ->exists()
            )->toBeTrue();
        }
    }
});

test('enrollment intake seeder targets upcoming school year when ongoing year is absent', function () {
    $upcomingYear = AcademicYear::query()->create([
        'name' => '2031-2032',
        'start_date' => null,
        'end_date' => null,
        'status' => 'upcoming',
        'current_quarter' => '1',
    ]);

    $this->seed(EnrollmentIntakeSeeder::class);

    $seededCount = Enrollment::query()
        ->where('academic_year_id', $upcomingYear->id)
        ->where('status', 'for_cashier_payment')
        ->count();

    expect($seededCount)->toBeGreaterThan(0);
});

/**
 * @return array<int, array<string, string>>
 */
function enrollmentIntakeReadCsvRows(string $path): array
{
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

function enrollmentIntakeNormalizeLrn(?string $value): ?string
{
    $digits = preg_replace('/\D/', '', (string) $value);

    return $digits !== '' ? $digits : null;
}
