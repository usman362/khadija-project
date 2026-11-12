<?php

namespace App\Support;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\LeadNote;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * One professional's leads, as Khadijah's Leads CRM lays them out.
 *
 * There is no leads table. A lead is an open request they could take or one
 * they have already acted on, so every stage below is read off records that
 * already exist and every one can be checked:
 *
 *   New lead       an open request they have not answered
 *   Proposal sent  they bid or offered, and nobody has replied
 *   Negotiation    there is a conversation running on it
 *   Booked         it became a booking
 *   Lost           the bid was declined, expired, or the booking cancelled
 *
 * The stage weights are Peter's, from the 28 October screen, and they are
 * shown on the page beside the total rather than applied quietly: a weighted
 * figure nobody can see the workings of is a number somebody plans against.
 */
final class LeadPipeline
{
    /** Stage key => [label, colour, weight as a percentage]. */
    public const STAGES = [
        'new'         => ['New Lead',      '#2563eb', 15],
        'proposal'    => ['Proposal sent', '#f59e0b', 20],
        'negotiation' => ['Negotiation',   '#7c3aed', 30],
        'booked'      => ['Booked',        '#10b981', 100],
        // Lost work is not pipeline. It is counted and shown, and contributes
        // nothing to the value, which is what a weight of nought means.
        'lost'        => ['Lost',          '#ef4444', 0],
    ];

    /**
     * Every lead this professional has, newest activity first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function forProfessional(User $me, ?\Illuminate\Support\Carbon $now = null): Collection
    {
        $now ??= now();

        $myBids     = Bid::where('supplier_id', $me->id)->get()->keyBy('event_id');
        $myBookings = Booking::where('supplier_id', $me->id)->get()->keyBy('event_id');
        $notes      = LeadNote::where('supplier_id', $me->id)->get()->keyBy('event_id');

        $touched = $myBids->keys()->merge($myBookings->keys())->unique();

        /*
         * Open requests they could still take, plus everything they have
         * already acted on, whatever became of it.
         *
         * An open request in another state is left out. Under R38 this
         * professional cannot work it, and a lead they are not allowed to
         * take is not a lead; it was counted once and it made the funnel
         * larger than anything they could act on. A request they have
         * already bid on stays whatever its state, because the work is done.
         */
        $state = $me->profile?->state;

        $events = Event::query()
            ->where(fn ($q) => $q
                ->whereIn('id', $touched)
                ->orWhere(fn ($open) => $open
                    ->where('is_published', true)
                    ->whereNull('supplier_id')
                    ->whereIn('status', ['pending', 'published'])
                    ->when($state, fn ($w) => $w->where('events.state', $state))))
            ->with(['client:id,name', 'categories:id,name'])
            ->get();

        /*
         * The same rule, applied to the second half of it.
         *
         * The paragraph above leaves out an open request in another state,
         * because R38 means this professional can never take it and a lead
         * they are not allowed to take is not a lead. R61 says exactly the
         * same thing about a service they have not listed, and that half was
         * never applied: on 13 November this account's funnel counted seven
         * open requests for work it does not offer, and every one of them
         * answered 403 when you clicked it. Asked of BidAccess, which is
         * also what the wizard asks, so the list and the wizard cannot
         * disagree about what is takeable.
         *
         * A request they have already bid on or been booked for stays
         * whatever became of it: that work happened.
         */
        $listed = FitScore::servicesOf($me);

        $events = $events->filter(
            fn (Event $event) => $touched->contains($event->id) || BidAccess::allows($me, $event, $listed)
        )->values();

        $talking = Conversation::whereIn('event_id', $events->pluck('id'))
            ->whereHas('participants', fn ($q) => $q->where('users.id', $me->id))
            ->has('messages')
            ->pluck('event_id')
            ->unique();

        // Who has hired them before, so a lead can say new client or repeat.
        $repeatClients = Booking::where('supplier_id', $me->id)
            ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
            ->pluck('client_id')
            ->filter()
            ->unique();

        return $events
            ->map(fn (Event $event) => self::row(
                $event,
                $myBids->get($event->id),
                $myBookings->get($event->id),
                $talking->contains($event->id),
                $notes->get($event->id),
                $now,
                $repeatClients,
                $me,
                $listed,
            ))
            ->sortByDesc(fn (array $r) => $r['activity_at']?->timestamp ?? 0)
            ->values();
    }

    /** One lead. */
    private static function row(
        Event $event,
        ?Bid $bid,
        ?Booking $booking,
        bool $talking,
        ?LeadNote $note,
        ?\Illuminate\Support\Carbon $now = null,
        ?Collection $repeatClients = null,
        ?User $me = null,
        ?Collection $listed = null,
    ): array {
        $now ??= now();
        $stage = match (true) {
            $booking && in_array($booking->status, ['confirmed', 'in_progress', 'completed'], true) => 'booked',
            $booking && $booking->status === 'cancelled'                                            => 'lost',
            $bid && in_array($bid->status, ['lost', 'declined', 'rejected', 'expired'], true)       => 'lost',
            $talking                                                                                => 'negotiation',
            $bid || $booking                                                                        => 'proposal',
            default                                                                                 => 'new',
        };

        /*
         * What the lead is worth, read the way it was stated.
         *
         * A booking carries the agreed price. Otherwise it is the client's own
         * budget, as a range only where they gave one. Nothing is estimated:
         * a request with no budget on it says so.
         */
        [$low, $high] = match (true) {
            $booking && $booking->price > 0 => [(float) $booking->price, (float) $booking->price],
            $bid && $bid->amount > 0        => [(float) $bid->amount, (float) $bid->amount],
            /*
             * A range only where the client gave one, and never on a request
             * whose type quotes a single figure by design. An emergency
             * request and a direct offer both name one number; showing a band
             * around it is arithmetic the platform did to something it was
             * told exactly.
             */
            $event->budget_min !== null && $event->budget_max !== null
                && (float) $event->budget_max > (float) $event->budget_min
                && ! in_array($event->source, ['esr', 'direct_offer'], true)
                                            => [(float) $event->budget_min, (float) $event->budget_max],
            $event->budget > 0              => [(float) $event->budget, (float) $event->budget],
            default                         => [null, null],
        };

        $lastContacted = collect([
            $note?->last_contacted_at,
            $bid?->submitted_at ?? $bid?->created_at,
            $booking?->created_at,
        ])->filter()->sortDesc()->first();

        return [
            'event_id'  => $event->id,
            'title'     => $event->title,
            'client'    => $event->client?->name,
            'where'     => $event->location ?: ($event->city ?: null),
            'state'     => $event->state,
            'when'      => $event->starts_at,
            'services'  => $event->categories->pluck('name')->filter()->values()->all(),
            'type'      => $event->event_type,

            'stage'     => $stage,
            'stage_label' => self::STAGES[$stage][0],
            'stage_color' => self::STAGES[$stage][1],

            /*
             * Whether the bid wizard will actually open for this one.
             *
             * The page offered "Open the request" on every row and linked it
             * at the wizard. On 13 November all seven of those links answered
             * 403 or 410: requests outside this professional's listed
             * services, or already closed to proposals. The wizard was right
             * to refuse; the page was wrong to offer. It asks first now, and
             * the answer comes from the same place the wizard's does.
             */
            'can_bid'   => $me !== null && \App\Support\BidAccess::allows($me, $event, $listed),

            // How the request reached them, in the request's own words.
            'source'    => match ($event->source) {
                'esr'          => 'Emergency request',
                'direct_offer' => 'Direct offer',
                'bsr', 'msr'   => 'Marketplace',
                default        => 'Marketplace',
            },

            'low'       => $low,
            'high'      => $high,
            'is_range'  => $low !== null && $high !== null && $high > $low,
            // Only booked work and open pipeline carry value. Lost work is
            // counted and shown, and weighted at nought.
            'weighted'  => $low !== null
                ? round((($low + ($high ?? $low)) / 2) * (self::STAGES[$stage][2] / 100), 2)
                : 0.0,

            'last_contacted'  => $lastContacted,
            'next_follow_up'  => $note?->next_follow_up_on,
            'overdue'         => (bool) $note?->isOverdue(),
            'note'            => $note?->note,

            'next_action' => match ($stage) {
                'new'         => 'Place a bid',
                'proposal'    => 'Follow it up',
                'negotiation' => 'Reply in messages',
                'booked'      => 'Open the gig',
                'lost'        => null,
            },

            'activity_at' => $lastContacted ?? $event->published_at ?? $event->created_at,

            /*
             * How soon it is and how big it is, which is what sets the
             * priority column on Khadijah's screen. Two tests, named, rather
             * than a score: nothing weighs them against each other, so a
             * number out of 100 would be arithmetic nobody agreed on, on the
             * page somebody uses to decide who to chase.
             */
            'priority' => match (true) {
                ($high ?? 0) >= 5000                                            => 'High',
                $event->starts_at && $event->starts_at->isFuture()
                    && $event->starts_at->lte($now->copy()->addDays(14))        => 'High',
                ($high ?? 0) >= 2500 || $event->starts_at === null              => 'Medium',
                default                                                         => 'Low',
            },

            // How long it has been sitting, from the page's one clock.
            'ageDays' => ($event->published_at ?? $event->created_at)
                ? (int) floor(($event->published_at ?? $event->created_at)->diffInDays($now))
                : null,

            // The names the funnel has always used. Kept so the rules pinned
            // against them keep being checked.
            'valueLow'  => $low,
            'valueHigh' => $high,
            'isRange'   => $low !== null && $high !== null && $high > $low,

            // What the table shows beside each row.
            'client_id'  => $event->client_id,
            'initials'   => self::initials($event->client?->name ?: $event->title),
            'repeat'     => $event->client_id !== null
                && (bool) $repeatClients?->contains($event->client_id),
            // A single figure is a fixed price; a range is a range. Saying
            // which is which stops one being read as the other.
            'fixed'      => $low !== null && ($high === null || $high <= $low),
            'description' => $event->description
                ? \Illuminate\Support\Str::limit($event->description, 220)
                : null,
            'guests'     => $event->guest_count,
            'venue'      => $event->venue,
        ];
    }

    /** Two letters for the avatar, from whatever name there is. */
    private static function initials(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $parts = array_values(array_filter($parts));

        if ($parts === []) {
            return '?';
        }

        return mb_strtoupper(
            mb_substr($parts[0], 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '')
        );
    }

    /**
     * The strip across the top: a count, a value and the weight per stage.
     *
     * @param  Collection<int, array>  $leads
     */
    public static function summary(Collection $leads): array
    {
        $out = [];

        foreach (self::STAGES as $key => [$label, $colour, $weight]) {
            $inStage = $leads->where('stage', $key);

            $out[$key] = [
                'label'    => $label,
                'colour'   => $colour,
                'weight'   => $weight,
                'count'    => $inStage->count(),
                'weighted' => round($inStage->sum('weighted'), 2),
            ];
        }

        return $out;
    }

    /**
     * How many leads became bookings.
     *
     * Null when there are none, because nobody with no leads has a conversion
     * rate of nought per cent.
     */
    public static function conversion(Collection $leads): ?int
    {
        return $leads->isEmpty()
            ? null
            : (int) round($leads->where('stage', 'booked')->count() / $leads->count() * 100);
    }
}
