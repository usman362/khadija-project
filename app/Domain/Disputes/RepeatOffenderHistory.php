<?php

namespace App\Domain\Disputes;

use App\Models\DisputeCase;
use App\Models\User;

/**
 * Rule R34 §7 — dispute history per account, and the consequence ladder.
 *
 * A read model over the cases, not a table. §7 says "disputes accumulate as a
 * HISTORY per account rather than being evaluated in isolation" — that is a
 * way of reading what already exists, and a stored counter would be a second
 * copy of the truth that drifts the first time a decision is revised (§5).
 *
 * The counting rule is §7's trust safeguard, and it is the whole point of the
 * class: ONLY confirmed outcomes count. Filing a dispute against someone
 * counts for nothing, and neither does losing one on a technicality. A ladder
 * that climbed on accusations would let one determined client suspend a
 * professional by filing four times.
 */
final class RepeatOffenderHistory
{
    /** §7's escalating ladder, in order. */
    public const LADDER = [
        'none'                => 'No action',
        'warning'             => 'Warning',
        'feature_restriction' => 'Temporary feature restriction',
        'account_review'      => 'Account review',
        'suspension'          => 'Suspension',
        'permanent_removal'   => 'Permanent removal',
    ];

    /**
     * Confirmed findings against this account.
     *
     * "Against" is doing real work here. A professional who was disputed four
     * times and prevailed four times has four cases and zero findings, and
     * the number that matters is the second one.
     */
    public static function findingsAgainst(User $user): int
    {
        return DisputeCase::query()
            ->where(fn ($q) => $q->where('professional_id', $user->id)->orWhere('client_id', $user->id))
            ->whereHas('decisions', fn ($q) => $q->whereIn('resolution_type', DisputeClassification::MAY_INFLUENCE_TRUST))
            ->get()
            ->filter(fn (DisputeCase $case) => self::decisionWentAgainst($case, $user))
            ->count();
    }

    /**
     * Did the case's STANDING decision go against this user?
     *
     * The standing one, not every one it ever had. A decision that was revised
     * (§5) keeps its original for the audit trail, and counting both would
     * charge an account twice for one case — the second time for a ruling the
     * platform itself withdrew.
     */
    public static function decisionWentAgainst(DisputeCase $case, User $user): bool
    {
        $decision = $case->currentDecision();

        if ($decision === null || ! $decision->mayInfluenceTrust()) {
            return false;
        }

        return match ($decision->resolution_type) {
            'client_prevails'       => $user->id === $case->professional_id,
            'professional_prevails' => $user->id === $case->client_id,
            // Fraud is read from the decision, never from the role — the party
            // who filed can be the party who fabricated the evidence.
            'fraud_confirmed'       => $decision->finding_against !== null
                                        && $user->id === $decision->finding_against,
            default                 => false,
        };
    }

    /**
     * Where the ladder stands — a RECOMMENDATION for a human, never applied.
     *
     * §7 calls it an escalating consequence ladder, and R29 keeps a person at
     * the end of it. Nothing in this module restricts or suspends an account
     * on a count; a Fraud Specialist reads the history and decides.
     */
    public static function suggestedStep(User $user): string
    {
        $findings = self::findingsAgainst($user);

        return match (true) {
            $findings >= 5 => 'permanent_removal',
            $findings >= 4 => 'suspension',
            $findings >= 3 => 'account_review',
            $findings >= 2 => 'feature_restriction',
            $findings >= 1 => 'warning',
            default        => 'none',
        };
    }

    public static function stepLabel(string $step): string
    {
        return self::LADDER[$step] ?? $step;
    }

    /**
     * Total cases involving this account, confirmed or not.
     *
     * Staff-facing context only. Never a public signal and never an input to
     * the ladder — see the safeguard at the top of this class.
     */
    public static function totalCases(User $user): int
    {
        return DisputeCase::query()
            ->where(fn ($q) => $q->where('professional_id', $user->id)->orWhere('client_id', $user->id))
            ->count();
    }
}
