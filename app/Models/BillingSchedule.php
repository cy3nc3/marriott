<?php

namespace App\Models;

use App\Services\Scheduling\FinanceDueReminderPlanner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingSchedule extends Model
{
    /** @use HasFactory<\Database\Factories\BillingScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id',
        'academic_year_id',
        'description',
        'due_date',
        'amount_due',
        'amount_paid',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(function (BillingSchedule $schedule): void {
            if (
                ! $schedule->wasRecentlyCreated
                && ! $schedule->wasChanged(['due_date', 'amount_due', 'amount_paid', 'status'])
            ) {
                return;
            }

            app(FinanceDueReminderPlanner::class)->reconcileSchedule($schedule);
        });

        static::deleted(function (BillingSchedule $schedule): void {
            app(FinanceDueReminderPlanner::class)->cancelSchedule($schedule, 'billing_schedule_deleted');
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function dueReminderDispatches(): HasMany
    {
        return $this->hasMany(FinanceDueReminderDispatch::class);
    }

    public function transactionDueAllocations(): HasMany
    {
        return $this->hasMany(TransactionDueAllocation::class);
    }
}
