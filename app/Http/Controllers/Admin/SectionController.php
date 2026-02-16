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
        $currentYear = AcademicYear::where('status', '!=', 'completed')->first();

        return Inertia::render('admin/section-manager/index', [
            'gradeLevels' => GradeLevel::with(['sections' => function ($query) use ($currentYear) {
                $query->where('academic_year_id', $currentYear?->id)
                    ->with('adviser');
            }])->orderBy('level_order')->get(),
            'teachers' => User::where('role', UserRole::TEACHER)
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
}
