<?php

namespace Tests\Feature;

use App\Domain\WhoHiredMe;
use App\Models\{Booking, Category, Conversation, Event, Review, User};
use App\Support\Commission;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The professional can see who has hired them.
 *
 * The last place the two sides were out of step. A client has had Hired
 * Talent since October — who they engaged, for what, and where the money
 * stands. The professional on the other end of those same bookings had a list
 * of gigs, which answers "what am I doing" and never "who keeps coming back".
 *
 * Grouped by client rather than by event, deliberately: a client plans one
 * event and hires several people for it, a professional works one event at a
 * time and the repeat is the thing worth seeing.
 */
class TheProfessionalCanSeeWhoHiredThemTest extends TestCase
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

    private function service(): Category
    {
        return Category::firstOrCreate(
            ['slug' => 'wedding-dj'],
            ['name' => 'Wedding DJ', 'kind' => Category::SERVICE, 'is_active' => true],
        );
    }

    /** One booking of this professional by this client, on an event that day. */
    private function booking(User $client, string $title, $when, string $status, float $price): Booking
    {
        $event = Event::create([
            'title'      => $title,
            'client_id'  => $client->id,
            'created_by' => $client->id,
            'status'     => 'published',
            'starts_at'  => $when,
        ]);

        return Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $client->id,
            'created_by'  => $client->id,
            'supplier_id' => $this->professional->id,
            'category_id' => $this->service()->id,
            'status'      => $status,
            'price'       => $price,
        ]);
    }

    private function page(array $query = [])
    {
        return $this->actingAs($this->professional)
            ->get(route('professional.clients.index', $query));
    }

    // ── The page exists, and has a door ──────────────────────

    public function test_the_professional_can_open_it(): void
    {
        $this->booking($this->client, 'Rivera Wedding', now()->subDays(20), 'completed', 4000);

        $this->page()
            ->assertSuccessful()
            ->assertSee($this->client->name, false)
            ->assertSee('Rivera Wedding', false);
    }

    public function test_the_sidebar_links_to_it(): void
    {
        $this->actingAs($this->professional)
            ->get(route('professional.dashboard'))
            ->assertSuccessful()
            ->assertSee(route('professional.clients.index'), false);
    }

    public function test_with_nobody_yet_it_says_so_rather_than_showing_an_empty_table(): void
    {
        $this->page()
            ->assertSuccessful()
            ->assertSee('Nobody has hired you yet', false);
    }

    // ── One row per client, not per booking ──────────────────

    public function test_two_bookings_by_one_client_are_one_row(): void
    {
        $this->booking($this->client, 'First Wedding', now()->subYear(), 'completed', 2000);
        $this->booking($this->client, 'Second Wedding', now()->subMonth(), 'completed', 3000);

        $rows = WhoHiredMe::rows($this->professional);

        $this->assertCount(1, $rows);
        $this->assertSame(2, $rows[0]['bookings']);
        $this->assertTrue($rows[0]['returning'], 'Two bookings is the repeat this page exists for.');
    }

    public function test_one_booking_is_not_a_returning_client(): void
    {
        $this->booking($this->client, 'Only Wedding', now()->subMonth(), 'completed', 2000);

        $this->assertFalse(WhoHiredMe::rows($this->professional)[0]['returning']);
    }

    /** And only this professional's own clients. */
    public function test_another_professionals_clients_are_not_here(): void
    {
        $stranger = $this->person('client');
        $other    = $this->person('professional');

        $event = Event::create([
            'title' => 'Somebody Else Gala', 'client_id' => $stranger->id,
            'created_by' => $stranger->id, 'status' => 'published', 'starts_at' => now()->subWeek(),
        ]);
        Booking::create([
            'event_id' => $event->id, 'client_id' => $stranger->id, 'created_by' => $stranger->id,
            'supplier_id' => $other->id,
            'category_id' => $this->service()->id, 'status' => 'completed', 'price' => 900,
        ]);

        $this->booking($this->client, 'Rivera Wedding', now()->subDays(10), 'completed', 4000);

        $this->page()
            ->assertSuccessful()
            ->assertSee($this->client->name, false)
            ->assertDontSee($stranger->name);
    }

    /**
     * A professional who once booked themselves is not their own client.
     *
     * The Booking model refuses this now, so the row is written straight to
     * the table: the guard is there for records made before it did, and a
     * test that cannot create one proves nothing about them.
     */
    public function test_they_are_not_their_own_client(): void
    {
        $event = Event::create([
            'title' => 'A job I gave myself', 'client_id' => $this->professional->id,
            'created_by' => $this->professional->id, 'status' => 'published',
            'starts_at' => now()->subWeek(),
        ]);

        \DB::table('bookings')->insert([
            'event_id'    => $event->id,
            'client_id'   => $this->professional->id,
            'created_by'  => $this->professional->id,
            'supplier_id' => $this->professional->id,
            'category_id' => $this->service()->id,
            'status'      => 'completed',
            'price'       => 500,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->assertCount(0, WhoHiredMe::rows($this->professional));
    }

    // ── What the money says ──────────────────────────────────

    /**
     * The figure is net, which is what the professional receives.
     *
     * The Earnings page is on the same basis. A gross figure here would make
     * the same booking worth two different amounts on two of their screens.
     */
    public function test_the_money_is_what_reaches_them_not_what_the_client_paid(): void
    {
        $this->booking($this->client, 'Rivera Wedding', now()->subMonth(), 'completed', 4000);

        $row = WhoHiredMe::rows($this->professional)[0];

        $this->assertSame(4000.0, $row['gross']);
        $this->assertSame(Commission::netOf(4000.0, $this->professional), $row['earned']);
        $this->assertLessThan($row['gross'], $row['earned'], 'Commission has to come off somewhere.');
    }

    /** Work not finished is not money earned. */
    public function test_an_unfinished_booking_does_not_count_as_earned(): void
    {
        $this->booking($this->client, 'Next Summer', now()->addMonths(3), 'confirmed', 5000);

        $row = WhoHiredMe::rows($this->professional)[0];

        $this->assertSame(0.0, $row['earned']);
        $this->assertSame(1, $row['ahead']);
        $this->assertSame(0, $row['completed']);
    }

    // ── Last worked, and what is coming ──────────────────────

    public function test_it_names_the_last_event_and_the_next_one(): void
    {
        $this->booking($this->client, 'Last Spring', now()->subMonths(4), 'completed', 2000);
        $this->booking($this->client, 'This Autumn', now()->addMonths(2), 'confirmed', 2500);

        $row = WhoHiredMe::rows($this->professional)[0];

        $this->assertSame('Last Spring', $row['last']['title']);
        $this->assertSame('This Autumn', $row['next']['title']);
    }

    /** An outstanding review is a reason to get in touch, so it is said. */
    public function test_a_completed_job_with_no_review_says_so(): void
    {
        $booking = $this->booking($this->client, 'Rivera Wedding', now()->subWeek(), 'completed', 4000);

        $this->assertTrue(WhoHiredMe::rows($this->professional)[0]['last']['to_review']);

        Review::create([
            'reviewer_id' => $this->client->id,
            'reviewee_id' => $this->professional->id,
            'booking_id'  => $booking->id,
            'rating'      => 5,
            'comment'     => 'Kept the floor full all night.',
        ]);

        $this->assertFalse(WhoHiredMe::rows($this->professional)[0]['last']['to_review']);
    }

    // ── The filters are a view of the same rows ──────────────

    public function test_the_came_back_filter_holds_only_returning_clients(): void
    {
        $once = $this->person('client');
        $this->booking($once, 'One Off', now()->subMonth(), 'completed', 1000);
        $this->booking($this->client, 'First', now()->subYear(), 'completed', 2000);
        $this->booking($this->client, 'Second', now()->subMonth(), 'completed', 2000);

        $this->page(['show' => 'repeat'])
            ->assertSuccessful()
            ->assertSee($this->client->name, false)
            ->assertDontSee($once->name);
    }

    public function test_the_summary_counts_everyone_whatever_the_filter_shows(): void
    {
        $once = $this->person('client');
        $this->booking($once, 'One Off', now()->subMonth(), 'completed', 1000);
        $this->booking($this->client, 'First', now()->subYear(), 'completed', 2000);
        $this->booking($this->client, 'Second', now()->subMonth(), 'completed', 2000);

        $summary = WhoHiredMe::summary(WhoHiredMe::rows($this->professional));

        $this->assertSame(2, $summary['clients']);
        $this->assertSame(1, $summary['repeat']);
    }

    /** A made-up value in the query string falls back rather than erroring. */
    public function test_an_unknown_filter_shows_everyone(): void
    {
        $this->booking($this->client, 'Rivera Wedding', now()->subMonth(), 'completed', 4000);

        $this->page(['show' => 'whatever'])
            ->assertSuccessful()
            ->assertSee($this->client->name, false);
    }

    // ── Getting in touch ─────────────────────────────────────

    /**
     * Straight into the conversation they already have.
     *
     * Sending somebody to the message centre to search for a name they are
     * looking at is a step nobody needed.
     */
    public function test_it_opens_the_conversation_they_already_have(): void
    {
        $booking = $this->booking($this->client, 'Rivera Wedding', now()->subMonth(), 'completed', 4000);

        $conversation = Conversation::create([
            'type' => 'booking', 'booking_id' => $booking->id, 'created_by' => $this->client->id,
        ]);
        $conversation->participants()->attach([$this->client->id, $this->professional->id]);

        $this->page()
            ->assertSuccessful()
            ->assertSee(route('professional.chat.show', $conversation), false);
    }

    public function test_with_no_conversation_it_offers_to_start_one(): void
    {
        $this->booking($this->client, 'Rivera Wedding', now()->subMonth(), 'completed', 4000);

        $this->page()
            ->assertSuccessful()
            ->assertSee('Start a message', false);
    }

    // ── Who may open it ──────────────────────────────────────

    /**
     * A client who reaches this address sees nobody.
     *
     * Not a redirect: every route in the professional group is behind
     * dashboard.view, which a client also holds, and that is true of the
     * Leads page and the rest of the side as well. Changing it belongs in its
     * own piece of work. What matters here is that the page cannot leak:
     * every row is read from bookings where the reader is the supplier, and a
     * client is the supplier on none of them.
     */
    public function test_a_client_who_reaches_this_address_sees_nobody(): void
    {
        $this->booking($this->client, 'Rivera Wedding', now()->subMonth(), 'completed', 4000);

        $this->actingAs($this->client)
            ->get(route('professional.clients.index'))
            ->assertSuccessful()
            ->assertSee('Nobody has hired you yet', false);

        $this->assertCount(0, WhoHiredMe::rows($this->client));
    }

    public function test_a_stranger_is_sent_to_sign_in(): void
    {
        $this->get(route('professional.clients.index'))
            ->assertRedirect(route('login'));
    }
}
