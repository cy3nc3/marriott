<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherSubject extends Model
{
    protected $fillable = [
        'teacher_id',
        'subject_id',
        'qualification_status',
        'eligibility_documents',
    ];

    protected function casts(): array
    {
        return [
            'eligibility_documents' => 'array',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(SubjectAssignment::class);
    }
}
