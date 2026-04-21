<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\GradeSubmission;
use App\Models\Setting;
use App\Models\SubjectAssignment;
use App\Models\User;
use Carbon\CarbonInterface;
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
        CarbonInterface $referenceDate,
        User $actor
    ): int {
        $postedCount = 0;
        $configuredReminderDays = $this->resolveReminderDays();

        foreach (['1', '2', '3', '4'] as $quarter) {
            $deadlineValue = Setting::get(
                $this->deadlineSettingKey((int) $academicYear->id, $quarter)
            );

            if (! is_string($deadlineValue) || $deadlineValue === '') {
                continue;
            }

            $deadline = Carbon::parse($deadlineValue);
            $daysBefore = $this->resolveReminderDaysBefore(
                $referenceDate,
                $deadline,
                $configuredReminderDays
            );

            if ($daysBefore === null) {
                continue;
            }

            $pendingTeacherIds = $this->resolvePendingTeacherIds($academicYear, $quarter);

            if ($pendingTeacherIds->isEmpty()) {
                continue;
            }

            $sentKey = $this->reminderSentSettingKey(
                (int) $academicYear->id,
                $quarter,
                "d{$daysBefore}",
                $deadline
            );

            if (Setting::get($sentKey) === '1') {
                continue;
            }

            $quarterLabel = $this->resolveQuarterLabel($quarter);
            $deadlineText = $deadline->format('m/d/Y h:i A');

            $title = "Reminder: {$daysBefore} Day".($daysBefore > 1 ? 's' : '')." Before Grade Deadline ({$quarterLabel})";

            $content = "This is a reminder that the {$quarterLabel} grade submission deadline for SY {$academicYear->name} is in {$daysBefore} day".($daysBefore > 1 ? 's' : '')." ({$deadlineText}). Please submit any pending class grades.";

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

    private function resolveReminderDaysBefore(
        CarbonInterface $referenceDate,
        CarbonInterface $deadline,
        Collection $configuredReminderDays
    ): ?int {
        $reference = $referenceDate->copy()->startOfDay();
        $deadlineDay = $deadline->copy()->startOfDay();

        return $configuredReminderDays->first(function (int $daysBefore) use ($reference, $deadlineDay): bool {
            return $reference->isSameDay($deadlineDay->copy()->subDays($daysBefore));
        });
    }

    /**
     * @return Collection<int, int>
     */
    private function resolveReminderDays(): Collection
    {
        $configuredValue = Setting::get('grade_deadline_reminder_days');
        $decoded = is_string($configuredValue)
            ? json_decode($configuredValue, true)
            : null;

        $resolved = collect(is_array($decoded) ? $decoded : [3, 2, 1])
            ->filter(fn (mixed $value): bool => is_numeric($value))
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $value): bool => $value >= 1 && $value <= 14)
            ->unique()
            ->sortDesc()
            ->values();

        return $resolved->isEmpty()
            ? collect([3, 2, 1])
            : $resolved;
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
