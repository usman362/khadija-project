<?php

namespace Tests\Feature;

use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * `php artisan db:seed` is what gets run on the server, on every deploy, on a
 * database that already has data.
 *
 * Two things have to hold, and both have been broken in the past:
 *
 *   Every seeder is listed. A seeder somebody has to remember to run by hand
 *   is one that gets forgotten — TaxonomyV2Seeder did not exist at all, so a
 *   fresh server had no live category tree and half the site was blank.
 *
 *   Running it twice changes nothing. CategorySeeder used to delete every
 *   category and null out events.category_id before re-inserting, which on an
 *   empty database looks like "idempotent reseed" and on the server cuts every
 *   client's event loose from its category.
 */
class SeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    /** Tables a second seeding must not add rows to. */
    private function counts(): array
    {
        $out = [];

        foreach (Schema::getTableListing() as $table) {
            $name = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;

            if (in_array($name, ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'], true)) {
                continue;
            }

            $out[$name] = DB::table($name)->count();
        }

        return $out;
    }

    public function test_seeding_twice_does_not_duplicate_anything(): void
    {
        $this->seed();
        $first = $this->counts();

        $this->seed();
        $second = $this->counts();

        $grew = [];

        foreach ($first as $table => $n) {
            if (($second[$table] ?? $n) !== $n) {
                $grew[] = "{$table}: {$n} -> {$second[$table]}";
            }
        }

        $this->assertSame([], $grew,
            "these tables gain rows on a second db:seed:\n" . implode("\n", $grew));
    }

    public function test_seeding_actually_puts_something_in_the_database(): void
    {
        // A seeder that writes nothing is also perfectly idempotent, so the
        // test above cannot be trusted on its own.
        $this->seed();

        $this->assertGreaterThan(0, DB::table('users')->count());
        $this->assertGreaterThan(0, DB::table('categories')->count());
        $this->assertGreaterThan(0, DB::table('membership_plans')->count());
        $this->assertGreaterThan(0, DB::table('permissions')->count());
    }

    public function test_the_live_taxonomy_is_seeded_and_not_mixed_with_the_legacy_one(): void
    {
        /*
         * v1 is the 360-row import from the old live site; v2 is the tree
         * TAXONOMY_VERSION points at. CategorySeeder read the config to decide
         * which version to write, so the legacy rows landed in v2 and the
         * taxonomy had 700 rows in it. The version a seeder writes is a fact
         * about its data, not about which tree happens to be switched on.
         */
        $this->seed();

        $v1 = DB::table('categories')->where('taxonomy_version', 'v1')->count();
        $v2 = DB::table('categories')->where('taxonomy_version', 'v2')->count();

        $this->assertGreaterThan(0, $v1, 'the legacy tree carries the artwork and must be seeded');
        $this->assertGreaterThan(0, $v2, 'the live tree is empty — half the site will be blank');

        $this->assertSame(106, DB::table('categories')
            ->where('taxonomy_version', 'v2')->where('kind', 'event_type')->count());
    }

    public function test_the_artwork_reaches_the_live_tree(): void
    {
        // It is imported onto v1 and has to be carried across, and on a fresh
        // server the migration that does it runs before any category exists.
        $this->seed();

        $this->assertGreaterThan(0, DB::table('categories')
            ->where('taxonomy_version', 'v2')->whereNotNull('thumbnail')->count(),
            'no live category has a picture — the carry-over did not run');
    }

    public function test_every_seeder_is_reachable_from_db_seed(): void
    {
        /*
         * Read off the filesystem rather than a hand-kept list, because the
         * failure this guards against IS somebody adding a seeder and not
         * wiring it up.
         */
        $onDisk = collect(glob(database_path('seeders/*.php')))
            ->map(fn ($p) => 'Database\\Seeders\\' . basename($p, '.php'))
            ->reject(fn ($c) => in_array($c, [
                'Database\Seeders\DatabaseSeeder',
                // A backward-compatible alias for the two access-control
                // seeders, both of which DatabaseSeeder calls directly.
                'Database\Seeders\RolesTableSeeder',
            ], true))
            ->values();

        $called = $this->seedersCalledBy(\Database\Seeders\DatabaseSeeder::class);

        $missing = $onDisk->reject(fn ($c) => in_array($c, $called, true))->values()->all();

        $this->assertSame([], $missing,
            "these seeders exist but db:seed never runs them:\n" . implode("\n", $missing));
    }

    /** @return array<int, string> class names DatabaseSeeder passes to call() */
    private function seedersCalledBy(string $class): array
    {
        $source = file_get_contents((new \ReflectionClass($class))->getFileName());
        // Block comments need the s flag, line comments must NOT have it —
        // with it, `//.*` swallows the rest of the file and the list comes back
        // empty, which makes this test claim every seeder is unwired.
        $source = preg_replace('#/\*.*?\*/#s', '', $source);
        $source = preg_replace('#//.*#', '', $source);

        preg_match_all('/(\w+)::class/', $source, $m);

        return array_map(fn ($n) => 'Database\\Seeders\\' . $n, $m[1]);
    }
}
