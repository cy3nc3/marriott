<?php

namespace App\Http\Middleware;

use App\Events\SystemDataUpdated;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BroadcastSystemDataUpdates
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->user()) {
            return $response;
        }

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        broadcast(new SystemDataUpdated(
            actorId: (int) $request->user()->id,
            path: (string) $request->path(),
            method: (string) $request->method(),
        ));

        return $response;
    }
}
