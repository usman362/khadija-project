<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Which state a request carries — SUPERSEDED, and rewritten to match.
 *
 * This file used to assert R38's closing amendment and R71: that a request
 * carries the state of the WORK, not the client's account. The review that
 * locked that wording gave a concrete reason — a client registered in Virginia
 * may hold an event at their office in Maryland, and a request filled from the
 * client's account would reach nobody who could work it.
 *
 * On 2026-08-26 Sir Peter reversed it, deliberately and in writing, naming two
 * rules:
 *
 *   Event Location Rule   event location when provided, home address as
 *                         fallback.
 *   State Boundary Rule   matching is ALWAYS by the client's home state; no
 *                         cross-state, even if the event is out of state.
 *
 * The boundary rule wins, in his words: "each state has its own rules/laws so
 * until we can figure it all out then we will at least get this problem
 * resolved." So the Event Location Rule is recorded and NOT in force, and R71's
 * Virginia/Maryland case is now the ACCEPTED cost rather than the bug: that
 * client reaches Virginia professionals only.
 *
 * The old expectations are kept below as comments beside each test, because
 * the limit is explicitly temporary — when cross-state opens up, this file is
 * where the previous behaviour is written down.
 */
class RequestEventStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function user(string $role, string $state): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->givePermissionTo(['dashboard.view', 'events.create', 'bookings.view_any', 'bookings.update']);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => $state, 'city' => 'Somewhere',
            'service_area_status' => 'supported',
        ]);

        return $u->fresh();
    }

    /** Bookable under either taxonomy version: v1 wants a parent, v2 wants the kind. */
    /** Event type is required at step 1 now, and must be one we actually have. */
    private function eventType(): Category
    {
        return Category::firstOrCreate(
            ['slug' => 'wedding-res'],
            ['name' => 'Wedding', 'kind' => Category::EVENT_TYPE, 'is_active' => true],
        );
    }

    private function service(): Category
    {
        $parent = Category::firstOrCreate(
            ['slug' => 'photo-services'],
            ['name' => 'Photography Services', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true],
        );

        return Category::firstOrCreate(
            ['slug' => 'photography'],
            ['name' => 'Photography', 'kind' => Category::SERVICE, 'is_active' => true, 'parent_id' => $parent->id],
        );
    }

    /** Walk the wizard's first step, which the later steps refuse to skip. */
    private function openEventStep(User $client)
    {
        $this->actingAs($client)->post(route('client.bsr.save', 'service'), [
            'event_type'        => $this->eventType()->name,
            'services'          => [$this->service()->id],
            'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
            'characteristic'    => array_key_first(\App\Http\Controllers\Client\ClientBsrController::CHARACTERISTICS),
        ]);

        return $this->actingAs($client)->get(route('client.bsr.step', 'event'));
    }

    /* ── The case the rule turns on ─────────────────────────── */

    /**
     * A Virginia client holding an event at their Maryland office gets a
     * VIRGINIA request.
     *
     * Until 2026-08-26 this asserted the opposite, and R71 was written for
     * exactly this person. The State Boundary Rule accepts the cost knowingly:
     * they will not reach a Maryland professional who could work it, and are
     * matched inside their own state until cross-state opens up.
     *
     * `event_state` is still posted here on purpose — a stale form or a typed
     * request can still send it, and it must change nothing.
     */
    public function test_an_out_of_state_event_still_carries_the_clients_own_state(): void
    {
        $client = $this->user('client', 'VA');

        $this->actingAs($client)->post(route('client.esr.store'), [
            'organization_type' => 'business',
            'reason'      => array_key_first(\App\Http\Controllers\Client\ClientEsrController::REASONS),
            'needed_by'   => now()->addHours(30)->format('Y-m-d\TH:i'),
            'scope'       => 'single',
            'services'    => [$this->service()->id],
            'location'    => 'Baltimore office',
            'event_state' => 'MD',   // ignored — see the class docblock
        ]);

        $this->assertSame('VA', Event::firstOrFail()->state);
    }

    /**
     * Unchanged, and still the point of the column: whatever state a request
     * carries, only professionals in that state see it. What changed is which
     * state gets written there, not what the column does.
     */
    public function test_only_professionals_in_the_requests_state_can_see_it(): void
    {
        $client = $this->user('client', 'VA');
        $inMd   = $this->user('professional', 'MD');
        $inVa   = $this->user('professional', 'VA');

        Event::create([
            'title' => 'Baltimore shoot', 'client_id' => $client->id, 'created_by' => $client->id,
            'is_published' => true, 'status' => 'published', 'starts_at' => now()->addMonth(),
            'state' => 'MD',
        ]);

        $visibleTo = fn (User $pro) => \App\Support\StateMatching::scopeForViewer(Event::query(), $pro)->count();

        $this->assertSame(1, $visibleTo($inMd), 'a Maryland request is seen in Maryland');
        $this->assertSame(0, $visibleTo($inVa), 'and nowhere else');
    }

    /* ── The default, which is most of the time ─────────────── */

    public function test_the_clients_own_state_is_the_default(): void
    {
        $client = $this->user('client', 'MD');

        $this->assertSame('MD', \App\Support\StateMatching::requestState($client, null));
        $this->assertSame('MD', \App\Support\StateMatching::requestState($client, ''));
    }

    /** Unchanged by the reversal: a state we do not trade in was never honoured. */
    public function test_an_unsupported_state_falls_back_rather_than_being_taken(): void
    {
        $client = $this->user('client', 'MD');

        $this->assertSame('MD', \App\Support\StateMatching::requestState($client, 'CA'));
    }

    /**
     * Whatever arrives in that field, in whatever case, is ignored. It used to
     * be read case-insensitively — 'md' became MD; now nothing is read.
     */
    public function test_a_posted_event_state_is_ignored_whatever_its_case(): void
    {
        $client = $this->user('client', 'VA');

        foreach (['md', 'MD', 'Md', 'CA', ''] as $posted) {
            $this->assertSame('VA', \App\Support\StateMatching::requestState($client, $posted));
        }
    }

    /* ── Direct Offer takes it from the professional ────────── */

    /**
     * No state question is asked there: the client chose the person, and the
     * same-state gate has already refused any pair across a line. Taking it
     * from the professional keeps the offer and the rule agreeing by
     * construction.
     */
    public function test_a_direct_offer_takes_the_state_of_the_professional_chosen(): void
    {
        $client  = $this->user('client', 'MD');
        $pro     = $this->user('professional', 'MD');
        $service = $this->service();

        // The offer is refused unless the professional actually offers the
        // service, which is a separate rule doing its job — not incidental.
        $pro->serviceCategories()->attach($service->id);

        $this->actingAs($client)->post(route('client.direct-offers.store'), [
            'organization_type' => 'individual',
            'professional_id' => $pro->id,
            'event_name'      => 'Garden Party',
            'services'        => [$service->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame('MD', Event::firstOrFail()->state);
    }

    /* ── The form no longer asks ────────────────────────────── */

    /**
     * It used to offer "State the event is in", defaulted to the client's own.
     * Under the boundary rule the answer cannot change the outcome, and the
     * hint under it — "Professionals in this state are the ones who can bid" —
     * was untrue for any state but their own. A control that changes nothing
     * is worse than no control.
     */
    public function test_the_wizard_does_not_offer_a_state_that_changes_nothing(): void
    {
        $client = $this->user('client', 'DE');

        $this->openEventStep($client)
            ->assertOk()
            ->assertDontSee('name="event_state"', false)
            ->assertDontSee('State the event is in', false);
    }

    /** It says who will actually see the request instead. */
    public function test_the_wizard_states_which_professionals_will_see_it(): void
    {
        $client = $this->user('client', 'DE');

        $this->openEventStep($client)
            ->assertOk()
            ->assertSee('Delaware', false)
            ->assertSee('within one state', false);
    }
}
