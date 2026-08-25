<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Posting a virtual or hybrid event (workflow stages 2 and 3).
 *
 * The form this replaces asked for the platform as radio buttons that carried
 * no value — so the browser submitted "on" — and the controller neither
 * validated nor stored it either way. It also asked for a bidding model,
 * interactive features and a language interpreter, none of which were saved,
 * and prefilled the date with "Oct 25, 2024", a date already in the past that
 * a client could post without noticing.
 *
 * These hold the new form to the rule that broke the old one: everything it
 * asks for is stored, and nothing it stores is invented.
 */
class VirtualHubBriefTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private Category $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client->givePermissionTo('dashboard.view');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();

        $parent = Category::firstOrCreate(['slug' => 'vhb-cat'],
            ['name' => 'Technical Production', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true]);
        $this->service = Category::create([
            'name' => 'Streaming Technician', 'slug' => 'streaming-technician',
            'kind' => Category::SERVICE, 'parent_id' => $parent->id, 'is_active' => true,
        ]);
    }

    /** Step 2 — the plan. */
    private function plan(array $overrides = []): array
    {
        return array_merge([
            'title'        => 'Annual Leadership Conference',
            'event_format' => 'virtual',
            'event_type'   => 'Conference',
            'starts_at'    => now()->addMonth()->format('Y-m-d\TH:i'),
            'guest_count'  => 150,
            'platform'     => 'Zoom',
            'meeting_url'  => 'https://zoom.us/j/123456789',
        ], $overrides);
    }

    /** Step 3 — the services. */
    private function servicesStep(array $overrides = []): array
    {
        return array_merge(['services' => [$this->service->id]], $overrides);
    }

    /** Walk both steps the way a client does. */
    private function postBoth(array $plan = [], array $services = []): \Illuminate\Testing\TestResponse
    {
        $this->actingAs($this->client)
            ->post(route('client.virtual-hub.save', 'plan'), $this->plan($plan))
            ->assertSessionHasNoErrors();

        return $this->actingAs($this->client)
            ->post(route('client.virtual-hub.save', 'services'), $this->servicesStep($services));
    }

    /** The bug that started this: the platform is kept. */
    public function test_everything_the_form_asks_for_is_stored(): void
    {
        $this->postBoth()
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('client.virtual-hub.index'));

        $event = Event::where('client_id', $this->client->id)->latest('id')->firstOrFail();

        $this->assertSame('virtual', $event->event_format);
        $this->assertSame('Zoom', $event->platform);
        $this->assertSame('https://zoom.us/j/123456789', $event->meeting_url);
        $this->assertSame(150, (int) $event->guest_count);
        $this->assertSame('Conference', $event->event_type);
        $this->assertTrue($event->is_published);
        $this->assertTrue($event->categories->contains($this->service->id),
            'The services picked must reach the event professionals bid on.');
    }

    /** A hybrid event happens somewhere; a virtual one does not. */
    public function test_a_hybrid_event_must_name_its_venue(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.virtual-hub.save', 'plan'), $this->plan(['event_format' => 'hybrid', 'location' => null]))
            ->assertSessionHasErrors('location');

        $this->actingAs($this->client)
            ->post(route('client.virtual-hub.save', 'plan'), $this->plan(['event_format' => 'hybrid', 'location' => 'Baltimore, MD']))
            ->assertSessionHasNoErrors();
    }

    public function test_a_request_needs_at_least_one_service(): void
    {
        $this->postBoth([], ['services' => []])->assertSessionHasErrors('services');
    }

    /** It gets the same approved bidding window as every other request (R37). */
    public function test_it_closes_bidding_on_the_approved_window(): void
    {
        $this->postBoth();

        $event = Event::where('client_id', $this->client->id)->latest('id')->firstOrFail();

        $this->assertNotNull($event->proposal_deadline);
        $this->assertEqualsWithDelta(
            (int) config('bsr.default_proposal_window_hours'),
            now()->diffInHours($event->proposal_deadline),
            1,
        );
    }

    /**
     * A rejected submit must say why, where the client is standing.
     *
     * Ali hit Post at the foot of a long form, the page reloaded to the top,
     * and it read as "nothing happened, back to the start" — the error summary
     * was up there, out of sight. Each field now carries its own reason, in
     * words rather than "The starts at field is required".
     */
    public function test_a_rejected_submit_explains_itself_at_the_field(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.virtual-hub.save', 'plan'), $this->plan(['starts_at' => null, 'event_format' => null]))
            ->assertSessionHasErrors(['starts_at', 'event_format']);

        $errors = session('errors');
        $this->assertSame('Pick the date and time your event starts.', $errors->first('starts_at'));
        $this->assertSame('Choose whether this is fully virtual or hybrid.', $errors->first('event_format'));

        // And on the services step, its own reason.
        $this->postBoth([], ['services' => []]);
        $this->assertSame('Pick at least one service you need.', session('errors')->first('services'));
    }

    /** The form renders those reasons beside the fields, not only in a summary. */
    public function test_the_form_shows_errors_inline(): void
    {
        $this->postBoth([], ['services' => []]);

        $html = $this->actingAs($this->client)
            ->get(route('client.virtual-hub.brief', 'services'))->assertOk()->getContent();

        $this->assertStringContainsString('vhb-err', $html,
            'A rejected field must carry its reason next to it.');
        $this->assertStringContainsString('Pick at least one service you need.', $html);
    }

    // ── The two steps ────────────────────────────────────────────

    /**
     * Plan then Services, with a Continue between — the two stages the
     * client's workflow draws. They were one page, so submitting it looked
     * like a jump from step 2 to step 4 with step 3 never seen.
     */
    public function test_the_plan_step_leads_to_the_services_step(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.virtual-hub.save', 'plan'), $this->plan())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('client.virtual-hub.brief', 'services'));
    }

    /**
     * Each step says which of the two it is.
     *
     * This form briefly sat under a seven-card strip that looked like a
     * wizard and was not one. The form itself IS a two-step wizard, so it
     * says so — plainly, and only about itself.
     */
    public function test_each_step_says_which_of_two_it_is(): void
    {
        $plan = $this->actingAs($this->client)
            ->get(route('client.virtual-hub.brief', 'plan'))->assertOk()->getContent();
        $this->assertStringContainsString('Step 1 of 2', $plan);

        $this->actingAs($this->client)->post(route('client.virtual-hub.save', 'plan'), $this->plan());

        $services = $this->actingAs($this->client)
            ->get(route('client.virtual-hub.brief', 'services'))->assertOk()->getContent();
        $this->assertStringContainsString('Step 2 of 2', $services);
    }

    /** The services step needs the plan behind it. */
    public function test_the_services_step_cannot_be_reached_cold(): void
    {
        $this->actingAs($this->client)
            ->get(route('client.virtual-hub.brief', 'services'))
            ->assertRedirect(route('client.virtual-hub.brief', 'plan'));
    }

    /** Nothing is created by the plan step alone. */
    public function test_the_plan_step_creates_no_event(): void
    {
        $this->actingAs($this->client)->post(route('client.virtual-hub.save', 'plan'), $this->plan());

        $this->assertSame(0, Event::where('client_id', $this->client->id)->count(),
            'An event must not exist until the services step is submitted.');
    }

    /** What was entered on step 2 is still there on step 3, and editable. */
    public function test_the_services_step_shows_the_plan_it_is_choosing_for(): void
    {
        $this->actingAs($this->client)->post(route('client.virtual-hub.save', 'plan'), $this->plan());

        $html = $this->actingAs($this->client)
            ->get(route('client.virtual-hub.brief', 'services'))->assertOk()->getContent();

        $this->assertStringContainsString('Annual Leadership Conference', $html);
        $this->assertStringContainsString('150 attending', $html);
        $this->assertStringContainsString(route('client.virtual-hub.brief', 'plan'), $html,
            'There must be a way back to change it.');
    }

    /** Going back keeps what was typed. */
    public function test_going_back_to_plan_keeps_the_answers(): void
    {
        $this->actingAs($this->client)->post(route('client.virtual-hub.save', 'plan'), $this->plan());

        $this->actingAs($this->client)
            ->get(route('client.virtual-hub.brief', 'plan'))->assertOk()
            ->assertSee('Annual Leadership Conference', false)
            ->assertSee('Conference', false);
    }

    /** Once posted, the draft is gone — a second visit starts clean. */
    public function test_posting_clears_the_draft(): void
    {
        $this->postBoth()->assertSessionHasNoErrors();

        $this->actingAs($this->client)
            ->get(route('client.virtual-hub.brief', 'services'))
            ->assertRedirect(route('client.virtual-hub.brief', 'plan'));
    }

    /**
     * The workspace must describe the event you just posted.
     *
     * It picked the event with the latest start date, so posting a new one and
     * landing on the hub showed a different event entirely — the flash naming
     * one, the panel beside it describing another.
     */
    public function test_the_workspace_shows_the_event_just_posted(): void
    {
        // An older event whose date is further out than the new one's.
        $far = Event::create([
            'title' => 'Corporate Gala — Full Production',
            'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'published', 'is_published' => true,
            'starts_at' => now()->addYear(),
        ]);

        $this->postBoth(['starts_at' => now()->addWeek()->format('Y-m-d\TH:i')])
            ->assertSessionHasNoErrors();

        $html = $this->actingAs($this->client)
            ->get(route('client.virtual-hub.index', ['stage' => 5]))->assertOk()->getContent();

        $this->assertStringContainsString('Annual Leadership Conference', $html,
            'The workspace should describe the event that was just posted.');
        $this->assertStringNotContainsString($far->title, $html,
            'A later start date must not outrank the event the client just created.');
    }

    /** A closed request is not the one you are working on. */
    public function test_a_closed_event_is_not_the_active_one(): void
    {
        $this->postBoth()->assertSessionHasNoErrors();
        $event = Event::where('client_id', $this->client->id)->latest('id')->firstOrFail();

        $event->update(['closed_at' => now()]);

        $this->actingAs($this->client)
            ->get(route('client.virtual-hub.index'))->assertOk()
            ->assertViewHas('workspace', null);
    }

    /** No prefilled date, and none in the past. */
    public function test_the_form_offers_no_prefilled_date(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.virtual-hub.brief'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Oct 25, 2024', $html);
        $this->assertStringContainsString('name="starts_at"', $html);
    }
}
