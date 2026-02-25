<?php

namespace App\Console\Commands;

use App\Services\AnnouncementEventReminderService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendAnnouncementEventRemindersCommand extends Command
{
    protected $signature = 'announcements:send-event-reminders {--date=}';

    protected $description = 'Send event reminders to unresolved recipients one day before and on the event deadline day.';

    public function handle(AnnouncementEventReminderService $announcementEventReminderService): int
    {
        $referenceDate = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : now()->startOfDay();

        $summary = $announcementEventReminderService->sendForDate($referenceDate);

        $this->info("Processed events: {$summary['processed_events']}");
        $this->info("Matched reminder windows: {$summary['matched_events']}");
        $this->info("Unresolved recipients: {$summary['unresolved_recipients']}");
        $this->info("Dispatched reminders: {$summary['dispatched']}");
        $this->info("Skipped duplicate dispatches: {$summary['skipped_duplicates']}");

        return self::SUCCESS;
    }
}
