<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Package;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Put a state on the events and packages that still have none — Rule R38.
 *
 * The R38 migration already did this once. It runs at deploy time and takes
 * each row's state from the account that owns it, which means it can only be
 * as complete as the accounts were at that moment: any account whose own
 * state was filled in afterwards leaves its rows behind, with nothing to say
 * so. On this database that was 73 of 84 events.
 *
 * It matters beyond tidiness. A stateless event matches nobody under R38, so
 * it is invisible on every board, and the admin report cannot attribute its
 * money to anywhere. A command rather than a second migration because the
 * situation recurs — every time an account is corrected, its old rows are
 * stranded again.
 *
 *     php artisan gigresource:backfill-states
 *     php artisan gigresource:backfill-states --dry-run
 */
class BackfillEventStates extends Command
{
    protected $signature = 'gigresource:backfill-states {--dry-run : Report what would change, write nothing}';

    protected $description = 'Give events and packages the state of the account that owns them (R38)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $events = $this->pending(Event::query()->whereNull('state'), 'client_id');
        $packages = $this->pending(Package::query()->whereNull('state'), 'user_id');

        $this->line(sprintf('Events without a state:   %d — %d can be filled from their client.',
            Event::whereNull('state')->count(), $events->count()));
        $this->line(sprintf('Packages without a state: %d — %d can be filled from their owner.',
            Package::whereNull('state')->count(), $packages->count()));

        if ($dry) {
            $this->comment('Dry run — nothing written.');

            return self::SUCCESS;
        }

        foreach ([[Event::class, $events], [Package::class, $packages]] as [$model, $rows]) {
            foreach ($rows->groupBy('state') as $state => $group) {
                $model::whereIn('id', $group->pluck('id'))->update(['state' => $state]);
            }
        }

        $this->info(sprintf('Filled %d events and %d packages.', $events->count(), $packages->count()));

        // Said out loud rather than left in the total: these are rows whose
        // owning account has no state either, so there is nothing to copy.
        $stranded = Event::whereNull('state')->count();

        if ($stranded > 0) {
            $this->warn("{$stranded} events still have none — their client has no state on file either.");
        }

        return self::SUCCESS;
    }

    /** Rows whose owner does have a state to lend them. */
    private function pending($query, string $ownerColumn)
    {
        $states = DB::table('user_profiles')
            ->whereNotNull('state')->where('state', '<>', '')
            ->pluck('state', 'user_id');

        return $query->get(['id', $ownerColumn])
            ->map(fn ($row) => (object) [
                'id'    => $row->id,
                'state' => strtoupper((string) ($states[$row->{$ownerColumn}] ?? '')),
            ])
            ->filter(fn ($row) => $row->state !== '')
            ->values();
    }
}
