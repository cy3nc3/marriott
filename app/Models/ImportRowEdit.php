<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRowEdit extends Model
{
    protected $fillable = [
        'import_batch_row_id',
        'field_name',
        'original_values',
        'edited_values',
        'metadata',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'original_values' => 'array',
            'edited_values' => 'array',
            'metadata' => 'array',
            'edited_at' => 'datetime',
        ];
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(ImportBatchRow::class, 'import_batch_row_id');
    }
}
