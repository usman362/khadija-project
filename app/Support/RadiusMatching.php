<?php

namespace App\Support;

use App\Domain\Geolocation\LocationPrecision;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Q2 Option B — full geodesic circle, then drop anything not in the event's
 * state. R38 stays the eligibility gate; radius is travel willingness inside it.
 *
 * Direct Offers are not filtered here: a named professional is a choice, not a
 * marketplace search.
 */
final class RadiusMatching
{
    public static function enabled(): bool
    {
        return (bool) config('geo.radius_matching', true);
    }

    public static function originIsMatchable(?User $pro): bool
    {
        $p = $pro?->profile;

        return $p !== null
            && LocationPrecision::isMatchable($p->origin_precision)
            && $p->origin_lat !== null
            && $p->origin_lng !== null
            && $p->travel_radius_miles !== null
            && (int) $p->travel_radius_miles > 0;
    }

    public static function eventIsMatchable(?Event $event): bool
    {
        return $event !== null
            && LocationPrecision::isMatchable($event->location_precision)
            && $event->location_lat !== null
            && $event->location_lng !== null;
    }

    /** May this professional take this request on distance + state? */
    public static function allows(?User $pro, ?Event $event): bool
    {
        if (! self::enabled()) {
            return true;
        }

        if ($event?->source === 'direct_offer') {
            return true;
        }

        if (! self::originIsMatchable($pro) || ! self::eventIsMatchable($event)) {
            return false;
        }

        /*
         * State is no longer a gate.
         *
         * This ran BEFORE the distance was worked out, so a New Jersey
         * photographer was refused a Philadelphia wedding forty minutes away
         * while the radius that would have allowed it was never even measured.
         * Sir Peter, 2026-08-31: distance from the event is the filter.
         *
         * It is still honoured when the switch is on — config('geo.state_matching')
         * — because that is what turning it back on has to mean.
         */
        if (StateMatching::appliesTo($pro)
            && ! StateMatching::matches($pro->profile?->state, $event->state)) {
            return false;
        }

        $miles = Haversine::miles(
            (float) $pro->profile->origin_lat,
            (float) $pro->profile->origin_lng,
            (float) $event->location_lat,
            (float) $event->location_lng,
        );

        return $miles <= (float) $pro->profile->travel_radius_miles;
    }

    /** Drop unresolved / out-of-radius gigs from a loaded board collection. */
    public static function filterEventsForProfessional(Collection $events, ?User $pro): Collection
    {
        if (! self::enabled()) {
            return $events;
        }

        // No Service Origin yet — keep today's same-state board. Radius
        // becomes the gate the moment the origin is placeable.
        if (! self::originIsMatchable($pro)) {
            return $events;
        }

        return $events->values()->filter(fn (Event $event) => self::allows($pro, $event))->values();
    }

    /**
     * Keep professionals whose origin reaches this point, each using their
     * own travel radius. Callers must already have applied R38.
     *
     * @param  iterable<int, User>  $users
     * @return Collection<int, User>
     */
    public static function filterUsersNearPoint(iterable $users, float $lat, float $lng): Collection
    {
        return collect($users)->values()->filter(function (User $user) use ($lat, $lng) {
            if (! self::originIsMatchable($user)) {
                return false;
            }

            $miles = Haversine::miles(
                (float) $user->profile->origin_lat,
                (float) $user->profile->origin_lng,
                $lat,
                $lng,
            );

            return $miles <= (float) $user->profile->travel_radius_miles;
        })->values();
    }
}
