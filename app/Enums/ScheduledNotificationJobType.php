<?php

namespace App\Enums;

enum ScheduledNotificationJobType: string
{
    case FinanceDueReminder = 'finance_due_reminder';
    case GradeDeadlineReminder = 'grade_deadline_reminder';
    case AnnouncementEventReminder = 'announcement_event_reminder';
}
