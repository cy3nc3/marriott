<?php

namespace App\Jobs;

use App\Models\ScheduledNotificationJob;
use App\Services\AnnouncementEventReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAnnouncementEventReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $scheduledNotificationJobId
    ) {}

    public function handle(AnnouncementEventReminderService $service): void
    {
        $scheduledJob = ScheduledNotificationJob::query()
            ->findOrFail($this->scheduledNotificationJobId);

        $service->sendScheduledJob($scheduledJob);
    }
}
