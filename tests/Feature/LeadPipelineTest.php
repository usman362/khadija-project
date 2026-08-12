<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checklist rows 134 to 138 — the Lead Pipeline.
 *
 * Rows 135 and 136 turned out to be one fault. "New Leads" counted every open
 * event on the platform: including events in states this professional can
 * never work under R38, and including events they had ALREADY sent a proposal
 * on. That second one is where the duplicate rows came from — the same request
 * appeared once as a new lead and again as Proposal Sent — and it is also why
 * the four stage counts summed past the pipeline they were meant to partition.
 *
 * Row 138 is quieter but worse. Every row printed a ±20% band around whatever
 * single figure was on record, so a client who stated $4,000 was shown to the
 * professional as "$3,200 – $4,800". Nobody said that. It is arithmetic the
 * platform performed on a number it had been given exactly, and on a rush job
 * or a fixed-fee direct offer — which quote one figure by design — it
 * contradicts the request itself.
 */
class LeadPipelineTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro    = $this->account('professional', 'MD');
        $this->client = $this->account('client', 'MD');
    }

    private function account(string $role, string $state): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->givePermissionTo('dashboard.view');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => $state, 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function opportunity(array $attributes = []): Event
    {
        return Event::create(array_merge([
            'title'        => 'Corporate Gala',
            'client_id'    => $this->client->id,
            'created_by'   => $this->client->id,
            'status'       => 'published',
            'is_published' => true,
            'starts_at'    => now()->addDays(30),
            'budget'       => 4000,
            'location'     => 'Baltimore',
        ], $attributes));
    }

    private function page()
    {
        return $this->actingAs($this->pro)->get(route('professional.leads.index'))->assertOk();
    }

    /* ── Rows 135 and 136: one set behind the count and the list ── */

    /**
     * The duplicate. A request this professional has already bid on is not a
     * new lead — it is the Proposal Sent row further down the same list.
     */
    public function test_a_request_already_pursued_is_not_also_a_new_lead(): void
    {
        $event = $this->opportunity();

        Bid::create([
            'event_id' => $event->id, 'supplier_id' => $this->pro->id,
            'amount' => 3800, 'status' => 'submitted',
        ]);

        $stats = $this->page()->viewData('stats');

        $this->assertSame(0, $stats['new'], 'already pursued, so not a fresh lead');
    }

    public function test_a_request_with_a_proposal_out_is_counted_once_not_twice(): void
    {
        $event = $this->opportunity();

        Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'requested', 'price' => 3800,
        ]);

        $stats = $this->page()->viewData('stats');

        $this->assertSame(0, $stats['new']);
        $this->assertSame(1, $stats['proposal']);
        $this->assertSame(1, $stats['total'], 'one request, one place in the pipeline');
    }

    /** A lead in another state is a lead this professional cannot take (R38). */
    public function test_an_out_of_state_opportunity_is_not_counted(): void
    {
        $far = $this->account('client', 'PA');

        $event = $this->opportunity(['client_id' => $far->id, 'created_by' => $far->id]);
        $event->forceFill(['state' => 'PA'])->save();

        $this->assertSame(0, $this->page()->viewData('stats')['new']);
    }

    /** The four stages partition the funnel — that is what row 136 asks for. */
    public function test_the_stages_add_up_to_the_pipeline_total(): void
    {
        $this->opportunity(['title' => 'Untouched one']);
        $this->opportunity(['title' => 'Untouched two']);

        $proposed = $this->opportunity(['title' => 'Proposed on']);
        Booking::create([
            'event_id' => $proposed->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'requested', 'price' => 3800,
        ]);

        $won = $this->opportunity(['title' => 'Won']);
        Booking::create([
            'event_id' => $won->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'confirmed', 'price' => 4200,
        ]);

        $stats = $this->page()->viewData('stats');

        $this->assertSame(
            $stats['total'],
            $stats['new'] + $stats['proposal'] + $stats['negotiation'] + $stats['booked'],
        );
        $this->assertSame(2, $stats['new'], 'only the two nobody has touched');
    }

    /** And the list is a subset of what the counters counted. */
    public function test_the_list_never_shows_more_than_the_counters_claim(): void
    {
        foreach (range(1, 3) as $i) {
            $this->opportunity(['title' => "Opportunity {$i}"]);
        }

        $page = $this->page();

        $this->assertLessThanOrEqual(
            $page->viewData('stats')['total'],
            collect($page->viewData('leads'))->count(),
        );
    }

    /* ── Row 138: the value reads the way the request was made ── */

    /** A client who said $4,000 is shown as $4,000. */
    public function test_a_single_stated_budget_is_shown_as_one_figure(): void
    {
        $this->opportunity(['budget' => 4000, 'budget_min' => null, 'budget_max' => null]);

        $lead = collect($this->page()->viewData('leads'))->first();

        $this->assertFalse($lead['isRange']);
        $this->assertSame(4000.0, $lead['valueLow']);
        $this->page()->assertDontSee('$3,200', false);
    }

    /** A client who gave a range is shown their range, not a computed one. */
    public function test_a_stated_range_is_shown_as_the_clients_own_range(): void
    {
        $this->opportunity(['budget' => 4000, 'budget_min' => 3000, 'budget_max' => 6000]);

        $lead = collect($this->page()->viewData('leads'))->first();

        $this->assertTrue($lead['isRange']);
        $this->assertSame(3000.0, $lead['valueLow']);
        $this->assertSame(6000.0, $lead['valueHigh']);
    }

    /**
     * A rush request quotes one figure by design, so it is never shown as a
     * band even if a range happens to be on the row.
     */
    public function test_an_emergency_request_is_never_shown_as_a_range(): void
    {
        $this->opportunity(['source' => 'esr', 'budget' => 600, 'budget_min' => 400, 'budget_max' => 800]);

        $lead = collect($this->page()->viewData('leads'))->first();

        $this->assertFalse($lead['isRange']);
        $this->assertSame(600.0, $lead['valueLow']);
    }

    public function test_no_budget_on_record_states_that_rather_than_guessing(): void
    {
        $this->opportunity(['budget' => null, 'budget_min' => null, 'budget_max' => null]);

        $lead = collect($this->page()->viewData('leads'))->first();

        $this->assertNull($lead['valueLow']);
        $this->page()->assertSee('Budget not stated', false);
    }

    /* ── Rows 134 and 137: age, and telling stage from priority ── */

    public function test_lead_age_is_measured_from_the_pages_one_today(): void
    {
        $event = $this->opportunity();
        $event->forceFill(['published_at' => now()->subDays(6)])->save();

        $page = $this->page();

        $this->assertTrue($page->viewData('today')->isToday());
        $this->assertSame(6, collect($page->viewData('leads'))->first()['ageDays']);
        $page->assertSee('6 days old', false);
    }

    /** Row 137 — the two labels are told apart by shape as well as colour. */
    public function test_stage_and_priority_are_distinguishable(): void
    {
        $this->opportunity();

        $page = $this->page();

        $page->assertSee('pl-stage-chip', false);
        $page->assertSee('New Lead', false);
        // The priority pill names itself, so it cannot read as another stage.
        $page->assertSee('Priority:', false);
    }
}
