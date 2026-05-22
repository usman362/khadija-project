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
