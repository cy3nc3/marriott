<?php

namespace App\Services\Scheduling;

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use App\Models\ScheduledNotificationJob;
use Illuminate\Support\CarbonInterface;

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
        $desiredJobsByKey = collect($desiredJobs)->keyBy('dedupe_key');

        $existingPendingJobs = ScheduledNotificationJob::query()
            ->where('type', $type)
            ->where('group_key', $groupKey)
            ->where('status', ScheduledNotificationJobStatus::Pending)
            ->get()
            ->keyBy('dedupe_key');

        foreach ($existingPendingJobs as $dedupeKey => $job) {
            if ($desiredJobsByKey->has($dedupeKey)) {
                continue;
            }

            $job->forceFill([
                'status' => ScheduledNotificationJobStatus::Superseded,
                'canceled_at' => now(),
            ])->save();
        }

        foreach ($desiredJobsByKey as $dedupeKey => $jobAttributes) {
            if ($existingPendingJobs->has($dedupeKey)) {
                continue;
            }

            ScheduledNotificationJob::query()->create([
                'type' => $type,
                'status' => ScheduledNotificationJobStatus::Pending,
                'group_key' => $groupKey,
                'dedupe_key' => $jobAttributes['dedupe_key'],
                'run_at' => $jobAttributes['run_at'],
                'subject_type' => $jobAttributes['subject_type'],
                'subject_id' => $jobAttributes['subject_id'],
                'recipient_type' => $jobAttributes['recipient_type'] ?? null,
                'recipient_id' => $jobAttributes['recipient_id'] ?? null,
                'payload' => $jobAttributes['payload'] ?? null,
                'planned_by_type' => $jobAttributes['planned_by_type'] ?? null,
                'planned_by_id' => $jobAttributes['planned_by_id'] ?? null,
            ]);
        }
    }
}
