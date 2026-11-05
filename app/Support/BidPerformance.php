<?php

namespace App\Support;

use App\Models\Bid;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * How one professional's bidding has actually gone.
 *
 * Khadijah's 28 October Bid Intelligence screen carries an AI win rate
 * predictor, a market benchmark and a smart recommendation. None of those are
 * here, and the reason is the same each time: the platform has no model, no
 * competitor data and no record of why a client chose somebody else, so each
 * would be a number invented on the screen a professional uses to decide what
 * to charge.
 *
 * What is here is their own history, grouped the ways the screen groups it.
 * Every figure is a count of their own bids and what became of each one, so
 * any of it can be checked by hand.
 *
 * A bid is WON when it was accepted, LOST when the request closed without it,
 * and OPEN while the client is still deciding. An open bid is in neither
 * total: counting it as a loss would make every professional's rate fall for
 * a week and then recover, which says nothing about their bidding.
 */
final class BidPerformance
{
    private const WON  = ['won', 'accepted'];
    private const LOST = ['lost', 'declined', 'rejected', 'expired'];

    /** Every bid this professional has placed, with its event. */
    public static function bids(User $me): Collection
    {
        return Bid::where('supplier_id', $me->id)
            ->with(['event:id,title,event_type,state,city,starts_at,budget,budget_max,status', 'category:id,name'])
            ->get();
    }

    /**
     * Won, lost, open and the rate between the first two.
     *
     * @param  Collection<int, Bid>  $bids
     */
    public static function record(Collection $bids): array
    {
        $won  = $bids->whereIn('status', self::WON)->count();
        $lost = $bids->whereIn('status', self::LOST)->count();
        $open = $bids->count() - $won - $lost;

        return [
            'placed' => $bids->count(),
            'won'    => $won,
            'lost'   => $lost,
            'open'   => max(0, $open),
            // Null, not zero. Nobody who has never had a bid decided has a
            // win rate of nought per cent.
            'rate'   => ($won + $lost) > 0 ? (int) round($won / ($won + $lost) * 100) : null,
        ];
    }

    /**
     * The same record, split by something about the request.
     *
     * Groups with nothing decided yet are dropped rather than shown at zero,
     * for the reason above.
     *
     * @param  Collection<int, Bid>  $bids
     * @param  callable(Bid): ?string  $by
     * @return array<int, array{label:string, placed:int, won:int, lost:int, rate:int}>
     */
    public static function splitBy(Collection $bids, callable $by, int $limit = 6): array
    {
        return $bids
            ->groupBy(fn (Bid $b) => $by($b) ?: '')
            ->filter(fn ($group, $label) => $label !== '')
            ->map(function (Collection $group, $label) {
                $record = self::record($group);

                return [
                    'label'  => (string) $label,
                    'placed' => $record['placed'],
                    'won'    => $record['won'],
                    'lost'   => $record['lost'],
                    'rate'   => $record['rate'],
                ];
            })
            ->filter(fn (array $row) => $row['rate'] !== null)
            ->sortByDesc('rate')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * What they bid, in bands, and how each band has done.
     *
     * The bands are fixed rather than fitted to the data, so the same bid
     * lands in the same band whatever else they have bid on this month.
     *
     * @param  Collection<int, Bid>  $bids
     */
    public static function byPrice(Collection $bids): array
    {
        $bands = [
            ['Under $1,000',    0,     1000],
            ['$1,000 to $2,500', 1000,  2500],
            ['$2,500 to $5,000', 2500,  5000],
            ['Over $5,000',     5000,  PHP_INT_MAX],
        ];

        $out = [];

        foreach ($bands as [$label, $from, $to]) {
            $inBand = $bids->filter(fn (Bid $b) => $b->amount >= $from && $b->amount < $to);

            if ($inBand->isEmpty()) {
                continue;
            }

            $record = self::record($inBand);

            $out[] = [
                'label'   => $label,
                'placed'  => $record['placed'],
                'rate'    => $record['rate'],
                'average' => (int) round($inBand->avg('amount')),
            ];
        }

        return $out;
    }

    /**
     * What the work they did not win was worth.
     *
     * Their own bid amount, not the client's budget: the budget is what
     * somebody else was willing to spend, and this is money this professional
     * put a figure on and did not get.
     *
     * @param  Collection<int, Bid>  $bids
     */
    public static function lost(Collection $bids): array
    {
        $lost = $bids->whereIn('status', self::LOST);

        return [
            'count'  => $lost->count(),
            'amount' => (float) $lost->sum('amount'),
        ];
    }

    /**
     * Which months of the year their work actually falls in.
     *
     * Read from the events they bid on, so it describes the demand they see
     * rather than a season somebody assumed. Needs a full year of their own
     * history to mean anything, and says so when it has less.
     *
     * @param  Collection<int, Bid>  $bids
     * @return array{months: array<int, array{label:string, count:int}>, enough: bool}
     */
    public static function season(Collection $bids): array
    {
        $dated = $bids->filter(fn (Bid $b) => $b->event?->starts_at);

        $counts = $dated
            ->groupBy(fn (Bid $b) => $b->event->starts_at->format('n'))
            ->map(fn (Collection $g) => $g->count());

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = [
                'label' => \Illuminate\Support\Carbon::create(null, $m, 1)->format('M'),
                'count' => (int) ($counts->get((string) $m) ?? 0),
            ];
        }

        return [
            'months' => $months,
            // One or two requests do not make a season.
            'enough' => $dated->count() >= 6,
        ];
    }
}
