<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CertifyTeachersRequest;
use App\Http\Requests\Admin\StoreSubjectRequest;
use App\Http\Requests\Admin\UpdateSubjectRequest;
use App\Models\GradeLevel;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\TeacherEligibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CurriculumController extends Controller
{
    public function index(): Response
    {
        $eligibleTeachers = $this->eligibleTeachersQuery()
            ->get(['id', 'name']);

        return Inertia::render('admin/curriculum-manager/index', [
            'gradeLevels' => GradeLevel::with([
                'subjects.teachers' => fn ($query) => $query->where('is_active', true),
            ])
                ->orderBy('level_order')
                ->get()
                ->map(function ($grade) {
                    return [
                        'id' => $grade->id,
                        'name' => $grade->name,
                        'level_order' => $grade->level_order,
                        'subjects' => $grade->subjects->map(function ($sub) {
                            return [
                                'id' => $sub->id,
                                'grade_level_id' => $sub->grade_level_id,
                                'subject_code' => $sub->subject_code,
                                'subject_name' => $sub->subject_name,
                                'required_weekly_minutes' => (int) ($sub->required_weekly_minutes ?? 200),
                                'teachers' => $sub->teachers->map(function ($t) {
                                    return [
                                        'id' => $t->id,
                                        'name' => $t->name,
                                        'qualification_status' => $t->teacherProfile?->qualification_status ?? 'not_qualified',
                                    ];
                                }),
                            ];
                        }),
                    ];
                }),
            'teachers' => $eligibleTeachers
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'initial' => collect(explode(' ', $user->name))->map(fn ($n) => $n[0])->take(2)->join(''),
                ]),
        ]);
    }

    public function store(StoreSubjectRequest $request, TeacherEligibilityService $eligibilityService): RedirectResponse
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $eligibilityService) {
            $subject = Subject::create($request->validated());

            if ($request->has('teacher_ids')) {
                $this->assertTeachersEligible((array) $request->teacher_ids, $subject, $eligibilityService);
                $subject->teachers()->sync($request->teacher_ids);
            }
        });

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

    public function certifyTeachers(CertifyTeachersRequest $request, Subject $subject, TeacherEligibilityService $eligibilityService, AuditLogService $auditLogService): RedirectResponse
    {
        $validated = $request->validated();
        $teacherData = $validated['teacher_details'];
        $ids = collect($teacherData)->pluck('id')->all();

        $this->assertTeachersEligible($ids, $subject, $eligibilityService);

        $subject->teachers()->sync($ids);

        $auditLogService->log('faculty.certified', $subject, null, [
            'subject_code' => $subject->subject_code,
            'teacher_ids' => $ids,
        ]);

        return back()->with('success', 'Faculty certification updated.');
    }

    private function eligibleTeachersQuery()
    {
        $policyMode = (string) Setting::get('teacher_assignment_policy_mode', 'strict');
        $allowProvisional = Setting::enabled('teacher_assignment_allow_provisional', false);

        $allowedStatuses = ['fully_qualified'];
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

    /**
     * @param  list<int|string>  $teacherIds
     */
    private function assertTeachersEligible(array $teacherIds, Subject $subject, TeacherEligibilityService $eligibilityService): void
    {
        $normalizedIds = collect($teacherIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($normalizedIds->isEmpty()) {
            return;
        }

        $teachers = User::whereIn('id', $normalizedIds)
            ->where('is_active', true)
            ->with('teacherProfile')
            ->get();
        $ineligibleReasons = [];

        foreach ($teachers as $teacher) {
            $result = $eligibilityService->evaluate($teacher, $subject);
            if (! $result['eligible']) {
                $ineligibleReasons[] = $teacher->name.': '.implode(' ', $result['reasons']);
            }
        }

        if (count($ineligibleReasons) > 0) {
            throw ValidationException::withMessages([
                'teacher_ids' => 'Eligibility check failed: '.implode(' | ', $ineligibleReasons),
            ]);
        }
    }
}
