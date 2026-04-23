<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentDirectoryController extends Controller
{
    public function index(Request $request): Response
    {
        $ongoingAcademicYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first();
        $search = trim((string) $request->input('search', ''));
        $normalizedSearch = mb_strtolower($search);

        $studentBaseQuery = Student::query()
            ->when($normalizedSearch !== '', function ($query) use ($normalizedSearch) {
                $searchPattern = "%{$normalizedSearch}%";

                $query->where(function ($studentQuery) use ($searchPattern) {
                    $studentQuery
                        ->whereRaw('LOWER(lrn) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(first_name) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$searchPattern]);
                });
            });

        $students = (clone $studentBaseQuery)
            ->with([
                'enrollments' => function ($query) use ($ongoingAcademicYear) {
                    $query
                        ->when($ongoingAcademicYear, function ($inner) use ($ongoingAcademicYear) {
                            $inner->where('academic_year_id', $ongoingAcademicYear->id);
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
                    'enrollment_id' => $enrollment?->id,
                    'lrn' => $student->lrn,
                    'first_name' => $student->first_name,
                    'middle_name' => $student->middle_name,
                    'last_name' => $student->last_name,
                    'gender' => $student->gender,
                    'birthdate' => $student->birthdate?->toDateString(),
                    'guardian_name' => $student->guardian_name,
                    'guardian_contact_number' => $student->contact_number,
                    'email' => $enrollment?->email,
                    'student_name' => trim("{$student->first_name} {$student->last_name}"),
                    'grade_section' => $gradeSection,
                    'enrollment_status' => $enrollment?->status,
                    'status' => $this->resolveDirectoryStatus($enrollment?->status),
                ];
            });

        $sectionOptions = collect();
        if ($ongoingAcademicYear instanceof AcademicYear) {
            $sectionOptions = Section::query()
                ->with('gradeLevel:id,name')
                ->where('academic_year_id', $ongoingAcademicYear->id)
                ->orderBy('grade_level_id')
                ->orderBy('name')
                ->get(['id', 'grade_level_id', 'name'])
                ->map(function (Section $section): array {
                    $gradeLevelName = trim((string) ($section->gradeLevel?->name ?? ''));
                    $sectionName = trim((string) $section->name);

                    return [
                        'id' => (int) $section->id,
                        'label' => $gradeLevelName !== ''
                            ? "{$gradeLevelName} - {$sectionName}"
                            : $sectionName,
                    ];
                })
                ->values();
        }

        return Inertia::render('registrar/student-directory/index', [
            'students' => $students,
            'section_options' => $sectionOptions->all(),
            'ongoing_academic_year_id' => $ongoingAcademicYear?->id,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'in:Male,Female'],
            'birthdate' => ['required', 'date', 'before_or_equal:today'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_contact_number' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $normalizedGuardianContactNumber = $this->normalizeGuardianContactNumber(
            (string) $validated['guardian_contact_number']
        );

        $activeAcademicYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first() ?? AcademicYear::query()
            ->latest('start_date')
            ->first();

        DB::transaction(function () use ($student, $validated, $normalizedGuardianContactNumber, $activeAcademicYear): void {
            $student->update([
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?: null,
                'last_name' => $validated['last_name'],
                'gender' => $validated['gender'],
                'birthdate' => $validated['birthdate'],
                'guardian_name' => $validated['guardian_name'],
                'contact_number' => $normalizedGuardianContactNumber,
            ]);

            $syncableEnrollment = Enrollment::query()
                ->where('student_id', $student->id)
                ->whereIn('status', ['for_cashier_payment', 'enrolled'])
                ->when(
                    $activeAcademicYear,
                    fn ($query) => $query->where('academic_year_id', $activeAcademicYear->id)
                )
                ->latest('id')
                ->first();

            if ($syncableEnrollment) {
                $syncableEnrollment->update([
                    'email' => $validated['email'] ?: null,
                ]);
            }
        });

        return back()->with('success', 'Student details updated.');
    }

    public function uploadSf1(Request $request): RedirectResponse
    {
        return back()->with(
            'error',
            'Inbound SF1 sync is disabled. Use Student Directory > Export SF1 Reference for LIS enrollment.'
        );
    }

    public function exportSf1Reference(Request $request): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'section_ids' => ['nullable', 'array'],
            'section_ids.*' => ['integer', 'exists:sections,id'],
        ]);

        $selectedAcademicYearId = (int) ($validated['academic_year_id'] ?? 0);
        $selectedSectionIds = collect($validated['section_ids'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $academicYear = $selectedAcademicYearId > 0
            ? AcademicYear::query()->find($selectedAcademicYearId)
            : AcademicYear::query()->where('status', 'ongoing')->first();

        if (! $academicYear) {
            return back()->with('error', 'No academic year found for SF1 reference export.');
        }

        $outputPath = storage_path('app/temp/'.uniqid('sf1-reference-', true).'.csv');
        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        $rows = Enrollment::query()
            ->with([
                'student:id,lrn,first_name,middle_name,last_name,gender,birthdate,address,guardian_name,contact_number',
                'gradeLevel:id,name',
                'section:id,name',
            ])
            ->where('academic_year_id', $academicYear->id)
            ->when(
                $selectedSectionIds->isNotEmpty(),
                fn ($query) => $query->whereIn('section_id', $selectedSectionIds->all())
            )
            ->whereIn('status', ['for_cashier_payment', 'enrolled'])
            ->get()
            ->sortBy(function (Enrollment $enrollment): string {
                return strtolower(trim("{$enrollment->student?->last_name} {$enrollment->student?->first_name}"));
            })
            ->values();

        $handle = fopen($outputPath, 'w');
        if ($handle === false) {
            return back()->with('error', 'Unable to generate SF1 reference export.');
        }

        fputcsv($handle, [
            'LRN',
            'First Name',
            'Middle Name',
            'Last Name',
            'Gender',
            'Birthdate',
            'Address',
            'Guardian Name',
            'Guardian Contact Number',
            'Grade Level',
            'Section',
            'Enrollment Status',
        ]);

        foreach ($rows as $enrollment) {
            fputcsv($handle, [
                (string) ($enrollment->student?->lrn ?? ''),
                (string) ($enrollment->student?->first_name ?? ''),
                (string) ($enrollment->student?->middle_name ?? ''),
                (string) ($enrollment->student?->last_name ?? ''),
                (string) ($enrollment->student?->gender ?? ''),
                (string) ($enrollment->student?->birthdate?->toDateString() ?? ''),
                (string) ($enrollment->student?->address ?? ''),
                (string) ($enrollment->student?->guardian_name ?? ''),
                (string) ($enrollment->student?->contact_number ?? ''),
                (string) ($enrollment->gradeLevel?->name ?? ''),
                (string) ($enrollment->section?->name ?? ''),
                (string) $enrollment->status,
            ]);
        }

        fclose($handle);

        $sanitizedYear = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $academicYear->name));

        return response()
            ->download($outputPath, "sf1-reference-{$sanitizedYear}.csv")
            ->deleteFileAfterSend(true);
    }

    private function resolveDirectoryStatus(?string $enrollmentStatus): string
    {
        return match ($enrollmentStatus) {
            'dropped' => 'dropped',
            'transferred_out' => 'transferred_out',
            'for_cashier_payment', 'enrolled' => 'enrolled',
            default => 'not_currently_enrolled',
        };
    }

    private function normalizeGuardianContactNumber(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return '+63'.substr($digits, 1);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '+63'.$digits;
        }

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        throw ValidationException::withMessages([
            'guardian_contact_number' => 'Guardian contact number must be a valid PH mobile number (e.g. +639XXXXXXXXX).',
        ]);
    }
}
