<?php

namespace App\Console\Commands;

use App\Domain\Messaging\TidyFinishedThreads;
use Illuminate\Console\Command;

/**
 * Moves conversations about finished jobs into Archived.
 *
 * The rules are in App\Domain\Messaging\TidyFinishedThreads. This is only
 * what runs them on a clock. Daily, because nothing here is urgent: a thread
 * that stays in the inbox until tomorrow morning costs somebody one glance.
 */
class TidyFinishedConversations extends Command
{
    protected $signature = 'conversations:tidy {--dry-run : Count what would be archived, archive nothing}';

    protected $description = 'Archive conversations whose event or booking is over';

    public function handle(): int
    {
        $dry  = (bool) $this->option('dry-run');
        $done = TidyFinishedThreads::run($dry);

        $this->info($dry
            ? "{$done} conversation(s) would be archived. None were."
            : "{$done} conversation(s) archived.");

        return self::SUCCESS;
    }
}
