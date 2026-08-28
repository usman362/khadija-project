<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Rule R38 — a client and a professional only match inside the SAME state.
 *
 * This is NOT the service-area gate. `ServiceArea` asks one question — is this
 * account somewhere we operate at all? — and answers it about a single user.
 * R38 asks a second, narrower question about a PAIR: are these two in the same
 * state as each other? Someone can pass the first and fail the second, which
 * is exactly the case the launch depends on getting right, so the two live in
 * separate classes rather than one growing a second meaning.
 *
 * Ratified 2026-08-07 alongside R38:
 *   • Enforcement is SERVER-SIDE AUTHORITATIVE and re-checked at finalization.
 *     A filtered list is a courtesy; the check at the point of transacting is
 *     the rule.
 *   • Search HIDES ineligible results. The Feed may show them as related but
 *     non-actionable — so the query scope and the act of bidding are gated
 *     separately, and a caller has to choose.
 *   • Influencers are carved out entirely (R26). They are not party to a
 *     booking, so there is no pair to match.
 *   • The state is the REGISTERED STATE OF THE ACTING ACCOUNT, not of the
 *     person (R47 makes state a property of an account — a professional
 *     working two states holds two accounts).
 */
final class StateMatching
{
    /** The registered state of an account, or null if it has none on file. */
    public static function stateOf(?User $user): ?string
    {
        $state = $user?->profile?->state;

        return is_string($state) && $state !== '' ? strtoupper($state) : null;
    }

    /** Does R38 govern this account at all? Influencers and admins do not transact. */
    public static function appliesTo(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return ! $user->hasRole('influencer') && ! $user->hasRole('admin');
    }

    /**
     * Are these two the same state?
     *
     * Unknown is not a match. A row with no state on file cannot be shown to
     * satisfy a rule whose whole content is that two values are equal — the
     * honest answer to "same state?" when one side is blank is no.
     */
    public static function matches(?string $a, ?string $b): bool
    {
        return $a !== null && $b !== null && strtoupper($a) === strtoupper($b);
    }

    /** May these two transact with each other? */
    public static function allows(?User $one, ?User $two): bool
    {
        if (! self::appliesTo($one) || ! self::appliesTo($two)) {
            return true;   // an influencer or admin on either side: not a pair R38 governs
        }

        return self::matches(self::stateOf($one), self::stateOf($two));
    }

    /**
     * Narrow a query of rows that carry their own `state` column to the ones
     * this viewer may act on. Search calls this; the Feed deliberately does
     * not, and marks the off-state rows as non-actionable instead.
     */
    public static function scopeForViewer(Builder $query, ?User $viewer, string $column = 'state'): Builder
    {
        if (! self::appliesTo($viewer)) {
            return $query;
        }

        $state = self::stateOf($viewer);

        // A viewer with no state on file has no state to match, so nothing
        // matches. Returning everything would make the rule opt-in.
        return $query->where($column, $state ?? '__none__');
    }

    /**
     * The state a REQUEST belongs to — R38's closing amendment, and R71.
     *
     * The rule as locked compares professional.state to the EVENT's state, and
     * the review that locked it was explicit about why: a client registered in
     * Virginia may hold an event at their office in Maryland. Before this, the
     * event's state was stamped from the client's account, so the column was
     * named for the event and filled from the person — the very substitution
     * the rule forbids. A Maryland event then went out to Virginia
     * professionals and to nobody who could actually work it.
     *
     * The client's own state stays the DEFAULT, because it is right nearly
     * every time and asking twice for the same answer is its own kind of bug.
     * It is now a default rather than the only possibility.
     *
     * An unsupported state is not honoured: the seven jurisdictions are the
     * whole marketplace, so a request outside them has nobody to reach.
     */
    /**
     * The state a request is matched in.
     *
     * Sir Peter named two rules on 2026-08-25, and one overrides the other:
     *
     *   Event Location Rule   the event's location when the client gives one,
     *                         their home address as the fallback.
     *   State Boundary Rule   matching is ALWAYS by the client's home state.
     *                         No cross-state work, even when the event itself
     *                         is out of state.
     *
     * The boundary rule wins, in his words: "each state has its own rules/laws
     * so until we can figure it all out then we will at least get this problem
     * resolved." So the Event Location Rule is recorded here and is NOT in
     * force.
     *
     * It mattered. This used to return whichever state the client picked for
     * the event, which meant a Maryland client whose event was in Pennsylvania
     * had their request routed to Pennsylvania professionals — while a Direct
     * Request to any of those same professionals was refused, because that
     * path compares home states. Same client, same event, two opposite
     * answers. One rule, in one place, now.
     *
     * `$chosenEventState` is still accepted because callers know where the
     * event is and that will matter again: when cross-state opens up, this is
     * the only function that has to change.
     */
    public static function requestState(?User $client, ?string $chosenEventState = null): ?string
    {
        return self::stateOf($client);
    }

    /** Narrow a query of USERS by the state on their profile. */
    public static function scopeUsersForViewer(Builder $query, ?User $viewer): Builder
    {
        if (! self::appliesTo($viewer)) {
            return $query;
        }

        $state = self::stateOf($viewer);

        return $query->whereHas('profile', fn ($q) => $q->where('state', $state ?? '__none__'));
    }
}
