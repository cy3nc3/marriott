<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrNumberReservation extends Model
{
    protected $fillable = [
        'token',
        'series_key',
        'or_number',
        'reserved_by',
        'reserved_at',
        'expires_at',
        'released_at',
        'used_at',
        'transaction_id',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'released_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function reservedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
