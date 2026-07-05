<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScheduleRequest;
use App\Http\Requests\Admin\UpdateScheduleRequest;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\GradeLevel;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TeacherSubject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
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
            'subjects' => Subject::with([
                'teachers' => fn ($query) => $query->where('is_active', true),
            ])->get()->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->subject_name,
                'code' => $s->subject_code,
                'qualifiedTeachers' => $s->teachers->pluck('id'),
            ]),
            'teachers' => $this->eligibleTeachersQuery()
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
            })->with(['section.gradeLevel', 'subjectAssignment.teacherSubject.subject', 'subjectAssignment.teacherSubject.teacher'])->get(),
        ]);
    }

    public function store(StoreScheduleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->type === 'academic' && $request->filled(['subject_id', 'teacher_id'])) {
            $teacherSubject = $this->resolveEligibleTeacherSubject(
                (int) $request->subject_id,
                (int) $request->teacher_id
            );

            $assignment = SubjectAssignment::firstOrCreate([
                'section_id' => (int) $request->section_id,
                'teacher_subject_id' => $teacherSubject->id,
            ]);

            $data['subject_assignment_id'] = $assignment->id;
        }

        ClassSchedule::create($data);

        return back()->with('success', 'Schedule added.');
    }

    public function update(UpdateScheduleRequest $request, ClassSchedule $schedule): RedirectResponse
    {
        $data = $request->validated();

        if ($request->type === 'academic' && $request->filled(['subject_id', 'teacher_id'])) {
            $teacherSubject = $this->resolveEligibleTeacherSubject(
                (int) $request->subject_id,
                (int) $request->teacher_id
            );

            $assignment = SubjectAssignment::firstOrCreate([
                'section_id' => $schedule->section_id,
                'teacher_subject_id' => $teacherSubject->id,
            ]);

            $data['subject_assignment_id'] = $assignment->id;
        } elseif ($request->type !== 'academic') {
            $data['subject_assignment_id'] = null;
        }

        $schedule->update($data);

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

    private function resolveEligibleTeacherSubject(int $subjectId, int $teacherId): TeacherSubject
    {
        $subject = Subject::query()
            ->with('teachers:id')
            ->find($subjectId);

        if (! $subject) {
            throw ValidationException::withMessages([
                'subject_id' => 'Selected subject is invalid.',
            ]);
        }

        $teacher = User::query()
            ->where('role', UserRole::TEACHER->value)
            ->where('is_active', true)
            ->with('teacherProfile')
            ->find($teacherId);

        if (! $teacher) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Selected teacher is invalid.',
            ]);
        }

        $isCurriculumCertified = $subject->teachers->pluck('id')->contains($teacher->id);
        if (! $isCurriculumCertified) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Selected teacher is not certified for this subject in Curriculum Manager.',
            ]);
        }

        $allowedStatuses = ['fully_qualified'];
        $policyMode = (string) Setting::get('teacher_assignment_policy_mode', 'strict');
        $allowProvisional = Setting::enabled('teacher_assignment_allow_provisional', false);
        if ($policyMode === 'transitional' && $allowProvisional) {
            $allowedStatuses[] = 'provisionally_qualified';
        }

        $teacherStatus = (string) ($teacher->teacherProfile?->qualification_status ?? 'not_qualified');
        if (! in_array($teacherStatus, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Selected teacher is not eligible under the current teacher qualification policy.',
            ]);
        }

        return TeacherSubject::firstOrCreate([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
        ]);
    }

    private function eligibleTeachersQuery()
    {
        $allowedStatuses = ['fully_qualified'];
        $policyMode = (string) Setting::get('teacher_assignment_policy_mode', 'strict');
        $allowProvisional = Setting::enabled('teacher_assignment_allow_provisional', false);
        if ($policyMode === 'transitional' && $allowProvisional) {
            $allowedStatuses[] = 'provisionally_qualified';
        }

        return User::query()
            ->where('role', UserRole::TEACHER)
            ->where('is_active', true)
            ->whereHas('teacherProfile', function ($query) use ($allowedStatuses): void {
                $query->whereIn('qualification_status', $allowedStatuses);
            });
    }
}
