<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    protected $casts = [
        'group' => 'string',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, $value, ?string $group = null)
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }
}
