<?php

namespace App\Jobs;

use App\Models\ScheduledNotificationJob;
use App\Services\Finance\DueReminderNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendFinanceDueReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $scheduledNotificationJobId
    ) {}

    public function handle(DueReminderNotificationService $service): void
    {
        $scheduledJob = ScheduledNotificationJob::query()
            ->findOrFail($this->scheduledNotificationJobId);

        $service->sendScheduledJob($scheduledJob);
    }
}
