<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemedialCaseSubject extends Model
{
    protected $fillable = [
        'remedial_case_id',
        'student_id',
        'academic_year_id',
        'subject_id',
        'assigned_teacher_id',
        'final_rating',
    ];

    protected $casts = [
        'final_rating' => 'decimal:2',
    ];

    public function remedialCase(): BelongsTo
    {
        return $this->belongsTo(RemedialCase::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function assignedTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_teacher_id');
    }
}
