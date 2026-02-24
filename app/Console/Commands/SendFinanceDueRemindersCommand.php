<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Finance\DueReminderNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendFinanceDueRemindersCommand extends Command
{
    protected $signature = 'finance:send-due-reminders {--date=}';

    protected $description = 'Send scheduled due-date reminder notifications for parent accounts.';

    public function handle(DueReminderNotificationService $dueReminderNotificationService): int
    {
        $referenceDate = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : now()->startOfDay();

        $actor = User::query()
            ->where('role', UserRole::FINANCE->value)
            ->where('is_active', true)
            ->first()
            ?? User::query()
                ->where('role', UserRole::SUPER_ADMIN->value)
                ->where('is_active', true)
                ->first()
            ?? User::query()
                ->where('role', UserRole::ADMIN->value)
                ->where('is_active', true)
                ->first();

        if (! $actor) {
            $this->warn('No active finance/admin actor account is available for posting reminders.');

            return self::SUCCESS;
        }

        $summary = $dueReminderNotificationService->sendForDate(
            $referenceDate,
            $actor
        );

        $this->info("Processed rules: {$summary['processed_rules']}");
        $this->info("Matched schedules: {$summary['matched_schedules']}");
        $this->info("Sent announcements: {$summary['sent_announcements']}");
        $this->info("Skipped no-parent schedules: {$summary['skipped_without_parents']}");
        $this->info("Skipped zero-outstanding schedules: {$summary['skipped_zero_outstanding']}");
        $this->info("Skipped duplicate dispatches: {$summary['skipped_duplicate_dispatches']}");

        return self::SUCCESS;
    }
}
