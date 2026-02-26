<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use App\Services\Finance\DueReminderNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendFinanceDueRemindersCommand extends Command
{
    protected $signature = 'finance:send-due-reminders {--date=} {--force}';

    protected $description = 'Send scheduled due-date reminder notifications for parent accounts.';

    public function handle(DueReminderNotificationService $dueReminderNotificationService): int
    {
        if (! $this->shouldRunNow()) {
            return self::SUCCESS;
        }

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
            $actor,
            $this->resolveMaxAnnouncementsPerRun()
        );

        $this->info("Processed rules: {$summary['processed_rules']}");
        $this->info("Matched schedules: {$summary['matched_schedules']}");
        $this->info("Sent announcements: {$summary['sent_announcements']}");
        $this->info("Skipped no-parent schedules: {$summary['skipped_without_parents']}");
        $this->info("Skipped zero-outstanding schedules: {$summary['skipped_zero_outstanding']}");
        $this->info("Skipped duplicate dispatches: {$summary['skipped_duplicate_dispatches']}");
        $this->info("Skipped due to run limit: {$summary['skipped_due_to_run_limit']}");

        return self::SUCCESS;
    }

    private function shouldRunNow(): bool
    {
        if ($this->option('force') || $this->option('date')) {
            return true;
        }

        if (! Setting::enabled('finance_due_reminder_auto_send_enabled', true)) {
            $this->info('Auto due reminders are currently disabled.');

            return false;
        }

        $configuredSendTime = $this->resolveSendTime();
        $currentTime = now()->format('H:i');

        if ($currentTime !== $configuredSendTime) {
            $this->info(
                "Skipping due reminders at {$currentTime}; configured send time is {$configuredSendTime}."
            );

            return false;
        }

        return true;
    }

    private function resolveSendTime(): string
    {
        $configuredValue = (string) Setting::get('finance_due_reminder_send_time', '07:30');

        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $configuredValue) !== 1) {
            return '07:30';
        }

        return $configuredValue;
    }

    private function resolveMaxAnnouncementsPerRun(): ?int
    {
        $value = Setting::get('finance_due_reminder_max_announcements_per_run');

        if ($value === null || $value === '') {
            return null;
        }

        return max((int) $value, 1);
    }
}
