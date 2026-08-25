<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Virtual & Hybrid Hub shows the client's actual event, not a fake studio.
 *
 * The right rail used to be a Live Stream Monitor, Stream Alerts, an Audience
 * Overview and Active Integrations — bitrate, dropped frames, CDN health,
 * viewer counts and stream delay — for a streaming backend that does not
 * exist. The controller's own docblock called it a scaffold. Every figure was
 * a placeholder presented as live telemetry.
 *
 * It is now the event workspace from the client's mockup, built from the
 * systems the mockup says to reuse: bookings and bids.
 */
class VirtualHubWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client->givePermissionTo('dashboard.view');
        $this->client = $this->client->fresh();

        $this->pro = User::factory()->create(['name' => 'Jordan Lee Photography']);
        $this->pro->assignRole('professional');
    }

    private function service(string $name): Category
    {
        $parent = Category::firstOrCreate(['slug' => 'vh-cat'],
            ['name' => 'Production', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true]);

        return Category::create([
            'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name),
            'kind' => Category::SERVICE, 'parent_id' => $parent->id, 'is_active' => true,
        ]);
    }

    private function event(): Event
    {
        return Event::create([
            'title' => 'Annual Leadership Conference', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'is_published' => true, 'starts_at' => now()->addMonth(),
        ]);
    }

    private function hub(): string
    {
        return $this->actingAs($this->client)->get(route('client.virtual-hub.index'))->assertOk()->getContent();
    }

    /** The invented telemetry must never come back. */
    public function test_the_hub_reports_no_streaming_telemetry(): void
    {
        $html = $this->hub();

        foreach (['Dropped Frames', 'CDN Status', 'Stream Alerts', 'Audience Overview',
                  'Active Integrations', 'Stream Delay', 'Gamification'] as $invented) {
            $this->assertStringNotContainsString('>' . $invented . '<', $html,
                "The hub is reporting '{$invented}' again — there is no streaming backend behind it.");
        }
    }

    /** Each service shows the state it is genuinely in. */
    public function test_each_service_shows_its_real_state(): void
    {
        $event = $this->event();
        $booked   = $this->service('Streaming Technician');
        $proposed = $this->service('Event Host');
        $open     = $this->service('Video Editor');
        $event->categories()->sync([$booked->id, $proposed->id, $open->id]);

        Booking::create([
            'event_id' => $event->id, 'category_id' => $booked->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'confirmed', 'price' => 1500, 'currency' => 'USD',
        ]);
        Bid::create([
            'event_id' => $event->id, 'category_id' => $proposed->id,
            'supplier_id' => $this->pro->id, 'amount' => 900, 'status' => 'submitted',
        ]);

        $html = $this->hub();

        $this->assertStringContainsString('Streaming Technician', $html);
        $this->assertStringContainsString('Jordan Lee Photography', $html);
        $this->assertStringContainsString('Booked', $html);
        $this->assertStringContainsString('Proposals in', $html);
        $this->assertStringContainsString('Still open', $html);
        $this->assertStringContainsString('1 of 3 services booked', $html);
    }

    /** The stage is read from the bookings, not stored and trusted. */
    public function test_the_stage_follows_the_work(): void
    {
        $event = $this->event();
        $svc   = $this->service('Streaming Technician');
        $event->categories()->sync([$svc->id]);

        // Nothing yet — planning.
        $this->assertStringContainsString('Planning', $this->hub());

        // A proposal arrives — hiring.
        Bid::create([
            'event_id' => $event->id, 'category_id' => $svc->id,
            'supplier_id' => $this->pro->id, 'amount' => 900, 'status' => 'submitted',
        ]);
        $this->assertStringContainsString('Hiring', $this->hub());

        // Someone is booked — preparation.
        Booking::create([
            'event_id' => $event->id, 'category_id' => $svc->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'confirmed', 'price' => 1500, 'currency' => 'USD',
        ]);
        $this->assertStringContainsString('Preparation', $this->hub());
    }

    // ── The workflow's seven stages ──────────────────────────────

    /** Stage 1 (entry) and stage 4 (hire) are doors onto systems that exist. */
    public function test_the_hub_offers_the_entry_and_hire_routes(): void
    {
        // Entry's three choices live on stage 1...
        $entry = $this->actingAs($this->client)
            ->get(route('client.virtual-hub.index', ['stage' => 1]))->assertOk()->getContent();
        foreach (['Plan a new event', 'Find a professional', 'Manage my events'] as $choice) {
            $this->assertStringContainsString($choice, $entry);
        }

        // ...and Hire's three routes on stage 4, not both at once.
        $hire = $this->actingAs($this->client)
            ->get(route('client.virtual-hub.index', ['stage' => 4]))->assertOk()->getContent();
        foreach (['Three ways to hire', 'Create a request', 'Send a direct request'] as $route) {
            $this->assertStringContainsString($route, $hire);
        }
        $this->assertStringNotContainsString('Plan a new event', $hire);
    }

    /** Stage 6 appears on the day, with only what the client actually gave us. */
    public function test_event_day_shows_the_platform_and_link_when_there_is_one(): void
    {
        $event = $this->event();
        $event->update([
            'starts_at'   => now()->addHours(2),
            'platform'    => 'Zoom',
            'meeting_url' => 'https://zoom.us/j/123456789',
        ]);

        $html = $this->actingAs($this->client)
            ->get(route('client.virtual-hub.index', ['stage' => 6]))->assertOk()->getContent();

        $this->assertStringContainsString('Event day', $html);
        $this->assertStringContainsString('Zoom', $html);
        $this->assertStringContainsString('https://zoom.us/j/123456789', $html);

        // No integration exists, so no connection status may be claimed.
        $this->assertStringNotContainsString('Connection', $html);
    }

    /** Without a link, it says so instead of showing a dead Join button. */
    public function test_event_day_without_a_link_says_so(): void
    {
        $this->event()->update(['starts_at' => now()->addHour()]);

        $html = $this->actingAs($this->client)
            ->get(route('client.virtual-hub.index', ['stage' => 6]))->assertOk()->getContent();

        $this->assertStringContainsString('No joining link saved', $html);
        $this->assertStringNotContainsString('Join event', $html);
    }

    /** Stage 7 offers only closing actions that lead somewhere real. */
    public function test_a_finished_event_offers_the_closing_actions(): void
    {
        $event = $this->event();
        $svc   = $this->service('Streaming Technician');
        $event->categories()->sync([$svc->id]);

        Booking::create([
            'event_id' => $event->id, 'category_id' => $svc->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'completed', 'price' => 1500, 'currency' => 'USD',
        ]);

        $html = $this->hub();

        $this->assertStringContainsString('Event complete', $html);
        $this->assertStringContainsString('Release payment', $html);
        $this->assertStringContainsString('Book again', $html);

        // There is no deliverables model, so no button pretends there is.
        $this->assertStringNotContainsString('Deliverables', $html);
    }

    // ── One stage at a time ──────────────────────────────────────

    /**
     * The mockup's own closing promise: "contextual tools appear when needed,
     * not all at once." The hub was the opposite — entry choices, hiring
     * routes, filters, a service grid, a professional grid, an RFP table and
     * three event panels on screen together. Ali: "jahan click karo kahin na
     * kahin chala jata."
     */
    public function test_each_stage_shows_only_its_own_panel(): void
    {
        $event = $this->event();
        $svc   = $this->service('Streaming Technician');
        $event->categories()->sync([$svc->id]);

        $open = fn (int $n) => $this->actingAs($this->client)
            ->get(route('client.virtual-hub.index', ['stage' => $n]))->assertOk()->getContent();

        $entry = $open(1);
        $this->assertStringContainsString('Plan a new event', $entry);
        $this->assertStringNotContainsString('Three ways to hire', $entry);

        $hire = $open(4);
        $this->assertStringContainsString('Three ways to hire', $hire);
        $this->assertStringNotContainsString('Plan a new event', $hire);

        $day = $open(6);
        $this->assertStringNotContainsString('Three ways to hire', $day);
        $this->assertStringNotContainsString('Plan a new event', $day);
    }

    /** A stage that describes an event is not offered without one. */
    public function test_event_stages_fall_back_when_there_is_no_event(): void
    {
        $this->actingAs($this->client)
            ->get(route('client.virtual-hub.index', ['stage' => 6]))
            ->assertOk()
            ->assertViewHas('stage', 1);
    }

    /** Where the event has got to decides which stage opens first. */
    public function test_the_opening_stage_follows_the_event(): void
    {
        $this->actingAs($this->client)
            ->get(route('client.virtual-hub.index'))->assertOk()->assertViewHas('stage', 1);

        $event = $this->event();
        $svc   = $this->service('Streaming Technician');
        $event->categories()->sync([$svc->id]);

        // Posted and waiting — opens on Hire.
        $this->actingAs($this->client)
            ->get(route('client.virtual-hub.index'))->assertOk()->assertViewHas('stage', 4);

        Booking::create([
            'event_id' => $event->id, 'category_id' => $svc->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'confirmed', 'price' => 1500, 'currency' => 'USD',
        ]);

        // Someone booked — opens on the workspace.
        $this->actingAs($this->client)
            ->get(route('client.virtual-hub.index'))->assertOk()->assertViewHas('stage', 5);
    }

    /** The four tiles that opened nothing are gone. */
    public function test_the_dead_feature_tiles_are_gone(): void
    {
        $html = $this->hub();

        foreach (['Virtual Venue Builder', 'Stream Assistant', 'Analytics Dashboard', 'Engagement Tools'] as $tile) {
            $this->assertStringNotContainsString($tile, $html,
                "'{$tile}' had no href — it opened nothing.");
        }
    }

    // ── A way out ────────────────────────────────────────────────

    /**
     * Reported by Ali: the hub showed his event parked on stage 4 with no way
     * to cancel it. The stage strip borrows the request wizard's look, which
     * you click through, so it read as a wizard he was stuck in — and there
     * genuinely was no exit on this screen.
     */
    public function test_the_workspace_offers_a_way_to_close_the_request(): void
    {
        $event = $this->event();

        $html = $this->hub();

        $this->assertStringContainsString('Close this request', $html);
        $this->assertStringContainsString(route('client.events.close', $event), $html,
            'The close action must use the existing close route, not a second way to end a request.');
    }

    /** Closing it actually closes it, and the hub stops offering to again. */
    public function test_closing_the_request_works_from_the_hub(): void
    {
        $event = $this->event();

        $this->actingAs($this->client)
            ->post(route('client.events.close', $event))
            ->assertRedirect();

        $this->assertNotNull($event->fresh()->closed_at, 'The request should be closed.');
    }

    /**
     * Ali, on the brief screen: "Entry wale step me kese jayega?" — there was
     * no way back. The only route out was a Cancel button at the foot of a long
     * form, and the strip's Entry card looked clickable but was not.
     */
    public function test_the_reachable_stages_are_links(): void
    {
        // Entry and Hire are separate stages now, each on its own tab.
        $entry = $this->actingAs($this->client)
            ->get(route('client.virtual-hub.index', ['stage' => 1]))->assertOk()->getContent();
        $this->assertStringContainsString('Plan a new event', $entry);
        $this->assertStringContainsString('Find a professional', $entry);

        $hire = $this->actingAs($this->client)
            ->get(route('client.virtual-hub.index', ['stage' => 4]))->assertOk()->getContent();
        $this->assertStringContainsString('Browse professionals', $hire);
        $this->assertStringContainsString('Send a direct request', $hire);
    }

    /** A stage with nowhere to go is plain text, not a link that does nothing. */
    public function test_event_stages_only_link_once_an_event_exists(): void
    {
        // No event: the last three stages have no destination.
        $html = $this->actingAs($this->client)
            ->get(route('client.virtual-hub.brief'))->assertOk()->getContent();
        $this->assertSame(4, substr_count($html, 'class="vhs-hit"'),
            'Only Entry, Plan, Services and Hire lead anywhere before an event exists.');

        // With an event, the workspace stages point at it.
        $event = $this->event();
        $html  = $this->hub();
        $this->assertStringContainsString(route('client.events.show', $event), $html);
    }

    /** The strip says what it is, because it looks like something it is not. */
    public function test_the_strip_says_it_is_wayfinding(): void
    {
        $html = $this->hub();

        $this->assertStringContainsString('Pick a step to see just that part', $html);

        // It must not claim to track the event — that was the confusion.
        $this->assertStringNotContainsString('Where your event is now', $html);
    }

    // ── The seven-stage strip (the mockup's own shape) ───────────

    public function test_the_hub_shows_the_seven_stage_journey(): void
    {
        $html = $this->hub();

        foreach (['Entry', 'Plan', 'Services', 'Hire', 'Event workspace', 'Event day', 'Complete'] as $stage) {
            $this->assertStringContainsString($stage, $html);
        }
        $this->assertStringContainsString('You are here', $html);
    }

    /**
     * The marker means "this screen", not "this event".
     *
     * It used to be derived from the event's bookings, so a client who clicked
     * Entry landed on the hub and was told "You are here: Hire" — the strip
     * answering a question they had not asked. Ali hit it immediately. Where
     * the event is has its own display in the workspace panel; this one is
     * wayfinding and nothing else.
     */
    public function test_the_marker_follows_the_page_not_the_event(): void
    {
        $event = $this->event();
        $svc   = $this->service('Streaming Technician');
        $event->categories()->sync([$svc->id]);

        // An event mid-hire must not move the marker off Entry on the hub.
        Booking::create([
            'event_id' => $event->id, 'category_id' => $svc->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'confirmed', 'price' => 1500, 'currency' => 'USD',
        ]);

        $hub = $this->hub();
        $this->assertMatchesRegularExpression(
            '/Entry.*?You are here/s',
            preg_replace('/<[^>]+>/', ' ', $hub),
            'The hub is the Entry screen, whatever stage the event has reached.',
        );

        // And the brief is always the Plan screen.
        $brief = $this->actingAs($this->client)
            ->get(route('client.virtual-hub.brief'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression(
            '/Plan.*?You are here/s',
            preg_replace('/<[^>]+>/', ' ', $brief),
        );
    }

    /** With no event, it says so rather than drawing an empty studio. */
    public function test_a_client_with_no_event_is_told_so(): void
    {
        $this->assertStringContainsString('appear here', $this->hub());
    }
}
