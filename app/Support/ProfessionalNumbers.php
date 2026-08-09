<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\User;

/**
 * The "By The Numbers" figures on a professional's public profile
 * (checklist row 208).
 *
 * Two things shaped this class more than the layout did.
 *
 * The target mockup lists "99% Cancellation Rate" among a 5.0-rated Top Rated
 * Pro's headline stats. That is almost certainly meant to be a COMPLETION
 * rate — a 99% cancellation rate next to a 98% reply rate is a contradiction,
 * and printed literally it would destroy the trust the section exists to
 * build. Row 208 flags it and says do not build the label as written, so this
 * returns `completion_rate` and the profile prints "Completed as booked".
 * If the Owner confirms they really meant cancellations, it is one line.
 *
 * And every figure is counted from records the platform already holds, or it
 * is null. Null prints as a dash. A profile stat is what a client decides on,
 * so a plausible invented number here is worse than a missing one.
 */
final class ProfessionalNumbers
{
    /**
     * @return array{
     *     years: ?int, events_completed: ?int, repeat_clients: ?int,
     *     completion_rate: ?int, response_hours: ?float, reply_rate: ?int
     * }
     */
    public static function for(User $user): array
    {
        $gigs     = GigStats::forProfessional($user);
        $response = ResponseStats::for($user);

        $finished = $gigs['completed'] + $gigs['cancelled'];

        return [
            'years'            => self::years($user),
            'events_completed' => $gigs['completed'] ?: null,
            'repeat_clients'   => self::repeatClientRate($user),

            // Of the bookings that reached an outcome. A professional with no
            // finished bookings has no rate — not a 100% one.
            'completion_rate'  => $finished > 0
                                    ? (int) round($gigs['completed'] / $finished * 100)
                                    : null,

            'response_hours'   => $response['hours'],
            'reply_rate'       => $response['rate'],
        ];
    }

    /**
     * Years in business.
     *
     * The professional's own stated figure first — they know when they
     * started, and it usually predates their GigResource account. Falling
     * back to account age is honest but says something different, so the
     * profile labels it accordingly.
     */
    public static function years(User $user): ?int
    {
        $stated = $user->profile?->experience_years;

        if ($stated) {
            return (int) $stated;
        }

        $onPlatform = $user->created_at?->diffInYears(now());

        return $onPlatform >= 1 ? (int) $onPlatform : null;
    }

    /** Is the years figure the professional's own, or just their account age? */
    public static function yearsAreStated(User $user): bool
    {
        return (bool) $user->profile?->experience_years;
    }

    /**
     * Share of this professional's clients who booked them more than once.
     *
     * Counted over completed bookings only. A client with two proposals out
     * and nothing delivered has not come back for more; they have not been
     * anywhere yet.
     */
    public static function repeatClientRate(User $user): ?int
    {
        $perClient = Booking::query()
            ->where('supplier_id', $user->id)
            ->where('status', 'completed')
            ->selectRaw('client_id, count(*) as bookings')
            ->groupBy('client_id')
            ->pluck('bookings', 'client_id');

        if ($perClient->count() < 2) {
            // One client, or none. A "100% repeat" badge off a single client
            // who booked twice is technically true and completely misleading.
            return null;
        }

        $repeat = $perClient->filter(fn ($count) => $count > 1)->count();

        return (int) round($repeat / $perClient->count() * 100);
    }
}
