<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Subject extends Model
{
    use Auditable;

    protected $fillable = [
        'grade_level_id',
        'subject_code',
        'subject_name',
        'required_weekly_minutes',
    ];

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'teacher_subjects', 'subject_id', 'teacher_id')
            ->withPivot(['id', 'qualification_status', 'eligibility_documents'])
            ->withTimestamps();
    }

    public function gradingRubric(): HasOne
    {
        return $this->hasOne(GradingRubric::class);
    }

    public function remedialSubjectFees(): HasMany
    {
        return $this->hasMany(RemedialSubjectFee::class);
    }
}
