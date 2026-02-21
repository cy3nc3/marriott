<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConductRating extends Model
{
    protected $fillable = [
        'enrollment_id',
        'quarter',
        'maka_diyos',
        'makatao',
        'makakalikasan',
        'makabansa',
        'remarks',
        'is_locked',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
