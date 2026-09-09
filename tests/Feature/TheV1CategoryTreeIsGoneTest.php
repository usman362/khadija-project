<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The superseded four-level category tree is not in the database any more.
 *
 * Sir Peter, 2026-09-09: "clear out L4 so we can get that over with."
 *
 * Levels 1–4 were v1 — 360 rows imported from the old live site. v2 replaced
 * it with three levels that mean something to a client: event type, service
 * category, service. Nothing had read a v1 row since the switch; they sat
 * there marked active, waiting for somebody to wire something to them by
 * mistake.
 */
class TheV1CategoryTreeIsGoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_v1_rows_survive_a_migrated_database(): void
    {
        if (! Schema::hasColumn('categories', 'taxonomy_version')) {
            $this->markTestSkipped('No taxonomy_version column on this schema.');
        }

        $this->assertSame(
            0,
            DB::table('categories')->where('taxonomy_version', 'v1')->count(),
            'The old four-level tree is still here.',
        );
    }

    /** Every category left says which of the three levels it is. */
    public function test_every_category_has_a_kind(): void
    {
        $this->assertSame(
            0,
            DB::table('categories')->whereRaw("COALESCE(kind, '') = ''")->count(),
            'A category with no kind belongs to no level and is read by nothing.',
        );
    }

    /**
     * And the seeder that holds that tree still refuses to put it back.
     *
     * This is the trap that caught us before: rows were corrected and the next
     * seeder run laid the old copies straight back on top.
     */
    public function test_the_seeder_will_not_restore_it_on_a_v2_site(): void
    {
        $seeder = file_get_contents(database_path('seeders/CategorySeeder.php'));

        $this->assertStringContainsString('SEED_LEGACY_TAXONOMY', $seeder,
            'Nothing stops the v1 tree being seeded straight back onto a v2 site.');
        $this->assertStringContainsString("\$live !== \$version", $seeder);
    }
}
