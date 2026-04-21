<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatchRow extends Model
{
    protected $fillable = [
        'import_batch_id',
        'row_index',
        'raw_payload',
        'normalized_payload',
        'validation_errors',
        'duplicate_flags',
        'classification',
        'action',
        'is_unresolved',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'normalized_payload' => 'array',
            'validation_errors' => 'array',
            'duplicate_flags' => 'array',
            'is_unresolved' => 'boolean',
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
