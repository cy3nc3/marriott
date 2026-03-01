<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->routeIs('logout')
            || $request->routeIs('user-password.edit')
            || $request->routeIs('user-password.update')
            || $request->routeIs('password.*')
            || $request->routeIs('verification.*')
            || $request->routeIs('two-factor.*')
        ) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        return redirect()->route('user-password.edit');
    }
}
