<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

/**
 * A scheduled hour is an hour for somebody.
 *
 * Every schedule was written as a bare time, which Laravel reads in the app
 * timezone. That is UTC, and the people here are on US Eastern, so the two
 * jobs that send mail were aimed at 1am and 4am for whoever received them.
 * `attendance:chase` carries a note saying a chaser arriving at 3 in the
 * morning is one people learn to ignore, and it was set to arrive at 4.
 *
 * Nobody had noticed because cron was never set up to call the scheduler, so
 * no scheduled job on the site had ever run at all. Switching cron on is what
 * made the hour real.
 *
 * The maintenance jobs stay on UTC on purpose: they run in the small hours
 * and a local clock skips an hour outright when daylight saving starts, so a
 * 03:10 local job would not happen that day.
 */
class NobodyIsEmailedAtFourInTheMorningTest extends TestCase
{
    /** @return array<string, Event> */
    private function jobs(): array
    {
        $out = [];

        foreach (app(Schedule::class)->events() as $event) {
            if (preg_match('/artisan[\'" ]+([a-z-]+:[a-z-]+)/', $event->command ?? '', $m)) {
                $out[$m[1]] = $event;
            }
        }

        return $out;
    }

    public static function reaches_a_person(): array
    {
        return [
            'renewal notices' => ['subscriptions:renewal-notices'],
            'who is attending' => ['attendance:chase'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('reaches_a_person')]
    public function test_a_job_that_sends_mail_runs_on_the_readers_clock(string $command): void
    {
        $job = $this->jobs()[$command] ?? null;

        $this->assertNotNull($job, "{$command} is not scheduled at all.");
        $this->assertSame(
            config('app.display_timezone'),
            $job->timezone,
            "{$command} is scheduled in the server's timezone, so its hour is not the reader's hour."
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('reaches_a_person')]
    public function test_no_mail_is_scheduled_between_ten_at_night_and_seven_in_the_morning(string $command): void
    {
        $job = $this->jobs()[$command] ?? null;
        $this->assertNotNull($job);

        // "m h * * *", where h may be a list such as "9,17".
        [, $hours] = explode(' ', $job->expression);

        foreach (explode(',', $hours) as $hour) {
            $this->assertGreaterThanOrEqual(7, (int) $hour, "{$command} sends mail at {$hour}:00.");
            $this->assertLessThan(22, (int) $hour, "{$command} sends mail at {$hour}:00.");
        }
    }

    public function test_the_quiet_maintenance_jobs_stay_on_the_server_clock(): void
    {
        // Not an oversight: a local 03:10 is skipped entirely on the day
        // daylight saving starts, and UTC has no such gap.
        foreach (['users:purge-expired', 'agreements:purge-old-pdfs', 'conversations:tidy'] as $command) {
            $job = $this->jobs()[$command] ?? null;

            $this->assertNotNull($job, "{$command} is not scheduled at all.");
            $this->assertSame('UTC', $job->timezone ?? 'UTC', "{$command} moved off UTC.");
        }
    }
}
