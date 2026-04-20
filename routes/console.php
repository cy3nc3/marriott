<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('grading:send-deadline-reminders')
    ->everyMinute();

Schedule::command('finance:send-due-reminders')
    ->everyMinute();

Schedule::command('notifications:dispatch-scheduled')
    ->everyMinute();

Schedule::command('announcements:send-event-reminders')
    ->dailyAt('08:00');
