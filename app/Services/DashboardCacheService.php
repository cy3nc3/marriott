<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class DashboardCacheService
{
    private const VERSION_KEY = 'dashboards:cache_version';

    /**
     * @template T
     *
     * @param  Closure(): T  $resolver
     * @return T
     */
    public static function remember(string $scope, Closure $resolver, int $minutes = 5): mixed
    {
        $version = (int) Cache::get(self::VERSION_KEY, 1);
        $cacheKey = "dashboards:v{$version}:{$scope}";

        return Cache::remember($cacheKey, now()->addMinutes($minutes), $resolver);
    }

    public static function bust(): void
    {
        if (! Cache::has(self::VERSION_KEY)) {
            Cache::forever(self::VERSION_KEY, 1);
        }

        Cache::increment(self::VERSION_KEY);
    }
}
