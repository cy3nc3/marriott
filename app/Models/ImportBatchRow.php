<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatchRow extends Model
{
    protected $fillable = [
        'import_batch_id',
        'row_number',
        'status',
        'raw_values',
        'normalized_values',
        'validation_errors',
        'validation_warnings',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_values' => 'array',
            'normalized_values' => 'array',
            'validation_errors' => 'array',
            'validation_warnings' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function edits(): HasMany
    {
        return $this->hasMany(ImportRowEdit::class);
    }
}
