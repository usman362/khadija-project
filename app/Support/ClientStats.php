<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Message;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Every public number about a client, worked out once.
 *
 * Rule R53's build spec is explicit about why this is a class and not a set of
 * queries in a controller: the Quick Stats row (section 2) and the Client
 * Statistics panel (section 7) repeat four of the same figures on ONE page,
 * and the Client Dashboard shows them a third time. Three copies of a
 * calculation is three chances to disagree — the same defect already found
 * between Earnings and Transactions, and the reason App\Support\Earnings
 * exists.
 *
 * Nothing here is estimated. Where the platform cannot know something the
 * value is null and the page says so, rather than showing a plausible number:
 * a client with no completed events has no cancellation rate, and one nobody
 * has messaged has no response rate.
 */
final class ClientStats
{
    /** Bookings that count as work the client actually saw through. */
    private const COMPLETED = ['completed'];

    /**
     * @return array{
     *   completed_events:int, total_events:int, cancelled:int,
     *   cancellation_rate:?int, repeat_professionals:int,
     *   response_rate:?int, response_hours:?float,
     *   rating:?float, reviews_count:int,
     *   member_since:?\Illuminate\Support\Carbon, last_active:?\Illuminate\Support\Carbon
     * }
     */
    public static function for(User $client): array
    {
        $bookings  = Booking::where('client_id', $client->id);
        $completed = (clone $bookings)->whereIn('status', self::COMPLETED)->count();
        $cancelled = (clone $bookings)->where('status', 'cancelled')->count();
        $decided   = $completed + $cancelled;

        $response = ResponseStats::for($client);
        $reviews  = Review::visible()->about($client->id);

        return [
            'completed_events' => $completed,
            'total_events'     => Event::where('client_id', $client->id)->count(),
            'cancelled'        => $cancelled,

            // Of the bookings that reached an outcome. Counting live bookings
            // in the denominator would make a busy client look more reliable
            // just for having work in flight.
            'cancellation_rate' => $decided > 0 ? (int) round($cancelled / $decided * 100) : null,

            'repeat_professionals' => self::repeatProfessionals($client),

            'response_rate'  => $response['rate'],
            'response_hours' => $response['hours'],

            'rating'        => ($avg = (clone $reviews)->avg('rating')) ? round((float) $avg, 2) : null,
            'reviews_count' => (clone $reviews)->count(),

            'member_since' => $client->created_at,
            'last_active'  => self::lastActive($client),
        ];
    }

    /** Professionals this client has booked more than once. */
    public static function repeatProfessionals(User $client): int
    {
        return Booking::where('client_id', $client->id)
            ->whereNotNull('supplier_id')
            ->whereIn('status', ['confirmed', 'completed'])
            ->selectRaw('supplier_id, count(*) as n')
            ->groupBy('supplier_id')
            ->havingRaw('count(*) > 1')
            ->get()
            ->count();
    }

    /**
     * Section 9 — completed events per category, biggest first.
     *
     * Reads the same completed bookings as `completed_events`, so the tiles
     * and the headline count cannot drift apart.
     */
    public static function eventTypeCounts(User $client): Collection
    {
        return Event::query()
            ->where('events.client_id', $client->id)
            ->whereHas('bookings', fn ($b) => $b->whereIn('status', self::COMPLETED))
            ->with('categories:id,name')
            ->get()
            ->flatMap(fn (Event $e) => $e->categories->pluck('name'))
            ->countBy()
            ->sortDesc();
    }

    /**
     * The most recent thing this client did that the platform can see.
     *
     * There is no last-seen column, and adding one would only start recording
     * from today. These three records are what actually exists, and each of
     * them is genuinely the client doing something.
     */
    public static function lastActive(User $client): ?\Illuminate\Support\Carbon
    {
        $candidates = collect([
            Message::where('sender_id', $client->id)->max('created_at'),
            Event::where('client_id', $client->id)->max('created_at'),
            Booking::where('client_id', $client->id)->max('created_at'),
        ])->filter()->map(fn ($t) => \Illuminate\Support\Carbon::parse($t));

        return $candidates->max();
    }
}
