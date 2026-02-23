<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDeparture extends Model
{
    protected $fillable = [
        'student_id',
        'enrollment_id',
        'academic_year_id',
        'reason',
        'effective_date',
        'remarks',
        'processed_by',
        'account_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'account_expires_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
