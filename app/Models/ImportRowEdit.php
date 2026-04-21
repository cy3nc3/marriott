<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRowEdit extends Model
{
    protected $fillable = [
        'import_batch_row_id',
        'edited_by',
        'before_payload',
        'after_payload',
    ];

    protected function casts(): array
    {
        return [
            'before_payload' => 'array',
            'after_payload' => 'array',
        ];
    }

    public function row(): BelongsTo
    {
        return $this->belongsTo(ImportBatchRow::class, 'import_batch_row_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }
}
