<?php

namespace App\Domain;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Review;
use App\Models\User;
use App\Support\Commission;
use App\Support\GigResourceId;
use Illuminate\Support\Collection;

/**
 * The clients a professional has worked for, one row each.
 *
 * The two sides had drifted here. A client has had Hired Talent since
 * October: who they engaged, for what, and where the money stands. The
 * professional on the other end of those same bookings had only a list of
 * gigs, which answers "what am I doing" and never "who keeps hiring me".
 *
 * So this is Hired Talent read from the other end, and it is grouped the
 * other way round on purpose. A client's page groups by event, because they
 * plan one event and hire several people for it. A professional works one
 * event at a time and the thing worth seeing is the repeat: who came back.
 *
 * Money is NET, through Commission, because that is what the professional
 * receives and it is the basis the Earnings page already shows. A gross
 * figure here would have the same booking worth two different amounts on two
 * screens of theirs.
 *
 * WHAT THIS DOES NOT INVENT. No lifetime-value forecast, no "next likely
 * event", no priority score. Nothing on the platform records a client's
 * intention, and a number with nothing behind it is worse on this page than
 * on most: it is the page somebody would use to decide who to chase.
 */
final class WhoHiredMe
{
    /** Bookings that are money already made, as the Earnings page counts it. */
    private const EARNED = ['completed'];

    /** Bookings still ahead: work agreed but not finished. */
    private const AHEAD = ['pending', 'requested', 'confirmed'];

    /**
     * Every client who has booked this professional, the most recent first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function rows(User $professional): Collection
    {
        $bookings = Booking::query()
            ->where('supplier_id', $professional->id)
            ->whereNotNull('client_id')
            // A professional is not their own client. An old self-booking
            // would otherwise appear here as somebody who hired them.
            ->where('client_id', '!=', $professional->id)
            ->with([
                'client:id,name,public_id,avatar',
                'client.profile:id,user_id,city,state',
                'event:id,title,starts_at',
                'category:id,name',
            ])
            ->get();

        if ($bookings->isEmpty()) {
            return collect();
        }

        $conversations = self::conversationsWith($professional, $bookings->pluck('client_id')->unique());
        $reviewedBy    = Review::whereIn('booking_id', $bookings->pluck('id'))
            ->where('reviewee_id', $professional->id)
            ->pluck('booking_id')
            ->all();

        return $bookings
            ->groupBy('client_id')
            ->map(fn (Collection $theirs, $clientId) => self::row(
                $theirs,
                (int) $clientId,
                $professional,
                $conversations,
                $reviewedBy,
            ))
            ->sortByDesc(fn (array $r) => $r['last']['at']?->timestamp ?? 0)
            ->values();
    }

    /** Totals for the strip above the list. */
    public static function summary(Collection $rows): array
    {
        return [
            'clients'  => $rows->count(),
            // Came back at least once. This is the figure the page exists for.
            'repeat'   => $rows->where('bookings', '>', 1)->count(),
            'upcoming' => $rows->sum('ahead'),
            'earned'   => round((float) $rows->sum('earned'), 2),
        ];
    }

    /**
     * One client, gathered from every booking they have made.
     *
     * @param  Collection<int, Booking>  $theirs
     */
    private static function row(
        Collection $theirs,
        int $clientId,
        User $professional,
        Collection $conversations,
        array $reviewedBy,
    ): array {
        $client = $theirs->first()->client;

        $completed = $theirs->whereIn('status', self::EARNED);
        $ahead     = $theirs->whereIn('status', self::AHEAD);

        // The event most recently worked, and the next one coming. Both read
        // from the event's own date rather than the booking's, because that
        // is the day the professional actually stands somewhere.
        $last = $theirs
            ->filter(fn (Booking $b) => $b->event?->starts_at && $b->event->starts_at->isPast())
            ->sortByDesc(fn (Booking $b) => $b->event->starts_at)
            ->first();

        $next = $ahead
            ->filter(fn (Booking $b) => $b->event?->starts_at && $b->event->starts_at->isFuture())
            ->sortBy(fn (Booking $b) => $b->event->starts_at)
            ->first();

        $gross = (float) $completed->sum('price');

        return [
            'client' => [
                'id'     => $clientId,
                'name'   => $client?->name,
                'avatar' => $client?->avatar_url,
                // Sir Peter's own reference for a person, shown as he reads it.
                'ref'    => $client?->public_id ? GigResourceId::display($client->public_id) : null,
                'where'  => self::place($client),
            ],

            'bookings'  => $theirs->count(),
            'completed' => $completed->count(),
            'ahead'     => $ahead->count(),
            'cancelled' => $theirs->where('status', 'cancelled')->count(),
            // The one thing a professional wants off this page at a glance.
            'returning' => $theirs->count() > 1,

            // Net, because that is what reaches them. See the class note.
            'earned' => round(Commission::netOf($gross, $professional), 2),
            'gross'  => round($gross, 2),

            // Every service they have booked, named once each.
            'services' => $theirs->map(fn (Booking $b) => $b->category?->name)
                ->filter()->unique()->values()->all(),

            'last' => $last ? [
                'title' => $last->event?->title,
                'at'    => $last->event?->starts_at,
                // Whether that job is still owed a review from this client.
                // A review belongs to a booking, so a client can owe one on
                // the last job and not on the one before it.
                'to_review' => $last->status === 'completed'
                    && ! in_array($last->id, $reviewedBy, true),
            ] : null,

            'next' => $next ? [
                'title' => $next->event?->title,
                'at'    => $next->event?->starts_at,
                'id'    => $next->event_id,
            ] : null,

            // The conversation they already have, where there is one. Sending
            // somebody to the message centre to find a name they are already
            // looking at is a step nobody needed.
            'conversation' => $conversations->get($clientId),
        ];
    }

    /**
     * The newest conversation this professional shares with each client.
     *
     * One query for all of them. Done per row it was one query per client on
     * a page whose whole point is having many.
     *
     * @return Collection<int, int> client id => conversation id
     */
    private static function conversationsWith(User $professional, Collection $clientIds): Collection
    {
        if ($clientIds->isEmpty()) {
            return collect();
        }

        return Conversation::query()
            ->whereHas('participants', fn ($q) => $q->where('users.id', $professional->id))
            ->with(['participants:id'])
            ->whereHas('participants', fn ($q) => $q->whereIn('users.id', $clientIds))
            ->latest('updated_at')
            ->get()
            ->reduce(function (Collection $carry, Conversation $c) use ($professional, $clientIds) {
                foreach ($c->participants as $p) {
                    if ($p->id !== $professional->id && $clientIds->contains($p->id) && ! $carry->has($p->id)) {
                        $carry->put($p->id, $c->id);
                    }
                }

                return $carry;
            }, collect());
    }

    private static function place(?User $client): ?string
    {
        $profile = $client?->profile;

        if (! $profile) {
            return null;
        }

        return trim(implode(', ', array_filter([$profile->city, $profile->state]))) ?: null;
    }
}
