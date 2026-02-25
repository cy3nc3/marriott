<?php

namespace App\Http\Middleware;

use App\Services\HandheldDeviceDetector;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureDesktopOnlyRoute
{
    public function __construct(private HandheldDeviceDetector $handheldDeviceDetector) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->handheldDeviceDetector->isHandheldRequest($request)) {
            return $next($request);
        }

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            abort(403, 'Desktop device required for this action.');
        }

        $user = $request->user();
        $role = $user?->role?->value ?? (string) ($user?->role ?? 'user');

        return Inertia::render('mobile/desktop-required', [
            'title' => 'Desktop Access Required',
            'message' => 'This page is available on desktop only to protect operational workflows.',
            'role' => $role,
            'requested_path' => '/'.$request->path(),
        ])->toResponse($request)->setStatusCode(403);
    }
}
