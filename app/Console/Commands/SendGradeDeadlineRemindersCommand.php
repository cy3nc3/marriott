<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\User;
use App\Services\GradeDeadlineAnnouncementService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendGradeDeadlineRemindersCommand extends Command
{
    protected $signature = 'grading:send-deadline-reminders {--date=}';

    protected $description = 'Send teacher reminder announcements for grade submission deadlines that are due tomorrow or today.';

    public function handle(GradeDeadlineAnnouncementService $announcementService): int
    {
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
}
