<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\GradeSubmission;
use App\Models\Setting;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GradeDeadlineAnnouncementService
{
    public function publishConfiguredDeadlineAnnouncement(
        AcademicYear $academicYear,
        string $quarter,
        Carbon $deadline,
        ?Carbon $previousDeadline,
        User $actor
    ): bool {
        $pendingTeacherIds = $this->resolvePendingTeacherIds($academicYear, $quarter);

        if ($pendingTeacherIds->isEmpty()) {
            return false;
        }

        $quarterLabel = $this->resolveQuarterLabel($quarter);
        $deadlineText = $deadline->format('m/d/Y h:i A');

        $title = $previousDeadline
            ? "Grade Submission Deadline Updated ({$quarterLabel})"
            : "Grade Submission Deadline Set ({$quarterLabel})";

        $content = $previousDeadline
            ? "The {$quarterLabel} grade submission deadline for SY {$academicYear->name} has been updated from {$previousDeadline->format('m/d/Y h:i A')} to {$deadlineText}. Please finalize and submit pending class grades on or before the deadline."
            : "The {$quarterLabel} grade submission deadline for SY {$academicYear->name} is set to {$deadlineText}. Please finalize and submit pending class grades on or before the deadline.";

        $this->createTeacherAnnouncement(
            $actor,
            $title,
            $content,
            $pendingTeacherIds,
            $deadline->copy()->addDays(2)
        );

        return true;
    }

    public function publishDueReminders(
        AcademicYear $academicYear,
        Carbon $referenceDate,
        User $actor
    ): int {
        $postedCount = 0;

        foreach (['1', '2', '3', '4'] as $quarter) {
            $deadlineValue = Setting::get(
                $this->deadlineSettingKey((int) $academicYear->id, $quarter)
            );

            if (! is_string($deadlineValue) || $deadlineValue === '') {
                continue;
            }

            $deadline = Carbon::parse($deadlineValue);
            $phase = $this->resolveReminderPhase($referenceDate, $deadline);

            if ($phase === null) {
                continue;
            }

            $pendingTeacherIds = $this->resolvePendingTeacherIds($academicYear, $quarter);

            if ($pendingTeacherIds->isEmpty()) {
                continue;
            }

            $sentKey = $this->reminderSentSettingKey(
                (int) $academicYear->id,
                $quarter,
                $phase,
                $deadline
            );

            if (Setting::get($sentKey) === '1') {
                continue;
            }

            $quarterLabel = $this->resolveQuarterLabel($quarter);
            $deadlineText = $deadline->format('m/d/Y h:i A');

            $title = $phase === 'tomorrow'
                ? "Reminder: Grade Deadline Tomorrow ({$quarterLabel})"
                : "Reminder: Grade Deadline Today ({$quarterLabel})";

            $content = $phase === 'tomorrow'
                ? "This is a reminder that the {$quarterLabel} grade submission deadline for SY {$academicYear->name} is tomorrow ({$deadlineText}). Please submit any pending class grades."
                : "The {$quarterLabel} grade submission deadline for SY {$academicYear->name} is today ({$deadlineText}). Please submit any remaining pending class grades.";

            $this->createTeacherAnnouncement(
                $actor,
                $title,
                $content,
                $pendingTeacherIds,
                $deadline->copy()->addDay()
            );

            Setting::set($sentKey, '1', 'grading');
            $postedCount++;
        }

        return $postedCount;
    }

    /**
     * @return Collection<int, int>
     */
    public function resolvePendingTeacherIds(
        AcademicYear $academicYear,
        string $quarter
    ): Collection {
        $assignments = SubjectAssignment::query()
            ->join('sections', 'sections.id', '=', 'subject_assignments.section_id')
            ->join('teacher_subjects', 'teacher_subjects.id', '=', 'subject_assignments.teacher_subject_id')
            ->where('sections.academic_year_id', $academicYear->id)
            ->get([
                'subject_assignments.id as assignment_id',
                'teacher_subjects.teacher_id as teacher_id',
            ]);

        if ($assignments->isEmpty()) {
            return collect();
        }

        $submissionStatusByAssignment = GradeSubmission::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('quarter', $quarter)
            ->whereIn('subject_assignment_id', $assignments->pluck('assignment_id'))
            ->pluck('status', 'subject_assignment_id');

        return $assignments
            ->filter(function (object $assignment) use ($submissionStatusByAssignment): bool {
                $status = $submissionStatusByAssignment->get((int) $assignment->assignment_id);

                if ($status === null) {
                    return true;
                }

                return in_array($status, [
                    GradeSubmission::STATUS_DRAFT,
                    GradeSubmission::STATUS_RETURNED,
                ], true);
            })
            ->pluck('teacher_id')
            ->map(fn ($teacherId): int => (int) $teacherId)
            ->unique()
            ->values();
    }

    private function createTeacherAnnouncement(
        User $actor,
        string $title,
        string $content,
        Collection $targetTeacherIds,
        Carbon $expiresAt
    ): Announcement {
        return Announcement::query()->create([
            'user_id' => $actor->id,
            'title' => $title,
            'content' => $content,
            'target_roles' => ['teacher'],
            'target_user_ids' => $targetTeacherIds->values()->all(),
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);
    }

    private function deadlineSettingKey(int $academicYearId, string $quarter): string
    {
        return "grade_submission_deadline_{$academicYearId}_q{$quarter}";
    }

    private function reminderSentSettingKey(
        int $academicYearId,
        string $quarter,
        string $phase,
        Carbon $deadline
    ): string {
        $hash = md5($deadline->toDateTimeString());

        return "grade_deadline_reminder_sent_{$academicYearId}_q{$quarter}_{$phase}_{$hash}";
    }

    private function resolveReminderPhase(
        Carbon $referenceDate,
        Carbon $deadline
    ): ?string {
        $reference = $referenceDate->copy()->startOfDay();
        $deadlineDay = $deadline->copy()->startOfDay();

        if ($reference->isSameDay($deadlineDay)) {
            return 'today';
        }

        if ($reference->isSameDay($deadlineDay->copy()->subDay())) {
            return 'tomorrow';
        }

        return null;
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
}
