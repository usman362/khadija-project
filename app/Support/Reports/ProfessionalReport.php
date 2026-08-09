<?php

namespace App\Support\Reports;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Support\Commission;
use App\Support\Earnings;
use App\Support\OpportunityFeed;
use App\Support\ResponseStats;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * A professional's own numbers, over a date range.
 *
 * The second of the three reports Peter asked for on 2026-08-09. Professionals
 * had two CSV exports of their transactions and nothing else — no view of
 * whether their bidding is working, which is the only question a professional
 * on a marketplace actually has.
 *
 * Money comes from App\Support\Earnings and the response figures from
 * App\Support\ResponseStats rather than being recomputed here. A report that
 * disagrees with the Earnings page about the same professional's money is
 * worse than no report, and that exact defect has already been found once on
 * this platform between Earnings and Transactions.
 */
final class ProfessionalReport
{
    public function __construct(
        private User $pro,
        private CarbonInterface $from,
        private CarbonInterface $to,
    ) {
    }

    /**
     * Bidding, which is the professional's actual job on here.
     *
     * A win rate needs a decided denominator: bids still open are neither won
     * nor lost, and counting them as losses would tell someone their bidding
     * is failing when it is merely in progress.
     */
    public function bidding(): array
    {
        $bids = Bid::where('supplier_id', $this->pro->id)
            ->whereBetween('created_at', [$this->from, $this->to]);

        $placed = (clone $bids)->count();

        // Won and lost are read off the booking, not off the bid's own
        // status: the booking is what the money follows.
        $wonEventIds = Booking::where('supplier_id', $this->pro->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->pluck('event_id');

        $won  = (clone $bids)->whereIn('event_id', $wonEventIds)->count();
        $lost = (clone $bids)->where('status', 'not_selected')->count();
        $decided = $won + $lost;

        return [
            'placed'      => $placed,
            'won'         => $won,
            'lost'        => $lost,
            'open'        => max(0, $placed - $decided),
            'win_rate'    => $decided > 0 ? (int) round($won / $decided * 100) : null,
            'average_bid' => $placed > 0 ? round((float) (clone $bids)->avg('amount'), 2) : null,
        ];
    }

    /**
     * Money, from the one place that owns it.
     *
     * `Earnings` answers for the account's whole history rather than a range,
     * which is correct for a balance — you cannot have "available to withdraw,
     * last 30 days". The range-bound figures sit beside it in `overTime()`.
     */
    public function money(): array
    {
        $all = Earnings::forProfessional($this->pro);

        $inRange = Booking::where('supplier_id', $this->pro->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$this->from, $this->to]);

        $gross = (float) (clone $inRange)->sum('price');

        return [
            'earned_in_range'   => Commission::netOf($gross, $this->pro),
            'bookings_in_range' => (clone $inRange)->count(),
            'commission_pct'    => $all['commissionPct'],
            // Balances, not range figures — labelled that way on the page.
            'available'         => $all['available'],
            'pending'           => $all['pending'],
            'lifetime_earned'   => $all['earned'],
        ];
    }

    /** How the client experience of this professional actually looks. */
    public function reputation(): array
    {
        $reviews = Review::visible()->about($this->pro->id);
        $response = ResponseStats::for($this->pro);

        return [
            'rating'         => ($avg = (clone $reviews)->avg('rating')) ? round((float) $avg, 2) : null,
            'reviews'        => (clone $reviews)->count(),
            'response_rate'  => $response['rate'],
            'response_hours' => $response['hours'],
        ];
    }

    /**
     * What is on the table right now.
     *
     * Read through the Opportunity Feed so this and the dashboard cannot
     * disagree about how much work is available to the same professional.
     */
    public function opportunities(): array
    {
        $feed = OpportunityFeed::for($this->pro);

        return [
            'in_your_services' => $feed['listed']->count(),
            'related'          => $feed['related']->count(),
            'has_services'     => $feed['hasServices'],
        ];
    }

    /**
     * Earnings month by month, so a trend is visible rather than asserted.
     *
     * Months with nothing in them are included as zero. Skipping them makes a
     * quiet spring look like continuous work.
     */
    public function overTime(): Collection
    {
        // Grouped in PHP rather than in SQL: DATE_FORMAT is MySQL's and
        // strftime is SQLite's, and a query using either passes in production
        // while failing every test — which is exactly how CURDATE() sat
        // unnoticed on /browse until R38's tests reached it.
        $completed = Booking::where('supplier_id', $this->pro->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$this->from, $this->to])
            ->get(['price', 'created_at'])
            ->groupBy(fn (Booking $b) => $b->created_at->format('Y-m'));

        $rows   = $completed->map(fn ($group) => (float) $group->sum('price'));
        $counts = $completed->map(fn ($group) => $group->count());

        $months = collect();
        $cursor = $this->from->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($this->to)) {
            $key = $cursor->format('Y-m');
            $months->push([
                'month'    => $cursor->format('M Y'),
                'earned'   => Commission::netOf((float) ($rows[$key] ?? 0), $this->pro),
                'bookings' => (int) ($counts[$key] ?? 0),
            ]);
            $cursor->addMonth();
        }

        return $months;
    }

    public function all(): array
    {
        return [
            'from'          => $this->from,
            'to'            => $this->to,
            'bidding'       => $this->bidding(),
            'money'         => $this->money(),
            'reputation'    => $this->reputation(),
            'opportunities' => $this->opportunities(),
            'over_time'     => $this->overTime(),
        ];
    }
}
