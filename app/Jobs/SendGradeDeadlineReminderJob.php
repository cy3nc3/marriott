<?php

namespace App\Jobs;

use App\Models\ScheduledNotificationJob;
use App\Services\GradeDeadlineAnnouncementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendGradeDeadlineReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $scheduledNotificationJobId
    ) {}

    public function handle(GradeDeadlineAnnouncementService $service): void
    {
        $scheduledJob = ScheduledNotificationJob::query()
            ->findOrFail($this->scheduledNotificationJobId);

        $service->sendScheduledJob($scheduledJob);
    }
}
