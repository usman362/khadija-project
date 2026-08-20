<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * The live category tree.
 *
 * V2 — 106 event types, 27 service categories, 241 services — is what
 * TAXONOMY_VERSION points at, and it was only ever created by running
 * `php artisan taxonomy:import-v2` by hand. `db:seed` on a fresh server
 * therefore produced a database with no live categories at all: the browse
 * pages were empty and DemoGigsSeeder skipped itself with "V2 taxonomy not
 * seeded".
 *
 * The import matches on slug and updates in place, so running this repeatedly
 * is safe. Pruning is deliberately not passed: it deletes rows the sheet has
 * stopped listing, and a deploy is not the moment to discover a half-written
 * sheet.
 */
class TaxonomyV2Seeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('taxonomy:import-v2');

        $this->command?->info(trim(Artisan::output()));
    }
}
