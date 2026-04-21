<?php

namespace App\Services\Finance;

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\BillingSchedule;
use App\Models\FinanceDueReminderDispatch;
use App\Models\FinanceDueReminderRule;
use App\Models\ScheduledNotificationJob;
use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class DueReminderNotificationService
{
    /**
     * @return array{
     *     processed_rules: int,
     *     matched_schedules: int,
     *     sent_announcements: int,
     *     skipped_without_parents: int,
     *     skipped_zero_outstanding: int,
     *     skipped_duplicate_dispatches: int,
     *     skipped_due_to_run_limit: int
     * }
     */
    public function sendForDate(
        CarbonInterface $referenceDate,
        User $actor,
        ?int $maxAnnouncementsPerRun = null
    ): array {
        $runDate = $referenceDate->copy()->startOfDay();

        $rules = FinanceDueReminderRule::query()
            ->where('is_active', true)
            ->orderBy('days_before_due')
            ->get();

        $summary = [
            'processed_rules' => (int) $rules->count(),
            'matched_schedules' => 0,
            'sent_announcements' => 0,
            'skipped_without_parents' => 0,
            'skipped_zero_outstanding' => 0,
            'skipped_duplicate_dispatches' => 0,
            'skipped_due_to_run_limit' => 0,
        ];

        foreach ($rules as $rule) {
            $targetDueDate = $runDate
                ->copy()
                ->addDays((int) $rule->days_before_due)
                ->toDateString();

            $schedules = BillingSchedule::query()
                ->with([
                    'student:id,first_name,last_name,lrn',
                    'student.parents:id,role,is_active',
                ])
                ->whereDate('due_date', $targetDueDate)
                ->whereIn('status', ['unpaid', 'partially_paid'])
                ->orderBy('id')
                ->get();

            $summary['matched_schedules'] += (int) $schedules->count();

            foreach ($schedules as $schedule) {
                if (
                    $maxAnnouncementsPerRun !== null
                    && $summary['sent_announcements'] >= $maxAnnouncementsPerRun
                ) {
                    $summary['skipped_due_to_run_limit']++;

                    continue;
                }

                $outstandingAmount = max(
                    (float) $schedule->amount_due - (float) $schedule->amount_paid,
                    0
                );

                if ($outstandingAmount <= 0) {
                    $summary['skipped_zero_outstanding']++;

                    continue;
                }

                $parentIds = $schedule->student?->parents
                    ?->filter(function (User $user): bool {
                        $role = is_string($user->role) ? $user->role : $user->role?->value;

                        return $role === UserRole::PARENT->value
                            && (bool) $user->is_active;
                    })
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                if ($parentIds === []) {
                    $summary['skipped_without_parents']++;

                    continue;
                }

                try {
                    DB::transaction(function () use (
                        $actor,
                        $outstandingAmount,
                        $parentIds,
                        $rule,
                        $runDate,
                        $schedule
                    ): void {
                        $announcement = Announcement::query()->create([
                            'user_id' => $actor->id,
                            'title' => $this->buildTitle((int) $rule->days_before_due),
                            'content' => $this->buildContent($schedule, $outstandingAmount),
                            'target_roles' => [UserRole::PARENT->value],
                            'target_user_ids' => $parentIds,
                            'expires_at' => $schedule->due_date?->copy()->addDays(7),
                            'is_active' => true,
                        ]);

                        FinanceDueReminderDispatch::query()->create([
                            'finance_due_reminder_rule_id' => $rule->id,
                            'billing_schedule_id' => $schedule->id,
                            'reminder_date' => $runDate->toDateString(),
                            'announcement_id' => $announcement->id,
                            'sent_at' => now(),
                        ]);
                    });

                    $summary['sent_announcements']++;
                } catch (QueryException $queryException) {
                    if ($this->isDuplicateDispatch($queryException)) {
                        $summary['skipped_duplicate_dispatches']++;

                        continue;
                    }

                    throw $queryException;
                }
            }
        }

        return $summary;
    }

    public function sendScheduledJob(ScheduledNotificationJob $scheduledJob): void
    {
        if ($scheduledJob->type !== ScheduledNotificationJobType::FinanceDueReminder) {
            $this->markScheduledJobSkipped($scheduledJob, 'type_mismatch');

            return;
        }

        if (! in_array($scheduledJob->status, [
            ScheduledNotificationJobStatus::Pending,
            ScheduledNotificationJobStatus::Processing,
        ], true)) {
            return;
        }

        if (! Setting::enabled('finance_due_reminder_auto_send_enabled', true)) {
            $this->markScheduledJobSkipped($scheduledJob, 'automation_disabled');

            return;
        }

        $schedule = BillingSchedule::query()
            ->with([
                'student:id,first_name,last_name,lrn',
                'student.parents:id,role,is_active',
            ])
            ->find($scheduledJob->subject_id);

        if (! $schedule) {
            $this->markScheduledJobSkipped($scheduledJob, 'subject_missing');

            return;
        }

        $ruleId = (int) ($scheduledJob->payload['rule_id'] ?? 0);
        $rule = FinanceDueReminderRule::query()
            ->whereKey($ruleId)
            ->where('is_active', true)
            ->first();

        if (! $rule) {
            $this->markScheduledJobSkipped($scheduledJob, 'rule_inactive');

            return;
        }

        if (! in_array($schedule->status, ['unpaid', 'partially_paid'], true)) {
            $this->markScheduledJobSkipped($scheduledJob, 'billing_schedule_paid');

            return;
        }

        $outstandingAmount = max(
            (float) $schedule->amount_due - (float) $schedule->amount_paid,
            0
        );

        if ($outstandingAmount <= 0) {
            $this->markScheduledJobSkipped($scheduledJob, 'zero_outstanding');

            return;
        }

        $parentIds = $schedule->student?->parents
            ?->filter(function (User $user): bool {
                $role = is_string($user->role) ? $user->role : $user->role?->value;

                return $role === UserRole::PARENT->value
                    && (bool) $user->is_active;
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($parentIds === []) {
            $this->markScheduledJobSkipped($scheduledJob, 'no_parent_recipients');

            return;
        }

        $actor = $this->resolveActor();

        if (! $actor) {
            $this->markScheduledJobSkipped($scheduledJob, 'no_actor');

            return;
        }

        try {
            DB::transaction(function () use (
                $actor,
                $outstandingAmount,
                $parentIds,
                $rule,
                $schedule,
                $scheduledJob
            ): void {
                $announcement = Announcement::query()->create([
                    'user_id' => $actor->id,
                    'title' => $this->buildTitle((int) $rule->days_before_due),
                    'content' => $this->buildContent($schedule, $outstandingAmount),
                    'target_roles' => [UserRole::PARENT->value],
                    'target_user_ids' => $parentIds,
                    'expires_at' => $schedule->due_date?->copy()->addDays(7),
                    'is_active' => true,
                ]);

                FinanceDueReminderDispatch::query()->create([
                    'finance_due_reminder_rule_id' => $rule->id,
                    'billing_schedule_id' => $schedule->id,
                    'reminder_date' => $scheduledJob->run_at?->toDateString(),
                    'announcement_id' => $announcement->id,
                    'sent_at' => now(),
                ]);

                $scheduledJob->forceFill([
                    'status' => ScheduledNotificationJobStatus::Dispatched,
                    'dispatched_at' => now(),
                ])->save();
            });
        } catch (QueryException $queryException) {
            if ($this->isDuplicateDispatch($queryException)) {
                $this->markScheduledJobSkipped($scheduledJob, 'duplicate_dispatch');

                return;
            }

            throw $queryException;
        }
    }

    private function resolveActor(): ?User
    {
        return User::query()
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
    }

    private function markScheduledJobSkipped(ScheduledNotificationJob $scheduledJob, string $reason): void
    {
        $scheduledJob->forceFill([
            'status' => ScheduledNotificationJobStatus::Skipped,
            'skip_reason' => $reason,
        ])->save();
    }

    private function buildTitle(int $daysBeforeDue): string
    {
        if ($daysBeforeDue === 0) {
            return 'Payment Due Reminder (Today)';
        }

        if ($daysBeforeDue === 1) {
            return 'Payment Due Reminder (1 Day Left)';
        }

        return "Payment Due Reminder ({$daysBeforeDue} Days Left)";
    }

    private function buildContent(BillingSchedule $schedule, float $outstandingAmount): string
    {
        $studentName = trim(
            "{$schedule->student?->first_name} {$schedule->student?->last_name}"
        );
        $studentLabel = $studentName !== '' ? $studentName : 'Your child';
        $dueDateLabel = $schedule->due_date?->format('m/d/Y') ?? 'N/A';
        $amountLabel = number_format($outstandingAmount, 2);
        $description = $schedule->description;

        return "{$description} for {$studentLabel} is due on {$dueDateLabel}. Outstanding amount: PHP {$amountLabel}.";
    }

    private function isDuplicateDispatch(QueryException $queryException): bool
    {
        $sqlState = (string) ($queryException->errorInfo[0] ?? '');

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
