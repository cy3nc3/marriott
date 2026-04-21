<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'module',
        'uploaded_by',
        'file_name',
        'file_hash',
        'mapping',
        'summary',
        'status',
        'previewed_at',
        'applied_at',
        'rolled_back_at',
    ];

    protected function casts(): array
    {
        return [
            'mapping' => 'array',
            'summary' => 'array',
            'previewed_at' => 'datetime',
            'applied_at' => 'datetime',
            'rolled_back_at' => 'datetime',
        ];
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportBatchRow::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
