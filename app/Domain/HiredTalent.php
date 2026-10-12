<?php

namespace App\Domain;

use App\Domain\Finance\PaymentTracker;
use App\Models\Booking;
use App\Models\User;
use App\Support\SupplierProfile;
use App\Support\VerifiedBadge;
use Illuminate\Support\Collection;

/**
 * Who the client has actually hired, gathered by event.
 *
 * The Calendar page carried a "Hired Professionals" tab that was switched off,
 * and the reason written on it was "Professionals do not record their
 * available dates yet". That is the reason Check Availability is waiting; it
 * is not this one. Who a client has hired is a booking, and bookings are
 * something we know completely.
 *
 * Sir Peter's screen groups them by event date, with a list on the left and
 * the one you picked on the right. The money comes from PaymentTracker, which
 * already reconciles an agreement and its booking into a single row, so the
 * same figure is never counted twice.
 *
 * ONE THING HIS SCREEN SHOWS THAT THIS DOES NOT: a Readiness bar, "3 of 5
 * complete, Next: Sound Check". Nothing in the system records steps towards an
 * event, so that number would have to be invented. It is left out rather than
 * filled in.
 */
final class HiredTalent
{
    /** Booking status => what the client should read. */
    private const STATES = [
        'requested' => ['Awaiting confirmation', 'wait'],
        'confirmed' => ['Confirmed', 'ok'],
        'completed' => ['Completed', 'done'],
        'cancelled' => ['Cancelled', 'off'],
    ];

    /**
     * Every supplier the client has engaged, newest event first.
     *
     * @return Collection<int, array{
     *     id:int, event:array, supplier:array, service:?string,
     *     state:string, tone:string, money:array, urls:array
     * }>
     */
    public static function rows(User $client): Collection
    {
        // Keyed by event and the professional's own id. Keying on their name
        // would pair the wrong money together the day two suppliers on one
        // event share a name, and names are not ours to make unique.
        $money = PaymentTracker::rows($client)
            ->keyBy(fn ($r) => $r['event_id'] . '|' . $r['pro_id']);

        return Booking::query()
            ->where('client_id', $client->id)
            ->whereNotNull('supplier_id')
            // A client is not their own supplier; an older self-booking would
            // otherwise appear here as somebody they hired.
            ->where('supplier_id', '!=', $client->id)
            ->with([
                'event:id,title,starts_at,ends_at,city,state,venue,location',
                // withAvg on the relation, because the rating belongs to the
                // person, not to this booking of them.
                'supplier' => fn ($q) => $q
                    ->withAvg(['reviewsReceived as rating' => fn ($r) => $r->where('is_hidden', false)], 'rating')
                    ->withCount(['reviewsReceived as reviews' => fn ($r) => $r->where('is_hidden', false)]),
                'supplier.profile',
                'category:id,name',
            ])

            ->get()
            ->map(fn (Booking $b) => self::row($b, $money))
            ->sortByDesc(fn ($r) => $r['event']['at']?->timestamp ?? 0)
            ->values();
    }

    /** The same rows under the date they belong to, which is how the page reads. */
    public static function byEvent(User $client): Collection
    {
        return self::rows($client)
            ->groupBy(fn ($r) => $r['event']['id'])
            ->map(fn ($rows) => [
                'event' => $rows->first()['event'],
                'rows'  => $rows->values(),
            ])
            ->sortByDesc(fn ($g) => $g['event']['at']?->timestamp ?? 0)
            ->values();
    }

    /** Totals for the strip above the list. */
    public static function summary(Collection $rows): array
    {
        return [
            'all'    => $rows->count(),
            'active' => $rows->reject(fn ($r) => in_array($r['raw_state'], ['completed', 'cancelled'], true))->count(),
            'past'   => $rows->filter(fn ($r) => in_array($r['raw_state'], ['completed', 'cancelled'], true))->count(),
        ];
    }

    private static function row(Booking $b, Collection $money): array
    {
        [$state, $tone] = self::STATES[$b->status] ?? ['Booked', 'ok'];
        $pay = $money->get($b->event_id . '|' . $b->supplier?->public_id);
        $supplier = $b->supplier;

        return [
            'id'        => $b->id,
            'raw_state' => $b->status,
            'state'     => $state,
            'tone'      => $tone,
            'service'   => $b->category?->name,

            'event' => [
                'id'    => $b->event_id,
                'title' => $b->event?->title,
                'at'    => $b->event?->starts_at,
                'ends'  => $b->event?->ends_at,
                'where' => self::place($b),
            ],

            'supplier' => [
                'id'       => $supplier?->id,
                'name'     => $supplier?->name,
                // The accessor, not the column: it falls back to generated
                // initials, which is why the raw value was a broken image.
                'avatar'   => $supplier?->avatar_url,
                'headline' => $supplier?->profile?->headline,
                // "professional" or "influencer", so the page never calls an
                // influencer a professional. See SupplierProfile.
                'noun'     => SupplierProfile::noun($supplier),
                'where'    => $supplier?->profile
                    ? (trim(implode(', ', array_filter([$supplier->profile->city, $supplier->profile->state]))) ?: null)
                    : null,
                // Shown only where the badge is held and the feature is on.
                'verified' => VerifiedBadge::shown()
                    && VerifiedBadge::holds($supplier?->profile, 'trade_license'),
                'rating'   => $supplier?->rating ? round((float) $supplier->rating, 1) : null,
                'reviews'  => (int) ($supplier?->reviews ?? 0),
            ],

            // Null where the booking never carried money through the platform,
            // which reads as "nothing recorded" rather than as zero owed.
            'money' => $pay ? [
                'total'   => $pay['amount'],
                'paid'    => $pay['paid'],
                'balance' => $pay['balance'],
                'label'   => $pay['label'],
                'due'     => $pay['due'],
                'overdue' => $pay['overdue'] ?? false,
            ] : null,

            'urls' => [
                'event'   => $b->event_id ? route('client.events.show', $b->event_id) : null,
                // Sending them a fresh request, which is what "book again"
                // means on a marketplace where every job is its own agreement.
                'again'   => $supplier
                    ? route('client.direct-offers.create', ['pro' => $supplier->id])
                    : null,
                'profile' => SupplierProfile::url($supplier),
                // Keyed by the booking, not the event: the two ids happen to
                // line up on some rows and the route takes the booking.
                'payments' => route('client.payments.show', $b->id),
            ],
        ];
    }

    /** Where it happens, as much of it as the event actually recorded. */
    private static function place(Booking $b): ?string
    {
        $e = $b->event;

        if (! $e) {
            return null;
        }

        $parts = array_filter([
            $e->venue,
            trim(implode(', ', array_filter([$e->city, $e->state]))) ?: null,
        ]);

        return $parts === [] ? ($e->location ?: null) : implode(' &middot; ', $parts);
    }
}
