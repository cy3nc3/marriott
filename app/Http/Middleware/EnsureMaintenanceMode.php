<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMaintenanceMode
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

        if (! Setting::enabled('maintenance_mode')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $role = $user->role?->value ?? (string) $user->role;

        if (in_array($role, ['super_admin', 'admin'], true)) {
            return $next($request);
        }

        abort(503, 'System is currently in maintenance mode.');
    }
}
