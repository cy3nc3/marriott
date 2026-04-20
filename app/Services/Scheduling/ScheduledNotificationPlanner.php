<?php

namespace App\Services\Scheduling;

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Models\ScheduledNotificationJob;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ScheduledNotificationPlanner
{
    /**
     * @param  array<int, array{
     *     dedupe_key: string,
     *     run_at: CarbonInterface,
     *     subject_type: class-string,
     *     subject_id: int,
     *     recipient_type?: class-string|null,
     *     recipient_id?: int|null,
     *     payload?: array<string, mixed>|null,
     *     planned_by_type?: class-string|null,
     *     planned_by_id?: int|null
     * }>  $desiredJobs
     */
    public function reconcile(ScheduledNotificationJobType $type, string $groupKey, array $desiredJobs): void
    {
        $lockKey = sprintf('scheduled-notification-planner:%s:%s', $type->value, $groupKey);

        Cache::lock($lockKey, 10)->block(5, function () use ($desiredJobs, $type, $groupKey): void {
            DB::transaction(function () use ($desiredJobs, $type, $groupKey): void {
                $desiredJobsByKey = collect($desiredJobs)->keyBy('dedupe_key');
                $timestamp = now();

                ScheduledNotificationJob::query()
                    ->where('type', $type)
                    ->where('group_key', $groupKey)
                    ->where('status', ScheduledNotificationJobStatus::Pending)
                    ->when(
                        $desiredJobsByKey->isNotEmpty(),
                        fn ($query) => $query->whereNotIn('dedupe_key', $desiredJobsByKey->keys()->all())
                    )
                    ->when(
                        $desiredJobsByKey->isEmpty(),
                        fn ($query) => $query
                    )
                    ->update([
                        'status' => ScheduledNotificationJobStatus::Superseded->value,
                        'canceled_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);

                $desiredRows = $desiredJobsByKey
                    ->map(fn (array $jobAttributes): array => [
                        'type' => $type->value,
                        'status' => ScheduledNotificationJobStatus::Pending->value,
                        'group_key' => $groupKey,
                        'dedupe_key' => $jobAttributes['dedupe_key'],
                        'run_at' => $this->normalizeRunAt($jobAttributes['run_at']),
                        'subject_type' => $jobAttributes['subject_type'],
                        'subject_id' => $jobAttributes['subject_id'],
                        'recipient_type' => $jobAttributes['recipient_type'] ?? null,
                        'recipient_id' => $jobAttributes['recipient_id'] ?? null,
                        'payload' => $this->normalizePayload($jobAttributes['payload'] ?? null),
                        'planned_by_type' => $jobAttributes['planned_by_type'] ?? null,
                        'planned_by_id' => $jobAttributes['planned_by_id'] ?? null,
                        'dispatched_at' => null,
                        'canceled_at' => null,
                        'skip_reason' => null,
                        'failure_reason' => null,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ])
                    ->values()
                    ->all();

                if ($desiredRows !== []) {
                    ScheduledNotificationJob::query()->upsert(
                        $desiredRows,
                        ['dedupe_key'],
                        [
                            'type',
                            'status',
                            'group_key',
                            'run_at',
                            'subject_type',
                            'subject_id',
                            'recipient_type',
                            'recipient_id',
                            'payload',
                            'planned_by_type',
                            'planned_by_id',
                            'dispatched_at',
                            'canceled_at',
                            'skip_reason',
                            'failure_reason',
                            'updated_at',
                        ]
                    );
                }
            });
        });
    }

    private function normalizePayload(?array $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    private function normalizeRunAt(CarbonInterface $runAt): string
    {
        return Carbon::instance($runAt)->toDateTimeString();
    }
}
