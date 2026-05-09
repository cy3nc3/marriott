<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\ResolvesTeacherAcademicYearAccess;
use App\Http\Requests\Teacher\IndexAdvisoryBoardRequest;
use App\Http\Requests\Teacher\StoreAdvisoryConductRequest;
use App\Models\AcademicYear;
use App\Models\ConductRating;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradeRelease;
use App\Models\GradeSubmission;
use App\Models\Section;
use App\Models\SubjectAssignment;
use App\Services\Registrar\PermanentRecordPopulationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AdvisoryBoardController extends Controller
{
    use ResolvesTeacherAcademicYearAccess;

    public function index(IndexAdvisoryBoardRequest $request): Response
    {
        $validated = $request->validated();
        $teacherId = (int) auth()->id();
        $academicYearOptions = $this->resolveTeacherAcademicYearOptions($teacherId);
        $selectedAcademicYearId = $this->resolveSelectedTeacherAcademicYearId(
            $academicYearOptions,
            (int) ($validated['academic_year_id'] ?? 0) ?: null
        );
        if (isset($validated['academic_year_id']) && ! $academicYearOptions->pluck('id')->contains((int) $validated['academic_year_id'])) {
            abort(403);
        }
        $selectedAcademicYear = $selectedAcademicYearId
            ? AcademicYear::query()->find($selectedAcademicYearId)
            : null;
        $isReadOnlyHistorical = $this->isReadOnlyHistoricalYear($selectedAcademicYearId);

        $selectedQuarter = (string) ($validated['quarter']
            ?? ($selectedAcademicYear?->current_quarter ?: '1'));

        $advisorySections = Section::query()
            ->with('gradeLevel:id,name')
            ->where('adviser_id', $teacherId)
            ->when($selectedAcademicYearId, function ($query) use ($selectedAcademicYearId) {
                $query->where('academic_year_id', $selectedAcademicYearId);
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

        if (isset($validated['section_id']) && ! in_array((int) $validated['section_id'], $allowedSectionIds, true)) {
            abort(403);
        }

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
                        'maka_diyos' => $conductRating?->maka_diyos,
                        'makatao' => $conductRating?->makatao,
                        'makakalikasan' => $conductRating?->makakalikasan,
                        'makabansa' => $conductRating?->makabansa,
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

        $gradeRelease = null;
        $canReleaseGrades = false;
        $releaseBlockingMessage = null;

        if ($selectedSection) {
            $gradeRelease = GradeRelease::query()
                ->with('releasedBy:id,first_name,last_name,name')
                ->where('academic_year_id', $selectedSection->academic_year_id)
                ->where('section_id', $selectedSection->id)
                ->where('quarter', $selectedQuarter)
                ->first();

            if (! $gradeRelease) {
                $releaseReadiness = $this->resolveGradeReleaseReadiness($selectedSection, $selectedQuarter);
                $canReleaseGrades = $releaseReadiness['can_release'];
                $releaseBlockingMessage = $releaseReadiness['message'];
            }
        }

        return Inertia::render('teacher/advisory-board/index', [
            'context' => [
                'section_options' => $sectionOptions,
                'selected_section_id' => $selectedSectionId > 0 ? $selectedSectionId : null,
                'selected_quarter' => $selectedQuarter,
                'academic_year_options' => $academicYearOptions->all(),
                'selected_academic_year_id' => $selectedAcademicYearId,
                'is_read_only_historical' => $isReadOnlyHistorical,
            ],
            'grade_columns' => $subjectColumns,
            'grade_rows' => $gradeRows,
            'conduct_rows' => $conductRows,
            'status' => $isLocked ? 'locked' : 'draft',
            'grade_release' => [
                'is_released' => (bool) $gradeRelease,
                'released_at' => $gradeRelease?->released_at?->toIso8601String(),
                'released_by_name' => $gradeRelease
                    ? $this->formatUserName(
                        $gradeRelease->releasedBy?->first_name,
                        $gradeRelease->releasedBy?->last_name,
                        $gradeRelease->releasedBy?->name
                    )
                    : null,
                'can_release' => $canReleaseGrades,
                'blocking_message' => $releaseBlockingMessage,
            ],
        ]);
    }

    public function releaseGrades(Request $request, PermanentRecordPopulationService $permanentRecordPopulationService): RedirectResponse
    {
        $validated = $request->validate([
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'quarter' => ['required', 'string', 'in:1,2,3,4'],
        ]);

        $section = Section::query()
            ->whereKey($validated['section_id'])
            ->where('adviser_id', auth()->id())
            ->firstOrFail();
        $this->enforceCurrentYearWriteAccess((int) $section->academic_year_id);

        $releaseReadiness = $this->resolveGradeReleaseReadiness($section, (string) $validated['quarter']);
        if (! $releaseReadiness['can_release']) {
            return back()->with('error', $releaseReadiness['message']);
        }

        $populationSummary = DB::transaction(function () use ($section, $validated, $permanentRecordPopulationService): array {
            GradeRelease::query()->updateOrCreate(
                [
                    'academic_year_id' => $section->academic_year_id,
                    'section_id' => $section->id,
                    'quarter' => $validated['quarter'],
                ],
                [
                    'released_by' => auth()->id(),
                    'released_at' => now(),
                ]
            );

            return $permanentRecordPopulationService->populateForReleasedQuarter(
                $section,
                (string) $validated['quarter']
            );
        });

        return back()->with(
            'success',
            "Quarter grades released to students and parents. Permanent records updated for {$populationSummary['processed']} learner(s)."
        );
    }

    public function storeConduct(StoreAdvisoryConductRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $teacherId = (int) auth()->id();

        $section = Section::query()
            ->whereKey($validated['section_id'])
            ->where('adviser_id', $teacherId)
            ->firstOrFail();
        $this->enforceCurrentYearWriteAccess((int) $section->academic_year_id);

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

        if ($validated['save_mode'] === 'locked') {
            $subjectAssignmentIds = SubjectAssignment::query()
                ->where('section_id', $section->id)
                ->pluck('id');

            if ($subjectAssignmentIds->isEmpty()) {
                return back()->with('error', 'Cannot finalize conduct yet. No class grade submissions were found for this advisory section.');
            }

            $verifiedAssignmentCount = GradeSubmission::query()
                ->where('academic_year_id', $section->academic_year_id)
                ->where('quarter', $validated['quarter'])
                ->whereIn('subject_assignment_id', $subjectAssignmentIds)
                ->where('status', GradeSubmission::STATUS_VERIFIED)
                ->distinct('subject_assignment_id')
                ->count('subject_assignment_id');

            if ($verifiedAssignmentCount < $subjectAssignmentIds->count()) {
                return back()->with('error', 'Cannot finalize conduct until all class grades for this quarter are verified.');
            }

            $hasIncompleteConduct = $enrollmentIds->contains(function (int $enrollmentId) use ($rowsByEnrollment): bool {
                $row = $rowsByEnrollment->get((int) $enrollmentId);
                if (! $row) {
                    return true;
                }

                return in_array($row['maka_diyos'] ?? null, [null, ''], true)
                    || in_array($row['makatao'] ?? null, [null, ''], true)
                    || in_array($row['makakalikasan'] ?? null, [null, ''], true)
                    || in_array($row['makabansa'] ?? null, [null, ''], true);
            });

            if ($hasIncompleteConduct) {
                return back()->with('error', 'Cannot finalize conduct while some core values are still blank.');
            }
        }

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
                    'maka_diyos' => $row['maka_diyos'] ?? null,
                    'makatao' => $row['makatao'] ?? null,
                    'makakalikasan' => $row['makakalikasan'] ?? null,
                    'makabansa' => $row['makabansa'] ?? null,
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

    /**
     * @return array{can_release: bool, message: string|null}
     */
    private function resolveGradeReleaseReadiness(Section $section, string $quarter): array
    {
        $subjectAssignmentIds = SubjectAssignment::query()
            ->where('section_id', $section->id)
            ->pluck('id');

        if ($subjectAssignmentIds->isEmpty()) {
            return [
                'can_release' => false,
                'message' => 'Cannot release grades yet. No subject assignments were found for this advisory section.',
            ];
        }

        $enrollmentCount = Enrollment::query()
            ->where('academic_year_id', $section->academic_year_id)
            ->where('section_id', $section->id)
            ->where('status', 'enrolled')
            ->count();

        if ($enrollmentCount === 0) {
            return [
                'can_release' => false,
                'message' => 'Cannot release grades yet. No enrolled students were found for this advisory section.',
            ];
        }

        $verifiedAssignmentCount = GradeSubmission::query()
            ->where('academic_year_id', $section->academic_year_id)
            ->where('quarter', $quarter)
            ->whereIn('subject_assignment_id', $subjectAssignmentIds)
            ->where('status', GradeSubmission::STATUS_VERIFIED)
            ->distinct('subject_assignment_id')
            ->count('subject_assignment_id');

        if ($verifiedAssignmentCount < $subjectAssignmentIds->count()) {
            return [
                'can_release' => false,
                'message' => 'Cannot release grades until all subject grades for this quarter are verified.',
            ];
        }

        $lockedGradeRows = FinalGrade::query()
            ->where('quarter', $quarter)
            ->whereIn('subject_assignment_id', $subjectAssignmentIds)
            ->where('is_locked', true)
            ->whereHas('enrollment', function ($query) use ($section): void {
                $query
                    ->where('academic_year_id', $section->academic_year_id)
                    ->where('section_id', $section->id)
                    ->where('status', 'enrolled');
            })
            ->count();

        $expectedGradeRows = $enrollmentCount * $subjectAssignmentIds->count();
        if ($lockedGradeRows < $expectedGradeRows) {
            return [
                'can_release' => false,
                'message' => 'Cannot release grades yet. Some student grade rows are missing or unlocked.',
            ];
        }

        return [
            'can_release' => true,
            'message' => null,
        ];
    }

    private function formatUserName(?string $firstName, ?string $lastName, ?string $fallbackName): string
    {
        $trimmed = trim("{$firstName} {$lastName}");

        return $trimmed !== '' ? $trimmed : ($fallbackName ?: 'Unknown User');
    }
}
