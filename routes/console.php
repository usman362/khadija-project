<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily at 03:10 local time, purge user accounts whose 60-day grace period has expired.
Schedule::command('users:purge-expired')
    ->dailyAt('03:10')
    ->withoutOverlapping()
    ->onOneServer();
