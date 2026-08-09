<?php

namespace App\Domain\Disputes;

/**
 * Rule R34, Phase 1 artifact 2 — the state machine.
 *
 * The architecture asks for "every valid and prohibited status transition",
 * which is why this is a whitelist rather than a list of forbidden moves: a
 * transition nobody wrote down is prohibited, and a new state added later
 * cannot quietly become reachable from everywhere.
 *
 * Two things this deliberately does NOT encode, because §12 holds them for
 * attorney review and inventing them here would put an unreviewed number in
 * front of a user:
 *
 *   No deadlines. Not filing windows, not response windows, not an
 *   auto-expiry. EXPIRED exists as a state because a case can reach it, but
 *   nothing here decides when.
 *
 *   No appeal layer. §2 is explicit that there is one post-decision step —
 *   outside escalation — and no second internal review, because that adds
 *   delay without adding independence.
 */
final class DisputeStates
{
    // §2's four steps, plus the terminal and housekeeping states.
    public const DRAFT              = 'draft';
    public const DIRECT_RESOLUTION  = 'direct_resolution';
    public const AWAITING_RESPONSE  = 'awaiting_response';
    public const FORMAL_INVESTIGATION = 'formal_investigation';
    public const DECIDED            = 'decided';
    public const CURE_PERIOD        = 'cure_period';
    public const OUTSIDE_ESCALATION = 'outside_escalation';
    public const CLOSED             = 'closed';
    public const EXPIRED            = 'expired';
    public const WITHDRAWN          = 'withdrawn';

    /** What a person is told each state means. */
    public const LABELS = [
        self::DRAFT                => 'Draft',
        self::DIRECT_RESOLUTION    => 'Direct Resolution',
        self::AWAITING_RESPONSE    => 'Awaiting Response',
        self::FORMAL_INVESTIGATION => 'Under Review',
        self::DECIDED              => 'Decided',
        self::CURE_PERIOD          => 'Cure Period',
        self::OUTSIDE_ESCALATION   => 'Outside Escalation',
        self::CLOSED               => 'Closed',
        self::EXPIRED              => 'Expired',
        self::WITHDRAWN            => 'Withdrawn',
    ];

    /**
     * Every permitted move. Anything absent is prohibited.
     *
     * The shape follows §2 with one exception written into the rule itself:
     * severity 4 and 5 bypass Direct Resolution entirely, so DRAFT reaches
     * FORMAL_INVESTIGATION directly — see `openingStateFor()`.
     */
    public const TRANSITIONS = [
        self::DRAFT => [
            self::DIRECT_RESOLUTION,
            self::FORMAL_INVESTIGATION,   // severity 4–5 only
            self::WITHDRAWN,
        ],
        self::DIRECT_RESOLUTION => [
            self::AWAITING_RESPONSE,
            self::FORMAL_INVESTIGATION,
            self::CLOSED,                 // settled between the parties
            self::WITHDRAWN,
            self::EXPIRED,
        ],
        self::AWAITING_RESPONSE => [
            self::DIRECT_RESOLUTION,
            self::FORMAL_INVESTIGATION,
            self::CLOSED,
            self::WITHDRAWN,
            self::EXPIRED,
        ],
        self::FORMAL_INVESTIGATION => [
            self::DECIDED,
            self::WITHDRAWN,
            self::EXPIRED,
        ],
        self::DECIDED => [
            self::CURE_PERIOD,            // only when the outcome is cure-redo
            self::OUTSIDE_ESCALATION,
            self::CLOSED,
        ],
        // §5: if the cure deadline passes without a cure, the case reopens.
        self::CURE_PERIOD => [
            self::FORMAL_INVESTIGATION,
            self::CLOSED,
        ],
        self::OUTSIDE_ESCALATION => [
            self::CLOSED,
        ],

        // Terminal. A closed case is never edited back open — a new case is
        // opened instead, which is what keeps the audit trail honest.
        self::CLOSED    => [],
        self::EXPIRED   => [],
        self::WITHDRAWN => [],
    ];

    /**
     * Which roles may make each move.
     *
     * Kept beside the transitions rather than in the permissions matrix
     * because a transition and who may perform it are one fact: splitting
     * them is how a state machine ends up enforcing the shape of a move but
     * not who is allowed to make it.
     */
    public const ACTORS = [
        self::DRAFT . '>' . self::DIRECT_RESOLUTION      => ['client', 'professional', 'intake_specialist'],
        self::DRAFT . '>' . self::FORMAL_INVESTIGATION   => ['intake_specialist', 'fraud_specialist', 'super_admin'],
        self::DRAFT . '>' . self::WITHDRAWN              => ['client', 'professional'],

        self::DIRECT_RESOLUTION . '>' . self::AWAITING_RESPONSE     => ['client', 'professional', 'intake_specialist'],
        self::DIRECT_RESOLUTION . '>' . self::FORMAL_INVESTIGATION  => ['client', 'professional', 'intake_specialist'],
        self::DIRECT_RESOLUTION . '>' . self::CLOSED                => ['intake_specialist', 'senior_reviewer', 'super_admin'],
        self::DIRECT_RESOLUTION . '>' . self::WITHDRAWN             => ['client', 'professional'],
        self::DIRECT_RESOLUTION . '>' . self::EXPIRED               => ['system'],

        self::AWAITING_RESPONSE . '>' . self::DIRECT_RESOLUTION     => ['client', 'professional'],
        self::AWAITING_RESPONSE . '>' . self::FORMAL_INVESTIGATION  => ['client', 'professional', 'intake_specialist'],
        self::AWAITING_RESPONSE . '>' . self::CLOSED                => ['intake_specialist', 'senior_reviewer', 'super_admin'],
        self::AWAITING_RESPONSE . '>' . self::WITHDRAWN             => ['client', 'professional'],
        self::AWAITING_RESPONSE . '>' . self::EXPIRED               => ['system'],

        // A decision is a staff act. Never the parties, and never automatic:
        // §2 puts a human conformance review here, and R29 keeps it there.
        self::FORMAL_INVESTIGATION . '>' . self::DECIDED    => ['investigator', 'senior_reviewer', 'super_admin'],
        self::FORMAL_INVESTIGATION . '>' . self::WITHDRAWN  => ['client', 'professional'],
        self::FORMAL_INVESTIGATION . '>' . self::EXPIRED    => ['system'],

        self::DECIDED . '>' . self::CURE_PERIOD         => ['investigator', 'senior_reviewer', 'super_admin'],
        self::DECIDED . '>' . self::OUTSIDE_ESCALATION  => ['client', 'professional', 'legal_administrator'],
        self::DECIDED . '>' . self::CLOSED              => ['senior_reviewer', 'super_admin'],

        self::CURE_PERIOD . '>' . self::FORMAL_INVESTIGATION => ['investigator', 'senior_reviewer', 'system'],
        self::CURE_PERIOD . '>' . self::CLOSED               => ['senior_reviewer', 'super_admin'],

        self::OUTSIDE_ESCALATION . '>' . self::CLOSED => ['legal_administrator', 'super_admin'],
    ];

    /**
     * Where a new case starts.
     *
     * §2: Direct Resolution is the required first step, EXCEPT severity 4
     * (fraud) and 5 (safety or criminal concern), which go straight to formal
     * investigation. Asking a party to negotiate directly with someone they
     * have accused of fraud is the thing that exception exists to prevent.
     */
    public static function openingStateFor(int $severity): string
    {
        return $severity >= DisputeClassification::SEVERITY_FRAUD
            ? self::FORMAL_INVESTIGATION
            : self::DIRECT_RESOLUTION;
    }

    public static function isPermitted(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /** May this role make this move? */
    public static function allows(string $from, string $to, string $role): bool
    {
        if (! self::isPermitted($from, $to)) {
            return false;
        }

        return in_array($role, self::ACTORS["{$from}>{$to}"] ?? [], true);
    }

    /** Nothing further happens to a case in one of these. */
    public static function isTerminal(string $state): bool
    {
        return (self::TRANSITIONS[$state] ?? []) === [];
    }
}
