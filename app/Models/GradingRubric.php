<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradingRubric extends Model
{
    protected $fillable = [
        'subject_id',
        'ww_weight',
        'pt_weight',
        'qa_weight',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
