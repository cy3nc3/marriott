<?php

namespace App\Services\Scheduling;

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Models\BillingSchedule;
use App\Models\FinanceDueReminderRule;
use App\Models\ScheduledNotificationJob;
use App\Models\Setting;
use Illuminate\Support\Collection;

class FinanceDueReminderPlanner
{
    public function __construct(
        private readonly ScheduledNotificationPlanner $planner
    ) {}

    public function reconcileAllRules(): void
    {
        FinanceDueReminderRule::query()
            ->orderBy('id')
            ->each(fn (FinanceDueReminderRule $rule): null => $this->reconcileRule($rule));
    }

    public function reconcileRule(FinanceDueReminderRule $rule): void
    {
        if (! $rule->exists) {
            return;
        }

        if (! $rule->is_active) {
            $this->cancelRule($rule, 'rule_inactive');

            return;
        }

        if (! Setting::enabled('finance_due_reminder_auto_send_enabled', true)) {
            $this->cancelRule($rule, 'automation_disabled');

            return;
        }

        $this->planner->reconcile(
            ScheduledNotificationJobType::FinanceDueReminder,
            $this->groupKey($rule),
            $this->desiredJobsForRule($rule)->all()
        );
    }

    public function reconcileSchedule(BillingSchedule $schedule): void
    {
        if (! $this->scheduleIsReminderEligible($schedule)) {
            $this->cancelSchedule($schedule, 'billing_schedule_paid');

            return;
        }

        $this->reconcileAllRules();
    }

    public function cancelRule(FinanceDueReminderRule $rule, string $reason = 'rule_canceled'): void
    {
        ScheduledNotificationJob::query()
            ->where('type', ScheduledNotificationJobType::FinanceDueReminder)
            ->where('group_key', $this->groupKey($rule))
            ->where('status', ScheduledNotificationJobStatus::Pending)
            ->update([
                'status' => ScheduledNotificationJobStatus::Canceled,
                'canceled_at' => now(),
                'skip_reason' => $reason,
                'updated_at' => now(),
            ]);
    }

    public function cancelSchedule(BillingSchedule $schedule, string $reason = 'billing_schedule_ineligible'): void
    {
        ScheduledNotificationJob::query()
            ->where('type', ScheduledNotificationJobType::FinanceDueReminder)
            ->where('subject_type', BillingSchedule::class)
            ->where('subject_id', $schedule->id)
            ->where('status', ScheduledNotificationJobStatus::Pending)
            ->update([
                'status' => ScheduledNotificationJobStatus::Canceled,
                'canceled_at' => now(),
                'skip_reason' => $reason,
                'updated_at' => now(),
            ]);
    }

    /**
     * @return Collection<int, array{
     *     dedupe_key: string,
     *     run_at: \Carbon\CarbonInterface,
     *     subject_type: class-string,
     *     subject_id: int,
     *     payload: array<string, mixed>
     * }>
     */
    private function desiredJobsForRule(FinanceDueReminderRule $rule): Collection
    {
        $sendTime = $this->resolveSendTime();

        return BillingSchedule::query()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->whereColumn('amount_paid', '<', 'amount_due')
            ->orderBy('id')
            ->get()
            ->map(function (BillingSchedule $schedule) use ($rule, $sendTime): array {
                $runAt = $schedule->due_date
                    ->copy()
                    ->subDays((int) $rule->days_before_due)
                    ->setTimeFromTimeString($sendTime);

                return [
                    'dedupe_key' => $this->dedupeKey($rule, $schedule, $runAt),
                    'run_at' => $runAt,
                    'subject_type' => BillingSchedule::class,
                    'subject_id' => (int) $schedule->id,
                    'payload' => [
                        'rule_id' => (int) $rule->id,
                        'billing_schedule_id' => (int) $schedule->id,
                        'days_before_due' => (int) $rule->days_before_due,
                    ],
                ];
            })
            ->filter(fn (array $job): bool => $job['run_at']->greaterThanOrEqualTo(now()))
            ->values();
    }

    private function scheduleIsReminderEligible(BillingSchedule $schedule): bool
    {
        return in_array($schedule->status, ['unpaid', 'partially_paid'], true)
            && (float) $schedule->amount_paid < (float) $schedule->amount_due;
    }

    private function resolveSendTime(): string
    {
        $configuredValue = (string) Setting::get('finance_due_reminder_send_time', '07:30');

        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $configuredValue) !== 1) {
            return '07:30';
        }

        return $configuredValue;
    }

    private function groupKey(FinanceDueReminderRule $rule): string
    {
        return "finance:rule-{$rule->id}";
    }

    private function dedupeKey(FinanceDueReminderRule $rule, BillingSchedule $schedule, mixed $runAt): string
    {
        return "finance:rule-{$rule->id}:schedule-{$schedule->id}:{$runAt->format('YmdHi')}";
    }
}
