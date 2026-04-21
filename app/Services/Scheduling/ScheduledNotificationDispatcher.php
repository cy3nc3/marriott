<?php

namespace App\Services\Scheduling;

use App\Enums\ScheduledNotificationJobStatus;
use App\Jobs\DispatchScheduledNotificationJob;
use App\Models\ScheduledNotificationJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ScheduledNotificationDispatcher
{
    public function dispatchDue(): int
    {
        $jobIds = ScheduledNotificationJob::query()
            ->where('status', ScheduledNotificationJobStatus::Pending)
            ->where('run_at', '<=', now())
            ->orderBy('run_at')
            ->pluck('id');

        return $jobIds->reduce(function (int $processed, int $jobId): int {
            return $processed + $this->claimAndProcess($jobId);
        }, 0);
    }

    private function claimAndProcess(int $jobId): int
    {
        return DB::transaction(function () use ($jobId): int {
            $job = ScheduledNotificationJob::query()
                ->whereKey($jobId)
                ->where('status', ScheduledNotificationJobStatus::Pending)
                ->lockForUpdate()
                ->first();

            if (! $job) {
                return 0;
            }

            if (! $this->subjectExists($job)) {
                $job->forceFill([
                    'status' => ScheduledNotificationJobStatus::Skipped,
                    'skip_reason' => 'subject_missing',
                ])->save();

                return 1;
            }

            $job->forceFill([
                'status' => ScheduledNotificationJobStatus::Processing,
            ])->save();

            DispatchScheduledNotificationJob::dispatch($job->id);

            return 1;
        });
    }

    private function subjectExists(ScheduledNotificationJob $job): bool
    {
        if (! class_exists($job->subject_type) || ! is_subclass_of($job->subject_type, Model::class)) {
            return false;
        }

        return $job->subject_type::query()
            ->whereKey($job->subject_id)
            ->exists();
    }
}
