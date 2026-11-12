<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter's Master Service Classification, 8 November.
 *
 * His list is a faithful export of this tree: all 241 service titles match
 * ours exactly and every one sits under the category it already sat under.
 * What it adds is a column nobody had, saying who may offer a service, and
 * fifteen services for a side of the site that had none.
 *
 * His four instructions, in his words:
 *   "keep the existing 241 service titles unchanged"
 *   "add the 15 new Influencer service titles as proposals for approval"
 *   "allow clients to search for and hire either Professionals or Influencers"
 *   "allow users who qualify for both roles to offer services under both roles"
 *
 * These hold the first two and the shape of the fourth. The third is the
 * hiring flow, which does not exist yet.
 */
class TheMasterServiceClassificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        /*
         * The tree itself, which migrations do not build.
         *
         * This is the order that matters: on a fresh database the
         * classification's migration runs before any of the 241 exist, so it
         * marks nothing, and the import has to apply the classification as
         * well. Importing here rather than hand-building rows is what proves
         * that it does.
         */
        config(['taxonomy.version' => 'v2']);
        $this->artisan('taxonomy:import-v2')->assertSuccessful();
    }

    private function services(string $eligibility): int
    {
        return Category::anyTaxonomy()
            ->where('kind', Category::SERVICE)
            ->where('taxonomy_version', 'v2')
            ->where('eligibility', $eligibility)
            ->count();
    }

    public function test_the_three_groups_are_the_sizes_he_gave(): void
    {
        $this->assertSame(213, $this->services(Category::ELIGIBLE_PROFESSIONAL));
        $this->assertSame(28, $this->services(Category::ELIGIBLE_BOTH));
        $this->assertSame(15, $this->services(Category::ELIGIBLE_INFLUENCER));
    }

    public function test_the_241_existing_titles_are_untouched(): void
    {
        // His first instruction. The classification adds; it does not rename,
        // move or remove, and 213 + 28 is the list he started from.
        $this->assertSame(
            241,
            $this->services(Category::ELIGIBLE_PROFESSIONAL) + $this->services(Category::ELIGIBLE_BOTH),
        );

        foreach (['Valet Parking Services', 'Wedding Photography', '360 Photo Booths'] as $name) {
            $this->assertTrue(
                Category::anyTaxonomy()->where('kind', Category::SERVICE)->where('name', $name)->exists(),
                "{$name} went missing",
            );
        }
    }

    public function test_the_fifteen_are_proposals_and_not_live(): void
    {
        $proposals = Category::anyTaxonomy()
            ->where('kind', Category::SERVICE)
            ->where('eligibility', Category::ELIGIBLE_INFLUENCER)
            ->get();

        // "Add the 15 new Influencer service titles as proposals for
        // approval." A proposal that is already on offer is not a proposal.
        $this->assertCount(15, $proposals);
        $this->assertTrue($proposals->every(fn ($s) => $s->is_active === false));
    }

    public function test_they_sit_under_a_category_of_their_own(): void
    {
        $category = Category::anyTaxonomy()
            ->where('kind', Category::SERVICE_CATEGORY)
            ->where('name', 'Influencer Marketing & Promotion')
            ->first();

        $this->assertNotNull($category);
        $this->assertSame(Category::ELIGIBLE_INFLUENCER, $category->eligibility);
        $this->assertSame(15, Category::anyTaxonomy()->where('parent_id', $category->id)->count());

        // And inactive, like everything in it: a client choosing services for
        // a wedding was being offered a category with nothing behind it.
        $this->assertFalse((bool) $category->is_active);
    }

    // ── who sees what ────────────────────────────────────────

    public function test_a_professional_is_offered_their_own_plus_the_shared(): void
    {
        $count = Category::anyTaxonomy()
            ->where('kind', Category::SERVICE)
            ->offerableBy('professional')
            ->count();

        $this->assertSame(241, $count, 'a professional may offer the 213 and the 28');
    }

    public function test_an_influencer_is_offered_theirs_plus_the_shared(): void
    {
        $count = Category::anyTaxonomy()
            ->where('kind', Category::SERVICE)
            ->offerableBy('influencer')
            ->count();

        $this->assertSame(43, $count, 'an influencer may offer the 15 and the 28');
    }

    public function test_a_client_searching_is_shown_everything(): void
    {
        // A client is not offering the service, they are looking for somebody
        // who does, so nothing is filtered out from under them.
        $this->assertSame(
            256,
            Category::anyTaxonomy()->where('kind', Category::SERVICE)->offerableBy(null)->count(),
        );
    }

    public function test_the_shared_twenty_eight_belong_to_both(): void
    {
        // The point of the middle group, and the shape of his fourth
        // instruction: one row, offered from either side.
        $shared = Category::anyTaxonomy()->where('name', 'Wedding Photography')->first();

        $this->assertTrue($shared->offerableBy('professional'));
        $this->assertTrue($shared->offerableBy('influencer'));
    }

    // ── and what that stops ──────────────────────────────────

    public function test_a_professional_cannot_take_on_an_influencer_service(): void
    {
        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $pro = User::findOrFail($pro->id);

        $theirs = Category::anyTaxonomy()->where('kind', Category::SERVICE)
            ->where('eligibility', Category::ELIGIBLE_PROFESSIONAL)->first();
        $notTheirs = Category::anyTaxonomy()->where('kind', Category::SERVICE)
            ->where('eligibility', Category::ELIGIBLE_INFLUENCER)->first();

        // The picker does not show it, so this is the back door: posting the
        // id directly. A caterer does not offer Social Media Takeovers.
        $this->actingAs($pro)->patch(route('professional.profile.update.services'), [
            'services' => [$theirs->id, $notTheirs->id],
        ]);

        $saved = $pro->serviceCategories()->pluck('categories.name')->all();

        $this->assertContains($theirs->name, $saved);
        $this->assertNotContains($notTheirs->name, $saved);
    }

    public function test_the_influencer_category_is_not_a_package_filter(): void
    {
        // A package is a professional's bundle of their own services, and
        // this list is also what a package's services are validated against.
        $this->assertNotContains(
            'Influencer Marketing & Promotion',
            \App\Http\Controllers\Public\PackageController::services(),
        );
    }
}
