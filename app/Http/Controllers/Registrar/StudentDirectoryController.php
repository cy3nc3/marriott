<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $search = trim((string) $request->input('search', ''));
        $normalizedSearch = mb_strtolower($search);

        $studentBaseQuery = Student::query()
            ->when($selectedAcademicYear, function ($query) use ($selectedAcademicYear) {
                $query->whereHas('enrollments', function ($enrollmentQuery) use ($selectedAcademicYear) {
                    $enrollmentQuery->where('academic_year_id', $selectedAcademicYear->id);
                });
            })
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
                    'enrollment_id' => $enrollment?->id,
                    'lrn' => $student->lrn,
                    'student_name' => trim("{$student->first_name} {$student->last_name}"),
                    'grade_section' => $gradeSection,
                ];
            });

        $sectionOptions = collect();
        if ($selectedAcademicYear instanceof AcademicYear) {
            $sectionOptions = \App\Models\Section::query()
                ->with('gradeLevel:id,name')
                ->where('academic_year_id', $selectedAcademicYear->id)
                ->orderBy('grade_level_id')
                ->orderBy('name')
                ->get(['id', 'grade_level_id', 'name'])
                ->map(function (\App\Models\Section $section): array {
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
            'section_options' => $sectionOptions->all(),
            'summary' => $summary,
            'school_year_options' => $schoolYearOptions->all(),
            'selected_school_year_id' => $selectedAcademicYear?->id,
            'filters' => [
                'search' => $search,
                'academic_year_id' => $selectedAcademicYear?->id,
            ],
        ]);
    }

    public function uploadSf1(Request $request): RedirectResponse
    {
        return back()->with(
            'error',
            'Inbound SF1 sync is disabled. Use Enrollment > Export SF1 Reference for LIS enrollment.'
        );
    }
}
