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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title'        => 'Annual Leadership Conference',
            'event_format' => 'virtual',
            'event_type'   => 'Conference',
            'starts_at'    => now()->addMonth()->format('Y-m-d\TH:i'),
            'guest_count'  => 150,
            'platform'     => 'Zoom',
            'meeting_url'  => 'https://zoom.us/j/123456789',
            'services'     => [$this->service->id],
        ], $overrides);
    }

    /** The bug that started this: the platform is kept. */
    public function test_everything_the_form_asks_for_is_stored(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.virtual-hub.store'), $this->payload())
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
            ->post(route('client.virtual-hub.store'), $this->payload(['event_format' => 'hybrid', 'location' => null]))
            ->assertSessionHasErrors('location');

        $this->actingAs($this->client)
            ->post(route('client.virtual-hub.store'), $this->payload(['event_format' => 'hybrid', 'location' => 'Baltimore, MD']))
            ->assertSessionHasNoErrors();
    }

    public function test_a_request_needs_at_least_one_service(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.virtual-hub.store'), $this->payload(['services' => []]))
            ->assertSessionHasErrors('services');
    }

    /** It gets the same approved bidding window as every other request (R37). */
    public function test_it_closes_bidding_on_the_approved_window(): void
    {
        $this->actingAs($this->client)->post(route('client.virtual-hub.store'), $this->payload());

        $event = Event::where('client_id', $this->client->id)->latest('id')->firstOrFail();

        $this->assertNotNull($event->proposal_deadline);
        $this->assertEqualsWithDelta(
            (int) config('bsr.default_proposal_window_hours'),
            now()->diffInHours($event->proposal_deadline),
            1,
        );
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
