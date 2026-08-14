<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R38's closing amendment and R71 — a request carries the state of the WORK.
 *
 * The locked wording compares professional.state to the EVENT's state, and the
 * review that locked it gave the reason: a client registered in Virginia may
 * hold an event at their office in Maryland. Before this, the column was named
 * for the event and filled from the client's account — the substitution the
 * rule exists to forbid — so a Maryland event went out to Virginia
 * professionals and reached nobody who could work it.
 *
 * The client's own state remains the default, because it is the answer nearly
 * every time.
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
            'services'          => [$this->service()->id],
            'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
            'characteristic'    => array_key_first(\App\Http\Controllers\Client\ClientBsrController::CHARACTERISTICS),
        ]);

        return $this->actingAs($client)->get(route('client.bsr.step', 'event'));
    }

    /* ── The case the rule was written for ──────────────────── */

    /** A Virginia client holding an event in Maryland gets a Maryland request. */
    public function test_an_event_takes_the_state_it_happens_in_not_the_clients(): void
    {
        $client = $this->user('client', 'VA');

        $this->actingAs($client)->post(route('client.esr.store'), [
            'event_name'  => 'Server room power failure',
            'reason'      => array_key_first(\App\Http\Controllers\Client\ClientEsrController::REASONS),
            'needed_by'   => now()->addHours(30)->format('Y-m-d\TH:i'),
            'scope'       => 'single',
            'services'    => [$this->service()->id],
            'location'    => 'Baltimore office',
            'event_state' => 'MD',
        ]);

        $this->assertSame('MD', Event::firstOrFail()->state, 'the work is in Maryland');
    }

    /** And the professionals it reaches are the ones who can actually work it. */
    public function test_the_request_reaches_professionals_where_the_work_is(): void
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

        $this->assertSame(1, $visibleTo($inMd), 'the Maryland professional can work it');
        $this->assertSame(0, $visibleTo($inVa), "the client's own state is not what decides this");
    }

    /* ── The default, which is most of the time ─────────────── */

    public function test_the_clients_own_state_is_the_default(): void
    {
        $client = $this->user('client', 'MD');

        $this->assertSame('MD', \App\Support\StateMatching::requestState($client, null));
        $this->assertSame('MD', \App\Support\StateMatching::requestState($client, ''));
    }

    /** A state we do not trade in is not honoured — there is nobody there. */
    public function test_an_unsupported_state_falls_back_rather_than_being_taken(): void
    {
        $client = $this->user('client', 'MD');

        $this->assertSame('MD', \App\Support\StateMatching::requestState($client, 'CA'));
    }

    public function test_the_choice_is_case_insensitive(): void
    {
        $this->assertSame('MD', \App\Support\StateMatching::requestState($this->user('client', 'VA'), 'md'));
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
            'professional_id' => $pro->id,
            'event_name'      => 'Garden Party',
            'services'        => [$service->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame('MD', Event::firstOrFail()->state);
    }

    /* ── The form asks ──────────────────────────────────────── */

    public function test_the_wizard_asks_which_state_the_event_is_in(): void
    {
        $client = $this->user('client', 'MD');

        $this->openEventStep($client)
            ->assertOk()
            ->assertSee('name="event_state"', false)
            ->assertSee('State the event is in', false);
    }

    /** With the client's own state already chosen, so the common case is one click. */
    public function test_the_wizard_preselects_the_clients_own_state(): void
    {
        $client = $this->user('client', 'DE');

        $html = $this->openEventStep($client)->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<option value="DE"\s+selected/', $html);
    }
}
