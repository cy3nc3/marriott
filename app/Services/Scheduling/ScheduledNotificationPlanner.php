<?php

namespace App\Services\Scheduling;

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Models\ScheduledNotificationJob;
use Carbon\CarbonInterface;
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

                $existingDesiredJobs = $desiredJobsByKey->isEmpty()
                    ? collect()
                    : ScheduledNotificationJob::query()
                        ->whereIn('dedupe_key', $desiredJobsByKey->keys()->all())
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('dedupe_key');

                foreach ($desiredJobsByKey as $dedupeKey => $jobAttributes) {
                    $existingJob = $existingDesiredJobs->get($dedupeKey);

                    if ($existingJob && $this->isFinalStatus($existingJob->status)) {
                        continue;
                    }

                    $attributes = $this->attributesFor(
                        type: $type,
                        groupKey: $groupKey,
                        jobAttributes: $jobAttributes,
                    );

                    if ($existingJob) {
                        $existingJob->forceFill($attributes)->save();

                        continue;
                    }

                    ScheduledNotificationJob::query()->create([
                        ...$attributes,
                        'dedupe_key' => $dedupeKey,
                    ]);
                }
            });
        });
    }

    /**
     * @param  array{
     *     dedupe_key: string,
     *     run_at: CarbonInterface,
     *     subject_type: class-string,
     *     subject_id: int,
     *     recipient_type?: class-string|null,
     *     recipient_id?: int|null,
     *     payload?: array<string, mixed>|null,
     *     planned_by_type?: class-string|null,
     *     planned_by_id?: int|null
     * }  $jobAttributes
     * @return array<string, mixed>
     */
    private function attributesFor(
        ScheduledNotificationJobType $type,
        string $groupKey,
        array $jobAttributes
    ): array {
        return [
            'type' => $type,
            'status' => ScheduledNotificationJobStatus::Pending,
            'group_key' => $groupKey,
            'run_at' => $jobAttributes['run_at'],
            'subject_type' => $jobAttributes['subject_type'],
            'subject_id' => $jobAttributes['subject_id'],
            'recipient_type' => $jobAttributes['recipient_type'] ?? null,
            'recipient_id' => $jobAttributes['recipient_id'] ?? null,
            'payload' => $jobAttributes['payload'] ?? null,
            'planned_by_type' => $jobAttributes['planned_by_type'] ?? null,
            'planned_by_id' => $jobAttributes['planned_by_id'] ?? null,
            'dispatched_at' => null,
            'canceled_at' => null,
            'skip_reason' => null,
            'failure_reason' => null,
        ];
    }

    private function isFinalStatus(ScheduledNotificationJobStatus $status): bool
    {
        return in_array($status, [
            ScheduledNotificationJobStatus::Dispatched,
            ScheduledNotificationJobStatus::Failed,
        ], true);
    }
}
