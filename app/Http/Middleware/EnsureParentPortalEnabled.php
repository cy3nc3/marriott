<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParentPortalEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->routeIs('login')
            || $request->routeIs('logout')
            || $request->routeIs('password.*')
            || $request->routeIs('verification.*')
            || $request->routeIs('two-factor.*')
        ) {
            return $next($request);
        }

        if (Setting::enabled('parent_portal', true)) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $role = $user->role?->value ?? (string) $user->role;

        if ($role === 'parent') {
            abort(403, 'Parent portal is currently disabled.');
        }

        return $next($request);
    }
}
