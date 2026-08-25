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
        $html = $this->hub();

        foreach (['Plan a new event', 'Find a professional', 'Manage my events',
                  'Browse professionals', 'Create a request', 'Send a direct request'] as $route) {
            $this->assertStringContainsString($route, $html);
        }
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

        $html = $this->hub();

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

        $html = $this->hub();

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

    // ── The seven-stage strip (the mockup's own shape) ───────────

    public function test_the_hub_shows_the_seven_stage_journey(): void
    {
        $html = $this->hub();

        foreach (['Entry', 'Plan', 'Services', 'Hire', 'Event workspace', 'Event day', 'Complete'] as $stage) {
            $this->assertStringContainsString($stage, $html);
        }
        $this->assertStringContainsString('You are here', $html);
    }

    /** The marker follows the real work, never a stored step. */
    public function test_the_current_stage_is_read_from_the_bookings(): void
    {
        // No event at all — the client is still choosing what to do.
        $this->actingAs($this->client)->get(route('client.virtual-hub.index'))
            ->assertOk()->assertViewHas('stage', 1);

        $event = $this->event();
        $svc   = $this->service('Streaming Technician');
        $event->categories()->sync([$svc->id]);

        // Posted and waiting — the hiring stage.
        $this->actingAs($this->client)->get(route('client.virtual-hub.index'))
            ->assertOk()->assertViewHas('stage', 4);

        // Somebody booked — the workspace stage.
        Booking::create([
            'event_id' => $event->id, 'category_id' => $svc->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'confirmed', 'price' => 1500, 'currency' => 'USD',
        ]);
        $this->actingAs($this->client)->get(route('client.virtual-hub.index'))
            ->assertOk()->assertViewHas('stage', 5);
    }

    /** With no event, it says so rather than drawing an empty studio. */
    public function test_a_client_with_no_event_is_told_so(): void
    {
        $this->assertStringContainsString('appear here', $this->hub());
    }
}
