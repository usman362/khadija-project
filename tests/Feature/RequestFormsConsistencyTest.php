<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The four request forms, made to agree with each other (2026-08-20).
 *
 * Every one of these came from the Owner or the PM looking at the real screens:
 *
 *   BR asked the event type LAST and optionally, so a client arriving from Post
 *   Event could skip it and the request had no event type on it at all — and
 *   the event type is what the relevance matrix orders the services by.
 *
 *   ER asked "What do you need?" as free text AND showed the service picker
 *   underneath, so it asked the same thing twice. BR was held up as the one
 *   that "is clean with only asking once".
 *
 *   "This request is for" existed on BR alone, so three of the four forms
 *   collected nothing about who the client is.
 *
 *   An event not on the list had nowhere to go. It picks "Other Event" now and
 *   the client's own wording rides beside it — never instead of it, because
 *   free text as the event type leaves the matrix nothing to order by.
 */
class RequestFormsConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private Category $wedding;
    private Category $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        config(['taxonomy.version' => 'v2']);

        $this->client = $this->account('client');

        $this->wedding = $this->cat('Wedding', Category::EVENT_TYPE);
        $this->cat('Other Event', Category::EVENT_TYPE);

        $parent = $this->cat('Photography & Videography', Category::SERVICE_CATEGORY);
        $this->service = $this->cat('Event Photography', Category::SERVICE, ['parent_id' => $parent->id]);
    }

    private function account(string $role, string $state = 'MD'): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        UserProfile::updateOrCreate(['user_id' => $user->id], [
            'country' => 'US', 'state' => $state, 'city' => 'Baltimore',
            'service_area_status' => 'supported',
        ]);

        return $user->fresh();
    }

    private function cat(string $name, string $kind, array $over = []): Category
    {
        return Category::create(array_merge([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-rfc',
            'kind' => $kind,
            'taxonomy_version' => 'v2',
            'is_active' => true,
        ], $over));
    }

    private function step1(array $over = [])
    {
        return $this->actingAs($this->client)->post(route('client.bsr.save', 'service'), array_merge([
            'services'          => [$this->service->id],
            'event_type'        => $this->wedding->name,
            'organization_type' => 'individual',
        ], $over));
    }

    // ── G: event type, first and required ────────────────────────

    public function test_a_request_cannot_be_started_without_an_event_type(): void
    {
        $this->step1(['event_type' => ''])->assertSessionHasErrors('event_type');
    }

    public function test_an_event_type_we_do_not_have_is_refused(): void
    {
        // Free text here would break the relevance matrix — an event type
        // nothing recognises has no archetype, so there is nothing to order
        // the services by.
        $this->step1(['event_type' => 'Maryland Horse Show'])->assertSessionHasErrors('event_type');
    }

    public function test_the_event_type_is_asked_before_the_services(): void
    {
        $html = $this->actingAs($this->client)->get(route('client.bsr.step', 'service'))
            ->assertOk()->getContent();

        $this->assertLessThan(
            strpos($html, 'name="services[]"'),
            strpos($html, 'name="event_type"'),
            'the event type still sits below the services',
        );
    }

    // ── H: an event that is not on the list ──────────────────────

    public function test_other_lets_the_client_use_their_own_words(): void
    {
        $this->step1([
            'event_type'  => \App\Http\Controllers\Client\ClientBsrController::OTHER_EVENT_TYPE,
            'event_title' => "Maryland's Horse Show Event",
        ])->assertSessionHasNoErrors();

        $state = session('bsr_wizard');

        $this->assertSame('Other Event', $state['event_type']);
        // Kept beside the list entry, and used as the working title so they
        // name their event once rather than twice.
        $this->assertSame("Maryland's Horse Show Event", $state['title']);
    }

    public function test_other_without_a_title_is_refused(): void
    {
        $this->step1([
            'event_type'  => \App\Http\Controllers\Client\ClientBsrController::OTHER_EVENT_TYPE,
            'event_title' => '',
        ])->assertSessionHasErrors('event_title');
    }

    public function test_going_back_to_a_real_event_type_drops_the_private_wording(): void
    {
        $this->step1([
            'event_type'  => \App\Http\Controllers\Client\ClientBsrController::OTHER_EVENT_TYPE,
            'event_title' => 'Some one-off thing',
        ]);

        $this->step1(['event_type' => $this->wedding->name]);

        $this->assertNull(session('bsr_wizard')['event_title'],
            'wording for an event they are no longer describing');
    }

    // ── C: ER asks once, like BR ─────────────────────────────────

    public function test_the_emergency_form_no_longer_asks_what_you_need_twice(): void
    {
        $html = $this->actingAs($this->client)->get(route('client.esr.create'))
            ->assertOk()->getContent();

        $this->assertStringNotContainsString('name="event_name"', $html,
            'the free-text question is back beside the service picker');
    }

    public function test_the_emergency_request_titles_itself_from_the_service(): void
    {
        $this->actingAs($this->client)->post(route('client.esr.store'), [
            'organization_type' => 'individual',
            'reason'      => array_key_first(\App\Http\Controllers\Client\ClientEsrController::REASONS),
            'needed_by'   => now()->addHours(30)->format('Y-m-d\TH:i'),
            'services'    => [$this->service->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame('Urgent: Event Photography', Event::firstOrFail()->title);
    }

    // ── E: "This request is for", on all of them ─────────────────

    public function test_every_request_form_asks_who_it_is_for(): void
    {
        foreach ([
            route('client.bsr.step', 'service'),
            route('client.esr.create'),
            route('client.post-event.event-info'),
        ] as $url) {
            $this->actingAs($this->client)->get($url)
                ->assertOk()
                ->assertSee('name="organization_type"', false);
        }
    }

    public function test_the_emergency_request_stores_who_it_is_for(): void
    {
        $this->actingAs($this->client)->post(route('client.esr.store'), [
            'organization_type' => 'nonprofit',
            'reason'      => array_key_first(\App\Http\Controllers\Client\ClientEsrController::REASONS),
            'needed_by'   => now()->addHours(30)->format('Y-m-d\TH:i'),
            'services'    => [$this->service->id],
        ]);

        $this->assertSame('nonprofit', Event::firstOrFail()->organization_type);
    }

    public function test_the_four_options_are_defined_once(): void
    {
        // Four copies of the same four options is four chances for them to
        // drift apart.
        $this->assertSame(
            Event::ORGANIZATION_TYPES,
            \App\Http\Controllers\Client\ClientBsrController::ORG_TYPES,
        );
    }

    // ── F: the door between an event and its request ─────────────

    public function test_an_unpublished_event_links_back_into_its_request(): void
    {
        /*
         * A bidding request does not sit beside an event — it creates one. But
         * the event page had publish, reopen, extend, duplicate and close, and
         * no way to open the request itself, so the two halves of one record
         * had no door between them.
         */
        $this->step1();
        $this->actingAs($this->client)->post(route('client.bsr.save', 'service'), [
            'services' => [$this->service->id], 'event_type' => $this->wedding->name,
            'organization_type' => 'individual',
            'action' => 'draft',
        ]);

        $event = Event::firstOrFail();
        $this->assertFalse((bool) $event->is_published);

        $this->actingAs($this->client)->get(route('client.events.show', $event))
            ->assertOk()
            ->assertSee(route('client.bsr.resume', $event), false)
            ->assertSee('Continue this request');
    }

    public function test_the_door_only_opens_for_the_client_who_owns_it(): void
    {
        $this->step1();
        $this->actingAs($this->client)->post(route('client.bsr.save', 'service'), [
            'services' => [$this->service->id], 'event_type' => $this->wedding->name,
            'organization_type' => 'individual',
            'action' => 'draft',
        ]);

        $this->actingAs($this->account('client'))
            ->get(route('client.bsr.resume', Event::firstOrFail()))
            ->assertForbidden();
    }
}
