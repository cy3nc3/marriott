<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexGradeVerificationRequest;
use App\Http\Requests\Admin\ReturnGradeSubmissionRequest;
use App\Http\Requests\Admin\UpdateGradeReminderAutomationRequest;
use App\Http\Requests\Admin\UpdateGradeSubmissionDeadlineRequest;
use App\Http\Requests\Admin\VerifyGradeSubmissionRequest;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradeSubmission;
use App\Models\Setting;
use App\Models\User;
use App\Services\DashboardCacheService;
use App\Services\GradeDeadlineAnnouncementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GradeVerificationController extends Controller
{
    public function index(IndexGradeVerificationRequest $request): Response
    {
        $request->validated();

        $activeYear = $this->resolveActiveAcademicYear();
        $currentQuarter = (string) ($activeYear?->current_quarter ?: '1');

        $submissions = GradeSubmission::query()
            ->with([
                'subjectAssignment:id,section_id,teacher_subject_id',
                'subjectAssignment.section:id,grade_level_id,name',
                'subjectAssignment.section.gradeLevel:id,name',
                'subjectAssignment.teacherSubject:id,teacher_id,subject_id',
                'subjectAssignment.teacherSubject.teacher:id,first_name,last_name,name',
                'subjectAssignment.teacherSubject.subject:id,subject_name',
                'submittedBy:id,first_name,last_name,name',
                'verifiedBy:id,first_name,last_name,name',
                'returnedBy:id,first_name,last_name,name',
            ])
            ->when($activeYear, function (Builder $query) use ($activeYear): void {
                $query->where('academic_year_id', $activeYear->id);
            })
            ->where('quarter', $currentQuarter)
            ->orderByRaw("case status when 'submitted' then 1 when 'returned' then 2 when 'verified' then 3 else 4 end")
            ->orderByDesc('submitted_at')
            ->orderByDesc('updated_at')
            ->get();

        $sectionIds = $submissions
            ->pluck('subjectAssignment.section_id')
            ->filter()
            ->unique()
            ->values();

        $enrolledCountBySection = collect();
        if ($activeYear && $sectionIds->isNotEmpty()) {
            $enrolledCountBySection = Enrollment::query()
                ->where('academic_year_id', $activeYear->id)
                ->whereIn('section_id', $sectionIds)
                ->where('status', 'enrolled')
                ->selectRaw('section_id, count(*) as total')
                ->groupBy('section_id')
                ->pluck('total', 'section_id');
        }

        $gradeStatsByKey = collect();
        if ($activeYear && $submissions->isNotEmpty()) {
            $assignmentIds = $submissions->pluck('subject_assignment_id')->unique()->values();

            $gradeStatsByKey = FinalGrade::query()
                ->join('enrollments', 'enrollments.id', '=', 'final_grades.enrollment_id')
                ->where('enrollments.academic_year_id', $activeYear->id)
                ->where('enrollments.status', 'enrolled')
                ->whereIn('final_grades.subject_assignment_id', $assignmentIds)
                ->where('final_grades.quarter', $currentQuarter)
                ->selectRaw('
                    final_grades.subject_assignment_id,
                    final_grades.quarter,
                    count(*) as posted_rows,
                    sum(case when final_grades.is_locked then 1 else 0 end) as locked_rows,
                    avg(final_grades.grade) as average_grade,
                    min(final_grades.grade) as min_grade,
                    max(final_grades.grade) as max_grade,
                    sum(case when final_grades.grade < 75 then 1 else 0 end) as at_risk_count
                ')
                ->groupBy('final_grades.subject_assignment_id', 'final_grades.quarter')
                ->get()
                ->keyBy(function (object $row): string {
                    return "{$row->subject_assignment_id}-{$row->quarter}";
                });
        }

        $submissionRows = $submissions
            ->map(function (GradeSubmission $submission) use ($enrolledCountBySection, $gradeStatsByKey): array {
                $section = $submission->subjectAssignment?->section;
                $gradeLevelName = $section?->gradeLevel?->name;
                $sectionName = $section?->name;
                $subjectName = $submission->subjectAssignment?->teacherSubject?->subject?->subject_name ?? 'Subject';

                $classLabel = $sectionName
                    ? "{$gradeLevelName} - {$sectionName} ({$subjectName})"
                    : "Unassigned ({$subjectName})";

                $teacher = $submission->subjectAssignment?->teacherSubject?->teacher;
                $teacherName = $this->formatUserName(
                    $teacher?->first_name,
                    $teacher?->last_name,
                    $teacher?->name
                );

                $statsKey = "{$submission->subject_assignment_id}-{$submission->quarter}";
                $stats = $gradeStatsByKey->get($statsKey);

                $expectedRows = (int) ($enrolledCountBySection[$submission->subjectAssignment?->section_id] ?? 0);
                $postedRows = (int) ($stats?->posted_rows ?? 0);
                $lockedRows = (int) ($stats?->locked_rows ?? 0);

                return [
                    'id' => $submission->id,
                    'academic_year_id' => (int) $submission->academic_year_id,
                    'class_label' => $classLabel,
                    'teacher_name' => $teacherName,
                    'quarter' => (string) $submission->quarter,
                    'quarter_label' => $this->resolveQuarterLabel((string) $submission->quarter),
                    'status' => $submission->status,
                    'status_label' => $this->resolveStatusLabel($submission->status),
                    'expected_rows' => $expectedRows,
                    'posted_rows' => $postedRows,
                    'locked_rows' => $lockedRows,
                    'missing_rows' => max($expectedRows - $postedRows, 0),
                    'average_grade' => $stats?->average_grade !== null ? round((float) $stats->average_grade, 2) : null,
                    'min_grade' => $stats?->min_grade !== null ? round((float) $stats->min_grade, 2) : null,
                    'max_grade' => $stats?->max_grade !== null ? round((float) $stats->max_grade, 2) : null,
                    'at_risk_count' => (int) ($stats?->at_risk_count ?? 0),
                    'return_notes' => $submission->return_notes,
                    'submitted_at' => $submission->submitted_at?->toIso8601String(),
                    'verified_at' => $submission->verified_at?->toIso8601String(),
                    'returned_at' => $submission->returned_at?->toIso8601String(),
                    'can_verify' => $submission->status === GradeSubmission::STATUS_SUBMITTED,
                    'can_return' => $submission->status === GradeSubmission::STATUS_SUBMITTED,
                ];
            })
            ->values();

        return Inertia::render('admin/grade-verification/index', [
            'context' => [
                'academic_year' => $activeYear?->name,
                'current_quarter' => $currentQuarter,
                'current_quarter_label' => $this->resolveQuarterLabel($currentQuarter),
                'submission_deadline' => $this->resolveSubmissionDeadline($activeYear, $currentQuarter),
                'reminder_automation' => $this->resolveReminderAutomation(),
            ],
            'summary' => [
                'submitted_count' => $submissionRows->where('status', GradeSubmission::STATUS_SUBMITTED)->count(),
                'returned_count' => $submissionRows->where('status', GradeSubmission::STATUS_RETURNED)->count(),
                'verified_count' => $submissionRows->where('status', GradeSubmission::STATUS_VERIFIED)->count(),
            ],
            'submissions' => $submissionRows,
        ]);
    }

    public function updateDeadline(
        UpdateGradeSubmissionDeadlineRequest $request,
        GradeDeadlineAnnouncementService $deadlineAnnouncementService
    ): RedirectResponse {
        $validated = $request->validated();
        $activeYear = $this->resolveActiveAcademicYear();

        if (! $activeYear) {
            return back()->with('error', 'No school year available for deadline setup.');
        }

        $currentQuarter = (string) ($activeYear->current_quarter ?: '1');
        $settingKey = $this->deadlineSettingKey((int) $activeYear->id, $currentQuarter);
        $newDeadline = Carbon::parse((string) $validated['submission_deadline']);

        $oldDeadlineValue = Setting::get($settingKey);
        $previousDeadline = null;

        if (is_string($oldDeadlineValue) && $oldDeadlineValue !== '') {
            $previousDeadline = Carbon::parse($oldDeadlineValue);
        }

        Setting::set($settingKey, $newDeadline->toDateTimeString(), 'grading');

        $announcementPosted = false;
        $hasMeaningfulChange = ! $previousDeadline || ! $previousDeadline->equalTo($newDeadline);

        if ($hasMeaningfulChange) {
            $actor = auth()->user();

            if ($actor instanceof User) {
                $announcementPosted = $deadlineAnnouncementService->publishConfiguredDeadlineAnnouncement(
                    $activeYear,
                    $currentQuarter,
                    $newDeadline,
                    $previousDeadline,
                    $actor
                );
            }
        }

        if ($previousDeadline && $previousDeadline->equalTo($newDeadline)) {
            return back()->with('success', 'Deadline is unchanged.');
        }

        $baseMessage = $previousDeadline
            ? 'Submission deadline updated.'
            : 'Submission deadline set.';

        if (! $announcementPosted) {
            $baseMessage .= ' No pending teacher submissions detected for announcement recipients.';
        }

        return back()->with('success', $baseMessage);
    }

    public function updateReminderAutomation(
        UpdateGradeReminderAutomationRequest $request
    ): RedirectResponse {
        $validated = $request->validated();

        Setting::set(
            'grade_deadline_reminder_auto_send_enabled',
            (bool) $validated['auto_send_enabled'],
            'grading'
        );
        Setting::set(
            'grade_deadline_reminder_send_time',
            $validated['send_time'],
            'grading'
        );

        return back()->with('success', 'Grade reminder automation settings updated.');
    }

    public function verify(
        VerifyGradeSubmissionRequest $request,
        GradeSubmission $gradeSubmission
    ): RedirectResponse {
        $gradeSubmission->loadMissing('subjectAssignment:id,section_id');

        if ($gradeSubmission->status !== GradeSubmission::STATUS_SUBMITTED) {
            return back()->with('error', 'Only submitted class grades can be verified.');
        }

        $expectedRows = Enrollment::query()
            ->where('academic_year_id', $gradeSubmission->academic_year_id)
            ->where('section_id', $gradeSubmission->subjectAssignment?->section_id)
            ->where('status', 'enrolled')
            ->count();

        $lockedRows = $this->finalGradesForSubmission($gradeSubmission)
            ->where('is_locked', true)
            ->count();

        if ($lockedRows < $expectedRows) {
            return back()->with('error', 'Cannot verify. Some grade rows are missing or unlocked.');
        }

        DB::transaction(function () use ($gradeSubmission): void {
            $this->finalGradesForSubmission($gradeSubmission)
                ->update([
                    'is_locked' => true,
                ]);

            $gradeSubmission->update([
                'status' => GradeSubmission::STATUS_VERIFIED,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'returned_by' => null,
                'returned_at' => null,
                'return_notes' => null,
            ]);
        });

        DashboardCacheService::bust();

        return back()->with('success', 'Class quarter grades verified.');
    }

    public function returnSubmission(
        ReturnGradeSubmissionRequest $request,
        GradeSubmission $gradeSubmission
    ): RedirectResponse {
        $gradeSubmission->loadMissing('subjectAssignment:id,section_id');

        if ($gradeSubmission->status !== GradeSubmission::STATUS_SUBMITTED) {
            return back()->with('error', 'Only submitted class grades can be returned.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($gradeSubmission, $validated): void {
            $this->finalGradesForSubmission($gradeSubmission)
                ->update([
                    'is_locked' => false,
                ]);

            $gradeSubmission->update([
                'status' => GradeSubmission::STATUS_RETURNED,
                'verified_by' => null,
                'verified_at' => null,
                'returned_by' => auth()->id(),
                'returned_at' => now(),
                'return_notes' => $validated['return_notes'],
            ]);
        });

        DashboardCacheService::bust();

        return back()->with('success', 'Class quarter grades returned to teacher for revision.');
    }

    private function finalGradesForSubmission(GradeSubmission $gradeSubmission): Builder
    {
        $sectionId = (int) ($gradeSubmission->subjectAssignment?->section_id ?? 0);

        return FinalGrade::query()
            ->where('subject_assignment_id', $gradeSubmission->subject_assignment_id)
            ->where('quarter', $gradeSubmission->quarter)
            ->whereHas('enrollment', function (Builder $query) use ($gradeSubmission, $sectionId): void {
                $query
                    ->where('academic_year_id', $gradeSubmission->academic_year_id)
                    ->when($sectionId > 0, function (Builder $sectionQuery) use ($sectionId): void {
                        $sectionQuery->where('section_id', $sectionId);
                    });
            });
    }

    private function resolveActiveAcademicYear(): ?AcademicYear
    {
        return AcademicYear::query()
            ->where('status', 'ongoing')
            ->first()
            ?? AcademicYear::query()->orderByDesc('start_date')->first();
    }

    private function resolveSubmissionDeadline(?AcademicYear $activeYear, string $quarter): ?string
    {
        if (! $activeYear) {
            return null;
        }

        $value = Setting::get($this->deadlineSettingKey((int) $activeYear->id, $quarter));

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    /**
     * @return array{auto_send_enabled: bool, send_time: string}
     */
    private function resolveReminderAutomation(): array
    {
        return [
            'auto_send_enabled' => Setting::enabled('grade_deadline_reminder_auto_send_enabled', true),
            'send_time' => (string) Setting::get('grade_deadline_reminder_send_time', '07:00'),
        ];
    }

    private function deadlineSettingKey(int $academicYearId, string $quarter): string
    {
        return "grade_submission_deadline_{$academicYearId}_q{$quarter}";
    }

    private function resolveQuarterLabel(string $quarter): string
    {
        return match ($quarter) {
            '1' => '1st Quarter',
            '2' => '2nd Quarter',
            '3' => '3rd Quarter',
            '4' => '4th Quarter',
            default => "Quarter {$quarter}",
        };
    }

    private function resolveStatusLabel(string $status): string
    {
        return match ($status) {
            GradeSubmission::STATUS_SUBMITTED => 'For Verification',
            GradeSubmission::STATUS_VERIFIED => 'Verified',
            GradeSubmission::STATUS_RETURNED => 'Returned',
            default => 'Draft',
        };
    }

    private function formatUserName(?string $firstName, ?string $lastName, ?string $fallbackName): string
    {
        $trimmed = trim("{$firstName} {$lastName}");

        if ($trimmed !== '') {
            return $trimmed;
        }

        return $fallbackName ?: 'Unassigned';
    }
}
