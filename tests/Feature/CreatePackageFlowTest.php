<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\PackageProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Create a Package — the five-step flow.
 *
 * The mockup's own annotations ask for two things to be settled before this
 * ships: what the readiness percentage is actually counting, and whether "Save
 * as Draft" is one feature or four. Both are pinned here, along with the fields
 * the flow gained.
 */
class CreatePackageFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function pro(): User
    {
        $pro = User::factory()->create();
        $pro->assignRole('professional');
        UserProfile::updateOrCreate(['user_id' => $pro->id], [
            'city' => 'Baltimore', 'state' => 'MD', 'country' => 'US',
        ]);

        return $pro->fresh();
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'title'       => 'Elegant Wedding Photo & Video',
            'purpose'     => 'Complete wedding photography and videography',
            'description' => 'Complete visual storytelling on one timeline.',
            'services'    => ['Photography', 'Videography'],
            'price'       => 3250,
            'price_unit'  => 'from',
            'coverage'    => 'Up to 10 Hours',
            'guest_min'   => 50,
            'guest_max'   => 150,
            'is_active'   => 1,
        ], $over);
    }

    public function test_the_form_offers_the_five_steps(): void
    {
        $this->actingAs($this->pro())->get(route('professional.packages.create'))
            ->assertOk()
            ->assertSee('Package Details')
            ->assertSee('Services &amp; Deliverables', false)
            ->assertSee('Pricing &amp; Add-Ons', false)
            ->assertSee('Availability &amp; Terms', false)
            ->assertSee('Review &amp; Publish', false);
    }

    // ── The fields the flow gained ───────────────────────────────

    public function test_the_purpose_line_saves_and_reaches_the_client(): void
    {
        // A field that is stored and never rendered is a field that goes
        // nowhere, which is the defect this project keeps having.
        $pro = $this->pro();

        $this->actingAs($pro)->post(route('professional.packages.store'), $this->payload())
            ->assertRedirect();

        $package = Package::firstOrFail();
        $this->assertSame('Complete wedding photography and videography', $package->purpose);

        $this->get(route('public.package', $package->slug))
            ->assertOk()
            ->assertSee('Complete wedding photography and videography');
    }

    public function test_the_category_chosen_on_the_form_is_the_one_saved(): void
    {
        // The form was handed $categories and never drew them, so category_id
        // could only ever be set by a seeder — and it picks the stand-in hero
        // image and the "more like this" row on the public page.
        $pro = $this->pro();
        $category = \App\Models\Category::create([
            'name' => 'Wedding Photography', 'slug' => 'wedding-photography-test', 'is_active' => true,
        ]);

        $this->actingAs($pro)->post(route('professional.packages.store'),
            $this->payload(['category_id' => $category->id]))->assertRedirect();

        $this->assertSame($category->id, Package::firstOrFail()->category_id);

        $this->actingAs($pro)->get(route('professional.packages.create'))
            ->assertOk()
            ->assertSee('Wedding Photography');
    }

    public function test_the_name_limit_matches_the_counter_the_form_shows(): void
    {
        // The field counts up to 60, so 61 has to be refused — a counter that
        // is not the rule is decoration.
        $this->actingAs($this->pro())
            ->post(route('professional.packages.store'), $this->payload(['title' => str_repeat('a', 61)]))
            ->assertSessionHasErrors('title');

        $this->actingAs($this->pro())
            ->post(route('professional.packages.store'), $this->payload(['title' => str_repeat('a', 60)]))
            ->assertSessionHasNoErrors();
    }

    public function test_the_description_takes_the_five_hundred_the_counter_allows(): void
    {
        $this->actingAs($this->pro())
            ->post(route('professional.packages.store'), $this->payload(['description' => str_repeat('a', 500)]))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->pro())
            ->post(route('professional.packages.store'), $this->payload(['description' => str_repeat('a', 501)]))
            ->assertSessionHasErrors('description');
    }

    // ── The readiness number (the mockup's open question #2) ─────

    public function test_the_readiness_ring_is_the_same_calculation_as_the_shelf(): void
    {
        /*
         * Peter's note on the mockup says the completion maths needs defining
         * and that "32%" does not follow from one of eight items. It is now one
         * row per fillable step, and it is PackageProgress — the same function
         * the My Packages progress bar uses — so the two screens cannot quote
         * different numbers for one package.
         */
        $pro = $this->pro();
        $half = Package::create([
            'user_id' => $pro->id, 'title' => 'Half done', 'slug' => 'half-done', 'type' => 'solo',
            'description' => 'Something.', 'services' => ['Photography'], 'price' => 0,
            'status' => 'draft', 'is_active' => false,
        ]);

        $this->assertSame(25, PackageProgress::percent($half));

        $this->actingAs($pro)->get(route('professional.packages.edit', $half))
            ->assertOk()
            ->assertSee('>25%<', false);
    }

    public function test_the_ring_does_not_count_publishing_as_something_to_fill_in(): void
    {
        // If it did, a finished draft could never reach 100% and so could never
        // be "Ready to Publish" on the shelf.
        $pro = $this->pro();
        $done = Package::create([
            'user_id' => $pro->id, 'title' => 'All done', 'slug' => 'all-done', 'type' => 'solo',
            'description' => 'Something.', 'services' => ['Photography', 'Videography'],
            'price' => 2000, 'coverage' => 'Up to 8 Hours', 'status' => 'draft', 'is_active' => false,
        ]);

        $this->assertSame(100, PackageProgress::percent($done));
        $this->assertSame('ready', PackageProgress::shelfState($done));

        $this->actingAs($pro)->get(route('professional.packages.edit', $done))
            ->assertOk()->assertSee('>100%<', false);
    }

    // ── Save as Draft is ONE feature (the mockup's open question #4) ──

    public function test_there_is_exactly_one_save_as_draft_control(): void
    {
        /*
         * The mockup shows "Save as Draft" in four places. The card at the foot
         * of the page presses the header's button rather than being a second
         * submit that might behave differently.
         */
        $body = $this->actingAs($this->pro())->get(route('professional.packages.create'))
            ->assertOk()->getContent();

        $body = preg_replace('/\{\{--.*?--\}\}/s', '', $body);

        $this->assertSame(1, substr_count($body, 'name="is_active" value="0"'),
            'more than one control submits a draft');
    }

    public function test_the_page_never_claims_work_is_being_auto_saved(): void
    {
        // There is no autosave. "Auto-saved 2 min ago" would be a claim about
        // whether the professional's work is safe.
        $body = $this->actingAs($this->pro())->get(route('professional.packages.create'))
            ->assertOk()->getContent();

        $body = preg_replace('/\{\{--.*?--\}\}/s', '', $body);

        foreach (['auto-saved', 'autosave', 'automatically saved'] as $claim) {
            $this->assertStringNotContainsStringIgnoringCase($claim, $body);
        }
    }

    public function test_the_page_states_no_estimated_times(): void
    {
        // The mockup labels each step "Est. time: 15–20 min". Nobody has
        // measured that, and it is a promise about somebody's evening.
        $body = $this->actingAs($this->pro())->get(route('professional.packages.create'))
            ->assertOk()->getContent();

        $body = preg_replace('/\{\{--.*?--\}\}/s', '', $body);

        foreach (['Est. time', 'estimated time', '15-20 min', '15–20 min'] as $claim) {
            $this->assertStringNotContainsStringIgnoringCase($claim, $body);
        }
    }
}
