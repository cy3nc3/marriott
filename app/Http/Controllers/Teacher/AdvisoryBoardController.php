<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\IndexAdvisoryBoardRequest;
use App\Http\Requests\Teacher\StoreAdvisoryConductRequest;
use App\Models\AcademicYear;
use App\Models\ConductRating;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AdvisoryBoardController extends Controller
{
    public function index(IndexAdvisoryBoardRequest $request): Response
    {
        $validated = $request->validated();
        $teacherId = (int) auth()->id();

        $activeYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()->orderByDesc('start_date')->first();

        $selectedQuarter = (string) ($validated['quarter']
            ?? ($activeYear?->current_quarter ?: '1'));

        $advisorySections = Section::query()
            ->with('gradeLevel:id,name')
            ->where('adviser_id', $teacherId)
            ->when($activeYear, function ($query) use ($activeYear) {
                $query->where('academic_year_id', $activeYear->id);
            })
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get(['id', 'grade_level_id', 'name', 'academic_year_id']);

        $sectionOptions = $advisorySections
            ->map(function (Section $section) {
                $gradeLevelName = $section->gradeLevel?->name;
                $sectionName = $section->name;

                if ($gradeLevelName && $sectionName) {
                    $label = "{$gradeLevelName} - {$sectionName}";
                } elseif ($sectionName) {
                    $label = $sectionName;
                } else {
                    $label = 'Unassigned Section';
                }

                return [
                    'id' => (int) $section->id,
                    'label' => $label,
                ];
            })
            ->values();

        $selectedSectionId = (int) ($validated['section_id'] ?? ($sectionOptions->first()['id'] ?? 0));
        $allowedSectionIds = $sectionOptions
            ->pluck('id')
            ->all();

        if (! in_array($selectedSectionId, $allowedSectionIds, true)) {
            $selectedSectionId = (int) ($sectionOptions->first()['id'] ?? 0);
        }

        $selectedSection = $advisorySections->firstWhere('id', $selectedSectionId);

        $enrollments = collect();
        if ($selectedSection) {
            $enrollments = Enrollment::query()
                ->with('student:id,first_name,last_name')
                ->where('section_id', $selectedSection->id)
                ->where('academic_year_id', $selectedSection->academic_year_id)
                ->where('status', 'enrolled')
                ->orderBy('id')
                ->get(['id', 'student_id']);
        }

        $enrollmentIds = $enrollments
            ->pluck('id')
            ->values();

        $conductRatingsByEnrollment = collect();
        if ($enrollmentIds->isNotEmpty()) {
            $conductRatingsByEnrollment = ConductRating::query()
                ->whereIn('enrollment_id', $enrollmentIds)
                ->where('quarter', $selectedQuarter)
                ->get()
                ->keyBy('enrollment_id');
        }

        $finalGrades = collect();
        if ($enrollmentIds->isNotEmpty()) {
            $finalGrades = FinalGrade::query()
                ->with('subjectAssignment.teacherSubject.subject:id,subject_name')
                ->whereIn('enrollment_id', $enrollmentIds)
                ->where('quarter', $selectedQuarter)
                ->get();
        }

        $subjectColumns = $finalGrades
            ->map(function (FinalGrade $finalGrade) {
                $subject = $finalGrade->subjectAssignment?->teacherSubject?->subject;
                if (! $subject) {
                    return null;
                }

                return [
                    'id' => (int) $subject->id,
                    'name' => $subject->subject_name,
                ];
            })
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $gradeMapByEnrollment = $finalGrades
            ->groupBy('enrollment_id')
            ->map(function (Collection $enrollmentGrades) {
                return $enrollmentGrades
                    ->mapWithKeys(function (FinalGrade $finalGrade) {
                        $subjectId = (int) ($finalGrade->subjectAssignment?->teacherSubject?->subject_id ?? 0);
                        if ($subjectId <= 0) {
                            return [];
                        }

                        return [$subjectId => (float) $finalGrade->grade];
                    })
                    ->all();
            });

        $gradeRows = $enrollments
            ->map(function (Enrollment $enrollment) use ($subjectColumns, $gradeMapByEnrollment) {
                $gradeMap = (array) ($gradeMapByEnrollment->get($enrollment->id) ?? []);
                $gradeValues = collect($gradeMap)->values();
                $average = $gradeValues->isNotEmpty()
                    ? round((float) $gradeValues->avg(), 2)
                    : null;

                $subjectGrades = $subjectColumns
                    ->mapWithKeys(function (array $subjectColumn) use ($gradeMap) {
                        $subjectId = (int) $subjectColumn['id'];

                        return [
                            (string) $subjectId => array_key_exists($subjectId, $gradeMap)
                                ? $this->formatGrade((float) $gradeMap[$subjectId])
                                : null,
                        ];
                    })
                    ->all();

                return [
                    'enrollment_id' => (int) $enrollment->id,
                    'student_name' => trim("{$enrollment->student?->last_name}, {$enrollment->student?->first_name}"),
                    'subject_grades' => $subjectGrades,
                    'general_average' => $average === null ? null : $this->formatGrade($average),
                ];
            })
            ->values();

        $conductRows = $enrollments
            ->map(function (Enrollment $enrollment) use ($conductRatingsByEnrollment) {
                $conductRating = $conductRatingsByEnrollment->get($enrollment->id);

                return [
                    'enrollment_id' => (int) $enrollment->id,
                    'student_name' => trim("{$enrollment->student?->last_name}, {$enrollment->student?->first_name}"),
                    'ratings' => [
                        'maka_diyos' => $conductRating?->maka_diyos ?? 'AO',
                        'makatao' => $conductRating?->makatao ?? 'AO',
                        'makakalikasan' => $conductRating?->makakalikasan ?? 'AO',
                        'makabansa' => $conductRating?->makabansa ?? 'AO',
                    ],
                    'remarks' => $conductRating?->remarks ?? '',
                ];
            })
            ->values();

        $isLocked = false;
        if ($enrollmentIds->isNotEmpty()) {
            $lockedCount = ConductRating::query()
                ->whereIn('enrollment_id', $enrollmentIds)
                ->where('quarter', $selectedQuarter)
                ->where('is_locked', true)
                ->count();

            $isLocked = $lockedCount === $enrollmentIds->count();
        }

        return Inertia::render('teacher/advisory-board/index', [
            'context' => [
                'section_options' => $sectionOptions,
                'selected_section_id' => $selectedSectionId > 0 ? $selectedSectionId : null,
                'selected_quarter' => $selectedQuarter,
            ],
            'grade_columns' => $subjectColumns,
            'grade_rows' => $gradeRows,
            'conduct_rows' => $conductRows,
            'status' => $isLocked ? 'locked' : 'draft',
        ]);
    }

    public function storeConduct(StoreAdvisoryConductRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $teacherId = (int) auth()->id();

        $section = Section::query()
            ->whereKey($validated['section_id'])
            ->where('adviser_id', $teacherId)
            ->firstOrFail();

        $enrollmentIds = Enrollment::query()
            ->where('section_id', $section->id)
            ->where('academic_year_id', $section->academic_year_id)
            ->where('status', 'enrolled')
            ->pluck('id');

        if ($enrollmentIds->isEmpty()) {
            return back()->with('error', 'No enrolled students found for the selected advisory class.');
        }

        $lockedExists = ConductRating::query()
            ->whereIn('enrollment_id', $enrollmentIds)
            ->where('quarter', $validated['quarter'])
            ->where('is_locked', true)
            ->exists();

        if ($lockedExists) {
            return back()->with('error', 'Conduct ratings are already locked for this quarter.');
        }

        $rowsByEnrollment = collect($validated['rows'])
            ->mapWithKeys(function (array $row) {
                return [(int) $row['enrollment_id'] => $row];
            });

        foreach ($enrollmentIds as $enrollmentId) {
            $row = $rowsByEnrollment->get((int) $enrollmentId);
            if (! $row) {
                continue;
            }

            ConductRating::query()->updateOrCreate(
                [
                    'enrollment_id' => $enrollmentId,
                    'quarter' => $validated['quarter'],
                ],
                [
                    'maka_diyos' => $row['maka_diyos'],
                    'makatao' => $row['makatao'],
                    'makakalikasan' => $row['makakalikasan'],
                    'makabansa' => $row['makabansa'],
                    'remarks' => trim((string) ($row['remarks'] ?? '')) ?: null,
                    'is_locked' => $validated['save_mode'] === 'locked',
                ]
            );
        }

        $message = $validated['save_mode'] === 'locked'
            ? 'Conduct ratings finalized and locked.'
            : 'Conduct ratings saved as draft.';

        return back()->with('success', $message);
    }

    private function formatGrade(float $grade): string
    {
        return number_format($grade, 2, '.', '');
    }
}
