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

// Daily at 03:20, delete cached agreement PDFs older than 60 days.
// Pairs with AgreementPdfService::RETENTION_DAYS.
Schedule::command('agreements:purge-old-pdfs')
    ->dailyAt('03:20')
    ->withoutOverlapping()
    ->onOneServer();

/*
 * Renewal notices — Washington DC, Automatic Renewal Protections Act of 2018.
 *
 * Early, because a member who reads it over breakfast still has the whole day
 * to cancel before the charge. Each notice is recorded before it is sent and
 * the record is unique per renewal, so a second run in the same day sends
 * nothing twice.
 */
Schedule::command('subscriptions:renewal-notices')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->onOneServer();

/*
 * The scheduler saying it is alive.
 *
 * Every minute, because that is how often the host is meant to call it. Anything
 * else here runs only if this is running — so when a renewal notice does not
 * arrive, this is what says whether the job is broken or the cron entry is
 * simply missing.
 */
Schedule::command('scheduler:heartbeat')->everyMinute();
