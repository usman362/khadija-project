<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;

/**
 * The three things Khadijah's reference card checks off under each job.
 *
 * Her screen shows "Matches your services", "Within your travel radius" and
 * "Verified Client" as green ticks beneath every opportunity. Each is a claim
 * about this professional and this job, so each is answered from a record
 * rather than printed as decoration:
 *
 *   services   the request names a service this professional has listed,
 *              which is already the difference between the feed's two blocks
 *   travel     the distance between where they work out of and where the job
 *              is, against the radius they themselves set
 *   client     who posted it, told honestly: somebody they have worked for
 *              before, somebody whose identity has been checked, or somebody
 *              new, which is a fact and not a warning
 *
 * Any of the three can be unknown, and unknown is said. A card that prints
 * "0 mi" because an address could not be placed is claiming the job is next
 * door, which is the single answer the data does not support.
 */
final class OpportunityCard
{
    /**
     * @return array{
     *   services: array{state:string, text:string},
     *   travel:   array{state:string, text:string, miles:?float},
     *   client:   array{state:string, text:string},
     *   chips:    array<int, string>,
     *   distance: ?string
     * }
     */
    public static function for(Event $event, ?User $pro, bool $listed): array
    {
        return [
            'services' => self::services($listed),
            'travel'   => self::travel($event, $pro),
            'client'   => self::client($event, $pro),
            'chips'    => $event->categories->pluck('name')->take(3)->all(),
            'distance' => self::distanceLabel($event, $pro),
        ];
    }

    /** @return array{state:string, text:string} */
    private static function services(bool $listed): array
    {
        return $listed
            ? ['state' => 'yes', 'text' => 'Matches your services']
            : ['state' => 'no',  'text' => 'Outside your listed services'];
    }

    /** @return array{state:string, text:string, miles:?float} */
    private static function travel(Event $event, ?User $pro): array
    {
        $miles = RadiusMatching::milesBetween($pro, $event);

        if ($miles === null) {
            /*
             * Either this professional has not said where they travel from,
             * or the client's address could not be placed. Both are real and
             * both are common, and neither is a tick.
             */
            return [
                'state' => 'unknown',
                'text'  => RadiusMatching::originIsMatchable($pro)
                    ? 'Distance not known yet'
                    : 'Set where you travel from',
                'miles' => null,
            ];
        }

        $radius = (float) ($pro?->profile?->travel_radius_miles ?? 0);

        if ($radius <= 0) {
            return ['state' => 'unknown', 'text' => 'No travel radius set', 'miles' => $miles];
        }

        return $miles <= $radius
            ? ['state' => 'yes', 'text' => 'Within your travel radius', 'miles' => $miles]
            : ['state' => 'no',  'text' => 'Beyond your travel radius', 'miles' => $miles];
    }

    /**
     * Who posted it.
     *
     * "Verified Client" on the reference screen. There is no client badge on
     * this platform and inventing one would repeat the exact fault
     * App\Support\VerifiedBadge exists to stop, so this says the strongest
     * thing that is true: that they have hired this professional before, that
     * their identity has been checked, or that they are new. New is a fact
     * about a client, not a warning about one.
     *
     * @return array{state:string, text:string}
     */
    private static function client(Event $event, ?User $pro): array
    {
        $client = $event->client;

        if (! $client) {
            return ['state' => 'unknown', 'text' => 'Client not available'];
        }

        if ($pro && Booking::where('client_id', $client->id)
            ->where('supplier_id', $pro->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->exists()
        ) {
            return ['state' => 'yes', 'text' => 'You have worked for them before'];
        }

        if ($client->profile?->identity_verified_at) {
            return ['state' => 'yes', 'text' => 'Identity verified'];
        }

        return ['state' => 'new', 'text' => 'New client'];
    }

    /** The distance as it goes beside the place name, or nothing. */
    private static function distanceLabel(Event $event, ?User $pro): ?string
    {
        $miles = RadiusMatching::milesBetween($pro, $event);

        if ($miles === null) {
            return null;
        }

        // Under a mile is "less than a mile", not "0.4 mi", which reads as a
        // measurement nobody made.
        return $miles < 1 ? 'under a mile' : round($miles).' mi';
    }
}
