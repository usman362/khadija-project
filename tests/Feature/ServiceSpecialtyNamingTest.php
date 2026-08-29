<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Peter, 2026-08-29 — Level 4 is a **Service Specialty**, and there is ONE list.
 *
 *   Level 1 Event  →  Level 2 Service Category  →  Level 3 Service
 *                                               →  Level 4 Service Specialty
 *
 * A specialty is a narrower way of doing the level 3 service: "DJ" carries
 * "Wedding DJ", "Party DJ", "Corporate DJ", "Karaoke DJ". Only where the
 * specialization is meaningful — a level 3 service is not required to have any.
 *
 * The naming matters because "keywords" read as the paid Search Visibility
 * feature, and that reading invites a second, duplicate list of the same terms.
 * Paid Search Visibility REFERENCES eligible specialty rows; it does not copy
 * them. This file exists to stop that second list from ever being started.
 */
class ServiceSpecialtyNamingTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_four_is_a_service_specialty(): void
    {
        $this->assertSame('service_specialty', Category::SERVICE_SPECIALTY);
        $this->assertFalse(
            defined(Category::class . '::COMPONENT'),
            'Level 4 is a Service Specialty; the old COMPONENT name is retired.'
        );
    }

    public function test_the_four_tiers_are_the_ones_peter_named(): void
    {
        $this->assertSame('event_type', Category::EVENT_TYPE);
        $this->assertSame('service_category', Category::SERVICE_CATEGORY);
        $this->assertSame('service', Category::SERVICE);
        $this->assertSame('service_specialty', Category::SERVICE_SPECIALTY);
    }

    /**
     * There is no second store of the same terms. Search Visibility, when it is
     * built, points at these rows — it does not get a table of its own.
     */
    public function test_specialties_are_stored_once_in_the_taxonomy(): void
    {
        foreach (['keywords', 'search_keywords', 'search_visibility_keywords', 'service_keywords'] as $table) {
            $this->assertFalse(
                \Illuminate\Support\Facades\Schema::hasTable($table),
                "A second list of the same terms was started in `{$table}`. Level 4 specialties are stored once, in categories."
            );
        }
    }

    /** A level 3 service with no specialties is normal, not a gap. */
    public function test_a_service_may_carry_no_specialties(): void
    {
        $service = Category::create([
            'name' => 'Balloon Artistry', 'slug' => 'balloon-artistry', 'kind' => Category::SERVICE,
        ]);

        $this->assertCount(0, $service->children);
    }

    public function test_a_specialty_hangs_off_its_service(): void
    {
        $dj = Category::create(['name' => 'DJ', 'slug' => 'dj-l3', 'kind' => Category::SERVICE]);

        foreach (['Wedding DJ', 'Party DJ', 'Corporate DJ', 'Karaoke DJ'] as $i => $name) {
            Category::create([
                'name' => $name, 'slug' => \Illuminate\Support\Str::slug("dj-{$name}"),
                'kind' => Category::SERVICE_SPECIALTY, 'parent_id' => $dj->id, 'sort_order' => $i + 1,
            ]);
        }

        $this->assertCount(4, $dj->refresh()->children);
        $this->assertSame('Wedding DJ', $dj->children->first()->name);
        $this->assertSame(Category::SERVICE, $dj->children->first()->parent->kind);
    }
}
