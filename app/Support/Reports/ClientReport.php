<?php

namespace App\Support\Reports;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Support\ClientStats;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * A client's own numbers — the last of the three reports Peter asked for on
 * 2026-08-09. Clients had none at all.
 *
 * A client's question is not the professional's. They are not competing for
 * work; they are spending money and want to know where it went, who they keep
 * hiring, and whether posting a request actually produces anyone.
 *
 * The figures shared with the public Client Portfolio come from
 * App\Support\ClientStats, so the report, the portfolio and the dashboard
 * cannot disagree about the same account — the requirement R53's spec states
 * outright and the reason ClientStats exists.
 */
final class ClientReport
{
    public function __construct(
        private User $client,
        private CarbonInterface $from,
        private CarbonInterface $to,
    ) {
    }

    /** Where the money went. */
    public function spend(): array
    {
        $completed = Booking::where('client_id', $this->client->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$this->from, $this->to]);

        $spent = (float) (clone $completed)->sum('price');
        $count = (clone $completed)->count();

        $committed = (float) Booking::where('client_id', $this->client->id)
            ->whereIn('status', ['requested', 'confirmed'])
            ->sum('price');

        return [
            'spent'     => $spent,
            'bookings'  => $count,
            // The client pays the professional's price; commission is taken
            // from the professional at payout and is not the client's cost.
            // Showing it here would misstate what an event cost them.
            'average'   => $count > 0 ? round($spent / $count, 2) : null,
            'committed' => $committed,
        ];
    }

    /** Did posting a request actually produce anyone? */
    public function requests(): array
    {
        $posted = Event::where('client_id', $this->client->id)
            ->whereBetween('created_at', [$this->from, $this->to]);

        $postedCount = (clone $posted)->count();
        $withBid     = (clone $posted)->whereHas('bids')->count();
        $hired       = (clone $posted)->whereNotNull('supplier_id')->count();

        $bids = Bid::whereIn('event_id', (clone $posted)->pluck('id'))->count();

        return [
            'posted'       => $postedCount,
            'got_a_bid'    => $postedCount ? (int) round($withBid / $postedCount * 100) : null,
            'hired'        => $hired,
            'bids_received' => $bids,
            'bids_per_request' => $withBid ? round($bids / $withBid, 1) : null,
        ];
    }

    /**
     * How the client looks to professionals, from the same source the public
     * Client Portfolio reads — so a client cannot be shown one thing here and
     * professionals another about them.
     */
    public function standing(): array
    {
        $stats = ClientStats::for($this->client);

        return [
            'rating'            => $stats['rating'],
            'reviews'           => $stats['reviews_count'],
            'cancellation_rate' => $stats['cancellation_rate'],
            'response_rate'     => $stats['response_rate'],
            'response_hours'    => $stats['response_hours'],
            'repeat_pros'       => $stats['repeat_professionals'],
        ];
    }

    /** Which professionals this client keeps going back to. */
    public function professionals(): Collection
    {
        return Booking::where('client_id', $this->client->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereNotNull('supplier_id')
            ->with('supplier:id,name')
            ->get(['supplier_id', 'price', 'status'])
            ->groupBy('supplier_id')
            ->map(fn ($rows) => [
                'name'     => $rows->first()->supplier?->name ?? 'A professional',
                'bookings' => $rows->count(),
                'spent'    => (float) $rows->where('status', 'completed')->sum('price'),
            ])
            ->sortByDesc('bookings')
            ->values()
            ->take(8);
    }

    /**
     * What kind of events this client runs.
     *
     * The same completed-booking source as ClientStats::eventTypeCounts, so
     * the tiles here and on the portfolio agree.
     */
    public function eventTypes(): Collection
    {
        return ClientStats::eventTypeCounts($this->client)->take(8);
    }

    public function all(): array
    {
        return [
            'from'          => $this->from,
            'to'            => $this->to,
            'spend'         => $this->spend(),
            'requests'      => $this->requests(),
            'standing'      => $this->standing(),
            'professionals' => $this->professionals(),
            'event_types'   => $this->eventTypes(),
        ];
    }
}
