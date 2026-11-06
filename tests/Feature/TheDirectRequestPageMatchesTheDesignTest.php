<?php

namespace Tests\Feature;

use App\Models\{Booking, Category, Event, RequestAttachment, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Direct Request Details, against Khadijah's 28 October screen.
 *
 * The worst thing on this page was not a layout difference. With no direct
 * request waiting, it invented one: a client called Sarah Johnson, a budget of
 * $7,000 to $8,500 and a deadline of May 10. A professional could read it,
 * plan around it and reply to it, and none of it existed. That is gone, and
 * the first test here keeps it gone.
 *
 * Her screen also carries five things this platform does not record. None is
 * guessed:
 *
 *   parking · indoor/outdoor · a venue TYPE rather than a venue name ·
 *   a per-service budget breakdown · a quantity per service
 *
 * And her response panel says "Send a Quick Bid". A direct request is not
 * bidding here: it goes to one professional, who accepts or declines.
 */
class TheDirectRequestPageMatchesTheDesignTest extends TestCase
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
        $u = User::factory()->create(array_filter([
            'primary_role' => $role,
            'name'         => $name,
        ]));
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function request(array $attrs = [], array $services = ['DJ Services']): Event
    {
        $event = Event::create(array_merge([
            'title'             => 'Dana Whitfield Wedding Reception',
            'description'       => "We're looking for a professional DJ with lighting and sound.",
            'event_type'        => 'Wedding Reception',
            'organization_type' => 'individual',
            'status'            => 'pending',
            'is_published'      => false,
            'source'            => 'direct_offer',
            'client_id'         => $this->client->id,
            'created_by'        => $this->client->id,
            'supplier_id'       => $this->pro->id,
            'location'          => 'Baltimore, MD',
            'location_zip'      => '21201',
            'venue'             => 'Banquet Hall',
            'guest_count'       => 150,
            'budget_min'        => 5000,
            'budget_max'        => 8000,
            'starts_at'         => now()->addDays(60)->setTime(17, 0),
            'ends_at'           => now()->addDays(60)->setTime(23, 0),
            'proposal_deadline' => now()->addDays(3),
        ], $attrs));

        $ids = [];

        foreach ($services as $name) {
            $ids[] = Category::create([
                'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name) . '-dr',
                'kind' => Category::SERVICE, 'is_active' => true,
            ])->id;
        }

        $event->categories()->sync($ids);

        return $event;
    }

    private function page(): string
    {
        return $this->actingAs($this->pro)
            ->get(route('professional.direct-offers.show'))
            ->assertOk()
            ->getContent();
    }

    // ------------------------------------------------- nothing is invented

    /**
     * An empty inbox says so. It does not make a request up.
     *
     * This page used to fall back to a sample complete with a client name, a
     * budget and a deadline, so a professional with nothing waiting was shown
     * an offer that did not exist and could act on it.
     */
    public function test_an_empty_inbox_shows_no_request_at_all(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('No direct requests right now', $html);

        foreach (['Sarah Johnson', 'Luxury Wedding', '$7,000', '$8,500',
                  'DO-1058', 'Premium Member', 'Flexible Budget'] as $invented) {
            $this->assertStringNotContainsString($invented, $html,
                "\"{$invented}\" came from the sample this page used to invent.");
        }
    }

    /** The sample is gone from the controller, not merely unreachable. */
    public function test_the_sample_request_no_longer_exists_in_the_code(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/Professional/ProfessionalDirectOfferController.php'),
        );

        $this->assertStringNotContainsString('sampleOffer', $source);
        $this->assertStringNotContainsString('Sarah Johnson', $source);
    }

    // ----------------------------------------------------------- the shell

    /** Her title, her subtitle and her status pill. */
    public function test_the_header_names_the_client_and_the_event(): void
    {
        $this->request();

        $html = $this->page();

        $this->assertStringContainsString('Direct Request: Dana Whitfield', $html);
        $this->assertStringContainsString('Private Event', $html);
        $this->assertStringContainsString('Wedding Reception', $html);
        $this->assertStringContainsString('Back to Direct Requests', $html);
        $this->assertStringContainsString('Share Request', $html);
    }

    /** The facts she puts across the top, each from a real column. */
    public function test_the_meta_row_carries_place_date_guests_and_received(): void
    {
        $this->request();

        $html = $this->page();

        // The place, as the event holds it. The postcode is not asserted:
        // Event::applyLocationGeocode drops one the geocoder cannot place,
        // and the page is right to print only what survived.
        $this->assertStringContainsString('Baltimore, MD', $html);
        $this->assertStringContainsString('150 guests', $html);
        $this->assertStringContainsString('Received:', $html);
        $this->assertStringContainsString('Respond by', $html);
        $this->assertStringContainsString('3 days left', $html);
    }

    /** With no deadline set there is no countdown invented from the date. */
    public function test_no_deadline_means_no_countdown(): void
    {
        $this->request(['proposal_deadline' => null]);

        $html = $this->page();

        $this->assertStringContainsString('No response deadline was set', $html);
        $this->assertStringNotContainsString('Respond by', $html);
        $this->assertStringNotContainsString('Response Deadline', $html);
    }

    /** Her five tabs, with the attachment count in the fourth. */
    public function test_the_five_tabs_are_hers(): void
    {
        $event = $this->request();

        foreach (['Inspiration Board.pdf', 'Venue Layout.pdf'] as $name) {
            RequestAttachment::create([
                'user_id' => $this->client->id, 'event_id' => $event->id,
                'file_path' => 'x/' . $name, 'file_name' => $name,
                'file_size' => 184320, 'mime_type' => 'application/pdf',
            ]);
        }

        $html = $this->page();

        foreach (['Request Overview', 'Event Details', 'Services Requested',
                  'Attachments &amp; Files (2)', 'Additional Notes'] as $tab) {
            $this->assertStringContainsString($tab, $html, "The tab \"{$tab}\" is missing.");
        }

        $this->assertStringContainsString('Inspiration Board.pdf', $html);
    }

    // ------------------------------------------------------- the client strip

    /** A client with no reviews is not a nought out of five. */
    public function test_a_client_with_no_reviews_shows_no_rating(): void
    {
        $this->request();

        $html = $this->page();

        $this->assertStringContainsString('No reviews yet', $html);
        $this->assertStringNotContainsString('0 &starf;', $html);
    }

    /** Past events are the client's completed bookings, counted. */
    public function test_past_events_counts_completed_bookings(): void
    {
        $event = $this->request();

        $this->assertStringContainsString('0 past events', $this->page());

        Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $this->client->id,
            'supplier_id' => $this->pro->id,
            'created_by'  => $this->client->id,
            'status'      => 'completed',
            'price'       => 1000,
        ]);

        $this->assertStringContainsString('1 past event', $this->page());
    }

    /**
     * No verification check-mark.
     *
     * OA-165, Sir Peter, 23 September: every check-mark badge comes off the
     * live site until a verification feature is defined. Her screen has one
     * beside the client's name; this one does not.
     */
    public function test_no_verified_badge_is_shown(): void
    {
        $this->request();

        $this->assertStringNotContainsString('Verified Client', $this->page());
    }

    // ------------------------------------------ figures nobody actually gave

    /** The budget is one figure for everything, because that is what it is. */
    public function test_the_budget_is_not_split_across_the_services(): void
    {
        $this->request(services: ['DJ Services', 'Lighting', 'Sound System']);

        $html = $this->page();

        $this->assertStringContainsString('$5,000 – $8,000', $html);
        $this->assertStringContainsString('for all services', $html);
        $this->assertStringContainsString('Not split out', $html);
        $this->assertStringContainsString(
            'The client gave one figure for all services, not a figure per service.',
            $html,
        );
    }

    /** Parking and indoor/outdoor are not recorded, and are not guessed. */
    public function test_what_is_not_recorded_says_so(): void
    {
        $this->request();

        $html = $this->page();

        $this->assertStringContainsString('Not recorded on this platform', $html);
        $this->assertStringContainsString('Not broken out per service', $html);

        // Her screen's answers, which nothing here could produce.
        $this->assertStringNotContainsString('On-site parking available', $html);
        $this->assertStringNotContainsString('Indoor / Outdoor', $html);
    }

    // ----------------------------------------------------------- responding

    /** A direct request is accepted or declined. There is no bidding on it. */
    public function test_the_response_panel_accepts_or_declines_rather_than_bidding(): void
    {
        $this->request();

        $html = $this->page();

        $this->assertStringContainsString('Your Response Center', $html);
        $this->assertStringContainsString('Accept This Request', $html);
        $this->assertStringContainsString('Decline', $html);
        $this->assertStringContainsString('View Messaging', $html);
        $this->assertStringContainsString('View Client Profile', $html);

        $this->assertStringNotContainsString('Send Quick Bid', $html);
        $this->assertStringContainsString('so there is no bidding', $html);
    }

    /** Accepting it makes the booking the panel promises. */
    public function test_accepting_creates_a_confirmed_booking(): void
    {
        $event = $this->request();

        $this->actingAs($this->pro)
            ->post(route('professional.direct-offers.accept', $event))
            ->assertRedirect();

        $this->assertSame('confirmed', $event->fresh()->status);
        $this->assertDatabaseHas('bookings', [
            'event_id' => $event->id, 'supplier_id' => $this->pro->id, 'status' => 'confirmed',
        ]);

        // And the page then says so rather than still offering the buttons.
        $html = $this->page();
        $this->assertStringContainsString('Accepted', $html);
        $this->assertStringContainsString('It is a booking now.', $html);
        $this->assertStringNotContainsString('Accept This Request', $html);
    }

    /** Declining closes it, and the page says which way it went. */
    public function test_declining_closes_the_request(): void
    {
        $event = $this->request();

        $this->actingAs($this->pro)
            ->post(route('professional.direct-offers.decline', $event))
            ->assertRedirect();

        $this->assertSame('cancelled', $event->fresh()->status);
        $this->assertStringContainsString('You declined this request.', $this->page());
    }

    /** A request addressed to somebody else is not this professional's. */
    public function test_another_professionals_request_cannot_be_answered(): void
    {
        $other = $this->person('professional');
        $event = $this->request(['supplier_id' => $other->id]);

        $this->actingAs($this->pro)
            ->post(route('professional.direct-offers.accept', $event))
            ->assertForbidden();

        // And it is not shown either.
        $this->assertStringContainsString('No direct requests right now', $this->page());
    }
}
