<?php

namespace App\Support;

use App\Models\Agreement;
use App\Models\Bid;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Everything waiting on one professional, as a single list.
 *
 * Khadijah's 28 October screen is a list of individual things to do, each with
 * what it is, what it is about, when it is due and one button. The page it
 * replaces showed four counters, which tell somebody that four contracts need
 * signing without telling them which four.
 *
 * Every row here comes from a record that already exists. Nothing is scored,
 * guessed or predicted: an item is urgent because its own date says so, and an
 * item with no date is not given one.
 */
final class PriorityActions
{
    /** How soon counts as due soon, in days. */
    private const SOON = 3;

    /**
     * @return Collection<int, array{
     *   kind:string, tone:string, group:string, title:string, subject:?string,
     *   detail:?string, due:?Carbon, when:?string, cta:string, url:string
     * }>
     */
    public static function forProfessional(User $me): Collection
    {
        return collect()
            ->concat(self::agreements($me))
            ->concat(self::proposals($me))
            ->concat(self::shifts($me))
            ->concat(self::bids($me))
            ->concat(self::drafts($me))
            ->concat(self::conflicts($me))
            ->concat(self::payments($me))
            /*
             * Dated items first and soonest first, then the undated ones. A
             * list sorted by urgency alone puts an item due in an hour below
             * one that has no date at all.
             */
            ->sortBy(fn (array $i) => [$i['due'] === null, optional($i['due'])->timestamp ?? 0])
            ->values();
    }

    /** Agreements the client has signed and this professional has not. */
    private static function agreements(User $me): Collection
    {
        return Agreement::whereHas('booking', fn ($q) => $q->where('supplier_id', $me->id))
            ->whereIn('status', ['pending_review', 'client_accepted'])
            ->whereNull('supplier_accepted_at')
            ->with('booking.event:id,title')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Agreement $a) => [
                'kind'    => 'Agreement',
                'tone'    => 'urgent',
                'group'   => 'agreements',
                'title'   => 'Agreement awaiting your signature',
                'subject' => $a->booking?->event?->title,
                'detail'  => 'The client has signed. Review it and confirm the booking.',
                'due'     => null,
                'where'   => $a->booking?->event?->location,
                'sent'    => $a->created_at,
                'sent_as' => 'Sent',
                'when'    => $a->created_at ? 'Sent ' . $a->created_at->humanAgo() : null,
                'cta'     => 'Review and sign',
                'url'     => route('professional.agreements.index'),
            ]);
    }

    /** Bookings a client has asked for and nobody has answered. */
    private static function proposals(User $me): Collection
    {
        return Booking::where('supplier_id', $me->id)
            ->where('status', 'requested')
            ->with('event:id,title,starts_at')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Booking $b) => [
                'kind'    => 'Request',
                'tone'    => 'new',
                'group'   => 'clients',
                'title'   => 'A client is waiting on your answer',
                'subject' => $b->event?->title,
                'detail'  => $b->price ? 'Asking $' . number_format((float) $b->price, 0) : null,
                'due'     => null,
                'where'   => $b->event?->location,
                'sent'    => $b->created_at,
                'sent_as' => 'Received',
                'when'    => $b->created_at ? 'Received ' . $b->created_at->humanAgo() : null,
                'cta'     => 'Open the request',
                'url'     => route('professional.proposals.index'),
            ]);
    }

    /** Shifts with nobody on them, for work that has not happened yet. */
    private static function shifts(User $me): Collection
    {
        return Shift::where('supplier_id', $me->id)
            ->where('status', 'open')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '>=', now()))
            ->with('event:id,title')
            ->orderByRaw('starts_at is null, starts_at asc')
            ->limit(20)
            ->get()
            ->map(fn (Shift $s) => [
                'kind'    => 'Staffing',
                'tone'    => $s->starts_at && $s->starts_at->diffInDays(now()) <= self::SOON ? 'urgent' : 'soon',
                'group'   => 'urgent',
                'title'   => 'A shift still has nobody on it',
                'subject' => $s->event?->title ?: $s->role,
                'detail'  => $s->role ? $s->role . ($s->location ? ' at ' . $s->location : '') : null,
                'due'     => $s->starts_at,
                'where'   => $s->location,
                'sent'    => $s->starts_at,
                'sent_as' => 'Starts',
                'when'    => $s->starts_at ? 'Starts ' . $s->starts_at->humanAgo() : null,
                'cta'     => 'Fill the shift',
                'url'     => route('professional.team.index'),
            ]);
    }

    /** Bids already placed on a request whose deadline is coming. */
    private static function bids(User $me): Collection
    {
        return Bid::where('supplier_id', $me->id)
            ->whereIn('status', ['pending', 'submitted'])
            ->whereHas('event', fn ($q) => $q
                ->whereNotNull('proposal_deadline')
                ->where('proposal_deadline', '>=', now()))
            ->with('event:id,title,proposal_deadline')
            ->get()
            ->map(fn (Bid $b) => [
                'kind'    => 'Bid',
                'tone'    => $b->event?->proposal_deadline?->diffInDays(now()) <= self::SOON ? 'soon' : 'open',
                'group'   => 'urgent',
                'title'   => 'Your bid closes soon',
                'subject' => $b->event?->title,
                'detail'  => 'You can still change it until the client stops taking proposals.',
                'due'     => $b->event?->proposal_deadline,
                'where'   => $b->event?->location,
                'sent'    => $b->event?->proposal_deadline,
                'sent_as' => 'Expires',
                'when'    => $b->event?->proposal_deadline
                    ? 'Closes ' . $b->event->proposal_deadline->humanAgo()
                    : null,
                'cta'     => 'Open my bids',
                'url'     => route('professional.bidding-board.my-bids'),
            ]);
    }

    /** Packages started and never finished. */
    private static function drafts(User $me): Collection
    {
        return Package::where('user_id', $me->id)
            ->where('status', 'draft')
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(function (Package $p) {
                $next = PackageProgress::nextStep($p);

                return [
                    'kind'    => 'Package',
                    'tone'    => 'open',
                    'group'   => 'urgent',
                    'title'   => 'A package is unfinished',
                    'subject' => $p->title,
                    'detail'  => $next ? 'Stopped at ' . $next['label'] . ', needs ' . $next['missing'] : null,
                    'due'     => null,
                    'where'   => null,
                    'sent'    => $p->updated_at,
                    'sent_as' => 'Last edited',
                    'when'    => $p->updated_at ? 'Last edited ' . $p->updated_at->humanAgo() : null,
                    'cta'     => 'Finish it',
                    'url'     => route('professional.packages.edit', $p),
                ];
            });
    }

    /**
     * Two jobs on one day.
     *
     * Only days this professional has already agreed to: a request they have
     * not accepted is not a clash yet, and calling it one would have them
     * turning work down over a booking that does not exist.
     */
    private static function conflicts(User $me): Collection
    {
        $booked = Booking::where('supplier_id', $me->id)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->with('event:id,title,starts_at')
            ->get()
            ->filter(fn (Booking $b) => $b->event?->starts_at)
            ->groupBy(fn (Booking $b) => $b->event->starts_at->toDateString());

        return $booked
            ->filter(fn ($sameDay) => $sameDay->count() > 1)
            ->map(function ($sameDay, $day) {
                $date = Carbon::parse($day);

                return [
                    'kind'    => 'Scheduling',
                    'tone'    => 'urgent',
                    'group'   => 'urgent',
                    'title'   => 'Two jobs on the same day',
                    'subject' => $sameDay->map(fn (Booking $b) => $b->event->title)->implode(' and '),
                    'detail'  => $sameDay->count() . ' bookings on ' . $date->format('j M Y'),
                    'due'     => $date,
                    'where'   => null,
                    'sent'    => $date,
                    'sent_as' => 'Detected',
                    'when'    => $date->isFuture() ? $date->humanAgo() : null,
                    'cta'     => 'Open the calendar',
                    'url'     => route('professional.calendar.index'),
                ];
            })
            ->values();
    }

    /**
     * Money waiting on somebody.
     *
     * A payout this professional has asked for and nobody has actioned, and
     * finished work that has not been asked for yet. Both are real rows, not
     * a reminder invented from a status.
     */
    private static function payments(User $me): Collection
    {
        $requested = \App\Models\Payout::where('user_id', $me->id)
            ->where('status', 'requested')
            ->get()
            ->map(fn ($p) => [
                'kind'    => 'Payment',
                'tone'    => 'open',
                'group'   => 'payments',
                'title'   => 'A payout is waiting to be processed',
                'subject' => '$' . number_format((float) $p->amount, 2),
                'detail'  => 'You asked for this one. It is with us now.',
                'due'     => null,
                'where'   => null,
                'sent'    => $p->requested_at,
                'sent_as' => 'Requested',
                'when'    => $p->requested_at ? 'Requested ' . $p->requested_at->humanAgo() : null,
                'cta'     => 'Open payments',
                'url'     => route('professional.earnings.index'),
            ]);

        /*
         * Money that is yours and has not been asked for. Shown once, as one
         * row, rather than one row per booking: a professional does not
         * request a payout per job.
         */
        $available = \App\Support\Earnings::forProfessional($me)['available'] ?? 0;

        $toClaim = $available > 0
            ? collect([[
                'kind'    => 'Payment',
                'tone'    => 'open',
                'group'   => 'payments',
                'title'   => 'You have earnings you have not asked for',
                'subject' => '$' . number_format((float) $available, 2) . ' available',
                'detail'  => 'A payout happens when you request one.',
                'due'     => null,
                'where'   => null,
                'sent'    => null,
                'sent_as' => null,
                'when'    => null,
                'cta'     => 'Request a payout',
                'url'     => route('professional.transactions.index'),
            ]])
            : collect();

        return $requested->concat($toClaim);
    }

    /**
     * The tabs, each with its own count.
     *
     * @param  Collection<int, array>  $items
     * @return array<string, array{label:string, count:int}>
     */
    public static function tabs(Collection $items): array
    {
        return [
            'all'        => ['label' => 'All',             'count' => $items->count()],
            'urgent'     => ['label' => 'Urgent',          'count' => $items->where('tone', 'urgent')->count()],
            'soon'       => ['label' => 'Due Soon',        'count' => $items->where('tone', 'soon')->count()],
            'clients'    => ['label' => 'Client Activity', 'count' => $items->where('group', 'clients')->count()],
            'payments'   => ['label' => 'Payments',        'count' => $items->where('group', 'payments')->count()],
            // Her tab reads "Agreement", singular.
            'agreements' => ['label' => 'Agreement',       'count' => $items->where('group', 'agreements')->count()],
        ];
    }
}
