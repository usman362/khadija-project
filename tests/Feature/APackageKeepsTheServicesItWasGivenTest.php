<?php

namespace Tests\Feature;

use App\Http\Controllers\Public\PackageController;
use App\Models\{Category, Package, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Step 1 of the package builder asks a professional to tick their own
 * services. Every tick was thrown away on save.
 *
 * The save intersected the submitted names against
 * PackageController::services(), which is the 27 CATEGORY names the client's
 * browse filter is built from, not the level-3 services the form offers. So a
 * professional who had listed their services built a package, chose two, and
 * saved one tagged with nothing: invisible in Package Search, and unable to
 * meet the "two or more services" rule the product rests on. On real data, of
 * "Professional Bartenders" and "Buffet Catering", zero survived.
 *
 * The other half is that a client's filter has to keep working once packages
 * are tagged this way, which means matching a category to the services under
 * it rather than to its own name.
 */
class APackageKeepsTheServicesItWasGivenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        config(['taxonomy.version' => 'v2']);
        $this->artisan('taxonomy:import-v2')->assertSuccessful();
    }

    private function pro(): User
    {
        $u = User::factory()->create(['primary_role' => 'professional']);
        $u->assignRole('professional');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    public function test_the_services_a_professional_ticks_are_the_ones_saved(): void
    {
        $pro = $this->pro();

        $this->actingAs($pro)->post(route('professional.packages.store'), [
            'title' => 'Dinner and drinks',
            'category_id' => Category::where('kind', Category::SERVICE_CATEGORY)->first()->id,
            // Real level-3 services, which is what step 1 offers.
            'services' => ['Buffet Catering', 'Professional Bartenders'],
            'price' => 2400,
            'price_unit' => 'flat',
            'publish' => 1,
        ]);

        $package = Package::where('user_id', $pro->id)->latest('id')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            ['Buffet Catering', 'Professional Bartenders'],
            $package->services,
            'the package saved with nothing on it, so Package Search will never show it',
        );
    }

    public function test_a_category_name_is_still_accepted(): void
    {
        // The older path: a professional who has listed nothing is shown the
        // 27 categories instead, and that save has to keep working.
        $pro = $this->pro();

        $this->actingAs($pro)->post(route('professional.packages.store'), [
            'title' => 'Two categories',
            'category_id' => Category::where('kind', Category::SERVICE_CATEGORY)->first()->id,
            'services' => ['Catering & Food Services', 'Photography & Videography'],
            'price' => 2400,
            'price_unit' => 'flat',
            'publish' => 1,
        ]);

        $package = Package::where('user_id', $pro->id)->latest('id')->firstOrFail();

        $this->assertCount(2, $package->services);
    }

    public function test_an_invented_service_is_still_refused(): void
    {
        $pro = $this->pro();

        $this->actingAs($pro)->post(route('professional.packages.store'), [
            'title' => 'Made up',
            'category_id' => Category::where('kind', Category::SERVICE_CATEGORY)->first()->id,
            'services' => ['Buffet Catering', 'Unicorn Wrangling'],
            'price' => 2400,
            'price_unit' => 'flat',
        ]);

        $package = Package::where('user_id', $pro->id)->latest('id')->firstOrFail();

        $this->assertSame(['Buffet Catering'], $package->services);
    }

    public function test_an_influencer_service_cannot_be_put_in_a_package(): void
    {
        $pro = $this->pro();

        $influencerOnly = Category::where('kind', Category::SERVICE)
            ->where('eligibility', Category::ELIGIBLE_INFLUENCER)
            ->value('name');

        $this->actingAs($pro)->post(route('professional.packages.store'), [
            'title' => 'Not mine to sell',
            'category_id' => Category::where('kind', Category::SERVICE_CATEGORY)->first()->id,
            'services' => ['Buffet Catering', $influencerOnly],
            'price' => 2400,
            'price_unit' => 'flat',
        ]);

        $package = Package::where('user_id', $pro->id)->latest('id')->firstOrFail();

        $this->assertSame(['Buffet Catering'], $package->services);
    }

    // ── and the client can still find it ─────────────────────

    public function test_a_category_filter_finds_a_package_tagged_with_its_services(): void
    {
        $names = PackageController::namesUnder('Catering & Food Services');

        $this->assertContains('Catering & Food Services', $names, 'older packages are tagged this way');
        $this->assertContains('Buffet Catering', $names, 'and newer ones are tagged with the service');
    }

    public function test_a_package_tagged_with_a_service_shows_under_its_category(): void
    {
        $pro = $this->pro();

        Package::create([
            'user_id' => $pro->id,
            'category_id' => Category::where('kind', Category::SERVICE_CATEGORY)->first()->id,
            'title' => 'Dinner and drinks', 'slug' => 'dinner-' . uniqid(),
            'type' => 'solo', 'price' => 2400, 'price_unit' => 'flat',
            'services' => ['Buffet Catering', 'Professional Bartenders'],
            'event_types' => ['Wedding'], 'state' => 'MD',
            'is_active' => true, 'status' => 'active',
        ]);

        $page = $this->get(route('public.packages', ['services' => ['Catering & Food Services']]))
            ->assertOk();

        $page->assertSee('Dinner and drinks');
    }
}
