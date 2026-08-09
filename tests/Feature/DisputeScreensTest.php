<?php

namespace Tests\Feature;

use App\Domain\Disputes\DecisionGuide;
use App\Domain\Disputes\DisputeClassification;
use App\Domain\Disputes\DisputeStates;
use App\Models\Booking;
use App\Models\DisputeCase;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rule R34 Phase 2 — the screens.
 *
 * Phase 1's tests cover the rules. These cover the places a correct rule can
 * still reach the wrong person: an internal note rendered on a party's page, a
 * decision button drawn for someone who may not press it, a helpful deadline
 * written into a form, or the word "fair" in a sentence about a review the
 * platform is not independent of.
 */
class DisputeScreensTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $professional;
    private User $admin;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client       = $this->account('client');
        $this->professional = $this->account('professional');
        $this->admin        = $this->account('admin');

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

    private function filed(array $attributes = []): DisputeCase
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

    /* ── Filing ─────────────────────────────────────────────── */

    public function test_a_client_files_a_case_and_lands_on_it(): void
    {
        $response = $this->actingAs($this->client)->post(route('disputes.store'), [
            'booking_id'       => $this->booking->id,
            'taxonomy'         => 'incomplete_service',
            'summary'          => 'Two of the four agreed hours were not delivered on the day.',
            'attempted_direct' => 'yes',
            'certify_truthful' => '1',
        ]);

        $case = DisputeCase::first();

        $this->assertNotNull($case);
        $response->assertRedirect(route('disputes.show', $case));
        $this->assertSame($this->client->id, $case->filed_by);
        $this->assertSame($this->professional->id, $case->professional_id);
        $this->assertSame(DisputeStates::DIRECT_RESOLUTION, $case->state);
    }

    /** §1 — the certification is a signature. An unsigned form is not filed. */
    public function test_filing_without_the_certification_is_rejected(): void
    {
        $this->actingAs($this->client)
            ->post(route('disputes.store'), [
                'booking_id'       => $this->booking->id,
                'taxonomy'         => 'incomplete_service',
                'summary'          => 'Two of the four agreed hours were not delivered on the day.',
                'attempted_direct' => 'yes',
            ])
            ->assertSessionHasErrors('certify_truthful');

        $this->assertDatabaseCount('dispute_cases', 0);
    }

    /**
     * §3 — the filer picks the subject; intake sets the severity.
     *
     * Someone who could tick "Fraud" on the filing form would route their own
     * case past the direct-resolution step §2 requires.
     */
    public function test_the_filer_cannot_set_their_own_severity(): void
    {
        $this->actingAs($this->client)->post(route('disputes.store'), [
            'booking_id'       => $this->booking->id,
            'taxonomy'         => 'fraud',
            'summary'          => 'I believe the invoice they sent me was fabricated entirely.',
            'attempted_direct' => 'no',
            'certify_truthful' => '1',
            'severity'         => DisputeClassification::SEVERITY_FRAUD,   // ignored
            'priority'         => 'critical',                              // ignored
        ]);

        $case = DisputeCase::first();

        $this->assertSame(DisputeClassification::SEVERITY_QUALITY, $case->severity);
        $this->assertSame('normal', $case->priority);
        $this->assertSame(DisputeStates::DIRECT_RESOLUTION, $case->state);
    }

    public function test_a_second_open_case_on_the_same_booking_is_refused(): void
    {
        $this->filed();

        $this->actingAs($this->client)
            ->post(route('disputes.store'), [
                'booking_id'       => $this->booking->id,
                'taxonomy'         => 'late_arrival',
                'summary'          => 'They also turned up more than an hour after the agreed time.',
                'attempted_direct' => 'no',
                'certify_truthful' => '1',
            ])
            ->assertSessionHasErrors('booking_id');

        $this->assertDatabaseCount('dispute_cases', 1);
    }

    public function test_a_stranger_cannot_file_on_someone_elses_booking(): void
    {
        $outsider = $this->account('client');

        $this->actingAs($outsider)->post(route('disputes.store'), [
            'booking_id'       => $this->booking->id,
            'taxonomy'         => 'no_show',
            'summary'          => 'This booking has nothing at all to do with my own account.',
            'attempted_direct' => 'no',
            'certify_truthful' => '1',
        ])->assertForbidden();
    }

    /* ── Who may see a case ─────────────────────────────────── */

    public function test_both_parties_see_the_case_and_nobody_else_does(): void
    {
        $case = $this->filed();

        $this->actingAs($this->client)->get(route('disputes.show', $case))->assertOk();
        $this->actingAs($this->professional)->get(route('disputes.show', $case))->assertOk();
        $this->actingAs($this->account('client'))->get(route('disputes.show', $case))->assertForbidden();
    }

    /** §7 — staff deliberation never reaches the parties' page. */
    public function test_internal_notes_never_render_on_a_party_page(): void
    {
        $case = $this->filed();

        $case->log('internal_note', $this->admin, 'senior_reviewer', [
            'reason' => 'Caterer has three open cases this quarter.', 'visible' => false,
        ]);
        $case->log('response_submitted', $this->professional, 'professional', [
            'reason' => 'Professional replied.',
        ]);

        $page = $this->actingAs($this->professional)->get(route('disputes.show', $case));

        $page->assertOk();
        $page->assertDontSee('three open cases', false);
        $page->assertSee('Professional replied.', false);
    }

    /* ── Wording (§2, §12) ──────────────────────────────────── */

    /**
     * Rendered HTML, not source. A word can reach a page from a partial, a
     * layout or a variable, and only the rendered output settles it.
     */
    public function test_no_party_page_describes_the_review_as_neutral_or_fair(): void
    {
        $case = $this->filed();
        $case->decisions()->create([
            'decided_by'      => $this->admin->id,
            'decided_role'    => 'senior_reviewer',
            'resolution_type' => 'client_prevails',
            'financial_outcome' => DisputeClassification::PARTIAL_PRORATED,
            'reasoning'       => 'Two of the four contracted hours were not delivered.',
        ]);

        $pages = [
            $this->actingAs($this->client)->get(route('disputes.index')),
            $this->actingAs($this->client)->get(route('disputes.create')),
            $this->actingAs($this->client)->get(route('disputes.show', $case)),
            $this->actingAs($this->professional)->get(route('disputes.show', $case)),
        ];

        foreach ($pages as $page) {
            $page->assertOk();

            // Strip the chrome — the surrounding portal talks about fair
            // pricing and other unrelated things, and this rule is about how
            // the dispute process describes itself.
            $body = $page->getContent();

            $this->assertStringContainsString('dsp-head', $body, 'the dispute markup did not render');
            $body = substr($body, (int) strpos($body, 'dsp-head'));

            foreach (DecisionGuide::BANNED_WORDING as $banned) {
                // Whole words. "Fair" inside Fairfax is a Virginia city, and
                // Virginia is one of the seven states this platform operates
                // in — a substring match would fail on a real venue name and
                // teach everyone to ignore this test.
                $this->assertDoesNotMatchRegularExpression(
                    '/\b' . preg_quote($banned, '/') . '\b/i', $body,
                    "a dispute screen says \"{$banned}\"",
                );
            }
        }
    }

    /**
     * §12 holds every window, and Virginia treats deviating from your own
     * published process as a standalone violation. So no screen may state one.
     */
    public function test_no_party_page_publishes_a_deadline(): void
    {
        $case = $this->filed();

        foreach ([
            route('disputes.create'),
            route('disputes.show', $case),
        ] as $url) {
            $body = $this->actingAs($this->client)->get($url)->getContent();
            $body = substr($body, (int) strpos($body, 'dsp-head'));

            $this->assertDoesNotMatchRegularExpression(
                '/\b(\d+|one|two|three|five|seven|ten|fourteen|thirty)\s+(calendar |business )?(day|week|hour)s?\b/i',
                $body,
                "{$url} states a deadline",
            );
        }
    }

    /* ── Party actions ──────────────────────────────────────── */

    public function test_the_responding_party_can_answer_and_the_filer_cannot_see_that_form(): void
    {
        $case = $this->filed();

        $this->actingAs($this->professional)
            ->get(route('disputes.show', $case))
            ->assertSee('Your response', false);

        // The client filed it; there is nothing for them to respond to.
        $this->actingAs($this->client)
            ->get(route('disputes.show', $case))
            ->assertDontSee('Your response', false);

        $this->actingAs($this->professional)->post(route('disputes.respond', $case), [
            'position'         => 'The client cut the schedule short on the day and asked us to leave.',
            'certify_truthful' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('dispute_events', [
            'dispute_case_id' => $case->id, 'action' => 'response_submitted',
        ]);
    }

    /** Only the party who filed may withdraw — the other side agreeing is a settlement. */
    public function test_the_responding_party_cannot_withdraw_the_case(): void
    {
        $case = $this->filed();

        $this->actingAs($this->professional)->post(route('disputes.withdraw', $case), [
            'reason'            => 'I would rather this went away.',
            'acknowledge_final' => '1',
        ])->assertForbidden();

        $this->assertSame(DisputeStates::DIRECT_RESOLUTION, $case->fresh()->state);
    }

    public function test_evidence_cannot_be_added_to_a_closed_case(): void
    {
        $case = $this->filed();
        $case->transitionTo(DisputeStates::CLOSED, $this->admin, 'senior_reviewer');

        $this->actingAs($this->client)->post(route('disputes.evidence', $case), [
            'kind'              => 'timestamped_upload',
            'description'       => 'A photograph from the evening.',
            'certify_unaltered' => '1',
        ])->assertForbidden();
    }

    /** §4 — a party may replace their own submission, never the other side's. */
    public function test_a_party_cannot_supersede_the_other_sides_evidence(): void
    {
        $case = $this->filed();

        $theirs = $case->evidence()->create([
            'submitted_by' => $this->professional->id,
            'kind'         => 'timestamped_upload',
            'description'  => 'Photographs taken at the venue.',
        ]);

        $this->actingAs($this->client)->post(route('disputes.evidence', $case), [
            'kind'              => 'timestamped_upload',
            'description'       => 'A better copy of the same thing.',
            'supersedes'        => $theirs->id,
            'certify_unaltered' => '1',
        ])->assertForbidden();
    }

    /** §2 — outside escalation is the post-DECISION step, not an escape hatch. */
    public function test_outside_escalation_is_refused_before_a_decision(): void
    {
        $case = $this->filed();

        $this->actingAs($this->client)->post(route('disputes.escalate', $case), [
            'grounds' => 'I do not agree with how this is going at all so far.',
            'acknowledge_no_internal_appeal' => '1',
        ])->assertRedirect();

        $this->assertSame(DisputeStates::DIRECT_RESOLUTION, $case->fresh()->state);
        $this->assertDatabaseCount('dispute_escalations', 0);
    }

    /* ── Staff screens ──────────────────────────────────────── */

    public function test_the_queue_is_ordered_by_priority_not_severity(): void
    {
        // A minor communication issue, marked critical.
        $urgent = $this->filed([
            'severity' => DisputeClassification::SEVERITY_COMMUNICATION, 'priority' => 'critical',
        ]);

        // A safety concern, marked low. §3 says priority decides the queue.
        $second = Booking::create([
            'event_id' => $this->booking->event_id, 'client_id' => $this->client->id,
            'supplier_id' => $this->account('professional')->id, 'created_by' => $this->client->id,
            'status' => 'completed', 'price' => 500,
        ]);
        $this->filed([
            'booking_id' => $second->id, 'professional_id' => $second->supplier_id,
            'severity' => DisputeClassification::SEVERITY_SAFETY, 'priority' => 'low',
        ]);

        $page = $this->actingAs($this->admin)->get(route('app.admin.disputes.index'));

        $page->assertOk();
        $cases = $page->viewData('cases');

        $this->assertSame($urgent->id, $cases->first()->id);
    }

    public function test_a_party_cannot_open_the_staff_screens(): void
    {
        $case = $this->filed();

        $this->actingAs($this->client)->get(route('app.admin.disputes.index'))->assertForbidden();
        $this->actingAs($this->professional)->get(route('app.admin.disputes.show', $case))->assertForbidden();
    }

    /** §5 — an outcome that moves money has to say what happens to it. */
    public function test_a_money_moving_decision_needs_a_financial_outcome(): void
    {
        $case = $this->filed();

        $this->actingAs($this->admin)->post(route('app.admin.disputes.decide', $case), [
            'resolution_type' => 'client_prevails',
            'reasoning'       => 'Two of the four contracted hours were not delivered on the day.',
        ])->assertSessionHasErrors('financial_outcome');

        $this->assertDatabaseCount('dispute_decisions', 0);
    }

    /** §7 — a fraud finding names the party it is against. */
    public function test_a_fraud_decision_without_a_named_party_is_refused(): void
    {
        $case = $this->filed();

        $this->actingAs($this->admin)->post(route('app.admin.disputes.decide', $case), [
            'resolution_type'   => 'fraud_confirmed',
            'financial_outcome' => DisputeClassification::REFUND_NON_CONFORMING,
            'reasoning'         => 'The invoice submitted in evidence had been altered after issue.',
        ])->assertSessionHasErrors('finding_against');

        $this->assertDatabaseCount('dispute_decisions', 0);
    }

    public function test_a_decision_moves_the_case_and_reaches_both_parties(): void
    {
        $case = $this->filed();
        $case->transitionTo(DisputeStates::FORMAL_INVESTIGATION, $this->client, 'client');

        $this->actingAs($this->admin)->post(route('app.admin.disputes.decide', $case), [
            'resolution_type'   => 'client_prevails',
            'financial_outcome' => DisputeClassification::PARTIAL_PRORATED,
            'amount_to_client'  => 400,
            'reasoning'         => 'Two of the four contracted hours were not delivered on the day.',
        ])->assertRedirect();

        $this->assertSame(DisputeStates::DECIDED, $case->fresh()->state);

        foreach ([$this->client, $this->professional] as $party) {
            $this->actingAs($party)
                ->get(route('disputes.show', $case))
                ->assertSee('Two of the four contracted hours', false);
        }
    }

    /**
     * §2 — raising a case to fraud takes it out of Direct Resolution there and
     * then. Waiting for a second button press is how a safety case sits in a
     * negotiation between the two people it is about.
     */
    public function test_reclassifying_to_fraud_moves_the_case_out_of_direct_resolution(): void
    {
        $case = $this->filed();

        $this->actingAs($this->admin)->post(route('app.admin.disputes.classify', $case), [
            'severity' => DisputeClassification::SEVERITY_FRAUD,
            'priority' => 'critical',
            'taxonomy' => 'fraud',
        ])->assertRedirect();

        $this->assertSame(DisputeStates::FORMAL_INVESTIGATION, $case->fresh()->state);
    }

    /** §7 — a disclosed connection means a different owner, not a note on file. */
    public function test_a_disclosed_conflict_blocks_the_assignment(): void
    {
        $case  = $this->filed();
        $staff = $this->account('admin');

        $this->actingAs($this->admin)->post(route('app.admin.disputes.assign', $case), [
            'staff_id'        => $staff->id,
            'role'            => 'investigator',
            'has_connection'  => 'yes',
            'conflict_detail' => 'The professional is my brother-in-law.',
        ])->assertRedirect();

        $this->assertNull($case->fresh()->assigned_to);
        $this->assertDatabaseCount('dispute_assignments', 0);
    }

    /** §6 — exactly one role owns a case at a time. */
    public function test_assigning_releases_the_previous_owner(): void
    {
        $case  = $this->filed();
        $first = $this->account('admin');
        $then  = $this->account('admin');

        foreach ([$first, $then] as $staff) {
            $this->actingAs($this->admin)->post(route('app.admin.disputes.assign', $case), [
                'staff_id' => $staff->id, 'role' => 'investigator', 'has_connection' => 'no',
            ]);
        }

        $this->assertSame($then->id, $case->fresh()->assigned_to);
        $this->assertSame(1, $case->assignments()->whereNull('released_at')->count());
    }

    /** §8 — the hold ends when the case does. */
    public function test_closing_a_case_releases_the_hold(): void
    {
        $case = $this->filed();
        $this->assertTrue($case->balance_paused);

        $this->actingAs($this->admin)->post(route('app.admin.disputes.close', $case), [
            'closure_note' => 'Settled between the parties before review.',
        ])->assertRedirect(route('app.admin.disputes.index'));

        $this->assertFalse($case->fresh()->balance_paused);
        $this->assertSame(DisputeStates::CLOSED, $case->fresh()->state);
    }

    /** The guide sits beside the decision form and fills nothing in. */
    public function test_the_staff_page_shows_the_guide_as_reference_only(): void
    {
        $case = $this->filed();

        $page = $this->actingAs($this->admin)->get(route('app.admin.disputes.show', $case));

        $page->assertOk();
        $page->assertSee('Consistency guide', false);
        $page->assertSee('fills nothing in and decides nothing', false);

        // No control that would apply a suggestion to the form.
        $page->assertDontSee('Apply suggestion', false);
    }
}
