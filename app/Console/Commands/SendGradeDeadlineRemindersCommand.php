<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Setting;
use App\Models\User;
use App\Services\GradeDeadlineAnnouncementService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendGradeDeadlineRemindersCommand extends Command
{
    protected $signature = 'grading:send-deadline-reminders {--date=} {--force}';

    protected $description = 'Send teacher reminder announcements for grade submission deadlines due in 3, 2, or 1 day.';

    public function handle(GradeDeadlineAnnouncementService $announcementService): int
    {
        if (! $this->shouldRunNow()) {
            return self::SUCCESS;
        }

        $referenceDate = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->startOfDay()
            : now()->startOfDay();

        $academicYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first();

        if (! $academicYear) {
            $this->info('No ongoing school year found.');

            return self::SUCCESS;
        }

        $actor = User::query()
            ->where('role', UserRole::SUPER_ADMIN->value)
            ->first()
            ?? User::query()
                ->where('role', UserRole::ADMIN->value)
                ->first();

        if (! $actor) {
            $this->warn('No super admin or admin account available for posting announcements.');

            return self::SUCCESS;
        }

        $postedCount = $announcementService->publishDueReminders(
            $academicYear,
            $referenceDate,
            $actor
        );

        $this->info("Posted {$postedCount} deadline reminder announcement(s).");

        return self::SUCCESS;
    }

    private function shouldRunNow(): bool
    {
        if ($this->option('force') || $this->option('date')) {
            return true;
        }

        if (! Setting::enabled('grade_deadline_reminder_auto_send_enabled', true)) {
            $this->info('Grade reminder automation is currently disabled.');

            return false;
        }

        $configuredSendTime = $this->resolveSendTime();
        $currentTime = now()->format('H:i');

        if ($currentTime !== $configuredSendTime) {
            $this->info(
                "Skipping grade reminders at {$currentTime}; configured send time is {$configuredSendTime}."
            );

            return false;
        }

        return true;
    }

    private function resolveSendTime(): string
    {
        $configuredValue = (string) Setting::get('grade_deadline_reminder_send_time', '07:00');

        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $configuredValue) !== 1) {
            return '07:00';
        }

        return $configuredValue;
    }
}
