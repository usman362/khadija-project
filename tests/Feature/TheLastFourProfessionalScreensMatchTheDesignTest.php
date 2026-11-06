<?php

namespace Tests\Feature;

use App\Models\{Bid, Booking, Category, Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The last four of Khadijah's 28 October screens.
 *
 *   My Services & Pricing · Bid Intelligence · Priority Actions ·
 *   Gig Operations Hub
 *
 * Each of the four asks for at least one figure this platform does not
 * record. None of them is invented, and each omission is pinned here so it
 * cannot quietly come back:
 *
 *   Bid Intelligence    an AI win-rate prediction, a market benchmark, and a
 *                       reason a client declined. No model, no competitor
 *                       data, no recorded reason.
 *   Gig Operations Hub  "unread messages". Nothing on a message records
 *                       whether it was read.
 *   Priority Actions    a "want to grow faster?" promise, which the
 *                       compliance rules forbid.
 *   My Services         a photograph per service. None is stored.
 */
class TheLastFourProfessionalScreensMatchTheDesignTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-11-06 12:00:00'));

        $this->pro    = $this->person('professional');
        $this->client = $this->person('client', 'Dana Whitfield');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function person(string $role, ?string $name = null): User
    {
        $u = User::factory()->create(array_filter(['primary_role' => $role, 'name' => $name]));
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function service(string $name, ?float $price = null, bool $active = true): Category
    {
        $parent = Category::firstOrCreate(
            ['slug' => 'dj-and-music-last4'],
            ['name' => 'DJ & Music', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true],
        );

        $service = Category::create([
            'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name) . '-l4',
            'kind' => Category::SERVICE, 'parent_id' => $parent->id, 'is_active' => true,
        ]);

        $this->pro->serviceCategories()->attach($service->id, [
            'price' => $price, 'price_unit' => 'from', 'is_active' => $active,
            'duration_hours' => 4, 'extra_hour_price' => 150,
        ]);

        return $service;
    }

    private function event(array $attrs = []): Event
    {
        return Event::create(array_merge([
            'title'        => 'Riverside Corporate Gala',
            'client_id'    => $this->client->id,
            'created_by'   => $this->client->id,
            'status'       => 'published',
            'is_published' => true,
            'event_type'   => 'Corporate Event',
            'state'        => 'MD',
            'starts_at'    => now()->addDays(30),
            'published_at' => now()->subDays(20),
        ], $attrs));
    }

    private function page(string $route, array $query = []): string
    {
        return $this->actingAs($this->pro)
            ->get(route($route, $query))
            ->assertOk()
            ->getContent();
    }

    // ─────────────────────────────────── My Services & Pricing ──────────

    /** Her six tabs, and her five tiles with the words under each. */
    public function test_services_page_has_her_tabs_and_tiles(): void
    {
        $this->service('Wedding DJ', 1200);

        $html = $this->page('professional.services.index');

        foreach (['Services', 'Pricing Rules', 'Add-ons &amp; Extras',
                  'Travel Pricing', 'Templates', 'Quick Bid Settings'] as $tab) {
            $this->assertStringContainsString($tab, $html, "The tab \"{$tab}\" is missing.");
        }

        foreach ([
            'Total Services', 'Active services you offer',
            'Active Services', 'Visible to clients',
            'Package Usage', 'Services used in packages',
            'Quick Bid Ready', 'Services with pricing rules',
            'Inactive Services', 'Not visible to clients',
        ] as $copy) {
            $this->assertStringContainsString($copy, $html, "The tile copy \"{$copy}\" is missing.");
        }
    }

    /** Her card view, with the service's own price and hours on it. */
    public function test_services_page_shows_each_service_as_a_card(): void
    {
        $this->service('Wedding DJ', 1200);

        $html = $this->page('professional.services.index');

        $this->assertStringContainsString('Your Services', $html);
        $this->assertStringContainsString('Card View', $html);
        $this->assertStringContainsString('List View', $html);
        $this->assertStringContainsString('Reorder Services', $html);
        $this->assertStringContainsString('Wedding DJ', $html);
        $this->assertStringContainsString('$1,200', $html);
        $this->assertStringContainsString('Includes 4 hours', $html);
        $this->assertStringContainsString('$150 per additional hour', $html);
    }

    /** A service switched off is marked, not quietly dropped. */
    public function test_services_page_marks_an_inactive_service(): void
    {
        $this->service('Wedding DJ', 1200, true);
        $this->service('Retired Karaoke', 600, false);

        $html = $this->page('professional.services.index');

        $this->assertStringContainsString('Retired Karaoke', $html);
        $this->assertStringContainsString('>Inactive<', $html);
    }

    /** No stock photograph stands in for a service nobody pictured. */
    public function test_services_page_does_not_invent_a_photograph(): void
    {
        $this->service('Wedding DJ', 1200);

        $html = $this->page('professional.services.index');

        $this->assertStringContainsString('class="initial"', $html);
        $this->assertStringNotContainsString('unsplash', $html);
    }

    /** The rail she has, with its three cards. */
    public function test_services_page_has_her_right_rail(): void
    {
        $this->service('Wedding DJ', 1200);

        $html = $this->page('professional.services.index');

        $this->assertStringContainsString('Helpful Tips', $html);
        $this->assertStringContainsString('Service Display on Your Profile', $html);
        $this->assertStringContainsString('Need Help?', $html);
    }

    /** Templates is a tab with something in it, not a heading. */
    public function test_the_templates_tab_edits_a_real_record(): void
    {
        $this->service('Wedding DJ', 1200);

        $html = $this->page('professional.services.index', ['tab' => 'templates']);

        $this->assertStringContainsString('Response template', $html);
        $this->assertStringContainsString('name="quick_bid_terms"', $html);

        $this->actingAs($this->pro)
            ->post(route('professional.services.rules'), [
                'tab' => 'templates',
                'quick_bid_terms' => 'A 30% deposit holds the date.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('professional_pricing_rules', [
            'user_id' => $this->pro->id,
            'quick_bid_terms' => 'A 30% deposit holds the date.',
        ]);
    }

    // ───────────────────────────────────────── Bid Intelligence ─────────

    private function bid(string $status, float $amount, ?Event $event = null): Bid
    {
        $event = $event ?: $this->event(['title' => 'Gala ' . \Illuminate\Support\Str::random(5)]);

        return Bid::create([
            'event_id'     => $event->id,
            'supplier_id'  => $this->pro->id,
            'amount'       => $amount,
            'status'       => $status,
            'submitted_at' => now()->subDays(10),
            'breakdown'    => $status === 'won' ? ['Base' => $amount] : null,
        ]);
    }

    /** Her four tiles, counted off real bids. */
    public function test_bid_intelligence_counts_real_bids(): void
    {
        $this->bid('won', 2000);
        $this->bid('lost', 1500);
        $this->bid('submitted', 3000);

        $html = $this->page('professional.bid-intelligence.index');

        $this->assertStringContainsString('Submitted Bids', $html);
        $this->assertStringContainsString('Accepted', $html);
        $this->assertStringContainsString('Bid Funnel Conversion', $html);
        $this->assertStringContainsString('50% win rate on the 2 decided', $html);
    }

    /**
     * No prediction, no market benchmark, no decline reasons.
     *
     * All three are on her screen and none has a source. On the page somebody
     * uses to decide what to charge, a guess printed as a finding is worse
     * than no finding.
     */
    public function test_bid_intelligence_invents_no_forecast_or_benchmark(): void
    {
        $this->bid('won', 2000);

        $html = $this->page('professional.bid-intelligence.index');

        $this->assertStringContainsString('GigResource has no model that could predict a win rate', $html);
        $this->assertStringContainsString('GigResource does not record what other professionals bid', $html);
        $this->assertStringContainsString('nothing records why', $html);

        // And none of her invented figures reached the page.
        foreach (['Predicted Win Rate', 'Market Context', 'Moderate'] as $invented) {
            $this->assertStringNotContainsString($invented, $html);
        }
    }

    /** The funnel has no step nothing records. */
    public function test_the_funnel_has_no_viewed_step(): void
    {
        $this->bid('won', 2000);

        $html = $this->page('professional.bid-intelligence.index');

        $this->assertStringContainsString('There is no "viewed" step', $html);
        $this->assertStringNotContainsString('Client Viewed', $html);
    }

    /** A timing band needs a published request and a bid against it. */
    public function test_response_timing_is_measured_not_assumed(): void
    {
        $event = $this->event(['published_at' => now()->subDays(11)]);
        $this->bid('won', 2000, $event);

        $html = $this->page('professional.bid-intelligence.index');

        $this->assertStringContainsString('Response Timing Analysis', $html);
        $this->assertStringContainsString('How long after a request was published your bid went in', $html);
    }

    /** The quality checklist is a tally of their own accepted bids. */
    public function test_the_quality_checklist_counts_their_own_accepted_bids(): void
    {
        $this->bid('won', 2000);

        $html = $this->page('professional.bid-intelligence.index');

        $this->assertStringContainsString('Bid Quality Checklist', $html);
        $this->assertStringContainsString('A priced breakdown', $html);
        $this->assertStringContainsString('A tally of your own, not advice.', $html);
    }

    /** The filters narrow the same set of bids. */
    public function test_bid_intelligence_filters_narrow_the_page(): void
    {
        $this->bid('won', 500);
        $this->bid('lost', 9000);

        $html = $this->page('professional.bid-intelligence.index', ['band' => 'Under $1,000']);

        $this->assertStringContainsString('Under $1,000', $html);
        // The dear bid is filtered out, so its band carries no row.
        $this->assertStringNotContainsString('$9,000', $html);
    }

    // ───────────────────────────────────────── Priority Actions ─────────

    /** Her six tabs, each counting its own group. */
    public function test_priority_actions_has_her_tabs(): void
    {
        $html = $this->page('professional.priority.index');

        foreach (['All (', 'Urgent (', 'Due Soon (', 'Client Activity (',
                  'Payments (', 'Agreements ('] as $tab) {
            $this->assertStringContainsString($tab, $html, "The tab \"{$tab}\" is missing.");
        }
    }

    /** Her date card, and what it counts. */
    public function test_priority_actions_names_the_day_and_the_count(): void
    {
        $html = $this->page('professional.priority.index');

        $this->assertStringContainsString('Fri, Nov 6, 2026', $html);
        $this->assertStringContainsString('Nothing needs your attention', $html);
    }

    /** Money waiting is a real row, with the button that moves it. */
    public function test_a_payout_waiting_appears_under_payments(): void
    {
        $event = $this->event(['starts_at' => now()->subDays(5)]);

        Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'completed', 'price' => 1000,
        ]);

        $html = $this->page('professional.priority.index', ['tab' => 'payments']);

        $this->assertStringContainsString('You have earnings you have not asked for', $html);
        $this->assertStringContainsString('Request a payout', $html);
        $this->assertStringContainsString('$950.00 available', $html, 'Less the 5% Starter fee.');
    }

    /**
     * No growth promise.
     *
     * Her screen ends on "Want to grow faster?". The compliance rules forbid
     * promising anybody more business.
     */
    public function test_priority_actions_promises_nobody_more_business(): void
    {
        $html = $this->page('professional.priority.index');

        $this->assertStringNotContainsString('grow faster', $html);
        $this->assertStringNotContainsString('more visibility', $html);
    }

    /** The snapshot compares them with themselves, or says it cannot. */
    public function test_the_snapshot_compares_the_reader_with_themselves(): void
    {
        $html = $this->page('professional.priority.index');

        $this->assertStringContainsString('Business Snapshot', $html);
        $this->assertStringContainsString('Last 30 days, against your own 30 before.', $html);
        // Nothing before, so no percentage is claimed.
        $this->assertStringContainsString('first', $html);
    }

    // ─────────────────────────────────────── Gig Operations Hub ─────────

    /** Her banner, her three cards, her table. */
    public function test_the_hub_has_her_banner_and_cards(): void
    {
        $event = $this->event(['title' => 'Annual Corporate Gala']);

        Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'confirmed', 'price' => 4200,
        ]);

        $html = $this->page('professional.gig-hub.index');

        $this->assertStringContainsString('Everything for your event business, in one place.', $html);
        $this->assertStringContainsString('Create New', $html);
        $this->assertStringContainsString('Upcoming Events', $html);
        $this->assertStringContainsString('Pending Contracts', $html);
        $this->assertStringContainsString('Recent Gigs &amp; Contracts', $html);
        $this->assertStringContainsString('What Happens If You Click a Gig?', $html);
        $this->assertStringContainsString('Annual Corporate Gala', $html);
        $this->assertStringContainsString('$4,200', $html);
    }

    /**
     * No unread count, because nothing records a read.
     *
     * Her card says "6 Unread Messages". No message on GigResource carries a
     * read state, so the card counts the threads where the client spoke last.
     */
    public function test_the_hub_does_not_claim_to_know_what_was_read(): void
    {
        $html = $this->page('professional.gig-hub.index');

        $this->assertStringNotContainsString('Unread Messages', $html);
        $this->assertStringContainsString('Threads Awaiting You', $html);
    }

    /** Both rail cards, with their own lines under the headings. */
    public function test_the_hub_rail_carries_deadlines_and_activity(): void
    {
        $html = $this->page('professional.gig-hub.index');

        $this->assertStringContainsString('Upcoming Deadlines', $html);
        $this->assertStringContainsString('Items that need your attention soon.', $html);
        $this->assertStringContainsString('Recent Activity', $html);
        $this->assertStringContainsString('Latest updates across your gigs and messages.', $html);
    }

    /** With nothing booked, the card says so rather than counting down to it. */
    public function test_the_hub_says_when_nothing_is_booked_ahead(): void
    {
        $html = $this->page('professional.gig-hub.index');

        $this->assertStringContainsString('Nothing booked ahead', $html);
    }
}
