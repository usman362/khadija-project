<?php

namespace Tests\Feature;

use App\Domain\Disputes\DecisionGuide;
use App\Domain\Disputes\DisputeClassification;
use App\Domain\Disputes\DisputePermissions;
use App\Domain\Disputes\DisputeStates;
use App\Domain\Disputes\FormsLibrary;
use App\Domain\Disputes\NotificationMatrix;
use App\Domain\Disputes\RepeatOffenderHistory;
use App\Models\Booking;
use App\Models\DisputeCase;
use App\Models\DisputeDecision;
use App\Models\DisputeEvidence;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rule R34 — the six Phase 1 artifacts of the Dispute Resolution module,
 * built from GigResource_Dispute_Resolution_Module_Architecture_0725 and
 * locked 2026-07-25.
 *
 * Most of what follows tests things the module must NOT do. That is the shape
 * of this rule: §12 holds every deadline, dollar figure and legal clause for
 * attorney review, §7 forbids an unproven allegation from touching anyone's
 * standing, and §2 forbids describing a platform review as neutral or fair.
 * Each of those is easy to breach by writing something perfectly reasonable —
 * a "you have 14 days" hint, a rating that dips when a case opens, a
 * reassuring sentence in an email — so each has a test.
 */
class DisputeResolutionPhase1Test extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $professional;
    private User $staff;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client       = User::factory()->create();
        $this->professional = User::factory()->create();
        $this->staff        = User::factory()->create();

        $event = Event::create([
            'title'      => 'Wedding reception',
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
            'price'       => 2000,
        ]);
    }

    private function file(array $attributes = []): DisputeCase
    {
        return DisputeCase::create(array_merge([
            'booking_id'      => $this->booking->id,
            'filed_by'        => $this->client->id,
            'client_id'       => $this->client->id,
            'professional_id' => $this->professional->id,
            'severity'        => DisputeClassification::SEVERITY_QUALITY,
            'taxonomy'        => 'poor_workmanship',
            'summary'         => 'Half the agreed scope was not delivered.',
        ], $attributes));
    }

    /* ── Case numbering (§6) ────────────────────────────────── */

    public function test_case_numbers_are_a_single_global_sequence(): void
    {
        $first  = $this->file();
        $second = $this->file();

        $year = now()->year;

        $this->assertSame("DR-{$year}-000001", $first->reference);
        $this->assertSame("DR-{$year}-000002", $second->reference);
    }

    /**
     * §6 puts an immutable internal identifier behind the public number for
     * database and migration purposes. If the reference were the primary key
     * there would be nothing behind it.
     */
    public function test_the_public_number_is_not_the_internal_identifier(): void
    {
        $case = $this->file();

        $this->assertNotSame((string) $case->id, $case->reference);
        $this->assertSame('id', $case->getKeyName());
    }

    /* ── Opening state (§2) ─────────────────────────────────── */

    public function test_an_ordinary_case_opens_in_direct_resolution(): void
    {
        $this->assertSame(DisputeStates::DIRECT_RESOLUTION, $this->file()->state);
    }

    /**
     * §2's exception. Asking a client to negotiate directly with someone they
     * have just accused of fraud is what this bypass exists to prevent.
     */
    public function test_fraud_and_safety_bypass_direct_resolution(): void
    {
        $fraud  = $this->file(['severity' => DisputeClassification::SEVERITY_FRAUD, 'taxonomy' => 'fraud']);
        $safety = $this->file(['severity' => DisputeClassification::SEVERITY_SAFETY, 'taxonomy' => 'safety_concern']);

        $this->assertSame(DisputeStates::FORMAL_INVESTIGATION, $fraud->state);
        $this->assertSame(DisputeStates::FORMAL_INVESTIGATION, $safety->state);
    }

    /** §8 — filing pauses that one service line's held balance. */
    public function test_filing_pauses_the_held_balance(): void
    {
        $this->assertTrue($this->file()->balance_paused);
    }

    /* ── State machine ──────────────────────────────────────── */

    public function test_transitions_not_written_down_are_prohibited(): void
    {
        // A case cannot skip the review and go straight to a decision.
        $this->assertFalse(DisputeStates::isPermitted(
            DisputeStates::DIRECT_RESOLUTION, DisputeStates::DECIDED
        ));

        // And a closed case is never edited back open (§6 — a new case is
        // opened instead, which is what keeps the audit trail honest).
        $this->assertTrue(DisputeStates::isTerminal(DisputeStates::CLOSED));
        $this->assertSame([], DisputeStates::TRANSITIONS[DisputeStates::CLOSED]);
    }

    /** §2 — the decider is staff. Never the parties, never automatic. */
    public function test_only_staff_may_record_a_decision(): void
    {
        $from = DisputeStates::FORMAL_INVESTIGATION;

        $this->assertFalse(DisputeStates::allows($from, DisputeStates::DECIDED, 'client'));
        $this->assertFalse(DisputeStates::allows($from, DisputeStates::DECIDED, 'professional'));
        $this->assertFalse(DisputeStates::allows($from, DisputeStates::DECIDED, 'system'));
        $this->assertTrue(DisputeStates::allows($from, DisputeStates::DECIDED, 'investigator'));
    }

    /**
     * §2 — one post-decision step, and it is outside. There is no second
     * internal review, because that adds delay without adding independence.
     */
    public function test_there_is_no_internal_appeal_layer(): void
    {
        $this->assertNotContains(
            DisputeStates::FORMAL_INVESTIGATION,
            DisputeStates::TRANSITIONS[DisputeStates::DECIDED],
        );

        $this->assertNotContains('appeal', array_keys(DisputeStates::LABELS));
    }

    public function test_a_rejected_transition_leaves_the_case_where_it_was(): void
    {
        $case = $this->file();

        $moved = $case->transitionTo(DisputeStates::DECIDED, $this->client, 'client');

        $this->assertFalse($moved);
        $this->assertSame(DisputeStates::DIRECT_RESOLUTION, $case->fresh()->state);
        $this->assertSame(0, $case->events()->count());
    }

    /** §10 — previous value, new value, who, their role, and why. */
    public function test_a_transition_writes_a_full_audit_row(): void
    {
        $case = $this->file();
        $case->transitionTo(DisputeStates::FORMAL_INVESTIGATION, $this->client, 'client', 'No reply from the professional.');

        $event = $case->events()->latest('id')->first();

        $this->assertSame('state_changed', $event->action);
        $this->assertSame(DisputeStates::DIRECT_RESOLUTION, $event->old_value);
        $this->assertSame(DisputeStates::FORMAL_INVESTIGATION, $event->new_value);
        $this->assertSame($this->client->id, $event->actor_id);
        $this->assertSame('client', $event->actor_role);
        $this->assertSame('No reply from the professional.', $event->reason);
    }

    public function test_closing_a_case_stamps_when_it_closed(): void
    {
        $case = $this->file();
        $case->transitionTo(DisputeStates::CLOSED, $this->staff, 'senior_reviewer');

        $this->assertNotNull($case->fresh()->closed_at);
        $this->assertFalse($case->fresh()->isOpen());
    }

    /* ── Classification (§3) ────────────────────────────────── */

    /**
     * §3 is explicit: a high-dollar Level 2 case can outrank a Level 3 in the
     * queue. Anything that derived priority from severity would re-couple two
     * fields the architecture separated on purpose.
     */
    public function test_priority_is_not_derived_from_severity(): void
    {
        $low = $this->file(['severity' => DisputeClassification::SEVERITY_COMMUNICATION, 'priority' => 'critical']);
        $high = $this->file(['severity' => DisputeClassification::SEVERITY_SAFETY, 'priority' => 'low']);

        $this->assertSame('critical', $low->fresh()->priority);
        $this->assertSame('low', $high->fresh()->priority);
    }

    public function test_a_case_carries_all_three_classification_fields(): void
    {
        $case = $this->file(['priority' => 'high', 'secondary_taxonomy' => ['late_arrival']]);

        $this->assertSame(2, $case->severity);
        $this->assertSame('high', $case->priority);
        $this->assertSame('poor_workmanship', $case->taxonomy);
        $this->assertSame(['late_arrival'], $case->fresh()->secondary_taxonomy);
    }

    /* ── Evidence (§4) ──────────────────────────────────────── */

    public function test_platform_records_are_marked_primary_and_a_screenshot_is_not(): void
    {
        $case = $this->file();

        $contract = $case->evidence()->create([
            'submitted_by' => $this->client->id,
            'kind'         => 'platform_contract',
            'description'  => 'The signed agreement.',
        ]);

        // A photograph of a conversation is a photograph, not the record.
        $screenshot = $case->evidence()->create([
            'submitted_by' => $this->client->id,
            'kind'         => 'third_party_invoice',
            'description'  => 'Screenshot of a text message.',
        ]);

        $this->assertTrue($contract->platform_generated);
        $this->assertFalse($screenshot->platform_generated);
    }

    /** §4 — deletions are logged rather than allowed to truly delete. */
    public function test_withdrawn_evidence_is_kept_and_logged(): void
    {
        $case = $this->file();
        $item = $case->evidence()->create([
            'submitted_by' => $this->client->id,
            'kind'         => 'third_party_invoice',
            'description'  => 'Invoice from another caterer.',
        ]);

        $item->withdraw($this->client, 'Uploaded the wrong invoice.');

        $this->assertDatabaseHas('dispute_evidence', ['id' => $item->id]);
        $this->assertTrue($item->fresh()->isWithdrawn());
        $this->assertDatabaseHas('dispute_events', [
            'dispute_case_id' => $case->id,
            'action'          => 'evidence_withdrawn',
            'reason'          => 'Uploaded the wrong invoice.',
        ]);
    }

    /** §4 — no silent edits. A correction points at what it replaces. */
    public function test_replacing_evidence_keeps_the_original(): void
    {
        $case = $this->file();

        $original = $case->evidence()->create([
            'submitted_by' => $this->client->id, 'kind' => 'timestamped_upload', 'description' => 'Venue photo',
        ]);
        $case->evidence()->create([
            'submitted_by' => $this->client->id, 'kind' => 'timestamped_upload',
            'description'  => 'Venue photo, clearer copy', 'supersedes' => $original->id,
        ]);

        $this->assertTrue($original->fresh()->isSuperseded());
        $this->assertSame(2, $case->evidence()->count());
    }

    /**
     * §4 — the Evidence Weight Guide is a consistency aid, not scoring.
     *
     * No numbers, because a weight of 0.8 against 0.3 is a scoring system
     * whatever the surrounding text calls it, and two of them added together
     * have replaced the investigator.
     */
    public function test_the_evidence_weight_guide_carries_no_numbers(): void
    {
        foreach (DecisionGuide::EVIDENCE_WEIGHT as $kind => $entry) {
            $this->assertIsString($entry['weight'], $kind);
            $this->assertFalse(is_numeric($entry['weight']), "{$kind} has a numeric weight");
        }
    }

    /* ── Decisions and the trust safeguard (§5, §7) ─────────── */

    public function test_housekeeping_closures_need_no_financial_outcome(): void
    {
        $this->assertFalse(DisputeClassification::needsFinancialOutcome('administrative_closure'));
        $this->assertFalse(DisputeClassification::needsFinancialOutcome('duplicate_case'));
        $this->assertTrue(DisputeClassification::needsFinancialOutcome('client_prevails'));
    }

    /**
     * §7's safeguard, and the reason the two axes exist separately at all.
     *
     * A public score that drops on an unproven allegation punishes someone for
     * being accused — the architecture names it as UDAP exposure in several of
     * the seven states besides.
     */
    public function test_filing_a_dispute_never_touches_trust_on_its_own(): void
    {
        $case = $this->file();

        $this->assertSame(0, RepeatOffenderHistory::findingsAgainst($this->professional));
        $this->assertSame('none', RepeatOffenderHistory::suggestedStep($this->professional));
        $this->assertSame(1, RepeatOffenderHistory::totalCases($this->professional));
    }

    public function test_only_confirmed_outcomes_reach_the_repeat_offender_history(): void
    {
        $withdrawn = $this->file();
        DisputeDecision::create([
            'dispute_case_id' => $withdrawn->id, 'decided_by' => $this->staff->id,
            'decided_role' => 'senior_reviewer', 'resolution_type' => 'administrative_closure',
            'reasoning' => 'Filed against the wrong booking.',
        ]);

        $this->assertSame(0, RepeatOffenderHistory::findingsAgainst($this->professional));

        $upheld = $this->file();
        DisputeDecision::create([
            'dispute_case_id' => $upheld->id, 'decided_by' => $this->staff->id,
            'decided_role' => 'senior_reviewer', 'resolution_type' => 'client_prevails',
            'financial_outcome' => DisputeClassification::PARTIAL_PRORATED,
            'reasoning' => 'Two of the four agreed hours were not delivered.',
        ]);

        $this->assertSame(1, RepeatOffenderHistory::findingsAgainst($this->professional));
        $this->assertSame('warning', RepeatOffenderHistory::suggestedStep($this->professional));
    }

    /**
     * A fraud finding lands on the account it names, which can be the account
     * that filed. Inferring it from the role would put a permanent-removal
     * ladder under the wrong person.
     */
    public function test_a_fraud_finding_follows_the_named_party_not_the_role(): void
    {
        $case = $this->file(['severity' => DisputeClassification::SEVERITY_FRAUD, 'taxonomy' => 'fraud']);

        DisputeDecision::create([
            'dispute_case_id' => $case->id, 'decided_by' => $this->staff->id,
            'decided_role' => 'fraud_specialist', 'resolution_type' => 'fraud_confirmed',
            'finding_against' => $this->client->id,
            'reasoning' => 'The submitted invoice was altered.',
        ]);

        $this->assertSame(1, RepeatOffenderHistory::findingsAgainst($this->client));
        $this->assertSame(0, RepeatOffenderHistory::findingsAgainst($this->professional));
    }

    /**
     * §5 — a revision keeps the original for the audit trail. Counting both
     * would charge an account twice for one case, the second time for a
     * ruling the platform itself withdrew.
     */
    public function test_a_revised_decision_counts_once(): void
    {
        $case = $this->file();

        $original = DisputeDecision::create([
            'dispute_case_id' => $case->id, 'decided_by' => $this->staff->id,
            'decided_role' => 'investigator', 'resolution_type' => 'client_prevails',
            'reasoning' => 'Initial finding.',
        ]);

        DisputeDecision::create([
            'dispute_case_id' => $case->id, 'decided_by' => $this->staff->id,
            'decided_role' => 'senior_reviewer', 'resolution_type' => 'professional_prevails',
            'reasoning' => 'The contract covered a shorter scope than the client described.',
            'revises' => $original->id, 'revision_reason' => 'Original misread the agreed scope.',
        ]);

        $this->assertDatabaseCount('dispute_decisions', 2);
        $this->assertSame(0, RepeatOffenderHistory::findingsAgainst($this->professional));
        $this->assertSame(1, RepeatOffenderHistory::findingsAgainst($this->client));
    }

    /* ── The Decision Matrix is a guide (§5, R29) ───────────── */

    public function test_the_decision_guide_explains_rather_than_decides(): void
    {
        foreach (DecisionGuide::all() as $row) {
            $this->assertArrayHasKey('suggests', $row);
            $this->assertArrayHasKey('because', $row);
            $this->assertNotEmpty($row['because'], "{$row['finding']} suggests an outcome with no reasoning");
        }

        // No method on it produces an outcome from a case.
        $methods = get_class_methods(DecisionGuide::class);
        $this->assertSame(['all'], $methods);
    }

    /**
     * §2 and §12 — never describe Step 2 as neutral, impartial, unbiased,
     * fair or algorithmic. DC's consumer law has a low injury bar and this is
     * called out by name as drafting exposure.
     */
    public function test_no_banned_wording_anywhere_in_the_module(): void
    {
        $files = glob(app_path('Domain/Disputes/*.php'));
        $this->assertNotEmpty($files);

        $scanned = 0;

        foreach ($files as $path) {
            if (basename($path) === 'DecisionGuide.php') {
                continue; // it is the file that lists the banned words
            }

            foreach (self::stringLiteralsIn($path) as $string) {
                $scanned++;

                foreach (DecisionGuide::BANNED_WORDING as $banned) {
                    $this->assertStringNotContainsStringIgnoringCase(
                        $banned, $string,
                        basename($path) . " says \"{$banned}\" in a string: \"{$string}\"",
                    );
                }
            }
        }

        // A scan that reads nothing passes everything. The first version of
        // this test paired quotes with a regex, mis-aligned on the very first
        // array key, and reported clean on a file that said "impartial".
        $this->assertGreaterThan(100, $scanned, 'the scan read almost nothing');
    }

    /**
     * Actual PHP string literals — not comments, and not whatever a regex
     * decides a pair of quotes means.
     *
     * Comments are excluded on purpose: a comment explaining that "impartial"
     * is forbidden is not a claim made to anybody.
     */
    private static function stringLiteralsIn(string $path): array
    {
        $out = [];

        foreach (token_get_all(file_get_contents($path)) as $token) {
            if (is_array($token) && in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) {
                $out[] = trim($token[1], "'\"");
            }
        }

        return $out;
    }

    /* ── Permissions (§7, §11) ──────────────────────────────── */

    public function test_all_seven_staff_roles_exist(): void
    {
        $this->assertCount(7, DisputePermissions::STAFF_ROLES);
    }

    public function test_a_party_cannot_reach_anything_internal(): void
    {
        foreach (['client', 'professional'] as $role) {
            $this->assertFalse(DisputePermissions::can($role, 'record_decision'));
            $this->assertFalse(DisputePermissions::can($role, 'add_internal_note'));
            $this->assertFalse(DisputePermissions::can($role, 'execute_financial_outcome'));
            $this->assertTrue(DisputePermissions::can($role, 'submit_evidence'));
        }
    }

    /** Deciding and paying stay in different hands. */
    public function test_no_role_both_decides_and_pays(): void
    {
        foreach (DisputePermissions::STAFF_ROLES as $role => $label) {
            if ($role === DisputePermissions::SUPER_ADMIN) {
                continue; // the break-glass role, deliberately unrestricted
            }

            $this->assertFalse(
                DisputePermissions::can($role, 'record_decision')
                && DisputePermissions::can($role, 'execute_financial_outcome'),
                "{$label} can both decide a case and move the money on it",
            );
        }
    }

    /* ── Notification matrix (§11) ──────────────────────────── */

    public function test_every_notification_row_is_fully_specified(): void
    {
        foreach (NotificationMatrix::all() as $row) {
            foreach (['trigger', 'recipients', 'channels', 'timing', 'retry', 'cancellation'] as $key) {
                $this->assertArrayHasKey($key, $row, "{$row['trigger']} is missing {$key}");
                $this->assertNotEmpty($row[$key], "{$row['trigger']} has an empty {$key}");
            }
        }
    }

    /**
     * §12 holds every deadline. A reminder scheduled "3 days before the
     * response deadline" would have set the response deadline.
     */
    public function test_deadline_dependent_notifications_are_not_schedulable(): void
    {
        $blocked = array_column(NotificationMatrix::blockedOnLegalReview(), 'trigger');

        $this->assertContains('response_awaited', $blocked);
        $this->assertContains('case_expired', $blocked);

        foreach (NotificationMatrix::all() as $row) {
            if ($row['timing'] === NotificationMatrix::DEADLINE_DEPENDENT) {
                continue;
            }

            $this->assertDoesNotMatchRegularExpression(
                '/\b\d+\s*(day|business day|week)/i', $row['timing'],
                "{$row['trigger']} states a deadline in its timing",
            );
        }
    }

    /** A closed case that keeps sending reminders is the classic failure here. */
    public function test_closing_expiring_or_withdrawing_cancels_pending_reminders(): void
    {
        foreach (['case_closed', 'case_expired', 'case_withdrawn'] as $trigger) {
            $this->assertTrue(NotificationMatrix::cancelsAllPending($trigger), $trigger);
        }

        $this->assertFalse(NotificationMatrix::cancelsAllPending('case_filed'));
    }

    /* ── Forms library (§1, §11) ────────────────────────────── */

    public function test_every_certification_stores_the_wording_it_showed(): void
    {
        $certifications = FormsLibrary::certifications();

        $this->assertNotEmpty($certifications);

        foreach ($certifications as $certification) {
            $this->assertNotEmpty(
                $certification['text'],
                "{$certification['form']}.{$certification['field']} is a signature with no wording",
            );
        }
    }

    /** §1 — an electronic signature under ESIGN/UETA is never pre-ticked. */
    public function test_no_form_field_is_pre_ticked(): void
    {
        foreach (FormsLibrary::all() as $key => $form) {
            foreach ($form['fields'] as $field) {
                $this->assertArrayNotHasKey('default', $field, "{$key}.{$field['name']} has a default");
                $this->assertArrayNotHasKey('checked', $field, "{$key}.{$field['name']} ships checked");
            }
        }
    }

    /** §12 again — no form may publish a timeline nobody has approved. */
    public function test_no_form_states_a_deadline(): void
    {
        $text = json_encode(FormsLibrary::all());

        $this->assertDoesNotMatchRegularExpression('/\b\d+\s*(calendar |business )?(day|week|hour)s?\b/i', $text);
    }

    public function test_both_parties_have_a_way_to_file_and_to_respond(): void
    {
        $forParties = FormsLibrary::forAudience(FormsLibrary::CLIENT);

        $this->assertArrayHasKey('client_filing', $forParties);
        $this->assertArrayHasKey('response', $forParties);
        $this->assertArrayNotHasKey('staff_decision', $forParties);

        $this->assertArrayHasKey(
            'professional_filing',
            FormsLibrary::forAudience(FormsLibrary::PROFESSIONAL),
        );
    }

    /* ── Per-service scoping (§6, R12) ──────────────────────── */

    /**
     * Two professionals on one event, one dispute each. On an MSR this is the
     * per-service model working as intended — and it is the reason a case
     * cannot pause five people's money over one person's work.
     */
    public function test_two_cases_on_one_event_stay_independent(): void
    {
        $other = User::factory()->create();

        $secondBooking = Booking::create([
            'event_id'    => $this->booking->event_id,
            'client_id'   => $this->client->id,
            'supplier_id' => $other->id,
            'created_by'  => $this->client->id,
            'status'      => 'completed',
            'price'       => 800,
        ]);

        $first  = $this->file();
        $second = $this->file(['booking_id' => $secondBooking->id, 'professional_id' => $other->id]);

        $this->assertNotSame($first->reference, $second->reference);
        $this->assertTrue($first->relatedCases()->where('id', $second->id)->exists());

        // Independent: closing one leaves the other exactly where it was.
        $first->transitionTo(DisputeStates::CLOSED, $this->staff, 'senior_reviewer');

        $this->assertSame(DisputeStates::DIRECT_RESOLUTION, $second->fresh()->state);
        $this->assertTrue($second->fresh()->balance_paused);
    }
}
