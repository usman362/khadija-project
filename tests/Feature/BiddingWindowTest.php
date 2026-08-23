<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bidding windows, per the Owner's decision of 2026-08-22: a standard request
 * accepts bids for 48 hours, an emergency one for 2 hours with bids shown in
 * real time (never sealed until the close). R37 forbade inventing these values,
 * so until they were approved a request could stay open indefinitely. In both
 * cases the window auto-shortens so it never outlasts the event itself.
 */
class BiddingWindowTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create();
        $u->assignRole('client');
        $u->givePermissionTo('dashboard.view');
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $u->fresh();
    }

    /** A bookable service under either taxonomy — kind for v2, a parent for v1. */
    private function service(string $name, string $slug): Category
    {
        $parent = Category::firstOrCreate(
            ['slug' => 'window-services'],
            ['name' => 'Services', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true],
        );

        return Category::create([
            'name' => $name, 'slug' => $slug,
            'kind' => Category::SERVICE, 'parent_id' => $parent->id, 'is_active' => true,
        ]);
    }

    public function test_the_approved_windows_are_configured(): void
    {
        $this->assertSame(48, (int) config('bsr.default_proposal_window_hours'));
        $this->assertSame(2, (int) config('bsr.esr.default_window_hours'));
    }

    public function test_a_rush_request_closes_in_two_hours(): void
    {
        // kind + parent: an event type would now be refused as the service
        // requested, so the fixture has to say which this is.
        $category = $this->service('Photography', 'photography');

        $this->actingAs($this->client)->post(route('client.esr.store'), [
                'organization_type' => 'individual',
            'event_name'  => 'Replacement DJ needed',
            'reason'      => 'professional_cancelled',
            'description' => 'Our DJ cancelled and we need a replacement for Saturday evening.',
            'needed_by'   => now()->addDays(3)->format('Y-m-d H:i:s'),
            'services'    => [$category->id],
            'scope'       => 'single',
            'location'    => 'Baltimore, MD',
        ])->assertRedirect();

        $event = Event::where('source', 'esr')->latest('id')->first();

        $this->assertNotNull($event, 'the rush request was created');
        $this->assertNotNull($event->proposal_deadline, 'a rush request must have a closing time');
        $this->assertEqualsWithDelta(
            2,
            now()->diffInHours($event->proposal_deadline),
            1,
            'approved emergency window is 2 hours',
        );

        // Owner 2026-08-22: emergency bids are shown in real time, not sealed.
        $this->assertFalse((bool) $event->sealed_proposals,
            'an emergency request must not hold its bids until the window closes');
    }

    public function test_the_window_never_outlasts_the_event_itself(): void
    {
        $category = $this->service('Catering', 'catering');

        // Needed in six hours — bidding cannot still be open tomorrow.
        $neededBy = now()->addHours(6);

        $this->actingAs($this->client)->post(route('client.esr.store'), [
                'organization_type' => 'individual',
            'event_name'  => 'Emergency catering',
            'reason'      => 'no_show',
            'description' => 'Caterer pulled out this morning and we need cover for tonight.',
            'needed_by'   => $neededBy->format('Y-m-d H:i:s'),
            'services'    => [$category->id],
            'scope'       => 'single',
            'location'    => 'Baltimore, MD',
        ])->assertRedirect();

        $event = Event::where('source', 'esr')->latest('id')->first();

        $this->assertNotNull($event->proposal_deadline);
        $this->assertTrue(
            $event->proposal_deadline->lte($neededBy->addMinute()),
            'bidding must close by the time the service is needed',
        );
    }

    /**
     * Walk the standard-request wizard to publish, with whatever the client
     * put on the proposals step (a deadline, or nothing).
     */
    private function publishStandardRequest(Category $service, array $proposals): Event
    {
        Category::firstOrCreate(
            ['slug' => 'wedding'],
            ['name' => 'Wedding', 'kind' => Category::EVENT_TYPE, 'is_active' => true],
        );

        $save = fn (string $step, array $data) => $this->actingAs($this->client)
            ->post(route('client.bsr.save', $step), $data)->assertSessionHasNoErrors();

        $save('service', [
            'services' => [$service->id], 'event_type' => 'Wedding',
            'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
            'characteristic' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::CHARACTERISTICS),
        ]);
        $save('event', [
            'title' => 'Spring Wedding', 'starts_at' => now()->addMonths(3)->format('Y-m-d\TH:i'),
            'location' => 'Baltimore', 'guest_count' => 120, 'event_state' => 'MD',
        ]);
        $save('requirements', ['description' => 'A photographer for a spring wedding, 120 guests, all day.']);
        $save('budget', ['budget_min' => 2000, 'budget_max' => 3000]);
        $save('proposals', $proposals);
        $save('files', []);
        $this->actingAs($this->client)->post(route('client.bsr.save', 'review'), ['confirm' => 1])
            ->assertSessionHasNoErrors();

        return Event::where('client_id', $this->client->id)->latest('id')->firstOrFail();
    }

    /** No deadline set — the 48-hour default is applied, not a refusal. */
    public function test_a_standard_request_left_blank_gets_the_forty_eight_hour_default(): void
    {
        $event = $this->publishStandardRequest($this->service('Photography', 'photography'), []);

        $this->assertTrue($event->is_published);
        $this->assertNotNull($event->proposal_deadline);
        $this->assertEqualsWithDelta(48, now()->diffInHours($event->proposal_deadline), 1,
            'a blank deadline should fall back to the approved 48-hour window');
    }

    /** The client may set a shorter deadline, and it is respected. */
    public function test_a_client_may_set_a_shorter_deadline(): void
    {
        $event = $this->publishStandardRequest(
            $this->service('DJ', 'dj'),
            ['proposal_deadline' => now()->addHours(30)->format('Y-m-d\TH:i')],
        );

        $this->assertEqualsWithDelta(30, now()->diffInHours($event->proposal_deadline), 1,
            'a client-set deadline must win over the default');
    }
}
