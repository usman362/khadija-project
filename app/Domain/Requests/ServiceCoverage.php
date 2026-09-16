<?php

namespace App\Domain\Requests;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Support\Collection;

/**
 * Where each service on a request stands.
 *
 * Sir Peter, 2026-09-17, closing out the multi-service question: the client
 * needs to see the proposals on ONE event grouped by the service they are for,
 * competing bids on the same service side by side, and plainly which services
 * nobody has bid on yet. The awards were already per service (B6); what the
 * client saw was one flat list, with the service as a small tag.
 *
 * One answer per service, used by the event page, the Compare page and the
 * guard that stops a service being awarded twice.
 */
final class ServiceCoverage
{
    public const OPEN     = 'open';      // nobody has bid
    public const HAS_BIDS = 'has_bids';  // proposals in, nobody chosen
    public const AWARDED  = 'awarded';   // a confirmed or completed booking names it

    /**
     * One row per service the request asked for, in the request's own order.
     *
     * A whole-event request (no named service) is a single row with a null
     * service, so the page has the same shape either way.
     *
     * @param  Collection<int, Bid>  $bids  already loaded for the event
     * @return Collection<int, array{service: ?\App\Models\Category, bids: Collection, lowest: ?int, booking: ?Booking, state: string}>
     */
    public static function for(Event $event, Collection $bids): Collection
    {
        $services = $event->categories;
        $bookings = self::liveBookings($event);

        if ($services->isEmpty()) {
            return collect([self::row(null, $bids, $bookings->first())]);
        }

        $rows = $services->map(fn ($service) => self::row(
            $service,
            $bids->where('category_id', $service->id),
            $bookings->firstWhere('category_id', $service->id),
        ));

        // A whole-event bid on a multi-service request answers for all of it.
        // Rare, but it must not vanish because it names no one service.
        $whole = $bids->whereNull('category_id');

        if ($whole->isNotEmpty()) {
            $rows->push(self::row(null, $whole, $bookings->whereNull('category_id')->first()));
        }

        return $rows->values();
    }

    /** The booking that already holds this service on this event, if any. */
    public static function awardFor(Event $event, ?int $categoryId): ?Booking
    {
        return self::liveBookings($event)
            ->first(fn (Booking $b) => $categoryId === null || (int) $b->category_id === $categoryId);
    }

    /** Services on this request nobody has bid on yet. */
    public static function uncovered(Collection $rows): Collection
    {
        return $rows->filter(fn ($r) => $r['service'] && $r['state'] === self::OPEN)->values();
    }

    private static function row($service, Collection $bids, ?Booking $booking): array
    {
        $bids = $bids->sortBy('amount')->values();

        return [
            'service' => $service,
            'bids'    => $bids,
            'lowest'  => $bids->isEmpty() ? null : (int) $bids->first()->amount,
            'booking' => $booking,
            'state'   => match (true) {
                $booking !== null   => self::AWARDED,
                $bids->isNotEmpty() => self::HAS_BIDS,
                default             => self::OPEN,
            },
        ];
    }

    private static function liveBookings(Event $event): Collection
    {
        return Booking::where('event_id', $event->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->with('supplier:id,name')
            ->get();
    }
}
