<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSchedule extends Model
{
    protected $fillable = [
        'section_id',
        'subject_assignment_id',
        'type',
        'label',
        'day',
        'start_time',
        'end_time',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subjectAssignment(): BelongsTo
    {
        return $this->belongsTo(SubjectAssignment::class);
    }
}
