<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily at 03:10 local time, purge user accounts whose 60-day grace period has expired.
/*
 * The three jobs that touch nobody's inbox stay on UTC on purpose. They run
 * in the small hours, and local clocks skip an hour outright when daylight
 * saving starts, so a 03:10 local job simply would not happen that day. UTC
 * has no such gap. Only the jobs a person receives are moved to the reader's
 * clock.
 */
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
    /*
     * Nine in the morning where the reader is, not where the server is.
     *
     * Every time here was written as a bare hour, which Laravel reads in the
     * app timezone, and that is UTC. This one meant 06:00 UTC, so a notice
     * about somebody's money would have arrived at 1am Eastern. Nobody had
     * felt it because cron had never been set up to call the scheduler at
     * all, so none of these had ever run.
     *
     * display_timezone is the one place that decides what time it is for a
     * person here, the same setting the pages read. Nine in the morning is
     * far from the hour DST skips, so it cannot fall into the gap.
     */
    ->dailyAt('09:00')
    ->timezone(config('app.display_timezone'))
    ->withoutOverlapping()
    ->onOneServer();

/*
 * Who is attending, for agreements that left it open.
 *
 * Sir Peter, 29 October: a professional who says they will decide nearer the
 * time has to confirm at least 24 hours before the event. Twice a day rather
 * than every minute, because a reminder that is a few hours early or late is
 * the same reminder, and a chaser that can arrive at 3 in the morning is one
 * people learn to ignore. Each stage is written down before it is sent, so
 * two runs in a day send nothing twice.
 */
Schedule::command('attendance:chase')
    // 9 and 17 UTC is 4am and midday Eastern, and the note above is about
    // exactly that: a chaser arriving at 4 in the morning is the one people
    // learn to ignore. The hours were right, the clock was not.
    ->twiceDaily(9, 17)
    ->timezone(config('app.display_timezone'))
    ->withoutOverlapping()
    ->onOneServer();

/*
 * Conversations about jobs that are over.
 *
 * Khadijah, 30 October: archived once the event is completed or the booking
 * is closed or cancelled, and only when no dispute is outstanding. Daily,
 * because nothing here is urgent — a thread that stays in the inbox until
 * morning costs somebody one glance. Each conversation is marked when the
 * sweep deals with it, so a thread somebody restores by hand stays restored.
 */
Schedule::command('conversations:tidy')
    ->dailyAt('04:10')
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
