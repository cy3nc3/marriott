<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubjectRequest;
use App\Http\Requests\Admin\UpdateSubjectRequest;
use App\Models\GradeLevel;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CurriculumController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/curriculum-manager/index', [
            'gradeLevels' => GradeLevel::with(['subjects.teachers'])->orderBy('level_order')->get(),
            'teachers' => User::where('role', UserRole::TEACHER)
                ->get(['id', 'name'])
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'initial' => collect(explode(' ', $user->name))->map(fn ($n) => $n[0])->take(2)->join(''),
                ]),
        ]);
    }

    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $subject = Subject::create($request->validated());

        if ($request->has('teacher_ids')) {
            $subject->teachers()->sync($request->teacher_ids);
        }

        return back()->with('success', 'Subject created successfully.');
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update($request->validated());

        return back()->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return back()->with('success', 'Subject removed from curriculum.');
    }

    public function certifyTeachers(Request $request, Subject $subject): RedirectResponse
    {
        $request->validate([
            'teacher_ids' => ['required', 'array'],
            'teacher_ids.*' => ['exists:users,id'],
        ]);

        $subject->teachers()->sync($request->teacher_ids);

        return back()->with('success', 'Faculty certification updated.');
    }
}
