<?php

namespace Tests\Feature;

use App\Models\{Category, Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Asking the board why a gig is not on it.
 *
 * Ali, 28 October, twice: a request was posted and no professional could bid.
 * Both times the answer was a different gate and both times finding it meant
 * reading four classes.
 *
 * The board is a stack of filters that each hide what the viewer cannot act
 * on, which is right. The cost of being right that way is that a hidden gig
 * looks exactly like a broken one, and the only way to tell them apart was to
 * go and read the code. This asks the same gates in the same order.
 */
class AskingWhyAGigIsHiddenTest extends TestCase
{
    use RefreshDatabase;

    private User $professional;
    private User $client;
    private Category $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->professional = $this->person('professional', 'MD');
        $this->client       = $this->person('client', 'MD');

        $this->service = Category::firstOrCreate(
            ['slug' => 'av-equipment-rental'],
            ['name' => 'AV Equipment Rental', 'kind' => Category::SERVICE, 'is_active' => true],
        );

        $this->professional->syncServices([$this->service->id]);
    }

    private function person(string $role, string $state): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => $state, 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function gig(array $overrides = []): Event
    {
        $event = Event::create(array_merge([
            'title'        => 'A Gig',
            'client_id'    => $this->client->id,
            'created_by'   => $this->client->id,
            'status'       => 'published',
            'is_published' => true,
            'starts_at'    => now()->addDays(45),
            'location'     => 'Baltimore, MD',
            'published_at' => now(),
        ], $overrides));

        $event->categories()->attach($this->service->id);

        return $event;
    }

    private function ask(Event $event): \Illuminate\Testing\PendingCommand
    {
        return $this->artisan('gig:why-hidden', [
            'event'        => $event->id,
            'professional' => $this->professional->email,
        ]);
    }

    public function test_a_gig_with_nothing_wrong_reports_nothing_wrong(): void
    {
        $this->ask($this->gig())
            ->expectsOutputToContain('Nothing is hiding this gig')
            ->assertSuccessful();
    }

    public function test_it_names_a_date_that_has_passed(): void
    {
        $this->ask($this->gig(['starts_at' => now()->subMonths(3)]))
            ->expectsOutputToContain('the event date has passed')
            ->expectsOutputToContain('This gig is not on that professional')
            ->assertSuccessful();
    }

    public function test_it_names_a_draft(): void
    {
        $this->ask($this->gig(['is_published' => false]))
            ->expectsOutputToContain('is a draft')
            ->assertSuccessful();
    }

    /**
     * The state rule, which is the one nobody expects.
     *
     * R38 hides a gig in another state because the professional could not take
     * the work anyway, so the board looks empty rather than refusing a bid
     * later. Entirely correct and entirely invisible, which is why it is worth
     * naming out loud.
     */
    public function test_it_names_the_state_rule(): void
    {
        /*
         * `state`, not `location`. The first version of this set the location
         * to "Austin, TX" and the gate passed, because the matching is done on
         * the event's own state column and that is stamped from the client's
         * home state when the request is created. Sir Peter's boundary rule,
         * 25 August: matching is always by the client's home state, even when
         * the event itself is held out of state.
         */
        $this->ask($this->gig(['location' => 'Austin, TX', 'state' => 'TX']))
            ->expectsOutputToContain('R38 hides other states')
            ->assertSuccessful();
    }

    public function test_it_names_a_professional_who_offers_nothing(): void
    {
        $this->professional->syncServices([]);

        $this->ask($this->gig())
            ->expectsOutputToContain('offers no services at all')
            ->expectsOutputToContain('professional:offer-everything')
            ->assertSuccessful();
    }

    /** A service mismatch reads differently from offering nothing. */
    public function test_it_tells_a_mismatch_apart_from_an_empty_profile(): void
    {
        $other = Category::create([
            'name' => 'Ice Sculpture', 'slug' => 'ice-sculpture',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        $this->professional->syncServices([$other->id]);

        $this->ask($this->gig())
            ->expectsOutputToContain('none of the services this gig wants')
            ->assertSuccessful();
    }

    /**
     * Once the client moves a bid forward the request is off the market.
     *
     * Worth its own line, because the gig vanishing right after somebody bids
     * on it is the case most likely to be read as a fault.
     */
    public function test_it_names_a_request_already_taken_forward(): void
    {
        $event = $this->gig();

        \App\Models\Finalization::create([
            'event_id'      => $event->id,
            'category_id'   => $this->service->id,
            'client_id'     => $this->client->id,
            'supplier_id'   => $this->professional->id,
            'terms_version' => 1,
        ]);

        $this->ask($event)
            ->expectsOutputToContain('already moved a bid forward')
            ->assertSuccessful();
    }

    // ── What it refuses ──────────────────────────────────────

    public function test_it_refuses_an_event_that_is_not_here(): void
    {
        $this->artisan('gig:why-hidden', ['event' => 999999, 'professional' => $this->professional->email])
            ->expectsOutputToContain('No event with id')
            ->assertFailed();
    }

    public function test_it_refuses_an_email_that_is_not_here(): void
    {
        $this->artisan('gig:why-hidden', ['event' => $this->gig()->id, 'professional' => 'nobody@example.com'])
            ->expectsOutputToContain('No account with the email')
            ->assertFailed();
    }

    /** It reads and reports. It must not change anything. */
    public function test_it_changes_nothing(): void
    {
        $event  = $this->gig(['starts_at' => now()->subMonths(3)]);
        $before = $event->fresh()->toArray();

        $this->ask($event)->assertSuccessful();

        $this->assertSame($before, $event->fresh()->toArray());
    }
}
