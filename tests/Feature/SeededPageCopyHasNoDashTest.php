<?php

namespace Tests\Feature;

use App\Models\PageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The home page's seeded copy loses its dash on deploy — and an admin's own
 * edit of it does not get overwritten.
 */
class SeededPageCopyHasNoDashTest extends TestCase
{
    use RefreshDatabase;

    private const OLD = "You're in control. Each level unlocks more capability — from fully manual to fully automated.";
    private const NEW = "You're in control. Each level unlocks more capability, from fully manual to fully automated.";

    private function migration()
    {
        return require database_path('migrations/2026_09_10_180000_drop_spaced_dash_from_seeded_page_copy.php');
    }

    private function section(string $key, string $subheading): int
    {
        return DB::table('page_sections')->insertGetId([
            'page' => 'home', 'key' => $key, 'subheading' => $subheading,
            'is_active' => true, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_the_seeded_line_is_rewritten(): void
    {
        $id = $this->section('assistance', self::OLD);

        $this->migration()->up();

        $this->assertSame(self::NEW, DB::table('page_sections')->where('id', $id)->value('subheading'));
    }

    public function test_an_admins_own_wording_is_left_alone(): void
    {
        $id = $this->section('assistance', 'Our own words — chosen by the admin.');

        $this->migration()->up();

        $this->assertSame('Our own words — chosen by the admin.', DB::table('page_sections')->where('id', $id)->value('subheading'));
    }

    /** The sections are cached; a rewrite nobody sees until the cache expires is not a fix. */
    public function test_the_cache_is_cleared_so_the_site_shows_it(): void
    {
        $this->section('assistance', self::OLD);
        PageSection::cached();   // warm it with the old line

        $this->migration()->up();

        $this->assertSame(self::NEW, PageSection::cached()->firstWhere('key', 'assistance')?->subheading);
    }

    public function test_the_seeder_writes_the_new_line(): void
    {
        $src = file_get_contents(database_path('seeders/PageSectionSeeder.php'));

        $this->assertStringContainsString(self::NEW, $src);
        $this->assertStringNotContainsString(self::OLD, $src);
    }
}
