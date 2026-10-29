<?php

namespace App\Console\Commands;

use App\Domain\AttendanceChase;
use Illuminate\Console\Command;

/**
 * Chases professionals who said they would decide nearer the time.
 *
 * Sir Peter, 29 October, point 8. The rules are in App\Domain\AttendanceChase
 * because the screens ask the same questions; this is only what runs them on
 * a clock.
 *
 * Twice a day rather than every minute: the deadline is 24 hours before the
 * event, so a reminder that is a few hours early or late is the same reminder,
 * and a chaser that can arrive at 3 in the morning is one people learn to
 * ignore.
 */
class ChaseWhoIsAttending extends Command
{
    protected $signature = 'attendance:chase {--dry-run : Count what would be sent, send nothing}';

    protected $description = 'Remind professionals who have not said who is attending';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $due = AttendanceChase::due();

        foreach ($due as $row) {
            $this->line(sprintf(
                '  %-7s  booking %-6d  %s',
                $row['stage'],
                $row['booking']->id,
                $row['booking']->event?->title ?? 'an event',
            ));
        }

        $sent = AttendanceChase::run($dry);

        $this->info($dry
            ? "{$sent} reminder(s) would be sent. Nothing was."
            : "{$sent} reminder(s) sent.");

        return self::SUCCESS;
    }
}
