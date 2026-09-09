<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Proof that the scheduler is alive.
 *
 * Everything time-based on this site — renewal notices, account purges, PDF
 * cleanup — runs only if the host is calling Laravel's scheduler every minute.
 * If that cron entry is missing, none of it runs and nothing complains: the
 * code is correct, the emails simply never go.
 *
 * So the scheduler writes down that it ran. A page can then say "last run: 4
 * minutes ago" or "last run: never", which is the difference between knowing
 * and assuming.
 */
class SchedulerHeartbeat extends Command
{
    protected $signature = 'scheduler:heartbeat';

    protected $description = 'Record that the scheduler ran, so we can tell whether cron is working';

    public const KEY = 'scheduler.last_run';

    public function handle(): int
    {
        // Held well past a day: the useful answer when cron is broken is "not
        // since Tuesday", and a value that expires overnight would say
        // "never" instead — which reads as never set up.
        Cache::put(self::KEY, now()->toIso8601String(), now()->addDays(30));

        return self::SUCCESS;
    }

    /** When the scheduler last ran, or null if it never has. */
    public static function lastRun(): ?\Carbon\CarbonInterface
    {
        $at = Cache::get(self::KEY);

        return $at ? \Illuminate\Support\Carbon::parse($at) : null;
    }
}
