<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Generate automatic alerts daily at 9 AM
Schedule::command('alerts:generate')->dailyAt('09:00');

// Weekly alert digest — Monday at 9 AM (in each org's TZ approx)
Schedule::command('alerts:send-weekly-digest')->weeklyOn(1, '09:00')->withoutOverlapping();
