<?php

namespace Tests\Feature;

use App\Http\Controllers\Public\PackageController;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * OA-135 — the package Services filter invented its own categories.
 *
 * It listed Photography, Videography, Floral Design, Catering / Food, Decor &
 * Design, Lighting & Tech, DJ / Entertainment, Planning / Coordination,
 * Rentals, Transportation, Beauty & Hair, Invitations / Stationery. Not one is
 * a locked Level 2 category.
 *
 * Locked Owner Rule #2 fixes 27 categories that may not be added, removed or
 * renamed without Owner sign-off, and this list quietly did all three: it
 * split "Photography & Videography" into two, renamed "Decor, Floral & Balloon
 * Design" twice over, and invented Rentals and Transportation outright.
 *
 * The filter reads the taxonomy now, so it cannot drift from the rule again.
 */
class PackageFilterUsesLockedCategoriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('packages.service-filter.level2');
    }

    private function level2(string $name): Category
    {
        return Category::create([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'kind' => Category::SERVICE_CATEGORY,
            'is_active' => true,
        ]);
    }

    public function test_the_filter_comes_from_the_taxonomy(): void
    {
        $this->level2('Photography & Videography');
        $this->level2('Catering & Food Services');

        $this->assertSame(
            ['Catering & Food Services', 'Photography & Videography'],
            PackageController::services(),
        );
    }

    /** The invented names are gone, including the ones that look plausible. */
    public function test_none_of_the_invented_names_survive(): void
    {
        $this->level2('Photography & Videography');
        $this->level2('Decor, Floral & Balloon Design');

        $services = PackageController::services();

        foreach (['Photography', 'Videography', 'Floral Design', 'Decor & Design',
            'Lighting & Tech', 'Rentals', 'Transportation', 'Catering / Food'] as $invented) {
            $this->assertNotContains($invented, $services, "{$invented} is not a locked category");
        }
    }

    /**
     * A category the Owner adds appears without anybody editing a constant —
     * which is the half of Rule #2 a hardcoded list quietly broke in the other
     * direction.
     */
    public function test_a_new_locked_category_appears_on_its_own(): void
    {
        $this->level2('Catering & Food Services');
        $this->assertCount(1, PackageController::services());

        Cache::forget('packages.service-filter.level2');
        $this->level2('Security & Crowd Management');

        $this->assertContains('Security & Crowd Management', PackageController::services());
    }

    /** An inactive category is not offered as a filter. */
    public function test_an_inactive_category_is_not_offered(): void
    {
        $this->level2('Catering & Food Services');
        $this->level2('Retired Category')->update(['is_active' => false]);

        Cache::forget('packages.service-filter.level2');

        $this->assertNotContains('Retired Category', PackageController::services());
    }
}
