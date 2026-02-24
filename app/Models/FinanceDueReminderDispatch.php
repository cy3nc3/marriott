<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceDueReminderDispatch extends Model
{
    /** @use HasFactory<\Database\Factories\FinanceDueReminderDispatchFactory> */
    use HasFactory;

    protected $fillable = [
        'finance_due_reminder_rule_id',
        'billing_schedule_id',
        'reminder_date',
        'announcement_id',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'reminder_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(FinanceDueReminderRule::class, 'finance_due_reminder_rule_id');
    }

    public function billingSchedule(): BelongsTo
    {
        return $this->belongsTo(BillingSchedule::class);
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }
}
