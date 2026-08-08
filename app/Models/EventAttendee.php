<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * One guest on one event's list — Rule R60.
 *
 * Always reached through its event. There is no route that lists attendees
 * across events, which is the whole correction R60 makes: the widget this
 * replaces showed every guest a client had ever added, with no way to tell
 * which event any of them belonged to.
 */
class EventAttendee extends Model
{
    public const CONFIRMED   = 'confirmed';
    public const CANCELLED   = 'cancelled';
    public const NO_RESPONSE = 'no_response';

    /** The three states the summary counts, in the order it shows them. */
    public const STATUSES = [
        self::CONFIRMED   => 'Confirmed',
        self::CANCELLED   => 'Cancelled',
        self::NO_RESPONSE => 'No Response',
    ];

    protected $fillable = [
        'event_id', 'name', 'email', 'phone', 'rsvp_status', 'dietary', 'accessibility',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * The headline the event page and the dashboard both show —
     * "120 Guests · 75 Confirmed · 10 Cancelled · 35 No Response".
     *
     * Counted here rather than in each caller so the dashboard summary and
     * the event's own list can never disagree about the same event.
     *
     * @return array{total:int, confirmed:int, cancelled:int, no_response:int}
     */
    public static function summaryFor(Event $event): array
    {
        $counts = static::where('event_id', $event->id)
            ->selectRaw('rsvp_status, count(*) as n')
            ->groupBy('rsvp_status')
            ->pluck('n', 'rsvp_status');

        return [
            'total'       => (int) $counts->sum(),
            'confirmed'   => (int) $counts->get(self::CONFIRMED, 0),
            'cancelled'   => (int) $counts->get(self::CANCELLED, 0),
            'no_response' => (int) $counts->get(self::NO_RESPONSE, 0),
        ];
    }

    /**
     * Summaries for several events at once, keyed by event id.
     *
     * The dashboard shows one line per event with a guest list; doing that
     * with summaryFor() in a loop is a query per event.
     */
    public static function summariesFor(Collection|array $eventIds): Collection
    {
        return static::whereIn('event_id', $eventIds)
            ->selectRaw('event_id, rsvp_status, count(*) as n')
            ->groupBy('event_id', 'rsvp_status')
            ->get()
            ->groupBy('event_id')
            ->map(fn ($rows) => [
                'total'       => (int) $rows->sum('n'),
                'confirmed'   => (int) $rows->firstWhere('rsvp_status', self::CONFIRMED)?->n ?? 0,
                'cancelled'   => (int) $rows->firstWhere('rsvp_status', self::CANCELLED)?->n ?? 0,
                'no_response' => (int) $rows->firstWhere('rsvp_status', self::NO_RESPONSE)?->n ?? 0,
            ]);
    }
}
