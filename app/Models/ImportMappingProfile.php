<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportMappingProfile extends Model
{
    protected $fillable = [
        'module',
        'created_by',
        'profile_name',
        'header_map',
        'parsing_rules',
    ];

    protected function casts(): array
    {
        return [
            'header_map' => 'array',
            'parsing_rules' => 'array',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
