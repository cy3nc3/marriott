<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrNumberSequence extends Model
{
    protected $fillable = [
        'series_key',
        'prefix',
        'year',
        'next_number',
    ];

    protected $casts = [
        'year' => 'integer',
        'next_number' => 'integer',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(OrNumberReservation::class, 'series_key', 'series_key');
    }
}
