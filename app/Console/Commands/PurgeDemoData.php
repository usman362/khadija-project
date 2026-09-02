<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * PM-2 / OA-25, the removal half. `demo:inventory` says what is there; this
 * takes it away.
 *
 * Dry by default. Nothing is written without --apply, because the last time
 * data went missing on this platform the cause was a seeder run against the
 * wrong tree and the fix was three days of proving which rows to keep.
 *
 * What it removes:
 *
 *   • the demo accounts the seeders create, listed by name in
 *     InventoryDemoData::SEEDED_DEMO_EMAILS — never the administrator, never
 *     an account no seeder wrote;
 *   • everything those accounts own, which the schema removes for us: events,
 *     bookings, packages, profiles, payments and the reviews hanging off those
 *     bookings all cascade from the user row;
 *   • demo events stranded on real accounts by the old owner fallback, and
 *     only when --stranded is given, because those sit beside a real person's
 *     genuine events.
 *
 * What it refuses to do quietly: leave a real client's booking pointing at a
 * deleted professional. bookings.supplier_id and events.supplier_id are SET
 * NULL, so removing a demo professional does not remove a real client's
 * booking of them — it empties the column and leaves a booking with no-one
 * booked. That is worse than the sample data, so those rows are found first
 * and the run stops unless they are acknowledged.
 */
class PurgeDemoData extends Command
{
    protected $signature = 'demo:purge
        {--apply : actually delete (otherwise this is a dry run)}
        {--stranded : also remove demo events found on real accounts}
        {--force-orphans : proceed even though real bookings would lose their professional}';

    protected $description = 'Remove demo/sample data (dry run unless --apply)';

    public function handle(): int
    {
        $demoIds = InventoryDemoData::demoUserIds();
        $apply = (bool) $this->option('apply');

        // Not an early return. After a first purge there are no demo accounts
        // left, and the events the old owner fallback stranded on real
        // accounts are exactly what remains to be dealt with — bailing out
        // here would make --stranded unusable in the one case it is for.
        if ($demoIds->isEmpty()) {
            $this->info('No demo accounts found.');
        }

        $this->newLine();
        $this->line($apply
            ? '<fg=red>APPLYING</> — rows will be deleted.'
            : '<fg=yellow>DRY RUN</> — nothing will be written. Add --apply to go ahead.');

        /* ── What real data would be damaged ─────────────────── */

        $orphans = $this->realRowsPointingAtDemoProfessionals($demoIds);

        if ($orphans !== []) {
            $this->newLine();
            $this->error('Real rows would lose their professional:');

            foreach ($orphans as $table => $rows) {
                $this->line("  {$table}: ".count($rows).' row(s) — ids '.implode(', ', array_slice($rows, 0, 20)));
            }

            $this->newLine();
            $this->line('  These belong to real accounts and book a demo professional.');
            $this->line('  Deleting the professional empties the column and leaves a');
            $this->line('  booking with nobody booked. Look at them before continuing,');
            $this->line('  then re-run with --force-orphans if that is what you want.');

            if ($apply && ! $this->option('force-orphans')) {
                $this->newLine();
                $this->error('Stopped. Nothing was deleted.');

                return self::FAILURE;
            }
        }

        /* ── What would go ───────────────────────────────────── */

        $before = InventoryDemoData::ownedByDemoAccounts($demoIds);

        $this->newLine();
        $this->info('Demo accounts to remove: '.$demoIds->count());
        $this->line('  Rows they own (removed with them by the schema):');

        foreach ($before as $table => $count) {
            $this->line(sprintf('    %-24s %d', $table, $count));
        }

        $stranded = InventoryDemoData::strandedOnRealAccounts($demoIds);

        if ($stranded !== []) {
            $this->newLine();

            if ($this->option('stranded')) {
                $this->info('Demo events on real accounts to remove: '.count($stranded));
            } else {
                $this->warn('Demo events on real accounts: '.count($stranded).' — LEFT ALONE');
                $this->line('  Add --stranded to remove these too.');
            }

            foreach ($stranded as $row) {
                $this->line("    #{$row['id']}  {$row['title']}  ({$row['email']})");
            }
        }

        $this->renderWhatIsLeft($demoIds);

        if (! $apply) {
            $this->newLine();
            $this->line('Dry run complete. Nothing was changed.');

            return self::SUCCESS;
        }

        /* ── Do it ───────────────────────────────────────────── */

        DB::transaction(function () use ($demoIds, $stranded) {
            if ($this->option('stranded') && $stranded !== []) {
                DB::table('events')
                    ->whereIn('id', array_column($stranded, 'id'))
                    ->delete();
            }

            /*
             * forceDelete, not delete. User soft-deletes, so delete() only
             * stamps deleted_at — the row stays, and because the row stays,
             * none of the ON DELETE CASCADE rules fire. The demo events,
             * bookings, packages and reviews would all still be in the
             * database, and this command would report success having removed
             * nothing at all.
             *
             * One at a time through the model so any `deleting` hook still
             * fires. These are tens of rows, not thousands.
             */
            if ($demoIds->isNotEmpty()) {
                User::whereIn('id', $demoIds)->get()->each->forceDelete();
            }
        });

        $this->newLine();
        $this->info('Done. Re-run `php artisan demo:inventory` to confirm.');

        return self::SUCCESS;
    }

    /**
     * What the public site holds once this has run.
     *
     * "20 accounts removed" is not the number anyone needs. Most of the
     * professionals on the platform are demo professionals, so the honest
     * figure is how many are left to show on the browse page afterwards — and
     * whether that page is worth opening. Removing sample data is right, and
     * an empty marketplace is still a decision somebody should take on
     * purpose rather than discover.
     */
    private function renderWhatIsLeft(\Illuminate\Support\Collection $demoIds): void
    {
        $this->newLine();
        $this->info('What the public site would hold afterwards');

        foreach (['professional', 'client'] as $role) {
            $total = User::role($role)->count();
            $going = $demoIds->isEmpty() ? 0 : User::role($role)->whereIn('id', $demoIds)->count();

            $this->line(sprintf('  %-14s %d of %d remain', $role.'s', $total - $going, $total));
        }
    }

    /**
     * Rows owned by a REAL account that name a demo account as the
     * professional. These survive the purge with an empty supplier column.
     *
     * @return array<string, array<int, int>>
     */
    private function realRowsPointingAtDemoProfessionals(\Illuminate\Support\Collection $demoIds): array
    {
        $found = [];

        foreach (['events' => 'client_id', 'bookings' => 'client_id'] as $table => $ownerColumn) {
            if (! \Schema::hasTable($table) || ! \Schema::hasColumn($table, 'supplier_id')) {
                continue;
            }

            $ids = DB::table($table)
                ->whereIn('supplier_id', $demoIds)
                ->whereNotIn($ownerColumn, $demoIds->all() ?: [0])
                ->pluck('id')
                ->all();

            if ($ids !== []) {
                $found[$table] = $ids;
            }
        }

        return $found;
    }
}
