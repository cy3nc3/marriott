<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Services\DashboardCacheService;
use App\Services\SchoolForms\Sf1TemplateAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class StudentDirectoryController extends Controller
{
    public function index(Request $request): Response
    {
        $schoolYearOptions = AcademicYear::query()
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'status', 'start_date'])
            ->map(function (AcademicYear $academicYear) {
                return [
                    'id' => (int) $academicYear->id,
                    'name' => $academicYear->name,
                    'status' => $academicYear->status,
                ];
            })
            ->values();

        $selectedAcademicYearId = $request->integer('academic_year_id');
        if (
            $selectedAcademicYearId <= 0
            || ! $schoolYearOptions->pluck('id')->contains($selectedAcademicYearId)
        ) {
            $selectedAcademicYearId = (int) ($schoolYearOptions->firstWhere('status', 'ongoing')['id']
                ?? ($schoolYearOptions->first()['id'] ?? 0));
        }

        $selectedAcademicYear = $selectedAcademicYearId > 0
            ? AcademicYear::query()->find($selectedAcademicYearId)
            : null;

        $studentBaseQuery = Student::query()
            ->when($selectedAcademicYear, function ($query) use ($selectedAcademicYear) {
                $query->whereHas('enrollments', function ($enrollmentQuery) use ($selectedAcademicYear) {
                    $enrollmentQuery->where('academic_year_id', $selectedAcademicYear->id);
                });
            });

        $students = (clone $studentBaseQuery)
            ->with([
                'enrollments' => function ($query) use ($selectedAcademicYear) {
                    $query
                        ->when($selectedAcademicYear, function ($inner) use ($selectedAcademicYear) {
                            $inner->where('academic_year_id', $selectedAcademicYear->id);
                        })
                        ->with(['gradeLevel:id,name', 'section:id,name'])
                        ->latest('id');
                },
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString()
            ->through(function (Student $student) {
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
                    'lis_status_reason' => $this->resolveLisStatusReason($student),
                ];
            });

        $summary = [
            'matched' => (int) (clone $studentBaseQuery)
                ->where('sync_error_flag', false)
                ->where('is_lis_synced', true)
                ->count(),
            'pending' => (int) (clone $studentBaseQuery)
                ->where('sync_error_flag', false)
                ->where(function ($query) {
                    $query
                        ->where('is_lis_synced', false)
                        ->orWhereNull('is_lis_synced');
                })
                ->count(),
            'discrepancy' => (int) (clone $studentBaseQuery)
                ->where('sync_error_flag', true)
                ->count(),
        ];

        return Inertia::render('registrar/student-directory/index', [
            'students' => $students,
            'summary' => $summary,
            'school_year_options' => $schoolYearOptions->all(),
            'selected_school_year_id' => $selectedAcademicYear?->id,
            'last_upload' => [
                'at' => Setting::get('registrar_sf1_last_upload_at'),
                'file_name' => Setting::get('registrar_sf1_last_upload_name'),
            ],
        ]);
    }

    public function uploadSf1(Request $request, Sf1TemplateAdapter $sf1TemplateAdapter): RedirectResponse
    {
        $academicYearId = $request->integer('academic_year_id');

        $validated = $request->validate([
            'sf1_file' => 'required|file|mimes:csv,txt,xls,xlsx|max:10240',
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
        ]);

        $file = $validated['sf1_file'];
        $academicYearId = (int) $validated['academic_year_id'];

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (in_array($extension, ['xls', 'xlsx'], true)) {
            $parsedRows = $sf1TemplateAdapter->parseRows((string) $file->getRealPath());
            $processedRows = count($parsedRows);
            $lrns = array_values(array_filter(array_map(
                fn (array $parsedRow): string => (string) $parsedRow['lrn'],
                $parsedRows
            )));
        } else {
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
            $parsedRows = [];
            $lrns = [];

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

                $parsedRows[] = [
                    'row_data' => $rowData,
                    'lrn' => $lrn,
                ];

                if ($lrn !== '') {
                    $lrns[] = $lrn;
                }
            }

            fclose($handle);
        }

        $matched = 0;
        $discrepancy = 0;
        $reassigned = 0;

        $studentsByLrn = Student::query()
            ->whereIn('lrn', array_values(array_unique($lrns)))
            ->get()
            ->keyBy('lrn');
        $enrollmentsByStudentId = Enrollment::query()
            ->where('academic_year_id', $academicYearId)
            ->whereIn('student_id', $studentsByLrn->pluck('id')->all())
            ->get()
            ->keyBy('student_id');
        $sectionsByName = Section::query()
            ->where('academic_year_id', $academicYearId)
            ->with('gradeLevel:id,name')
            ->get()
            ->groupBy(function (Section $section) {
                return $this->normalizeForLookup($section->name);
            });

        foreach ($parsedRows as $parsedRow) {
            $lrn = (string) $parsedRow['lrn'];
            $rowData = (array) $parsedRow['row_data'];

            if ($lrn === '') {
                $discrepancy++;

                continue;
            }

            $student = $studentsByLrn->get($lrn);
            if (! $student instanceof Student) {
                $discrepancy++;

                continue;
            }

            $enrollment = $enrollmentsByStudentId->get($student->id);
            if (! $enrollment instanceof Enrollment) {
                $this->syncStudentFromSf1Row(
                    $student,
                    $rowData,
                    false,
                    'No enrollment record found for selected school year.'
                );
                $discrepancy++;

                continue;
            }

            $targetSection = $this->resolveSectionFromSf1Row($rowData, $sectionsByName);
            if (! $targetSection instanceof Section) {
                $this->syncStudentFromSf1Row(
                    $student,
                    $rowData,
                    false,
                    'Unable to resolve section assignment from SF1 row.'
                );
                $discrepancy++;

                continue;
            }

            if (
                (int) $enrollment->section_id !== (int) $targetSection->id
                || (int) $enrollment->grade_level_id !== (int) $targetSection->grade_level_id
            ) {
                $enrollment->update([
                    'section_id' => $targetSection->id,
                    'grade_level_id' => $targetSection->grade_level_id,
                ]);
                $reassigned++;
            }

            $this->syncStudentFromSf1Row($student, $rowData, true, null);

            $matched++;
        }

        Setting::set('registrar_sf1_last_upload_at', now()->toDateTimeString(), 'registrar');
        Setting::set('registrar_sf1_last_upload_name', $file->getClientOriginalName(), 'registrar');
        DashboardCacheService::bust();

        return back()->with(
            'success',
            "SF1 processed. Matched {$matched} of {$processedRows} rows, updated {$reassigned} section assignments, with {$discrepancy} discrepancies."
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

    private function resolveLisStatusReason(Student $student): ?string
    {
        if (! $student->sync_error_flag) {
            return null;
        }

        $reason = trim((string) $student->sync_error_notes);

        return $reason !== '' ? $reason : 'Discrepancy found during SF1 reconciliation.';
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

    private function syncStudentFromSf1Row(Student $student, array $rowData, bool $isLisSynced, ?string $syncErrorNotes): void
    {
        $student->update([
            'first_name' => $this->firstAvailable($rowData, ['first_name', 'firstname', 'given_name']) ?: $student->first_name,
            'last_name' => $this->firstAvailable($rowData, ['last_name', 'lastname', 'surname']) ?: $student->last_name,
            'gender' => $this->firstAvailable($rowData, ['gender', 'sex']) ?: $student->gender,
            'birthdate' => $this->parseDate($this->firstAvailable($rowData, ['birthdate', 'date_of_birth'])) ?: $student->birthdate,
            'address' => $this->firstAvailable($rowData, ['address', 'home_address']) ?: $student->address,
            'guardian_name' => $this->firstAvailable($rowData, ['guardian_name', 'parent_name']) ?: $student->guardian_name,
            'contact_number' => $this->firstAvailable($rowData, ['contact_number', 'contact', 'mobile']) ?: $student->contact_number,
            'is_lis_synced' => $isLisSynced,
            'sync_error_flag' => ! $isLisSynced,
            'sync_error_notes' => $syncErrorNotes,
        ]);
    }

    private function resolveSectionFromSf1Row(array $rowData, Collection $sectionsByName): ?Section
    {
        $sectionName = $this->firstAvailable($rowData, [
            'section',
            'section_name',
            'class_section',
            'advisory_section',
        ]);
        if (! $sectionName) {
            return null;
        }

        $candidates = $sectionsByName->get($this->normalizeForLookup($sectionName), collect());
        if (! $candidates instanceof Collection || $candidates->isEmpty()) {
            return null;
        }

        if ($candidates->count() === 1) {
            $single = $candidates->first();

            return $single instanceof Section ? $single : null;
        }

        $csvGradeLevel = $this->firstAvailable($rowData, [
            'grade_level',
            'grade',
            'year_level',
            'level',
        ]);
        if (! $csvGradeLevel) {
            return null;
        }

        $csvGradeLookup = $this->normalizeGradeLevelLookup($csvGradeLevel);
        $resolved = $candidates->first(function (Section $section) use ($csvGradeLookup) {
            return $this->normalizeGradeLevelLookup((string) $section->gradeLevel?->name) === $csvGradeLookup;
        });

        return $resolved instanceof Section ? $resolved : null;
    }

    private function normalizeForLookup(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return preg_replace('/[^a-z0-9]/', '', $normalized) ?? $normalized;
    }

    private function normalizeGradeLevelLookup(string $value): string
    {
        $normalized = $this->normalizeForLookup($value);
        $normalized = str_replace('grade', '', $normalized);

        return ltrim($normalized, '0');
    }
}
