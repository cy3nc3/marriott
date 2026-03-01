<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemedialCase extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'created_by',
        'failed_subject_count',
        'fee_per_subject',
        'total_amount',
        'amount_paid',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'fee_per_subject' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
