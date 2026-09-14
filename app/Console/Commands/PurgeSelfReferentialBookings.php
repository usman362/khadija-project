<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * OA-128 / OA-141 / OA-154: bookings where the client is also the
 * professional. They are test data, never a real booking; new ones are
 * already refused, and this removes the ones made before that rule.
 *
 * Lists by default. Nothing is deleted without --force.
 */
class PurgeSelfReferentialBookings extends Command
{
    protected $signature = 'bookings:purge-self-referential {--force : actually delete them}';

    protected $description = 'List (or with --force, delete) bookings where the client is their own professional';

    public function handle(): int
    {
        $rows = Booking::query()->whereNotNull('supplier_id')->whereColumn('supplier_id', 'client_id')
            ->with('event:id,title', 'client:id,name')->get();

        if ($rows->isEmpty()) {
            $this->info('No self-referential bookings.');

            return self::SUCCESS;
        }

        $this->table(['Booking', 'Event', 'Account', 'Status', 'Price'], $rows->map(fn ($b) => [
            $b->id, $b->event?->title, $b->client?->name, $b->status, $b->price,
        ])->all());

        if (! $this->option('force')) {
            $this->warn($rows->count() . ' found. Nothing deleted. Run again with --force to delete them.');

            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($rows as $b) {
            try {
                DB::transaction(fn () => Booking::withoutEvents(fn () => $b->delete()));
                $deleted++;
            } catch (\Throwable $e) {
                $this->error("Booking {$b->id} could not be deleted: " . $e->getMessage());
            }
        }

        $this->info("Deleted {$deleted} of {$rows->count()}.");

        return self::SUCCESS;
    }
}
