<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checklist row 193 — a Direct Offer starts with the service, not with a
 * dropdown of every professional in the state.
 *
 * The bug the row describes: a client could send a photography brief to a
 * florist, and only find out when the florist declined it. The list is now
 * filtered by the service — and, because a filtered list is only a courtesy
 * when the ids arrive in a request, the mismatch is refused at the point of
 * sending too.
 */
class DirectOfferServiceFirstTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $photographer;
    private User $florist;
    private Category $photography;
    private Category $florals;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->photography = Category::create(['name' => 'Wedding Photography DSR', 'slug' => 'wed-photo-dsr', 'kind' => 'service', 'is_active' => true]);
        $this->florals     = Category::create(['name' => 'Ceremony Florals DSR', 'slug' => 'florals-dsr', 'kind' => 'service', 'is_active' => true]);

        $this->client       = $this->account('client');
        $this->photographer = $this->account('professional', $this->photography, 'Quillon Photography');
        $this->florist      = $this->account('professional', $this->florals, 'Zarbek Florals');
    }

    /**
     * Names are fixed, not faked.
     *
     * These tests assert that one professional's name is ABSENT from the page.
     * With `User::factory()` the name is random, so the assertion passed or
     * failed on whatever Faker produced — a name that happened to be a
     * substring of any other word on the page failed the run, and the suite
     * went red for a reason that had nothing to do with the code. Two names
     * nothing else on the page contains.
     */
    private function account(string $role, ?Category $service = null, ?string $name = null): User
    {
        $user = User::factory()->create(array_filter([
            'primary_role' => $role,
            'name'         => $name,
        ]));
        $user->assignRole($role);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        if ($service) {
            $user->serviceCategories()->syncWithoutDetaching([$service->id]);
        }

        return $user->fresh();
    }

    public function test_the_form_asks_for_the_service_before_the_professional(): void
    {
        $page = $this->actingAs($this->client)->get(route('client.direct-offers.create'));

        $page->assertOk();
        $page->assertSee('What do you need?', false);
        $page->assertSee('Choose a service', false);
        // Reworded 2026-08-25: the prompt used to sit under an empty "Send to"
        // dropdown, so it said "above". The dropdown is gone until there is
        // somebody to put in it, and the prompt now stands on its own.
        $page->assertSee('Choose a service first', false);
    }

    /** The fix itself: the list can only contain people who do the work. */
    public function test_choosing_a_service_filters_the_professionals(): void
    {
        $page = $this->actingAs($this->client)
            ->get(route('client.direct-offers.create', ['service' => $this->photography->id]));

        $page->assertOk();
        $page->assertSee($this->photographer->name, false);
        $page->assertDontSee($this->florist->name, false);
    }

    public function test_a_service_nobody_offers_says_so_rather_than_listing_everyone(): void
    {
        $orphan = Category::create(['name' => 'Ice Sculpture DSR', 'slug' => 'ice-dsr', 'kind' => 'service', 'is_active' => true]);

        $this->actingAs($this->client)
            ->get(route('client.direct-offers.create', ['service' => $orphan->id]))
            ->assertSee('No professional in your state offers this yet', false);
    }

    /**
     * The second entry point: "Hire This Professional" fixes the person, so
     * the services offered are only theirs. Offering them work they do not do
     * is the same bug from the other end.
     */
    public function test_arriving_from_a_profile_offers_only_that_professionals_services(): void
    {
        $page = $this->actingAs($this->client)
            ->get(route('client.direct-offers.create', ['pro' => $this->photographer->id]));

        $page->assertOk();
        $page->assertSee($this->photographer->name, false);
        $page->assertSee($this->photography->name, false);
        $page->assertDontSee($this->florals->name, false);
    }

    /**
     * A filtered list is a courtesy. The ids arrive in the request, so a
     * stale tab or a typed URL would sail past it — the mismatch is refused
     * where it actually matters.
     */
    public function test_sending_a_service_the_professional_does_not_offer_is_refused(): void
    {
        $this->actingAs($this->client)->post(route('client.direct-offers.store'), [
                'organization_type' => 'individual',
            'professional_id' => $this->florist->id,
            'services'        => [$this->photography->id],
            'event_name'      => 'Our wedding',
        ])->assertStatus(422);

        $this->assertDatabaseCount('events', 0);
    }

    public function test_a_matching_offer_goes_through(): void
    {
        $this->actingAs($this->client)->post(route('client.direct-offers.store'), [
                'organization_type' => 'individual',
            'professional_id' => $this->photographer->id,
            'services'        => [$this->photography->id],
            'event_name'      => 'Our wedding',
        ])->assertRedirect();

        $event = Event::firstOrFail();

        $this->assertSame($this->photographer->id, $event->supplier_id);
        $this->assertFalse((bool) $event->is_published);   // targeted, never on the board
    }

    /**
     * R6 — a Direct Offer caps at one professional per SERVICE, not one
     * service per offer. Several services to one professional is allowed.
     */
    public function test_one_professional_may_be_offered_several_of_their_services(): void
    {
        $second = Category::create(['name' => 'Engagement Shoot DSR', 'slug' => 'engagement-dsr', 'kind' => 'service', 'is_active' => true]);
        $this->photographer->serviceCategories()->syncWithoutDetaching([$second->id]);

        $this->actingAs($this->client)->post(route('client.direct-offers.store'), [
                'organization_type' => 'individual',
            'professional_id' => $this->photographer->id,
            'services'        => [$this->photography->id, $second->id],
            'event_name'      => 'Our wedding',
        ])->assertRedirect();

        $this->assertSame(2, Event::firstOrFail()->categories()->count());
    }
}
