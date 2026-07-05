<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSectionRequest;
use App\Http\Requests\Admin\UpdateSectionRequest;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SectionController extends Controller
{
    public function index(): Response
    {
        $currentYear = $this->resolveActiveYear();
        $sectionAdviserHistory = $this->resolveSectionAdviserHistory($currentYear);

        return Inertia::render('admin/section-manager/index', [
            'gradeLevels' => GradeLevel::with(['sections' => function ($query) use ($currentYear) {
                $query->where('academic_year_id', $currentYear?->id)
                    ->with('adviser')
                    ->withCount('enrollments as students_count');
            }])->orderBy('level_order')->get()->map(function (GradeLevel $gradeLevel) use ($sectionAdviserHistory) {
                $gradeLevel->sections = $gradeLevel->sections->map(function (Section $section) use ($sectionAdviserHistory) {
                    $section->setAttribute(
                        'adviser_history',
                        $sectionAdviserHistory[$this->sectionHistoryKey($section->grade_level_id, $section->name)] ?? []
                    );

                    return $section;
                });

                return $gradeLevel;
            }),
            'teachers' => User::where('role', UserRole::TEACHER)
                ->where('is_active', true)
                ->get(['id', 'name'])
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'initial' => collect(explode(' ', $user->name))->map(fn ($n) => $n[0])->take(2)->join(''),
                ]),
            'activeYear' => $currentYear,
        ]);
    }

    public function store(StoreSectionRequest $request): RedirectResponse
    {
        Section::create($request->validated());

        return back()->with('success', 'Section created successfully.');
    }

    public function update(UpdateSectionRequest $request, Section $section): RedirectResponse
    {
        $section->update($request->validated());

        return back()->with('success', 'Section updated successfully.');
    }

    public function destroy(Section $section): RedirectResponse
    {
        $section->delete();

        return back()->with('success', 'Section removed.');
    }

    private function resolveActiveYear(): ?AcademicYear
    {
        return AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()
                ->where('status', 'upcoming')
                ->orderBy('start_date')
                ->first()
            ?? AcademicYear::query()
                ->where('status', '!=', 'completed')
                ->orderBy('start_date')
                ->first();
    }

    /**
     * @return array<string, list<array{id: int, adviser_id: int, adviser_name: string, academic_year_id: int, academic_year_name: string}>>
     */
    private function resolveSectionAdviserHistory(?AcademicYear $currentYear): array
    {
        if (! $currentYear) {
            return [];
        }

        $currentSections = Section::query()
            ->where('academic_year_id', $currentYear->id)
            ->get(['id', 'grade_level_id', 'name']);

        if ($currentSections->isEmpty()) {
            return [];
        }

        $gradeLevelIds = $currentSections->pluck('grade_level_id')->unique()->values();
        $sectionNames = $currentSections->pluck('name')->filter()->unique()->values();

        if ($gradeLevelIds->isEmpty() || $sectionNames->isEmpty()) {
            return [];
        }

        $historyRows = Section::query()
            ->with(['adviser:id,name', 'academicYear:id,name,start_date'])
            ->whereIn('grade_level_id', $gradeLevelIds)
            ->whereIn('name', $sectionNames)
            ->whereNotNull('adviser_id')
            ->orderByDesc('academic_year_id')
            ->get(['id', 'grade_level_id', 'name', 'adviser_id', 'academic_year_id']);

        return $currentSections
            ->mapWithKeys(function (Section $section) use ($historyRows): array {
                $key = $this->sectionHistoryKey($section->grade_level_id, $section->name);

                $history = $historyRows
                    ->filter(fn (Section $historySection): bool => $this->sectionHistoryKey(
                        $historySection->grade_level_id,
                        $historySection->name
                    ) === $key && $historySection->id !== $section->id)
                    ->sortByDesc(fn (Section $historySection): string => (string) ($historySection->academicYear?->start_date ?? ''))
                    ->values()
                    ->map(fn (Section $historySection): array => [
                        'id' => (int) $historySection->id,
                        'adviser_id' => (int) $historySection->adviser_id,
                        'adviser_name' => (string) ($historySection->adviser?->name ?? 'Unknown Adviser'),
                        'academic_year_id' => (int) $historySection->academic_year_id,
                        'academic_year_name' => (string) ($historySection->academicYear?->name ?? 'Unknown School Year'),
                    ])
                    ->all();

                return [$key => $history];
            })
            ->all();
    }

    private function sectionHistoryKey(int $gradeLevelId, string $name): string
    {
        return $gradeLevelId.'|'.mb_strtolower(trim($name));
    }
}
