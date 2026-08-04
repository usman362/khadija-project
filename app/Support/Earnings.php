<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Payout;
use App\Models\User;

/**
 * One professional's money, computed in one place.
 *
 * This exists because the same figures were being derived in three separate
 * spots — the Earnings page (which returned hardcoded zeros and so showed
 * "No earnings recorded yet"), the Transactions page (which queried for real),
 * and the payout request guard (its own copy of the same query). The result
 * was one account reporting Pending = $0.00 on one screen and $4,826.00 on
 * another at the same moment.
 *
 * Two figures matter and are easy to confuse:
 *   • gross   — the booking price the client agreed to
 *   • net     — what the professional actually receives, after commission
 *
 * Everything returned here is NET. A gross figure on the payout screen would
 * let a professional withdraw the platform's commission along with their own
 * money, so the withdrawal guard must use the same basis the screen shows.
 */
class Earnings
{
    /** Bookings that count as money already made. */
    private const EARNED_STATUSES = ['completed'];

    /** Bookings that count as money on the way. */
    private const PENDING_STATUSES = ['pending', 'confirmed'];

    /**
     * The full picture for one professional.
     *
     * @return array{
     *     total: int, gross: float, earned: float, commission: float,
     *     commissionPct: float, pending: float, pendingCount: int,
     *     withdrawn: float, requested: float, available: float,
     *     hasActivity: bool
     * }
     */
    public static function forProfessional(User $user): array
    {
        $bookings = Booking::where('supplier_id', $user->id);

        $gross        = (float) (clone $bookings)->whereIn('status', self::EARNED_STATUSES)->sum('price');
        $pendingGross = (float) (clone $bookings)->whereIn('status', self::PENDING_STATUSES)->sum('price');
        $earned       = Commission::netOf($gross, $user);

        $payouts   = Payout::where('user_id', $user->id);
        $withdrawn = (float) (clone $payouts)->where('status', 'paid')->sum('amount');
        $requested = (float) (clone $payouts)->where('status', 'requested')->sum('amount');

        $total = (clone $bookings)->count();

        return [
            'total'         => $total,
            'gross'         => $gross,
            'earned'        => $earned,
            'commission'    => round($gross - $earned, 2),
            'commissionPct' => Commission::rateFor($user),
            'pending'       => Commission::netOf($pendingGross, $user),
            'pendingCount'  => (clone $bookings)->whereIn('status', self::PENDING_STATUSES)->count(),
            'withdrawn'     => $withdrawn,
            'requested'     => $requested,
            // Still withdrawable: earned, less what has been paid out, less
            // what is already in flight.
            'available'     => max(0.0, $earned - $withdrawn - $requested),
            // An account with bookings has a story to tell even at $0 earned —
            // "no earnings yet" is only true when there is nothing at all.
            'hasActivity'   => $total > 0,
        ];
    }
}
