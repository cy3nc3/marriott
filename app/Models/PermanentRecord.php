<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermanentRecord extends Model
{
    protected $fillable = [
        'student_id',
        'school_name',
        'academic_year_id',
        'grade_level_id',
        'general_average',
        'status',
        'failed_subject_count',
        'conditional_resolved_at',
        'conditional_resolution_notes',
        'retained_resolved_at',
        'retained_resolution_notes',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'general_average' => 'decimal:2',
            'failed_subject_count' => 'integer',
            'conditional_resolved_at' => 'datetime',
            'retained_resolved_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }
}
