<?php

namespace App\Support;

use App\Models\User;

/**
 * Rule R47 — one professional account per state.
 *
 * A professional who works in more than one of the seven jurisdictions holds a
 * fully separate account for each: its own email, its own trade licence
 * proving THAT state's licensing, its own registration and payment. The phone
 * number is explicitly allowed to be shared (confirmed 2026-08-03) — it is the
 * one thing that may be the same across a person's accounts.
 *
 * The half of the rule that needed building is the half nobody would notice:
 * a professional could simply EDIT their state in Profile & Settings. That is
 * precisely the thing R47 exists to prevent. Flipping the field would carry
 * an account's reviews, badges and booking history into a state it was never
 * licensed in, and would silently move every package and gig it owns under
 * R38. The rule's answer is a second account, so the field is fixed after
 * registration and the page says why.
 *
 * Deliberately NOT here: anything that links a person's accounts to each
 * other. R47 records "open questions on reputation/profile sharing across
 * accounts", so the accounts stay independent until those are answered —
 * inventing a link now would answer them by accident.
 */
final class ProfessionalStateAccount
{
    /**
     * May this account's registered state still be set by its owner?
     *
     * Only while it has none. An account that registered without one — older
     * rows, an admin-created account — can be completed by its owner; once it
     * says a state, changing it is a second-account question, not an edit.
     */
    public static function ownerMaySetState(?User $user): bool
    {
        if ($user === null || ! $user->hasRole('professional')) {
            return true;   // R47 governs professionals; clients are R38's business
        }

        return StateMatching::stateOf($user) === null;
    }

    /**
     * Is this trade licence for the state the account operates in?
     *
     * A licence issued in Delaware proves nothing about working in Maryland,
     * and R47 asks for "proof of that state's licensing" on each account. A
     * licence with no state recorded is unknown rather than wrong — the
     * column is new and existing licences predate it.
     */
    public static function licenceCoversAccountState(?User $user): ?bool
    {
        $licence = $user?->profile?->trade_license_state;

        if ($licence === null || $licence === '') {
            return null;
        }

        return StateMatching::matches($licence, StateMatching::stateOf($user));
    }

    /**
     * What to tell a professional who works in another state.
     *
     * Said in full rather than as a refusal: the honest version of "no" here
     * is "not on this account", and leaving off the second half turns a
     * workable rule into a dead end.
     */
    public static function secondAccountExplanation(?User $user): string
    {
        $state = StateMatching::stateOf($user);

        return $state
            ? "This account works in {$state}. To take work in another state, open a separate "
              . 'account for it with its own email and that state’s licence — your phone number '
              . 'can stay the same.'
            : 'Add the state this account works in. Each state you work in needs its own account, '
              . 'with its own licence.';
    }
}
