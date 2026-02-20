<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemedialRecord extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'academic_year_id',
        'final_rating',
        'remedial_class_mark',
        'recomputed_final_grade',
        'status',
    ];

    protected $casts = [
        'final_rating' => 'decimal:2',
        'remedial_class_mark' => 'decimal:2',
        'recomputed_final_grade' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
