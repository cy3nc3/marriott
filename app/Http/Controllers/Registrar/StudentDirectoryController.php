<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StudentDirectoryController extends Controller
{
    public function index(): Response
    {
        $activeAcademicYearId = AcademicYear::query()
            ->where('status', 'ongoing')
            ->value('id');

        $students = Student::query()
            ->with([
                'enrollments' => function ($query) use ($activeAcademicYearId) {
                    $query
                        ->when($activeAcademicYearId, function ($inner) use ($activeAcademicYearId) {
                            $inner->where('academic_year_id', $activeAcademicYearId);
                        })
                        ->with(['gradeLevel:id,name', 'section:id,name'])
                        ->latest('id');
                },
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(function (Student $student) {
                $enrollment = $student->enrollments->first();

                $gradeSection = 'Unassigned';
                if ($enrollment?->gradeLevel?->name && $enrollment?->section?->name) {
                    $gradeSection = "{$enrollment->gradeLevel->name} - {$enrollment->section->name}";
                } elseif ($enrollment?->gradeLevel?->name) {
                    $gradeSection = $enrollment->gradeLevel->name;
                }

                return [
                    'id' => $student->id,
                    'lrn' => $student->lrn,
                    'student_name' => trim("{$student->first_name} {$student->last_name}"),
                    'grade_section' => $gradeSection,
                    'lis_status' => $this->resolveLisStatus($student),
                ];
            })
            ->values();

        return Inertia::render('registrar/student-directory/index', [
            'students' => $students,
            'summary' => [
                'matched' => Student::query()
                    ->where('is_lis_synced', true)
                    ->where('sync_error_flag', false)
                    ->count(),
                'pending' => Student::query()
                    ->where('is_lis_synced', false)
                    ->where('sync_error_flag', false)
                    ->count(),
                'discrepancy' => Student::query()
                    ->where('sync_error_flag', true)
                    ->count(),
            ],
            'last_upload' => [
                'at' => Setting::get('registrar_sf1_last_upload_at'),
                'file_name' => Setting::get('registrar_sf1_last_upload_name'),
            ],
        ]);
    }

    public function uploadSf1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sf1_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $validated['sf1_file'];
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return back()->with('error', 'Unable to read SF1 file.');
        }

        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);

            return back()->with('error', 'SF1 file is empty.');
        }

        $headers = array_map(function ($header) {
            $value = strtolower(trim((string) $header));
            $value = str_replace([' ', '-'], '_', $value);

            return preg_replace('/[^a-z0-9_]/', '', $value) ?: '';
        }, $headerRow);

        $processedRows = 0;
        $matched = 0;
        $discrepancy = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $processedRows++;

            $rowData = [];
            foreach ($headers as $index => $header) {
                $rowData[$header] = trim((string) ($row[$index] ?? ''));
            }

            $lrn = preg_replace('/\D/', '', (string) $this->firstAvailable($rowData, [
                'lrn',
                'learner_reference_number',
            ]));

            if (! $lrn) {
                $discrepancy++;

                continue;
            }

            $student = Student::query()->where('lrn', $lrn)->first();

            if (! $student) {
                $discrepancy++;

                continue;
            }

            $student->update([
                'first_name' => $this->firstAvailable($rowData, ['first_name', 'firstname', 'given_name']) ?: $student->first_name,
                'last_name' => $this->firstAvailable($rowData, ['last_name', 'lastname', 'surname']) ?: $student->last_name,
                'gender' => $this->firstAvailable($rowData, ['gender', 'sex']) ?: $student->gender,
                'birthdate' => $this->parseDate($this->firstAvailable($rowData, ['birthdate', 'date_of_birth'])) ?: $student->birthdate,
                'address' => $this->firstAvailable($rowData, ['address', 'home_address']) ?: $student->address,
                'guardian_name' => $this->firstAvailable($rowData, ['guardian_name', 'parent_name']) ?: $student->guardian_name,
                'contact_number' => $this->firstAvailable($rowData, ['contact_number', 'contact', 'mobile']) ?: $student->contact_number,
                'is_lis_synced' => true,
                'sync_error_flag' => false,
                'sync_error_notes' => null,
            ]);

            $matched++;
        }

        fclose($handle);

        Setting::set('registrar_sf1_last_upload_at', now()->toDateTimeString(), 'registrar');
        Setting::set('registrar_sf1_last_upload_name', $file->getClientOriginalName(), 'registrar');

        return back()->with(
            'success',
            "SF1 processed. Matched {$matched} of {$processedRows} rows with {$discrepancy} discrepancies."
        );
    }

    private function resolveLisStatus(Student $student): string
    {
        if ($student->sync_error_flag) {
            return 'discrepancy';
        }

        if ($student->is_lis_synced) {
            return 'matched';
        }

        return 'pending';
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

    private function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
