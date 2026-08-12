<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checklist rows 179, 191, 192 and 222 — the client dashboard telling the
 * truth about itself.
 *
 * All four were the same kind of fault: a number or a name on the screen that
 * no data stood behind. "Active Gigs: 21" beside an overview that counted
 * four; ten badge icons nobody had earned; "Tier 3 of 6 · 15 completed
 * events" above "35 / 50 events"; and everyone greeted as "Client User".
 */
class ClientDashboardHonestyTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = $this->account('client');
        $this->pro    = $this->account('professional');
    }

    private function account(string $role): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function booking(string $status): Booking
    {
        $event = Event::create([
            'title' => 'A booking', 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'published', 'is_published' => true, 'starts_at' => now()->addDays(20),
        ]);

        return Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id, 'supplier_id' => $this->pro->id,
            'created_by' => $this->client->id, 'status' => $status, 'price' => 500,
        ]);
    }

    private function dashboard()
    {
        return $this->actingAs($this->client)->get(route('client.dashboard'));
    }

    /**
     * Row 191 — the tile and the overview counted different things under one
     * word. Active now means what the overview means, so one is the other's
     * total and they add up by construction.
     */
    public function test_active_gigs_equals_the_overview_beside_it(): void
    {
        $this->booking('requested');
        $this->booking('confirmed');
        $this->booking('confirmed');
        $this->booking('completed');   // finished — not active

        $body = $this->dashboard()->assertOk()->getContent();

        // 3 active (1 requested + 2 confirmed), not 4 and not the event count.
        $this->assertMatchesRegularExpression(
            '/Active Gigs<\/div>\s*<div class="od-stat-value">3<\/div>/',
            $body,
        );
    }

    public function test_an_event_nobody_was_hired_for_is_not_an_active_gig(): void
    {
        // A published request with no booking is a request, not a gig.
        Event::create([
            'title' => 'Nobody hired yet', 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'published', 'is_published' => true, 'starts_at' => now()->addDays(20),
        ]);

        $this->assertMatchesRegularExpression(
            '/Active Gigs<\/div>\s*<div class="od-stat-value">0<\/div>/',
            $this->dashboard()->getContent(),
        );
    }

    /**
     * Row 192 — ten badge icons shown to everyone. No badge has an award rule
     * yet; the rulebook is proposed, not locked.
     */
    public function test_no_badge_is_claimed_before_a_rule_exists(): void
    {
        $page = $this->dashboard();

        $page->assertSee('You have no badges yet', false);

        foreach (['Fast Payer', 'Luxury Host', 'Trendsetter', 'VIP Client', 'Mega Event Planner'] as $badge) {
            $page->assertDontSee($badge, false);
        }
    }

    /**
     * Row 222 — the profile widget was entirely invented, and its own two
     * figures disagreed: 15 completed events on one line, 35 on the next.
     */
    public function test_the_profile_widget_shows_real_figures_and_no_invented_tier(): void
    {
        $page = $this->dashboard();

        $page->assertSee($this->client->name, false);
        $page->assertSee('0 events completed', false);

        foreach (['Tier 3 of 6', 'Trusted Planner', '35 / 50', '86 reviews', 'Elite Planner'] as $invented) {
            $page->assertDontSee($invented, false);
        }
    }

    /** Row 222 — the button now goes to the page R53 built. */
    public function test_view_profile_points_at_the_public_portfolio(): void
    {
        $this->dashboard()->assertSee(route('public.client.portfolio', $this->client), false);
    }

    public function test_completed_events_are_counted_not_asserted(): void
    {
        $this->booking('completed');
        $this->booking('completed');

        $this->dashboard()->assertSee('2 events completed', false);
    }

    /** Row 179 — no placeholder identities anywhere in the demo data. */
    public function test_the_demo_accounts_have_real_names(): void
    {
        $this->seed(\Database\Seeders\DemoUsersSeeder::class);

        foreach (['Client User', 'Professional User', 'Supplier User'] as $placeholder) {
            $this->assertDatabaseMissing('users', ['name' => $placeholder]);
        }

        $this->assertDatabaseHas('users', ['email' => 'client@example.com', 'name' => 'Dana Whitfield']);
    }
}
