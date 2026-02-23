<?php

namespace App\Models;

use App\Services\DashboardCacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    protected $casts = [
        'group' => 'string',
    ];

    public static function get(string $key, $default = null)
    {
        $settings = self::allCached();

        return $settings[$key] ?? $default;
    }

    public static function set(string $key, $value, ?string $group = null)
    {
        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget(self::allCacheKey());
        DashboardCacheService::bust();

        return $setting;
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

    public static function allCached(): array
    {
        return Cache::remember(self::allCacheKey(), now()->addMinutes(10), function (): array {
            return static::query()
                ->pluck('value', 'key')
                ->toArray();
        });
    }

    private static function allCacheKey(): string
    {
        return 'settings:all';
    }
}
