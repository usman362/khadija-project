<?php

namespace Tests\Feature;

use App\Models\{Booking, Category, Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The hollow cards on the professional side, and what replaced them.
 *
 * Four screens carried blocks that drew nothing real. Most were harmless
 * illustration; two were not, and those are the ones these tests exist for.
 *
 * The Leads gauge read `$conversion ?: 82`, so a professional with no leads
 * at all was shown 82% and the word High. The Calendar printed a travel time
 * of 35 minutes — typed into the page, the same for a venue down the road and
 * one three hours away — and worked out "Leave By" from it. Somebody reading
 * that and believing it arrives late to a wedding, and the site told them to.
 */
class NothingOnAProfessionalScreenIsMadeUpTest extends TestCase
{
    use RefreshDatabase;

    private User $professional;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client       = $this->person('client');
        $this->professional = $this->person('professional');
    }

    private function person(string $role): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function page(string $route): string
    {
        return $this->actingAs($this->professional)
            ->get(route($route))
            ->assertSuccessful()
            ->getContent();
    }

    /** A booking this professional holds, so the pages have something to draw. */
    private function job(string $status = 'confirmed'): Booking
    {
        $event = Event::create([
            'title'       => 'Rivera Wedding',
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => $this->professional->id,
            'status'      => 'published',
            'is_published' => true,
            'starts_at'   => now()->addDays(10),
            'location'    => 'Baltimore, MD',
        ]);

        return Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => $this->professional->id,
            'category_id' => Category::firstOrCreate(
                ['slug' => 'wedding-dj'],
                ['name' => 'Wedding DJ', 'kind' => Category::SERVICE, 'is_active' => true],
            )->id,
            'status'      => $status,
            'price'       => 4000,
        ]);
    }

    // ── The conversion gauge ─────────────────────────────────

    /**
     * With no leads, it says so rather than inventing a figure.
     *
     * 82 was the number somebody typed as a fallback. It was shown to every
     * professional who had never had a lead.
     */
    public function test_a_professional_with_no_leads_is_not_shown_a_rate(): void
    {
        $html = $this->page('professional.leads.index');

        // The sentence the gauge prints, not its class name: the class is
        // declared in the page's own stylesheet whether or not anything
        // uses it, and an assertion that matches a stylesheet is an
        // assertion about nothing.
        $this->assertStringNotContainsString('of every 100 leads booked', $html);
        $this->assertStringContainsString('No leads yet', $html);
    }

    /** And with leads, the real figure is drawn. */
    public function test_a_professional_with_leads_is_shown_the_real_rate(): void
    {
        $this->job('requested');

        $html = $this->page('professional.leads.index');

        $this->assertStringContainsString('of every 100 leads booked', $html);
        $this->assertStringNotContainsString('No leads yet', $html);
    }

    /** The four marks that were ticked for everybody, always, are gone. */
    public function test_the_always_ticked_lead_marks_are_gone(): void
    {
        $html = $this->page('professional.leads.index');

        foreach (['Location Match', 'High Budget', 'Fast Response', 'Engaged Lead'] as $mark) {
            $this->assertStringNotContainsString($mark, $html);
        }
    }

    /** And the illustrated reminder nothing sends. */
    public function test_the_drawn_follow_up_flow_is_gone(): void
    {
        $html = $this->page('professional.leads.index');

        $this->assertStringNotContainsString('No reply in 3 days', $html);
        $this->assertStringNotContainsString('You get a reminder to follow up', $html);
    }

    // ── The travel time ──────────────────────────────────────

    /**
     * No invented minutes, and no "leave by" worked out from them.
     *
     * Nothing on the platform measures travel time; there is no maps
     * provider, which is Sir Peter's own outstanding item.
     */
    public function test_the_calendar_does_not_invent_a_travel_time(): void
    {
        $this->job();

        $html = $this->page('professional.calendar.index');

        $this->assertStringNotContainsString('35 mins', $html);
        $this->assertStringNotContainsString('Leave By', $html);
        $this->assertStringNotContainsString('Travel Time', $html);
    }

    /** What it shows instead is read from the booking. */
    public function test_the_calendar_shows_where_the_next_job_is(): void
    {
        $this->job();

        $html = $this->page('professional.calendar.index');

        $this->assertStringContainsString('Where you are needed next', $html);
        $this->assertStringContainsString('Rivera Wedding', $html);
    }

    public function test_with_nothing_booked_it_says_nothing_is_booked(): void
    {
        $html = $this->page('professional.calendar.index');

        $this->assertStringContainsString('Nothing booked', $html);
    }

    /**
     * No switch that is not a switch.
     *
     * The availability card carried a toggle on every row. The toggles were
     * spans, always on, and there is no availability setting on this side for
     * them to act on — busy is worked out from what is already booked. A
     * professional could read it and believe they had told the platform when
     * they were free.
     */
    public function test_the_calendar_offers_no_switch_that_does_nothing(): void
    {
        $html = $this->page('professional.calendar.index');

        $this->assertStringNotContainsString('Availability Toggles', $html);
        $this->assertStringNotContainsString("Block off days", $html);
        $this->assertStringNotContainsString('mc-tog', $html);
    }

    /** Nor a drag that is not a drag. */
    public function test_the_calendar_does_not_claim_drag_and_drop(): void
    {
        $this->assertStringNotContainsString(
            'Drag and drop',
            $this->page('professional.calendar.index'),
        );
    }

    // ── Dead controls ────────────────────────────────────────

    /** Save was drawn over a feature that already worked. Now it reaches it. */
    public function test_save_on_the_bidding_board_reaches_the_real_saving(): void
    {
        // Open to the board: no supplier on it, published, and in a state
        // this professional is matched to.
        $open = Event::create([
            'title'        => 'Harbour Gala',
            'client_id'    => $this->client->id,
            'created_by'   => $this->client->id,
            'status'       => 'published',
            'is_published' => true,
            'state'        => 'MD',
            'starts_at'    => now()->addDays(30),
        ]);

        $html = $this->actingAs($this->professional)
            ->get(route('professional.bidding-board.index'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('Harbour Gala', $html, 'The gig should be on the board to be saved.');
        $this->assertStringContainsString(route('professional.bidding-board.save'), $html);

        $this->actingAs($this->professional)
            ->post(route('professional.bidding-board.save'), ['event_id' => $open->id])
            ->assertRedirect();

        $this->assertTrue($this->professional->savedEvents()->where('events.id', $open->id)->exists());
    }

    public function test_the_dead_share_and_insights_buttons_are_gone(): void
    {
        $html = $this->actingAs($this->professional)
            ->get(route('professional.bidding-board.index'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringNotContainsString('View Full Market Insights', $html);
        $this->assertStringNotContainsString('>Share<', $html);
    }

    /** Advice given to everybody, from nothing, is not an insight. */
    public function test_bid_intelligence_gives_no_recommendation_it_cannot_back(): void
    {
        $html = $this->page('professional.bid-intelligence.index');

        $this->assertStringNotContainsString('Consider adjusting pricing', $html);
    }

    /**
     * No conversation nobody had.
     *
     * Two messages sat in bubbles, timed at 11:30 and 11:32, under the real
     * name and initials of this professional's most recent lead. Nobody
     * wrote them, and a professional could read their own screen and believe
     * that person had got in touch.
     */
    public function test_leads_shows_no_conversation_nobody_had(): void
    {
        $this->job('requested');

        $html = $this->page('professional.leads.index');

        $this->assertStringNotContainsString("I'd like to learn more about your services", $html);
        $this->assertStringNotContainsString("happy to send you more info", $html);
    }

    /** And no list of what is in a proposal that has not been written. */
    public function test_leads_does_not_tick_off_an_unwritten_proposal(): void
    {
        $html = $this->page('professional.leads.index');

        foreach (['Services Included', 'Pricing Breakdown', 'Terms &amp; Conditions'] as $tick) {
            $this->assertStringNotContainsString($tick, $html);
        }
    }

    /**
     * A page does not explain itself to itself.
     *
     * Both screens carried a "Detailed Section Breakdown": columns restating
     * what was already drawn above them, and a mock-up of an interface shown
     * inside the interface, promising "advanced tools".
     */
    public function test_neither_page_draws_a_mock_up_of_itself(): void
    {
        foreach ([
            'professional.leads.index',
            'professional.calendar.index',
            'professional.bid-intelligence.index',
            'professional.gig-hub.index',
        ] as $route) {
            $html = $this->page($route);

            $this->assertStringNotContainsString('Detailed Section Breakdown', $html, $route);
            $this->assertStringNotContainsString('The Navigation Gate', $html, $route);
            $this->assertStringNotContainsString('advanced tools', $html, $route);
        }
    }

    // ── Claims ───────────────────────────────────────────────

    /**
     * Sir Peter's rules on claims: the platform does not promise an outcome.
     *
     * Three banners did, in the same four words.
     */
    public function test_no_professional_banner_promises_more_business(): void
    {
        foreach ([
            'professional.leads.index',
            'professional.calendar.index',
            'professional.bid-intelligence.index',
            'professional.gig-hub.index',
        ] as $route) {
            $html = $this->page($route);

            foreach ([
                'Win More', 'close more deals', 'close more bookings', 'Bigger Business',
                'convert more leads', 'like a pro', 'profitable, and stress-free',
            ] as $claim) {
                $this->assertStringNotContainsString($claim, $html, "{$route} still promises: {$claim}");
            }
        }
    }
}
