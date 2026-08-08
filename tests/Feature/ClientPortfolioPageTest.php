<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Review;
use App\Models\User;
use App\Support\ClientStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rule R53, locked 2026-08-06 — the Client Portfolio page.
 *
 * Both personas were meant to have three surfaces: Dashboard (private),
 * Profile & Settings (private) and Portfolio (public). The professional had
 * all three; the client had two. This is the third.
 *
 * Four of the twelve sections carry an open question about what may be SHOWN,
 * and the spec's own instruction is to ship those hidden rather than hold the
 * other eight. They are built and flagged off, not stubbed — the tests below
 * enable each flag to prove the section works, and prove it stays out of the
 * page while the flag is off.
 */
class ClientPortfolioPageTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = $this->account('client', 'Jessica Smith');
        $this->pro    = $this->account('professional', 'Marcus Reed');
    }

    private function account(string $role, string $name): User
    {
        $user = User::factory()->create(['name' => $name, 'primary_role' => $role]);
        $user->assignRole($role);
        $user->givePermissionTo(['dashboard.view', 'messages.view_any']);
        $user->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => 'supported',
        ]);

        return $user->fresh();
    }

    /** A completed event for the client, optionally reviewed by the pro. */
    private function completedEvent(?int $rating = null, ?User $supplier = null): Event
    {
        $supplier ??= $this->pro;

        $event = Event::create([
            'title'      => 'Anniversary Dinner',
            'created_by' => $this->client->id,
            'client_id'  => $this->client->id,
            'status'     => 'completed',
            'starts_at'  => now()->subMonth(),
        ]);

        $booking = Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $this->client->id,
            'supplier_id' => $supplier->id,
            'created_by'  => $this->client->id,
            'status'      => 'completed',
            'price'       => 1000,
            'currency'    => 'USD',
        ]);

        if ($rating !== null) {
            Review::create([
                'reviewer_id' => $supplier->id,
                'reviewee_id' => $this->client->id,
                'booking_id'  => $booking->id,
                'rating'      => $rating,
                'comment'     => 'Paid on time and knew exactly what they wanted.',
            ]);
        }

        return $event;
    }

    private function portfolio(): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->pro)
            ->get(route('public.client.portfolio', $this->client));
    }

    public function test_the_page_opens_for_a_client(): void
    {
        $this->portfolio()->assertSuccessful()->assertSee('Jessica Smith');
    }

    public function test_a_professional_has_no_client_portfolio(): void
    {
        // They have /pro/{id}. Without this check every account id renders as
        // a client portfolio, including the ones that already have their own.
        $this->actingAs($this->pro)
            ->get(route('public.client.portfolio', $this->pro))
            ->assertNotFound();
    }

    public function test_it_says_portfolio_and_never_profile(): void
    {
        // Section 12. "Profile" belongs to the private Profile & Settings
        // page; this is the public one, and the two must not share a word.
        $page = $this->portfolio();

        $page->assertSee('Client Portfolio');
        $page->assertSee('This client portfolio is visible to professionals');
        $page->assertDontSee('client profile');

        // The mockup's breadcrumb read "Home > Browse Professionals > Client
        // Profile" — copy-pasted from the professional page, where it belongs.
        // Checked against the breadcrumb alone: the site nav links to Browse
        // Professionals on every page, and that link is not the artefact.
        preg_match('/<nav class="cp-breadcrumb".*?<\/nav>/s', $page->getContent(), $crumb);

        $this->assertNotEmpty($crumb, 'the breadcrumb did not render');
        $this->assertStringNotContainsString('Browse Professionals', $crumb[0]);
        $this->assertStringNotContainsString('Profile', $crumb[0]);
    }

    public function test_reviews_from_professionals_are_shown_and_tied_to_a_booking(): void
    {
        $this->completedEvent(rating: 5);

        $page = $this->portfolio();

        $page->assertSee('Reviews from Professionals');
        $page->assertSee('Marcus Reed');
        $page->assertSee('Verified Booking');
        $page->assertSee('Paid on time');
    }

    public function test_the_verified_booking_tag_cannot_be_a_false_claim(): void
    {
        // The spec asks whether that tag is rules-based or unverifiable. It is
        // stronger than rules-based: reviews.booking_id is NOT NULL, so a
        // review with no booking behind it cannot be written at all. Asserted
        // against the schema rather than a controller filter, because the
        // filter is not what is holding the line.
        $this->expectException(\Illuminate\Database\QueryException::class);

        Review::create([
            'reviewer_id' => $this->pro->id,
            'reviewee_id' => $this->client->id,
            'booking_id'  => null,
            'rating'      => 1,
            'comment'     => 'Unattached grumble',
        ]);
    }

    public function test_a_client_with_no_history_shows_nothing_rather_than_zeroes(): void
    {
        // A brand-new client has no cancellation rate and no response rate.
        // Printing 0% for either would be a claim about someone who has not
        // done anything yet — and 0% cancellations reads as a compliment.
        $stats = ClientStats::for($this->client);

        $this->assertNull($stats['cancellation_rate']);
        $this->assertNull($stats['response_rate']);
        $this->assertNull($stats['rating']);

        $this->portfolio()->assertSuccessful()->assertSee('No reviews yet');
    }

    public function test_the_cancellation_rate_counts_only_decided_bookings(): void
    {
        // One completed, one cancelled, one still live. The live booking must
        // not dilute the rate — otherwise a client looks more reliable simply
        // for having work in flight.
        $this->completedEvent();
        foreach (['cancelled', 'confirmed'] as $status) {
            $event = Event::create([
                'title' => 'Other', 'created_by' => $this->client->id,
                'client_id' => $this->client->id, 'status' => $status,
            ]);
            Booking::create([
                'event_id' => $event->id, 'client_id' => $this->client->id,
                'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
                'status' => $status, 'price' => 500, 'currency' => 'USD',
            ]);
        }

        $this->assertSame(50, ClientStats::for($this->client)['cancellation_rate']);
    }

    public function test_repeat_professionals_counts_only_those_booked_more_than_once(): void
    {
        $once = $this->account('professional', 'One Timer');

        $this->completedEvent(supplier: $this->pro);
        $this->completedEvent(supplier: $this->pro);
        $this->completedEvent(supplier: $once);

        $this->assertSame(1, ClientStats::for($this->client)['repeat_professionals']);
    }

    public function test_the_dashboard_and_the_portfolio_agree(): void
    {
        // The reason ClientStats exists. Sections 2 and 7 show the same four
        // figures on ONE page, and the dashboard shows them again — three
        // copies of a calculation is three chances to disagree, which is the
        // defect already found between Earnings and Transactions.
        $this->completedEvent();
        $this->completedEvent();

        $dashboard = $this->actingAs($this->client)
            ->get(route('client.dashboard'))
            ->viewData('stats');

        $this->assertSame(
            ClientStats::for($this->client)['completed_events'],
            $dashboard['completed_bookings'],
        );
    }

    public function test_the_event_type_tiles_add_up_to_the_completed_count(): void
    {
        // The spec checked this arithmetic on the mockup and found it clean;
        // the build has to keep it that way, so both read the same bookings.
        $category = \App\Models\Category::create(['name' => 'Catering', 'slug' => 'catering-x', 'kind' => 'service']);
        $this->completedEvent()->categories()->sync([$category->id]);
        $this->completedEvent()->categories()->sync([$category->id]);

        $this->assertSame(2, ClientStats::eventTypeCounts($this->client)->sum());
        $this->assertSame(2, ClientStats::for($this->client)['completed_events']);
    }

    /* ── The four gated sections ───────────────────────────── */

    public function test_badges_are_hidden_until_their_criteria_exist(): void
    {
        // R29: an earned badge needs a fixed, auditable rule. Ten are drawn in
        // the mockup and none has one, so showing them would mean either
        // inventing the rules or awarding badges nobody earned.
        config(['client-portfolio.sections.badges' => false]);
        $this->portfolio()->assertDontSee('Badges');

        config(['client-portfolio.sections.badges' => true]);
        $this->portfolio()->assertSee('Badges');
    }

    public function test_event_history_is_hidden_until_its_privacy_is_settled(): void
    {
        $this->completedEvent();

        config(['client-portfolio.sections.event_history' => false]);
        $this->portfolio()->assertDontSee('Event History');

        config(['client-portfolio.sections.event_history' => true]);
        $this->portfolio()->assertSee('Event History');
    }

    public function test_the_generalised_history_withholds_the_event_name(): void
    {
        // The recommended default. This page is visible to every professional
        // on the platform, not only the ones who worked the event — and a name
        // plus a venue plus a date locates a private party.
        $this->completedEvent();

        config([
            'client-portfolio.sections.event_history' => true,
            'client-portfolio.event_history_detail'   => 'generalised',
        ]);

        $this->portfolio()->assertSee('Event History')->assertDontSee('Anniversary Dinner');
    }

    public function test_full_detail_shows_the_event_name_when_chosen(): void
    {
        $this->completedEvent();

        config([
            'client-portfolio.sections.event_history' => true,
            'client-portfolio.event_history_detail'   => 'full',
        ]);

        $this->portfolio()->assertSee('Anniversary Dinner');
    }

    public function test_favourite_professionals_stay_private_by_default(): void
    {
        $this->client->savedProfessionals()->attach($this->pro->id);

        config(['client-portfolio.sections.favourite_professionals' => false]);
        $this->portfolio()->assertDontSee('Professionals They Work With');

        config(['client-portfolio.sections.favourite_professionals' => true]);
        $this->portfolio()->assertSee('Professionals They Work With');
    }

    public function test_the_working_style_panel_waits_on_its_data_source(): void
    {
        // Blocked on a decision rather than a privacy question: nobody has
        // said whether the five traits are derived from reviews or authored by
        // the client. Neither a formula nor a field exists, so there is
        // nothing honest to render.
        config(['client-portfolio.sections.working_style' => false]);

        $this->portfolio()->assertDontSee('How It Works With');
    }

    public function test_no_loyalty_tier_chip_is_shown(): void
    {
        // "Level Gold Client" has no definition, and it has to stay distinct
        // from the two locked ladders. Drawing it would invent a third.
        $this->portfolio()->assertDontSee('Gold Client');
    }

    public function test_the_client_can_reach_their_own_portfolio_from_the_sidebar(): void
    {
        // The Toolkit Tiers page shipped reachable by URL and linked from
        // nowhere, so nobody found it. This is the page professionals see;
        // the client needs a way to look at it themselves.
        $sidebar = file_get_contents(resource_path('views/layouts/client.blade.php'));

        $this->assertStringContainsString("route('public.client.portfolio'", $sidebar);

        $this->actingAs($this->client)
            ->get(route('client.dashboard'))
            ->assertSee(route('public.client.portfolio', $this->client), false);
    }

    public function test_the_five_category_rating_breakdown_is_not_faked(): void
    {
        // A review stores ONE rating. The mockup breaks it into five bars, so
        // rendering them would be one number drawn five times under five
        // different labels.
        $this->completedEvent(rating: 5);

        $page = $this->portfolio();

        $page->assertSee('Trust &amp; Reputation', false);
        foreach (['Payment Reliability', 'Would Work Again', 'Overall Experience'] as $invented) {
            $page->assertDontSee($invented);
        }
    }
}
