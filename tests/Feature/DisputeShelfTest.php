<?php

namespace Tests\Feature;

use App\Domain\Disputes\DisputeClassification;
use App\Domain\Disputes\DisputeStates;
use App\Models\Booking;
use App\Models\DisputeCase;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rule R34 Phase 2 — the Disputes & Resolution landing screen.
 *
 * The screen states four numbers about a person's cases and then offers five
 * tabs that filter them. The thing worth guarding is that the numbers and the
 * tabs are the same reading of the same cases — and, above all, that "Waiting
 * on You" only says so when the state machine actually says so. Telling
 * somebody to act when there is nothing to act on is worse than saying nothing.
 */
class DisputeShelfTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $professional;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client       = $this->account('client');
        $this->professional = $this->account('professional');

        $event = Event::create([
            'title'      => 'Garden wedding',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->addMonth(),
        ]);

        $this->booking = Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $this->client->id,
            'supplier_id' => $this->professional->id,
            'created_by'  => $this->client->id,
            'status'      => 'completed',
            'price'       => 1500,
        ]);
    }

    private function account(string $role): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function case(array $attributes = []): DisputeCase
    {
        return DisputeCase::create(array_merge([
            'booking_id'      => $this->booking->id,
            'filed_by'        => $this->client->id,
            'client_id'       => $this->client->id,
            'professional_id' => $this->professional->id,
            'severity'        => DisputeClassification::SEVERITY_QUALITY,
            'taxonomy'        => 'incomplete_service',
            'summary'         => 'Two of the four agreed hours were not delivered on the day.',
        ], $attributes));
    }

    private function counts(User $user, array $query = []): array
    {
        return $this->actingAs($user)->get(route('disputes.index', $query))
            ->assertOk()->viewData('counts');
    }

    // ── "Waiting on You" ─────────────────────────────────────────

    public function test_awaiting_a_response_waits_on_the_other_party_not_the_filer(): void
    {
        $this->case(['state' => DisputeStates::AWAITING_RESPONSE]);

        $this->assertSame(1, $this->counts($this->professional)['action'], 'the respondent is the one being asked');
        $this->assertSame(0, $this->counts($this->client)['action'], 'the person who filed it is not waiting on themselves');
    }

    public function test_a_cure_period_waits_on_the_professional_who_has_to_cure(): void
    {
        $this->case(['state' => DisputeStates::CURE_PERIOD]);

        $this->assertSame(1, $this->counts($this->professional)['action']);
        $this->assertSame(0, $this->counts($this->client)['action']);
    }

    public function test_a_case_under_review_waits_on_nobody_here(): void
    {
        // It is with the platform. Neither party can do anything about it, so
        // neither is told they can.
        $this->case(['state' => DisputeStates::FORMAL_INVESTIGATION]);

        $this->assertSame(0, $this->counts($this->client)['action']);
        $this->assertSame(0, $this->counts($this->professional)['action']);
    }

    // ── Each tile counts the list its tab opens (R1/R6) ──────────

    public function test_every_tile_counts_the_list_its_tab_shows(): void
    {
        $this->case(['state' => DisputeStates::AWAITING_RESPONSE]);
        $this->case(['state' => DisputeStates::FORMAL_INVESTIGATION]);
        $this->case(['state' => DisputeStates::DECIDED]);
        $this->case(['state' => DisputeStates::CLOSED]);

        $counts = $this->counts($this->professional);

        foreach (['action', 'review', 'resolved'] as $tab) {
            $rows = $this->actingAs($this->professional)
                ->get(route('disputes.index', ['tab' => $tab]))
                ->assertOk()->viewData('cases');

            $this->assertCount($counts[$tab], $rows, "the {$tab} tab shows what its tile counted");
        }
    }

    public function test_open_cases_excludes_the_terminal_ones(): void
    {
        $this->case(['state' => DisputeStates::DIRECT_RESOLUTION]);
        $this->case(['state' => DisputeStates::CLOSED]);
        $this->case(['state' => DisputeStates::WITHDRAWN]);
        $this->case(['state' => DisputeStates::EXPIRED]);

        $this->assertSame(1, $this->counts($this->client)['open']);
    }

    public function test_the_resolved_tile_counts_the_last_thirty_days_because_that_is_what_it_says(): void
    {
        $recent = $this->case(['state' => DisputeStates::DECIDED]);
        $old    = $this->case(['state' => DisputeStates::DECIDED]);
        $old->forceFill(['updated_at' => now()->subMonths(4)])->saveQuietly();

        $this->assertSame(1, $this->counts($this->client)['resolved']);

        // The tab is not date-limited — the tile's own label is what scopes it.
        $rows = $this->actingAs($this->client)->get(route('disputes.index', ['tab' => 'resolved']))
            ->assertOk()->viewData('cases');

        $this->assertCount(2, $rows);
        $this->assertNotNull($recent->fresh());
    }

    // ── Filters ──────────────────────────────────────────────────

    public function test_the_issue_filter_narrows_to_that_classification(): void
    {
        $this->case(['taxonomy' => 'payment_dispute']);
        $this->case(['taxonomy' => 'no_show']);

        $rows = $this->actingAs($this->client)
            ->get(route('disputes.index', ['taxonomy' => 'payment_dispute']))
            ->assertOk()->viewData('cases');

        $this->assertCount(1, $rows);
        $this->assertSame('payment_dispute', $rows->first()->taxonomy);
    }

    public function test_the_date_range_narrows_by_when_the_case_was_opened(): void
    {
        $this->case();
        $old = $this->case();
        $old->forceFill(['created_at' => now()->subMonths(5)])->saveQuietly();

        $this->assertCount(2, $this->actingAs($this->client)
            ->get(route('disputes.index'))->assertOk()->viewData('cases'));

        $this->assertCount(1, $this->actingAs($this->client)
            ->get(route('disputes.index', ['range' => '30']))->assertOk()->viewData('cases'));
    }

    public function test_a_made_up_filter_falls_back_rather_than_emptying_the_page(): void
    {
        $this->case();

        foreach ([['tab' => 'nonsense'], ['range' => '999'], ['taxonomy' => 'gremlins']] as $query) {
            $this->assertCount(1, $this->actingAs($this->client)
                ->get(route('disputes.index', $query))->assertOk()->viewData('cases'));
        }
    }

    public function test_a_case_you_are_not_part_of_is_not_on_your_shelf(): void
    {
        $this->case();

        $stranger = $this->account('client');

        $this->assertCount(0, $this->actingAs($stranger)
            ->get(route('disputes.index'))->assertOk()->viewData('cases'));
    }

    // ── The Common Issues tiles ──────────────────────────────────

    public function test_a_common_issue_tile_lands_on_the_form_with_that_issue_chosen(): void
    {
        // The tile has already asked what the problem is; the form should not
        // ask again.
        $this->actingAs($this->client)
            ->get(route('disputes.create', ['taxonomy' => 'payment_dispute']))
            ->assertOk()
            ->assertSee('value="payment_dispute" selected', false);
    }

    public function test_a_made_up_issue_in_the_url_selects_nothing(): void
    {
        $this->actingAs($this->client)
            ->get(route('disputes.create', ['taxonomy' => 'gremlins']))
            ->assertOk()
            ->assertDontSee('gremlins');
    }

    // ── Wording the compliance rules forbid ──────────────────────

    public function test_the_screen_states_no_response_deadline(): void
    {
        /*
         * §12 holds every window for attorney review, and Virginia treats
         * deviating from your own published process as a standalone violation.
         * The mockup this screen was built from says "typically 24–48 hours";
         * that number has not been agreed, so it is not here.
         */
        $body = $this->actingAs($this->client)->get(route('disputes.index'))
            ->assertOk()->getContent();

        $body = substr($body, (int) strpos($body, 'Before You File'));

        foreach (['24-48', '24–48', 'within 48 hours', 'within 24 hours', 'business days'] as $deadline) {
            $this->assertStringNotContainsStringIgnoringCase($deadline, $body);
        }
    }

    public function test_the_screen_promises_no_coverage_nobody_has_signed_off(): void
    {
        /*
         * The mockup's "Booking Protection" card offered cancellation coverage,
         * refund support and replacement assistance under an "Eligible" badge.
         * Only the payment hold is a thing the platform actually does, and
         * Peter's compliance rule bans stated guarantees.
         */
        $body = $this->actingAs($this->client)->get(route('disputes.index'))
            ->assertOk()->getContent();

        $body = substr($body, (int) strpos($body, 'Your Protections'));
        $body = substr($body, 0, (int) strpos($body, 'Need Help?'));

        foreach (['guarantee', 'replacement assistance', 'refund support', 'cancellation coverage'] as $claim) {
            $this->assertStringNotContainsStringIgnoringCase($claim, $body);
        }
    }
}
