<?php

namespace App\Jobs;

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Models\ScheduledNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class DispatchScheduledNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $scheduledNotificationJobId
    ) {}

    public function handle(): void
    {
        $scheduledJob = ScheduledNotificationJob::query()
            ->findOrFail($this->scheduledNotificationJobId);

        if ($scheduledJob->status !== ScheduledNotificationJobStatus::Processing) {
            return;
        }

        try {
            match ($scheduledJob->type) {
                ScheduledNotificationJobType::FinanceDueReminder => dispatch_sync(new SendFinanceDueReminderJob($scheduledJob->id)),
                ScheduledNotificationJobType::GradeDeadlineReminder => dispatch_sync(new SendGradeDeadlineReminderJob($scheduledJob->id)),
                ScheduledNotificationJobType::AnnouncementEventReminder => dispatch_sync(new SendAnnouncementEventReminderJob($scheduledJob->id)),
            };
        } catch (Throwable $throwable) {
            $scheduledJob->forceFill([
                'status' => ScheduledNotificationJobStatus::Failed,
                'failure_reason' => $throwable::class,
            ])->save();

            throw $throwable;
        }

        $scheduledJob->refresh();

        if ($scheduledJob->status === ScheduledNotificationJobStatus::Processing) {
            $scheduledJob->forceFill([
                'status' => ScheduledNotificationJobStatus::Dispatched,
                'dispatched_at' => now(),
            ])->save();
        }
    }
}
