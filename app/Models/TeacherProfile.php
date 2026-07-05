<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherProfile extends Model
{
    use Auditable;

    protected $fillable = [
        'user_id',
        'qualification_status',
        'is_let_passer',
        'prc_license_no',
        'license_valid_until',
        'degree',
        'major',
        'professional_education_units',
        'exception_basis',
        'provisional_until',
        'grade_band_eligibility',
        'subject_competency_tags',
        'notes',
        'eligibility_documents',
    ];

    protected function casts(): array
    {
        return [
            'is_let_passer' => 'boolean',
            'license_valid_until' => 'date',
            'provisional_until' => 'date',
            'grade_band_eligibility' => 'array',
            'subject_competency_tags' => 'array',
            'eligibility_documents' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
