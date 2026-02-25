<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('grading:send-deadline-reminders')
    ->dailyAt('07:00');

Schedule::command('finance:send-due-reminders')
    ->dailyAt('07:30');

Schedule::command('announcements:send-event-reminders')
    ->dailyAt('08:00');
