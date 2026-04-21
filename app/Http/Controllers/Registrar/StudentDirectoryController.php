<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
                    'student_name' => trim("{$student->first_name} {$student->last_name}"),
                    'grade_section' => $gradeSection,
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

    public function uploadSf1(Request $request): RedirectResponse
    {
        return back()->with(
            'error',
            'Inbound SF1 sync is disabled. Use Enrollment > Export SF1 Reference for LIS enrollment.'
        );
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
}
