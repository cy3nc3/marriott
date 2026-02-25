<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

test('home redirects guests to login', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login', absolute: false));
});

test('request resolves forwarded tunnel host and scheme', function () {
    Route::middleware('web')->get('/_proxy-check', function (Request $request) {
        return response()->json([
            'scheme' => $request->getScheme(),
            'host' => $request->getHost(),
            'port' => $request->getPort(),
        ]);
    });

    $response = $this->withServerVariables([
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_HOST' => 'sample-tunnel.ngrok-free.app',
        'HTTP_X_FORWARDED_PORT' => '443',
        'REMOTE_ADDR' => '127.0.0.1',
    ])->get('/_proxy-check');

    $response->assertOk()->assertJson([
        'scheme' => 'https',
        'host' => 'sample-tunnel.ngrok-free.app',
        'port' => 443,
    ]);
});
