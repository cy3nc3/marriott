<?php

namespace App\Services\Scheduling;

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Models\AcademicYear;
use App\Models\ScheduledNotificationJob;
use App\Models\Setting;
use App\Services\GradeDeadlineAnnouncementService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GradeDeadlineReminderPlanner
{
    public function __construct(
        private readonly ScheduledNotificationPlanner $planner,
        private readonly GradeDeadlineAnnouncementService $announcementService
    ) {}

    public function reconcileActiveAcademicYear(): void
    {
        $academicYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first();

        if (! $academicYear) {
            return;
        }

        foreach (['1', '2', '3', '4'] as $quarter) {
            $this->reconcileAcademicYearQuarter($academicYear, $quarter);
        }
    }

    public function reconcileAcademicYearQuarter(AcademicYear $academicYear, string $quarter): void
    {
        if (! Setting::enabled('grade_deadline_reminder_auto_send_enabled', true)) {
            $this->cancelGroup($academicYear, $quarter, 'automation_disabled');

            return;
        }

        $deadlineValue = Setting::get($this->deadlineSettingKey((int) $academicYear->id, $quarter));

        if (! is_string($deadlineValue) || $deadlineValue === '') {
            $this->cancelGroup($academicYear, $quarter, 'deadline_missing');

            return;
        }

        if ($this->announcementService->resolvePendingTeacherIds($academicYear, $quarter)->isEmpty()) {
            $this->cancelGroup($academicYear, $quarter, 'all_grades_submitted');

            return;
        }

        $this->planner->reconcile(
            ScheduledNotificationJobType::GradeDeadlineReminder,
            $this->groupKey($academicYear, $quarter),
            $this->desiredJobs($academicYear, $quarter, Carbon::parse($deadlineValue))->all()
        );
    }

    public function cancelGroup(AcademicYear $academicYear, string $quarter, string $reason): void
    {
        ScheduledNotificationJob::query()
            ->where('type', ScheduledNotificationJobType::GradeDeadlineReminder)
            ->where('group_key', $this->groupKey($academicYear, $quarter))
            ->where('status', ScheduledNotificationJobStatus::Pending)
            ->update([
                'status' => ScheduledNotificationJobStatus::Canceled,
                'canceled_at' => now(),
                'skip_reason' => $reason,
                'updated_at' => now(),
            ]);
    }

    /**
     * @return Collection<int, array{
     *     dedupe_key: string,
     *     run_at: \Carbon\CarbonInterface,
     *     subject_type: class-string,
     *     subject_id: int,
     *     payload: array<string, mixed>
     * }>
     */
    private function desiredJobs(AcademicYear $academicYear, string $quarter, Carbon $deadline): Collection
    {
        $sendTime = $this->resolveSendTime();

        return collect([
            'tomorrow' => $deadline->copy()->subDay()->setTimeFromTimeString($sendTime),
            'today' => $deadline->copy()->setTimeFromTimeString($sendTime),
        ])
            ->map(function (Carbon $runAt, string $phase) use ($academicYear, $deadline, $quarter): array {
                return [
                    'dedupe_key' => $this->dedupeKey($academicYear, $quarter, $phase, $deadline, $runAt),
                    'run_at' => $runAt,
                    'subject_type' => AcademicYear::class,
                    'subject_id' => (int) $academicYear->id,
                    'payload' => [
                        'academic_year_id' => (int) $academicYear->id,
                        'quarter' => $quarter,
                        'phase' => $phase,
                        'deadline' => $deadline->toDateTimeString(),
                    ],
                ];
            })
            ->filter(fn (array $job): bool => $job['run_at']->greaterThanOrEqualTo(now()))
            ->values();
    }

    private function resolveSendTime(): string
    {
        $configuredValue = (string) Setting::get('grade_deadline_reminder_send_time', '07:00');

        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $configuredValue) !== 1) {
            return '07:00';
        }

        return $configuredValue;
    }

    private function deadlineSettingKey(int $academicYearId, string $quarter): string
    {
        return "grade_submission_deadline_{$academicYearId}_q{$quarter}";
    }

    private function groupKey(AcademicYear $academicYear, string $quarter): string
    {
        return "grading:ay-{$academicYear->id}:q{$quarter}";
    }

    private function dedupeKey(
        AcademicYear $academicYear,
        string $quarter,
        string $phase,
        Carbon $deadline,
        Carbon $runAt
    ): string {
        return "grading:ay-{$academicYear->id}:q{$quarter}:{$phase}:{$deadline->format('YmdHi')}:{$runAt->format('YmdHi')}";
    }
}
