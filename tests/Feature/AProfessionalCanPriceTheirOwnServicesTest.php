<?php

namespace Tests\Feature;

use App\Models\{Category, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What a professional charges, per service.
 *
 * They could always say which services they do. What any of them cost had
 * nowhere to live, and three things were waiting on it: Create a Package
 * asks them to pick from "your existing services" and shows a price beside
 * each one, Quick Bid answers a request with a number, and Sir Peter's rough
 * budget estimator was parked with the note that a price per service is a
 * field on their profile and nobody has one.
 */
class AProfessionalCanPriceTheirOwnServicesTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private Category $dj;
    private Category $photo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro = $this->person('professional', 'Hollis Tremaine');

        $this->dj    = $this->service('Wedding DJ', 'wedding-dj');
        $this->photo = $this->service('Event Photography', 'event-photography');

        $this->pro->syncServices([$this->dj->id, $this->photo->id]);
    }

    private function person(string $role, string $name): User
    {
        $u = User::factory()->create(['primary_role' => $role, 'name' => $name]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function service(string $name, string $slug): Category
    {
        return Category::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'kind' => Category::SERVICE, 'is_active' => true],
        );
    }

    private function priceOf(Category $service): ?string
    {
        return $this->pro->fresh()->serviceCategories()
            ->where('categories.id', $service->id)->first()?->pivot?->price;
    }

    public function test_the_page_lists_the_services_they_offer(): void
    {
        $this->actingAs($this->pro)->get(route('professional.services.index'))
            ->assertSuccessful()
            ->assertSee('Wedding DJ')
            ->assertSee('Event Photography');
    }

    /**
     * Sir Peter, 10 November: the service name belongs in the coloured band
     * and the category line under it comes out.
     *
     * His reason is a real one, and it is the whole point of the change: the
     * same service can sit in two categories — 360 Photo Booths under Photo
     * Booths, 360 Video Booths under Photography — and printing the category
     * under each made two different services look like one filed twice.
     */
    public function test_the_card_carries_the_name_and_not_the_category(): void
    {
        $parent = Category::firstOrCreate(
            ['slug' => 'entertainment-and-music'],
            ['name' => 'Entertainment & Music', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true],
        );

        $this->dj->update(['parent_id' => $parent->id]);

        $html = $this->actingAs($this->pro)->get(route('professional.services.index'))
            ->assertOk()->getContent();

        // The name, inside the band rather than under it.
        $this->assertMatchesRegularExpression(
            '/<span class="media"[^>]*>.*?<b class="name">Wedding DJ<\/b>/s',
            $html,
        );

        // And nothing printing the category beneath it.
        $this->assertStringNotContainsString('class="cat"', $html);
    }

    public function test_a_service_they_do_not_offer_is_not_in_their_list(): void
    {
        $other = $this->service('Catering', 'catering');

        $page = $this->actingAs($this->pro)->get(route('professional.services.index'))
            ->assertSuccessful();

        /*
         * It IS on the page, in the Add a service offering dropdown, which is
         * the whole point of that control. What it must not be is a row in
         * their own priced list.
         */
        $this->assertStringNotContainsString(
            'data-name="' . strtolower($other->name) . '"',
            $page->getContent()
        );
    }

    public function test_a_price_is_saved_against_the_service(): void
    {
        $this->actingAs($this->pro)->patch(route('professional.services.update'), [
            'rows' => [
                $this->dj->id => ['price' => '1200', 'price_unit' => 'from', 'duration_hours' => 4, 'extra_hour_price' => '150'],
            ],
        ])->assertRedirect();

        $pivot = $this->pro->fresh()->serviceCategories()
            ->where('categories.id', $this->dj->id)->firstOrFail()->pivot;

        // Compared as numbers: a pivot is not a cast model, so what comes
        // back is whatever the driver stored it as.
        $this->assertSame(1200.0, (float) $pivot->price);
        $this->assertSame('from', $pivot->price_unit);
        $this->assertSame(4, (int) $pivot->duration_hours);
        $this->assertSame(150.0, (float) $pivot->extra_hour_price);
    }

    /**
     * The ids come from the form, so a hand-edited one must not be able to
     * price a service this professional does not offer, or reach a row that
     * is not theirs.
     */
    public function test_a_price_cannot_be_written_against_a_service_they_do_not_offer(): void
    {
        $other = $this->service('Catering', 'catering');

        $this->actingAs($this->pro)->patch(route('professional.services.update'), [
            'rows' => [$other->id => ['price' => '9999']],
        ])->assertRedirect();

        $this->assertNull($this->priceOf($other));
    }

    /**
     * Re-saving the service list must not clear the prices.
     *
     * syncWithinKind detaches only what was removed and uses
     * syncWithoutDetaching for the rest, which leaves existing rows alone.
     * It is the reason the price can live on the pivot at all, so it is
     * worth holding.
     */
    public function test_choosing_services_again_keeps_the_prices(): void
    {
        $this->actingAs($this->pro)->patch(route('professional.services.update'), [
            'rows' => [$this->dj->id => ['price' => '1200']],
        ]);

        $this->pro->syncServices([$this->dj->id, $this->photo->id]);

        $this->assertSame(1200.0, (float) $this->priceOf($this->dj));
    }

    /** Dropping the service does drop its price, which is the right answer. */
    public function test_dropping_a_service_drops_its_price(): void
    {
        $this->actingAs($this->pro)->patch(route('professional.services.update'), [
            'rows' => [$this->dj->id => ['price' => '1200']],
        ]);

        $this->pro->syncServices([$this->photo->id]);

        $this->assertNull($this->priceOf($this->dj));
    }

    /** A blank price is "not priced yet", never "free". */
    public function test_a_blank_price_stays_blank(): void
    {
        $this->actingAs($this->pro)->patch(route('professional.services.update'), [
            'rows' => [$this->dj->id => ['price' => '', 'price_unit' => 'flat']],
        ]);

        $this->assertNull($this->priceOf($this->dj));
    }

    public function test_the_page_says_how_many_are_priced(): void
    {
        $this->actingAs($this->pro)->patch(route('professional.services.update'), [
            'rows' => [$this->dj->id => ['price' => '1200']],
        ]);

        $this->actingAs($this->pro)->get(route('professional.services.index'))
            ->assertSee('1 of 2 services have a price');
    }

    // ── Khadijah's 28 October screen ──────────────────────────────

    /** Adding a service was only possible on the profile. She asked for it here. */
    public function test_a_service_can_be_added_from_this_page(): void
    {
        $extra = $this->service('Photo Booth Hire', 'photo-booth-hire');

        $this->actingAs($this->pro)->post(route('professional.services.add'), [
            'category_id' => $extra->id,
        ])->assertRedirect();

        $this->assertTrue(
            $this->pro->fresh()->serviceCategories()->where('categories.id', $extra->id)->exists()
        );
    }

    /** An event type is not a service, however it arrives. */
    public function test_an_event_type_cannot_be_added_as_a_service(): void
    {
        $occasion = Category::create([
            'name' => 'Halloween Party', 'slug' => 'halloween-party-add', 'kind' => Category::EVENT_TYPE,
        ]);

        $this->actingAs($this->pro)->post(route('professional.services.add'), [
            'category_id' => $occasion->id,
        ])->assertSessionHasErrors('category_id');

        $this->assertFalse(
            $this->pro->fresh()->serviceCategories()->where('categories.id', $occasion->id)->exists()
        );
    }

    /**
     * Resting keeps the price. Taking a service off the profile was the only
     * way to stop offering it, and that threw away everything set on it.
     */
    public function test_resting_a_service_keeps_what_it_costs(): void
    {
        $this->actingAs($this->pro)->patch(route('professional.services.update'), [
            'rows' => [$this->dj->id => ['price' => '1200', 'price_unit' => 'flat']],
        ]);

        $this->actingAs($this->pro)->post(route('professional.services.toggle'), [
            'category_id' => $this->dj->id,
        ])->assertRedirect();

        $row = $this->pro->fresh()->serviceCategories()->where('categories.id', $this->dj->id)->first();

        $this->assertFalse((bool) $row->pivot->is_active);
        $this->assertSame(1200.0, (float) $row->pivot->price);

        // And back again.
        $this->actingAs($this->pro)->post(route('professional.services.toggle'), [
            'category_id' => $this->dj->id,
        ]);

        $this->assertTrue(
            (bool) $this->pro->fresh()->serviceCategories()->where('categories.id', $this->dj->id)->first()->pivot->is_active
        );
    }

    /** Somebody else's service is not theirs to rest. */
    public function test_a_service_they_do_not_offer_cannot_be_rested(): void
    {
        $other = $this->service('Somebody Elses Service', 'somebody-elses-service-rest');

        $this->actingAs($this->pro)->post(route('professional.services.toggle'), [
            'category_id' => $other->id,
        ])->assertRedirect();

        $this->assertDatabaseMissing('category_user', [
            'user_id'     => $this->pro->id,
            'category_id' => $other->id,
        ]);
    }

    /** The list is grouped under its parent categories, not run flat. */
    public function test_the_list_is_grouped_by_category(): void
    {
        $parent = Category::create([
            'name' => 'Audio, Visual, Lighting & Staging',
            'slug' => 'avl-staging-group-test',
            'kind' => Category::SERVICE_CATEGORY,
        ]);
        $this->dj->update(['parent_id' => $parent->id]);

        $this->actingAs($this->pro)->get(route('professional.services.index'))
            ->assertOk()
            ->assertSee('Audio, Visual, Lighting &amp; Staging', false)
            // The grouping lives in the list view now. Khadijah's screen opens
            // on cards, and the sentence that used to say "Grouped by
            // category" was replaced by her own line on the Your Services
            // card; the grouping it described is still here.
            ->assertSee('id="spListView"', false)
            ->assertSee('data-spgroup', false);
    }
}
