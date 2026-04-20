<?php

namespace App\Models;

use App\Enums\ScheduledNotificationJobStatus;
use App\Enums\ScheduledNotificationJobType;
use Illuminate\Database\Eloquent\Model;

class ScheduledNotificationJob extends Model
{
    protected $fillable = [
        'type',
        'status',
        'run_at',
        'dedupe_key',
        'group_key',
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
    ];

    protected function casts(): array
    {
        return [
            'type' => ScheduledNotificationJobType::class,
            'status' => ScheduledNotificationJobStatus::class,
            'run_at' => 'datetime',
            'payload' => 'array',
            'dispatched_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }
}
