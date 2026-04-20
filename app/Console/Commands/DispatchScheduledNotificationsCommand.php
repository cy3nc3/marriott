<?php

namespace App\Console\Commands;

use App\Services\Scheduling\ScheduledNotificationDispatcher;
use Illuminate\Console\Command;

class DispatchScheduledNotificationsCommand extends Command
{
    protected $signature = 'notifications:dispatch-scheduled';

    protected $description = 'Dispatch due scheduled notification jobs.';

    public function handle(ScheduledNotificationDispatcher $dispatcher): int
    {
        $processedCount = $dispatcher->dispatchDue();

        $this->info("Processed {$processedCount} scheduled notification job(s).");

        return self::SUCCESS;
    }
}
