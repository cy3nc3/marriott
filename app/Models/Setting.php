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

    public static function enabled(string $key, bool $default = false): bool
    {
        $value = static::get($key);

        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
