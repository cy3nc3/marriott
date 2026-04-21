<?php

namespace App\Enums;

enum ScheduledNotificationJobStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Canceled = 'canceled';
    case Superseded = 'superseded';
    case Dispatched = 'dispatched';
    case Skipped = 'skipped';
    case Failed = 'failed';
}
