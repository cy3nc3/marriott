<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportMappingProfile extends Model
{
    protected $fillable = [
        'name',
        'source_type',
        'created_by',
        'column_mapping',
        'transform_rules',
        'metadata',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'column_mapping' => 'array',
            'transform_rules' => 'array',
            'metadata' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
