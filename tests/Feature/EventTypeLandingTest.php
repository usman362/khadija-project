<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryRelevance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * The event-type page — plan first, hire second.
 *
 * Every row on this site came through one landing page, so all 106 event types
 * rendered as "Hire Charity Event" over a count of professionals. You do not
 * hire a Year-End Party; you plan one, and then hire the people for it. That
 * was the plainest form of the three-tier confusion the Owner has been
 * pointing at across several messages.
 *
 * The services offered are the ones Peter's Category Masterlist says matter for
 * that occasion, read from the archetype relevance matrix.
 */
class EventTypeLandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        \App\Domain\Taxonomy\ServiceRelevance::forget();
    }

    private function category(string $name, string $kind, ?string $archetype = null): Category
    {
        return Category::create([
            'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name),
            'kind' => $kind, 'is_active' => true, 'archetype' => $archetype,
        ]);
    }

    private function wedding(): Category
    {
        $arch = 'Wedding & Related Ceremonies';
        $event = $this->category('Wedding', Category::EVENT_TYPE, $arch);

        foreach (['Catering' => 'Essential', 'Photo Booths' => 'Occasional'] as $name => $tier) {
            CategoryRelevance::create([
                'archetype'   => $arch,
                'category_id' => $this->category($name, Category::SERVICE_CATEGORY)->id,
                'tier'        => $tier,
            ]);
        }

        $this->category('Ice Sculpture', Category::SERVICE_CATEGORY);   // unranked

        return $event;
    }

    /* ── The page ───────────────────────────────────────────── */

    public function test_an_event_type_is_planned_rather_than_hired(): void
    {
        $page = $this->get(route('public.category', $this->wedding()->slug))->assertOk();

        $page->assertSee('Plan Your', false);
        $page->assertDontSee('Hire <span class="grad">Wedding</span>', false);
    }

    /** A service category is still hired, and keeps the page it had. */
    public function test_a_service_category_still_uses_the_hire_page(): void
    {
        $cat = $this->category('Catering Services', Category::SERVICE_CATEGORY);

        $this->get(route('public.category', $cat->slug))->assertOk()->assertSee('Hire', false);
    }

    /**
     * The masterlist's order, most relevant first, with the ones it does not
     * rank last rather than dropped — a tier is a ranking, not a permission.
     */
    public function test_the_services_are_ordered_by_what_the_masterlist_says_matters(): void
    {
        $shown = collect($this->get(route('public.category', $this->wedding()->slug))
            ->assertOk()->viewData('services'));

        $this->assertSame(
            ['Catering', 'Photo Booths', 'Ice Sculpture'],
            $shown->pluck('name')->all(),
        );
        $this->assertSame(['Essential', 'Occasional', null], $shown->pluck('tier')->all());
    }

    /* ── The handoff ────────────────────────────────────────── */

    private function client(): User
    {
        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->givePermissionTo(['dashboard.view', 'events.create']);
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $u->fresh();
    }

    public function test_the_chosen_services_carry_into_the_request_wizard(): void
    {
        $event = $this->wedding();
        $ids   = Category::where('kind', Category::SERVICE_CATEGORY)->pluck('id')->take(2)->all();

        $this->actingAs($this->client())
            ->post(route('client.bsr.from-event-type'), [
                'event_type' => 'Wedding',
                'categories' => $ids,
            ])
            ->assertRedirect(route('client.bsr.step', 'service'));

        $wizard = (array) Session::get('bsr_wizard', []);

        $this->assertSame('Wedding', $wizard['event_type']);
        $this->assertSame($ids, $wizard['focus_categories']);
    }

    /**
     * The categories arrive as a focus, not as the answer. Expanding "Catering"
     * into every catering service under it would put a dozen requests in front
     * of professionals that the client never asked for.
     */
    public function test_no_services_are_chosen_on_the_clients_behalf(): void
    {
        $this->wedding();

        $this->actingAs($this->client())->post(route('client.bsr.from-event-type'), [
            'event_type' => 'Wedding',
            'categories' => Category::where('kind', Category::SERVICE_CATEGORY)->pluck('id')->all(),
        ]);

        $this->assertArrayNotHasKey('services', (array) Session::get('bsr_wizard', []));
    }

    /** Free text does not become an event type, for the same reason as elsewhere. */
    public function test_an_unrecognised_event_type_is_not_invented(): void
    {
        $this->wedding();

        $this->actingAs($this->client())->post(route('client.bsr.from-event-type'), [
            'event_type' => "Sarah and Alex's big day",
            'categories' => Category::where('kind', Category::SERVICE_CATEGORY)->pluck('id')->take(1)->all(),
        ]);

        $wizard = (array) Session::get('bsr_wizard', []);

        $this->assertArrayNotHasKey('event_type', $wizard);
        $this->assertArrayNotHasKey('title', $wizard);
    }

    public function test_at_least_one_service_is_required(): void
    {
        $this->wedding();

        $this->actingAs($this->client())
            ->post(route('client.bsr.from-event-type'), ['event_type' => 'Wedding', 'categories' => []])
            ->assertSessionHasErrors('categories');
    }

    /* ── Icons and chips ────────────────────────────────────── */

    /**
     * Every one of the 27 service categories has an icon, because none of them
     * has artwork. A photograph would have to come from somewhere, and a stock
     * picture of someone else's wedding is a claim this page cannot make.
     */
    public function test_every_service_category_has_an_icon(): void
    {
        $mapped = \App\Domain\Taxonomy\ServiceIcon::mappedSlugs();

        $this->assertCount(27, $mapped, 'one per service category in the masterlist');
        $this->assertNotEmpty(\App\Domain\Taxonomy\ServiceIcon::pathFor('something-unmapped'),
            'an unmapped category should look plain, never broken');
    }

    public function test_the_cards_carry_their_icon(): void
    {
        $shown = collect($this->get(route('public.category', $this->wedding()->slug))
            ->assertOk()->viewData('services'));

        foreach ($shown as $svc) {
            $this->assertNotEmpty($svc['icon'], "{$svc['name']} has no icon");
        }
    }

    /**
     * The chips are the trades the professionals on the page actually work in.
     * A fixed list would offer "DJs" where none of them is a DJ, and filter to
     * an empty row.
     */
    public function test_the_filter_chips_come_from_the_professionals_shown(): void
    {
        $this->wedding();

        $catering = Category::where('name', 'Catering')->firstOrFail();

        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $pro->serviceCategories()->attach($catering->id);

        $chips = $this->get(route('public.category', 'wedding'))->assertOk()->viewData('chips');

        $this->assertSame(['Catering'], $chips->all(), 'only trades somebody here actually offers');
    }

    /** With nobody to show, there is nothing to filter and no chip row. */
    public function test_no_chips_when_there_is_nobody_to_filter(): void
    {
        $chips = $this->get(route('public.category', $this->wedding()->slug))->assertOk()->viewData('chips');

        $this->assertCount(0, $chips);
    }
}
