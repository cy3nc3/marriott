<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScheduleRequest;
use App\Http\Requests\Admin\UpdateScheduleRequest;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TeacherSubject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(): Response
    {
        $activeYear = $this->resolveActiveYear();

        if (! $activeYear) {
            return Inertia::render('admin/schedule-builder/index', [
                'activeYear' => null,
                'gradeLevels' => [],
                'teachers' => [],
                'subjects' => [],
                'sectionSchedules' => [],
            ]);
        }

        return Inertia::render('admin/schedule-builder/index', [
            'activeYear' => $activeYear,
            'gradeLevels' => GradeLevel::with(['sections' => function ($q) use ($activeYear) {
                $q->where('academic_year_id', $activeYear->id);
            }])->orderBy('level_order')->get(),
            'subjects' => Subject::with(['teachers'])->get()->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->subject_name,
                'code' => $s->subject_code,
                'qualifiedTeachers' => $s->teachers->pluck('id'),
            ]),
            'teachers' => User::query()
                ->where('role', UserRole::TEACHER)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->orderBy('name')
                ->get(['id', 'name', 'first_name', 'last_name'])
                ->map(function (User $user): array {
                    $teacherName = trim((string) ($user->name ?: "{$user->first_name} {$user->last_name}"));

                    if ($teacherName === '') {
                        $teacherName = "Teacher {$user->id}";
                    }

                    return [
                        'id' => $user->id,
                        'name' => $teacherName,
                        'initial' => collect(explode(' ', $teacherName))
                            ->filter()
                            ->map(fn ($namePart) => substr($namePart, 0, 1))
                            ->take(2)
                            ->join(''),
                    ];
                }),
            'sectionSchedules' => ClassSchedule::whereHas('section', function ($q) use ($activeYear) {
                $q->where('academic_year_id', $activeYear->id);
            })->with(['subjectAssignment.teacherSubject.subject', 'subjectAssignment.teacherSubject.teacher'])->get(),
        ]);
    }

    public function store(StoreScheduleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->type === 'academic' && $request->filled(['subject_id', 'teacher_id'])) {
            // Find or create TeacherSubject link
            $teacherSubject = TeacherSubject::firstOrCreate([
                'teacher_id' => $request->teacher_id,
                'subject_id' => $request->subject_id,
            ]);

            // Find or create SubjectAssignment for this section
            $assignment = SubjectAssignment::firstOrCreate([
                'section_id' => $request->section_id,
                'teacher_subject_id' => $teacherSubject->id,
            ]);

            $data['subject_assignment_id'] = $assignment->id;
        }

        ClassSchedule::create($data);

        return back()->with('success', 'Schedule added.');
    }

    public function update(UpdateScheduleRequest $request, ClassSchedule $schedule): RedirectResponse
    {
        $schedule->update($request->validated());

        return back()->with('success', 'Schedule updated.');
    }

    public function destroy(ClassSchedule $schedule): RedirectResponse
    {
        $schedule->delete();

        return back()->with('success', 'Schedule removed.');
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
}
