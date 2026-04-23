<?php

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Mail;

test('mail demo redirect config forces global recipient when configured', function () {
    config()->set('mail.demo_redirect_to', 'demo-inbox@example.com');

    Mail::shouldReceive('alwaysTo')
        ->once()
        ->with('demo-inbox@example.com');

    (new AppServiceProvider(app()))->boot();
});

test('mail demo redirect is skipped when configuration is empty', function () {
    config()->set('mail.demo_redirect_to', '');

    Mail::shouldReceive('alwaysTo')
        ->never();

    (new AppServiceProvider(app()))->boot();
});
