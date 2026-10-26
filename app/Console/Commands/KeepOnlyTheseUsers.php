<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Empty the user table down to a named handful, and everything they own with
 * them.
 *
 * Asked for on 26 October: keep client@example.com and
 * professional@example.com on production and remove every other account and
 * its data. `demo:purge` could not do it. That command deletes only the
 * hand-listed seeded demo addresses, on purpose, so that it can never reach a
 * real person's account. This one is the opposite shape: it keeps a named list
 * and removes everything else, which is a far larger blast radius and is
 * written to behave like it.
 *
 * WHAT WAS CONFIRMED BEFORE THIS EXISTED, because the list is not obvious from
 * the outside. Of 50 accounts on production: 23 are seeded demo data, 22 are
 * real sign-ups with ordinary mail addresses, 3 belong to Sir Peter himself,
 * and 2 are the ones being kept. Deleting "everything else" therefore removes
 * the owner's own accounts and everybody who has ever signed up. That was put
 * to Ali as three options with the counts shown, and the widest one was chosen
 * deliberately.
 *
 * THE TRAPS, all three already paid for once in `demo:purge`:
 *
 *   1. The admin is never a candidate. It matches no rule here because it is
 *      refused by name: deleting it locks everybody out of the admin side,
 *      and nothing in this command's argument list can reach it.
 *
 *   2. `User` soft-deletes. `delete()` only stamps `deleted_at`, the row
 *      stays, and because the row stays NO `ON DELETE CASCADE` fires. Every
 *      event, booking, package and review would survive and the command would
 *      report success having removed nothing. It must be `forceDelete()`.
 *
 *   3. `bookings.supplier_id` and `events.supplier_id` are SET NULL. A kept
 *      account holding a booking with a deleted professional ends up with a
 *      booking nobody is booked for. Those are counted and shown, and the
 *      command stops unless they are accepted explicitly.
 *
 * Dry run unless --apply, like its sibling, because the first run of anything
 * like this should only ever print.
 */
class KeepOnlyTheseUsers extends Command
{
    protected $signature = 'users:keep-only
                            {emails* : The accounts to keep, by email}
                            {--apply : Actually delete. Without this it only prints}
                            {--force-orphans : Proceed although a kept account would lose a booking}';

    protected $description = 'Delete every user except the ones named, and everything they own';

    /**
     * Never a candidate, whatever is passed.
     *
     * The admin is how anybody gets back into the admin side. It is refused
     * here rather than relied upon being listed, because the one time it is
     * forgotten is the time it matters.
     */
    private const NEVER_DELETE = ['admin@example.com'];

    public function handle(): int
    {
        $named = collect($this->argument('emails'))
            ->map(fn ($e) => mb_strtolower(trim($e)))
            ->unique();

        $keep = $named->merge(array_map('mb_strtolower', self::NEVER_DELETE))->unique();

        $kept = User::withTrashed()->whereIn(DB::raw('LOWER(email)'), $keep->all())->get();

        /*
         * Only the addresses that were ASKED for have to exist. The protected
         * ones are added by this command, not typed by anybody, so a site with
         * no admin account must not look like a typo.
         */
        $missing = $named->diff($kept->map(fn ($u) => mb_strtolower($u->email)));

        if ($missing->isNotEmpty()) {
            $this->error('These were named but do not exist. Check the spelling before deleting anything:');
            $missing->each(fn ($e) => $this->line('  ' . $e));

            return self::FAILURE;
        }

        $doomed = User::withTrashed()->whereNotIn('id', $kept->pluck('id'))->get();

        if ($doomed->isEmpty()) {
            $this->info('Nothing to remove. Only the named accounts exist.');

            return self::SUCCESS;
        }

        $this->renderPlan($kept, $doomed);

        $orphans = $this->keptRowsLosingTheirSupplier($doomed->pluck('id'), $kept->pluck('id'));

        if ($orphans !== []) {
            $this->newLine();
            $this->warn('A kept account would be left holding work with nobody assigned:');

            foreach ($orphans as $table => $ids) {
                $this->line('  ' . count($ids) . ' in ' . $table . ' (ids ' . implode(', ', array_slice($ids, 0, 8)) . (count($ids) > 8 ? ', …' : '') . ')');
            }

            $this->line('  Re-run with --force-orphans if that is acceptable.');

            if ($this->option('apply') && ! $this->option('force-orphans')) {
                return self::FAILURE;
            }
        }

        if (! $this->option('apply')) {
            $this->newLine();
            $this->line('Dry run. Nothing was changed. Add --apply to do it.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($doomed) {
            /*
             * forceDelete, one at a time through the model, so the cascades
             * fire and any `deleting` hook still runs. Fifty rows, not fifty
             * thousand.
             */
            $doomed->each->forceDelete();
        });

        $this->newLine();
        $this->info($doomed->count() . ' accounts removed, with everything they owned.');
        $this->line('Remaining: ' . User::withTrashed()->count());

        return self::SUCCESS;
    }

    private function renderPlan($kept, $doomed): void
    {
        $this->newLine();
        $this->info('KEEPING ' . $kept->count() . ':');
        $kept->each(fn ($u) => $this->line('  ' . $u->id . '  ' . $u->email
            . '  [' . ($u->getRoleNames()->implode(', ') ?: 'no role') . ']'));

        $this->newLine();
        $this->warn('DELETING ' . $doomed->count() . ', and everything each one owns:');

        foreach ($doomed as $u) {
            $this->line('  ' . $u->id . '  ' . $u->email
                . '  [' . ($u->getRoleNames()->implode(', ') ?: 'no role') . ']');
        }
    }

    /**
     * Work belonging to a kept account whose professional is being deleted.
     *
     * SET NULL, so the row survives the delete with an empty supplier. It is a
     * booking for nobody, and it is the one way this command can damage an
     * account it was told to keep.
     *
     * @return array<string, array<int>>
     */
    private function keptRowsLosingTheirSupplier($doomedIds, $keptIds): array
    {
        $found = [];

        foreach (['events', 'bookings'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'supplier_id')) {
                continue;
            }

            $ids = DB::table($table)
                ->whereIn('supplier_id', $doomedIds)
                ->whereIn('client_id', $keptIds)
                ->pluck('id')
                ->all();

            if ($ids !== []) {
                $found[$table] = $ids;
            }
        }

        return $found;
    }
}
