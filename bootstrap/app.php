<?php

use App\Http\Middleware\EnsureMaintenanceMode;
use App\Http\Middleware\EnsureParentPortalEnabled;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $configuredTrustedProxies = env('TRUSTED_PROXIES');
        $trustedProxies = ['127.0.0.1', '::1'];

        if (is_string($configuredTrustedProxies)) {
            $configuredTrustedProxies = trim($configuredTrustedProxies);

            if ($configuredTrustedProxies !== '') {
                $trustedProxies = array_values(
                    array_filter(array_map('trim', explode(',', $configuredTrustedProxies)))
                );
            }
        }

        $middleware->trustProxies(
            at: $trustedProxies,
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->alias([
            'desktop_only' => \App\Http\Middleware\EnsureDesktopOnlyRoute::class,
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->validateCsrfTokens(except: [
            'account/claim/*/otp/send',
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            EnsureMaintenanceMode::class,
            EnsureParentPortalEnabled::class,
            EnsurePasswordChanged::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
