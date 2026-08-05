<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Moves the site from the v1 category tree onto v2.
 *
 * The dangerous part is not the categories — it is everything pointing at
 * them. 156 professional-to-category links, plus events, packages and bids,
 * all hold v1 ids. Switching without re-homing those leaves a professional
 * listed under nothing and a category page with no one on it.
 *
 * So this refuses to switch while anything still points at v1, and --remap
 * moves those links across first using the reviewed mapping file.
 */
class SwitchTaxonomy extends Command
{
    protected $signature = 'taxonomy:switch
                            {--remap : move professional and event links onto v2 first}
                            {--check : report what would happen and change nothing}';

    protected $description = 'Check, re-home and switch the live category tree to v2';

    private const MAP_PATH = 'database/seeders/data/taxonomy_v1_to_v2_map.json';

    /** table => the column holding a category id */
    private const DEPENDENTS = [
        'category_user'  => 'category_id',
        'category_event' => 'category_id',
        'events'         => 'category_id',
        'packages'       => 'category_id',
        'bids'           => 'category_id',
    ];

    /**
     * Pivots, and the column naming the other side of the pair.
     *
     * These carry a unique index on (owner, category). Twenty-seven new
     * categories replace three hundred and sixty old ones, so two of a
     * professional's categories routinely collapse into one — and updating the
     * rows in place hits that index and rolls the whole remap back. They are
     * rebuilt instead.
     */
    private const PIVOTS = [
        'category_user'  => 'user_id',
        'category_event' => 'event_id',
    ];

    public function handle(): int
    {
        if (Category::anyTaxonomy()->where('taxonomy_version', 'v2')->doesntExist()) {
            $this->error('No v2 rows found. Run `php artisan taxonomy:import-v2` first.');

            return self::FAILURE;
        }

        if ($this->option('remap')) {
            $this->remap();
        }

        $stranded = $this->stranded();
        $this->reportStranded($stranded);

        if ($this->option('check')) {
            return self::SUCCESS;
        }

        if (array_sum($stranded) > 0) {
            $this->newLine();
            $this->error('Not switching — the links above would point at categories the site can no longer see.');
            $this->line('Fix ' . self::MAP_PATH . ', then run this again with --remap.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Everything points at v2. To finish, set TAXONOMY_VERSION=v2 in .env and clear the config cache:');
        $this->line('  php artisan config:clear');

        return self::SUCCESS;
    }

    /** How many rows in each dependent table still point at a v1 category. */
    private function stranded(): array
    {
        $v1 = Category::anyTaxonomy()->where('taxonomy_version', 'v1')->pluck('id');

        $counts = [];
        foreach (self::DEPENDENTS as $table => $column) {
            $counts[$table] = DB::table($table)->whereIn($column, $v1)->count();
        }

        return $counts;
    }

    private function reportStranded(array $stranded): void
    {
        $this->newLine();
        $this->table(
            ['Still pointing at the old tree', 'Rows'],
            collect($stranded)->map(fn ($n, $t) => [$t, $n])->values()->all(),
        );
    }

    /**
     * Point each link at the v2 category its old one maps to. Runs in a
     * transaction: a half-remapped database is worse than an unremapped one.
     */
    private function remap(): void
    {
        $path = base_path(self::MAP_PATH);

        if (! is_file($path)) {
            $this->error('Mapping file not found: ' . self::MAP_PATH);

            return;
        }

        $map = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR)['map'] ?? [];

        $v2 = Category::anyTaxonomy()->where('taxonomy_version', 'v2')
            ->whereIn('kind', [Category::SERVICE_CATEGORY, Category::EVENT_TYPE])
            ->pluck('id', 'name');

        $missing = collect($map)->reject(fn ($to) => $v2->has($to));
        if ($missing->isNotEmpty()) {
            $this->error('These targets do not exist in v2:');
            $missing->each(fn ($to, $from) => $this->line("  {$from} → {$to}"));

            return;
        }

        // Matched on the lowercased name. The old tree holds the same category
        // under several spellings — "Food Services" and "Food services" are
        // separate rows, each with professionals attached — and a
        // case-sensitive match left the lowercase ones behind with no category
        // at all, which is the very failure the switch exists to prevent.
        $lookup = collect($map)->mapWithKeys(fn ($to, $from) => [mb_strtolower($from) => $to]);

        $moved = 0;
        $unmapped = collect();

        DB::transaction(function () use ($lookup, $v2, &$moved, &$unmapped) {
            $old = Category::anyTaxonomy()->where('taxonomy_version', 'v1')->get(['id', 'name']);

            // old category id => new category id, for the ones we can place
            $redirect = [];

            foreach ($old as $category) {
                $target = $lookup->get(mb_strtolower($category->name));

                if ($target === null) {
                    $unmapped->push($category);

                    continue;
                }

                $redirect[$category->id] = $v2->get($target);
            }

            if ($redirect === []) {
                return;
            }

            $oldIds = array_keys($redirect);

            // Pivots: collect the pairs, redirect them, drop the duplicates a
            // many-to-one move creates, then write them back.
            foreach (self::PIVOTS as $table => $ownerColumn) {
                $pairs = DB::table($table)
                    ->whereIn('category_id', $oldIds)
                    ->get([$ownerColumn, 'category_id']);

                if ($pairs->isEmpty()) {
                    continue;
                }

                $rebuilt = $pairs
                    ->map(fn ($row) => [
                        $ownerColumn  => $row->$ownerColumn,
                        'category_id' => $redirect[$row->category_id],
                    ])
                    ->unique(fn (array $row) => $row[$ownerColumn] . ':' . $row['category_id'])
                    // Skip any pair the owner already has on the new tree.
                    ->reject(fn (array $row) => DB::table($table)
                        ->where($ownerColumn, $row[$ownerColumn])
                        ->where('category_id', $row['category_id'])
                        ->exists())
                    ->values();

                DB::table($table)->whereIn('category_id', $oldIds)->delete();

                if ($rebuilt->isNotEmpty()) {
                    DB::table($table)->insert($rebuilt->all());
                }

                $moved += $rebuilt->count();
            }

            // Plain foreign keys — no pair to collide, so a straight update.
            foreach (array_diff_key(self::DEPENDENTS, self::PIVOTS) as $table => $column) {
                foreach ($redirect as $from => $to) {
                    $moved += DB::table($table)->where($column, $from)->update([$column => $to]);
                }
            }
        });

        $this->info("Re-homed {$moved} link(s) onto v2.");

        // Only worth reporting the ones something still points at — the old
        // tree has hundreds of categories nobody ever used.
        $stillUsed = $unmapped
            ->filter(fn ($category) => collect(self::DEPENDENTS)
                ->contains(fn ($col, $table) => DB::table($table)->where($col, $category->id)->exists()))
            ->pluck('name')
            ->unique();

        if ($stillUsed->isNotEmpty()) {
            $this->warn('These old categories are still in use but have no entry in the mapping file:');
            $stillUsed->each(fn ($name) => $this->line("  {$name}"));
        }
    }
}
