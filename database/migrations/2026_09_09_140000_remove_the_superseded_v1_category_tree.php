<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The old four-level category tree goes.
 *
 * Sir Peter, 2026-09-09: "clear out L4 so we can get that over with."
 *
 * Levels 1–4 were v1 — 360 rows imported from the old live site. v2 replaced
 * it with three levels that mean something to a client: event type (106),
 * service category (27), service (241). Nothing on the site has read a v1 row
 * since the switch; they have simply been sitting there, all marked active,
 * waiting for somebody to wire something to them by mistake.
 *
 * Safety, because production is not this database:
 *
 *  · Only rows whose taxonomy_version is v1 AND whose kind is empty. A v2 row
 *    cannot match either test.
 *  · Nothing is deleted while anything points at it. Every table that carries
 *    a category id is checked at run time, and a referenced row is kept and
 *    reported rather than cascaded away — a category deleted out from under a
 *    booking is worse than a category nobody reads.
 *  · Deepest first, so a parent is never removed before its children.
 *
 * The seeder that holds this tree already refuses to run on a v2 site, so
 * nothing puts them back.
 */
return new class extends Migration
{
    /** Every column that names a category, so "unused" is actually checked. */
    private const REFERENCES = [
        ['bids', 'category_id'],
        ['bookings', 'category_id'],
        ['category_event', 'category_id'],
        ['category_relevance', 'category_id'],
        ['category_user', 'category_id'],
        ['dispute_cases', 'category_id'],
        ['event_service_budgets', 'category_id'],
        ['events', 'category_id'],
        ['finalizations', 'category_id'],
        ['packages', 'category_id'],
    ];

    public function up(): void
    {
        if (! Schema::hasColumn('categories', 'taxonomy_version')) {
            return;
        }

        // Only ever the superseded tree, never the one in use.
        if (config('taxonomy.version') === 'v1') {
            return;
        }

        $legacy = DB::table('categories')
            ->where('taxonomy_version', 'v1')
            ->whereRaw("COALESCE(kind, '') = ''")
            ->pluck('id');

        if ($legacy->isEmpty()) {
            return;
        }

        $spoken = collect();

        foreach (self::REFERENCES as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $spoken = $spoken->merge(
                DB::table($table)->whereIn($column, $legacy)->pluck($column)
            );
        }

        // A v2 row parented to a legacy one would be orphaned by this.
        $spoken = $spoken->merge(
            DB::table('categories')
                ->whereIn('parent_id', $legacy)
                ->whereRaw("COALESCE(kind, '') <> ''")
                ->pluck('parent_id')
        );

        $keep   = $spoken->unique()->values();
        $remove = $legacy->diff($keep);

        if ($remove->isEmpty()) {
            return;
        }

        /*
         * Deepest first. parent_id points within the same table, so removing a
         * parent before its children either fails on the constraint or leaves
         * children pointing at nothing, depending on how the column was built.
         */
        $byDepth = [];
        $depth   = [];

        foreach (DB::table('categories')->whereIn('id', $remove)->get(['id', 'parent_id']) as $row) {
            $depth[$row->id] = $row->parent_id;
        }

        $levelOf = function ($id) use (&$depth, &$levelOf) {
            $n = 0;
            $seen = [];

            while (isset($depth[$id]) && $depth[$id] !== null && ! isset($seen[$id])) {
                $seen[$id] = true;
                $id = $depth[$id];
                $n++;
            }

            return $n;
        };

        foreach ($remove as $id) {
            $byDepth[$levelOf($id)][] = $id;
        }

        krsort($byDepth);

        foreach ($byDepth as $ids) {
            DB::table('categories')->whereIn('id', $ids)->delete();
        }
    }

    /**
     * Not reversible.
     *
     * The tree lives in CategorySeeder, which is where it would come back from
     * — deliberately, with SEED_LEGACY_TAXONOMY=true — rather than from a
     * rollback that would restore rows with new ids and match nothing.
     */
    public function down(): void
    {
        // Intentionally empty.
    }
};
