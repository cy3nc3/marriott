<?php

namespace App\Models;

use App\Services\Scheduling\GradeDeadlineReminderPlanner;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeSubmission extends Model
{
    use Auditable;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'academic_year_id',
        'subject_assignment_id',
        'quarter',
        'status',
        'submitted_by',
        'submitted_at',
        'verified_by',
        'verified_at',
        'returned_by',
        'returned_at',
        'return_notes',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (GradeSubmission $submission): void {
            if (
                ! $submission->wasRecentlyCreated
                && ! $submission->wasChanged(['academic_year_id', 'quarter', 'status'])
            ) {
                return;
            }

            $submission->loadMissing('academicYear');

            if ($submission->academicYear) {
                app(GradeDeadlineReminderPlanner::class)
                    ->reconcileAcademicYearQuarter($submission->academicYear, (string) $submission->quarter);
            }
        });

        static::deleted(function (GradeSubmission $submission): void {
            $submission->loadMissing('academicYear');

            if ($submission->academicYear) {
                app(GradeDeadlineReminderPlanner::class)
                    ->reconcileAcademicYearQuarter($submission->academicYear, (string) $submission->quarter);
            }
        });
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }
}
