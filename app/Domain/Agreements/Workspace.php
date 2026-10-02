<?php

namespace App\Domain\Agreements;

use App\Models\Finalization;
use App\Models\User;

/**
 * The Agreement Workspace: where an agreement stands, and what moves it.
 *
 * Sir Peter's developer recommendations, 27 September. Two rules carry the
 * whole thing, and both are about not claiming agreement nobody gave:
 *
 *   "E-signature should unlock only after both parties approve the same
 *    agreement version."
 *
 *   "material changes must invalidate prior approvals/signatures for the
 *    changed agreement version... A signature must never silently carry
 *    forward to terms the person did not sign."
 *
 * So approval is approval OF A VERSION, not of an agreement in the abstract.
 * Ask whether somebody approved and the honest question is "approved what?".
 * Everything here answers that question rather than a vaguer one.
 *
 * The statuses are derived, never stored. A status column and a set of
 * timestamps are two accounts of the same fact, and they drift.
 */
final class Workspace
{
    public const NEGOTIATING = 'negotiating';
    public const APPROVED    = 'terms_approved';
    public const AWAITING    = 'awaiting_signatures';
    public const SIGNED      = 'signed_funding_required';
    public const BOOKED      = 'funded_booked';
    public const DECLINED    = 'declined';

    /** How each state is named and coloured, wherever it is drawn. */
    public const STATES = [
        self::NEGOTIATING => ['Negotiating',            '#d97706', 'One or both sides are still clarifying or changing the terms.'],
        self::APPROVED    => ['Terms approved',         '#2563eb', 'Both of you approved the same version of this agreement.'],
        self::AWAITING    => ['Awaiting signatures',    '#7c3aed', 'The terms are locked. It needs both signatures.'],
        self::SIGNED      => ['Signed, funding needed', '#0891b2', 'Both signatures are in. The payment is still to come.'],
        self::BOOKED      => ['Booked',                 '#059669', 'Funded and active.'],
        self::DECLINED    => ['Declined',               '#64748b', 'One of you ended this agreement.'],
    ];

    /** The terms whose change invalidates what was agreed about them. */
    public const MATERIAL = [
        'scope', 'agreed_price', 'service_start', 'service_end',
        'deposit_percent', 'deposit_amount', 'balance_due_on', 'payment_terms',
    ];

    /**
     * Which version the terms are on.
     *
     * A row fresh from create() has not read the column's default back, so
     * the value is null in memory while the database says 1. Treating null as
     * 0 made "nobody has approved" and "both approved" the same comparison,
     * which is how an unapproved agreement reported itself settled.
     */
    public static function version(Finalization $f): int
    {
        return (int) ($f->terms_version ?: 1);
    }

    public static function status(Finalization $f): string
    {
        if ($f->declined_at) {
            return self::DECLINED;
        }

        if ($f->funded_at) {
            return self::BOOKED;
        }

        if (self::bothSigned($f)) {
            return self::SIGNED;
        }

        if (self::bothApproved($f)) {
            return self::AWAITING;
        }

        return self::NEGOTIATING;
    }

    /** Both approved, and both approved THIS version. */
    public static function bothApproved(Finalization $f): bool
    {
        return self::clientApproved($f) && self::supplierApproved($f);
    }

    /** Both signed, and both signed THIS version. */
    public static function bothSigned(Finalization $f): bool
    {
        return self::clientSigned($f) && self::supplierSigned($f);
    }

    public static function clientApproved(Finalization $f): bool
    {
        // Nobody having answered is not an answer, so null is never approval.
        return $f->client_approved_version !== null
            && (int) $f->client_approved_version === self::version($f);
    }

    public static function supplierApproved(Finalization $f): bool
    {
        return $f->supplier_approved_version !== null
            && (int) $f->supplier_approved_version === self::version($f);
    }

    public static function clientSigned(Finalization $f): bool
    {
        return (bool) $f->client_signed_at
            && $f->client_signed_version !== null
            && (int) $f->client_signed_version === self::version($f);
    }

    public static function supplierSigned(Finalization $f): bool
    {
        return (bool) $f->supplier_signed_at
            && $f->supplier_signed_version !== null
            && (int) $f->supplier_signed_version === self::version($f);
    }

    /**
     * Signing opens only once both sides approved this same version. Before
     * that there is nothing settled to sign, and offering the pen implies
     * there is.
     */
    public static function signingOpen(Finalization $f): bool
    {
        return ! $f->declined_at && self::bothApproved($f);
    }

    /** Approve, as this person, the version in front of them. */
    public static function approve(Finalization $f, User $by): void
    {
        if ($f->declined_at) {
            return;
        }

        $f->forceFill([
            self::sideOf($f, $by) === 'client'
                ? 'client_approved_version'
                : 'supplier_approved_version' => self::version($f),
        ])->save();
    }

    /**
     * Send it back to be negotiated, with a reason.
     *
     * This raises the version, which is what takes every approval and
     * signature out of play: they were given to terms that are about to
     * change. Nothing is deleted, because what somebody signed and when is a
     * record; it simply stops counting for the new version.
     */
    public static function proposeChanges(Finalization $f, User $by, string $reason): void
    {
        if ($f->declined_at || $f->funded_at) {
            return;
        }

        $f->forceFill([
            'terms_version'       => self::version($f) + 1,
            'change_request'      => $reason,
            'change_requested_by' => $by->id,
            'change_requested_at' => now(),
        ])->save();
    }

    /**
     * A material term changed, however it changed.
     *
     * Called wherever the agreement is edited, so a price typed into a step
     * has the same consequence as a price changed from the workspace. The
     * rule is about the terms, not about which screen altered them.
     */
    public static function termsChanged(Finalization $f, array $changed): bool
    {
        if (! array_intersect($changed, self::MATERIAL)) {
            return false;
        }

        if (self::clientApproved($f) || self::supplierApproved($f) || $f->client_signed_at || $f->supplier_signed_at) {
            $f->forceFill(['terms_version' => self::version($f) + 1])->save();

            return true;
        }

        return false;
    }

    public static function decline(Finalization $f, User $by): void
    {
        if ($f->funded_at) {
            return;
        }

        $f->forceFill(['declined_at' => now(), 'declined_by' => $by->id])->save();
    }

    /** Which side of this agreement somebody is on. */
    public static function sideOf(Finalization $f, User $user): ?string
    {
        if ((int) $f->client_id === (int) $user->id) {
            return 'client';
        }

        if ((int) $f->supplier_id === (int) $user->id) {
            return 'supplier';
        }

        return null;
    }
}
