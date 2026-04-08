<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'role',
        'module',
        'feature',
        'access_level',
    ];

    protected $casts = [
        'access_level' => 'integer',
    ];
}
