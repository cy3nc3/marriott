<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public const STATUS_PRESENT = 'present';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_TARDY_LATE_COMER = 'tardy_late_comer';

    public const STATUS_TARDY_CUTTING_CLASSES = 'tardy_cutting_classes';

    public const STATUSES = [
        self::STATUS_PRESENT,
        self::STATUS_ABSENT,
        self::STATUS_TARDY_LATE_COMER,
        self::STATUS_TARDY_CUTTING_CLASSES,
    ];

    protected $fillable = [
        'enrollment_id',
        'subject_assignment_id',
        'date',
        'status',
        'remarks',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }
}
