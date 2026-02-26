<?php

namespace App\Services\Finance;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\BillingSchedule;
use App\Models\FinanceDueReminderDispatch;
use App\Models\FinanceDueReminderRule;
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
