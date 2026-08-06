<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * How many gigs a professional has, and what each word means.
 *
 * Issue 6 of the Professional IA Consolidation Plan: the same handful of
 * numbers were worked out separately on every page, and they had drifted
 * apart. The Gig Operations Hub called a gig "In Progress" when its event was
 * happening at that moment; Contracts called a gig "Active" when it was
 * confirmed *or* still only requested — a set that includes proposals nobody
 * has accepted. Those two now sit on the same page as two tabs, so a
 * professional sees both words at once.
 *
 * The words are defined here, once:
 *
 *   pending     a proposal is out; nobody has accepted it yet
 *   booked      accepted, the work has not started
 *   inProgress  accepted, and the event is running right now
 *   completed   delivered
 *   cancelled   called off by either side
 *   active      anything still live — pending + booked
 *
 * `inProgress` is a subset of `booked`, not a separate status, so booked and
 * inProgress deliberately overlap. Everything else is exclusive, and
 * pending + booked + completed + cancelled = total.
 */
class GigStats
{
    /**
     * @return array{
     *     total: int, pending: int, booked: int, inProgress: int,
     *     completed: int, cancelled: int, active: int
     * }
     */
    public static function forProfessional(User $user): array
    {
        $counts = self::base($user)
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $pending   = (int) ($counts['requested'] ?? 0);
        $booked    = (int) ($counts['confirmed'] ?? 0);
        $completed = (int) ($counts['completed'] ?? 0);
        $cancelled = (int) ($counts['cancelled'] ?? 0);

        return [
            'total'      => $pending + $booked + $completed + $cancelled,
            'pending'    => $pending,
            'booked'     => $booked,
            'inProgress' => self::inProgressQuery($user)->count(),
            'completed'  => $completed,
            'cancelled'  => $cancelled,
            'active'     => $pending + $booked,
        ];
    }

    /** Accepted, and the event is running right now. */
    public static function inProgressQuery(User $user): Builder
    {
        return self::base($user)
            ->where('status', 'confirmed')
            ->whereHas('event', fn ($q) => $q
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now()));
    }

    /** Anything still live — nothing finished, nothing called off. */
    public static function activeQuery(User $user): Builder
    {
        return self::base($user)->whereIn('status', ['requested', 'confirmed']);
    }

    private static function base(User $user): Builder
    {
        return Booking::where('supplier_id', $user->id);
    }
}
