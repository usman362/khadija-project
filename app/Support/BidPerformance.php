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
    // Public: the Bid Intelligence controller counts the same two sets when
    // it compares this period with the one before, and a second copy of the
    // lists is how the two would come to disagree.
    public const WON  = ['won', 'accepted'];
    public const LOST = ['lost', 'declined', 'rejected', 'expired'];

    /** Every bid this professional has placed, with its event. */
    public static function bids(User $me): Collection
    {
        return Bid::where('supplier_id', $me->id)
            // published_at is read by timing(): a column left out of the
            // select comes back null, and the page then reports that nothing
            // can be timed when everything can.
            ->with(['event:id,title,event_type,state,city,starts_at,published_at,budget,budget_max,status',
                    'category:id,name'])
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
                // Her table names the accepted count beside the rate.
                'won'     => $record['won'],
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
        $lost     = $bids->whereIn('status', self::LOST);
        $declined = $bids->whereIn('status', ['lost', 'declined', 'rejected']);
        $expired  = $bids->where('status', 'expired');
        $open     = $bids->reject(fn (Bid $b) => in_array($b->status, [...self::WON, ...self::LOST], true));

        return [
            'count'  => $lost->count(),
            'amount' => (float) $lost->sum('amount'),
            // Her panel splits the closed ones into the two ways a bid is
            // lost, and counts the open ones apart from both.
            'declined' => ['count' => $declined->count(), 'amount' => (float) $declined->sum('amount')],
            'expired'  => ['count' => $expired->count(),  'amount' => (float) $expired->sum('amount')],
            'open'     => ['count' => $open->count(),     'amount' => (float) $open->sum('amount')],
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

    /**
     * The funnel, as far as this platform can honestly draw it.
     *
     * Khadijah's screen has four stages: invited, submitted, client viewed,
     * accepted. Three of them are recorded. Nothing anywhere records a client
     * opening a proposal, so that stage is missing rather than filled with a
     * number: a funnel with an invented step in the middle makes every
     * percentage after it wrong.
     *
     * @param  Collection<int, Bid>  $bids
     * @return array<int, array{label:string, count:int, pct:int, note:?string}>
     */
    public static function funnel(User $me, Collection $bids): array
    {
        // An invitation is a request that came to this professional by name.
        $invited = \App\Models\Event::where('source', 'direct_offer')
            ->where('supplier_id', $me->id)->count();

        // Plus the open requests they actually answered, which is the other
        // way a bid starts.
        $invited = max($invited, $bids->count());

        $submitted = $bids->count();
        $accepted  = $bids->whereIn('status', self::WON)->count();

        $pct = fn (int $n) => $invited > 0 ? (int) round($n / $invited * 100) : 0;

        return [
            ['label' => 'Invited',   'count' => $invited,   'pct' => $invited > 0 ? 100 : 0, 'note' => null],
            ['label' => 'Submitted', 'count' => $submitted, 'pct' => $pct($submitted),       'note' => null],
            ['label' => 'Accepted',  'count' => $accepted,  'pct' => $pct($accepted),        'note' => null],
        ];
    }

    /**
     * How quickly they answered, and whether it made any difference.
     *
     * The gap between a request being published and their bid going in. Bands
     * with nothing in them are dropped: a bar at zero per cent reads as
     * "answering in this time never works", which is not what no data means.
     *
     * @param  Collection<int, Bid>  $bids
     * @return array<int, array{label:string, placed:int, won:int, rate:?int}>
     */
    public static function timing(Collection $bids): array
    {
        $bands = [
            ['Within 24 hrs', 0, 1],
            ['1 to 2 days',   1, 2],
            ['3 to 5 days',   2, 5],
            ['6+ days',       5, PHP_INT_MAX],
        ];

        $timed = $bids->filter(fn (Bid $b) => $b->event?->published_at && ($b->submitted_at ?? $b->created_at));

        $out = [];

        foreach ($bands as [$label, $from, $to]) {
            $inBand = $timed->filter(function (Bid $b) use ($from, $to) {
                $days = $b->event->published_at->floatDiffInDays($b->submitted_at ?? $b->created_at);

                return $days >= $from && $days < $to;
            });

            if ($inBand->isEmpty()) {
                continue;
            }

            $record = self::record($inBand);

            $out[] = [
                'label'  => $label,
                'placed' => $record['placed'],
                'won'    => $record['won'],
                'rate'   => $record['rate'],
            ];
        }

        return $out;
    }

    /**
     * The last twelve months, month by month.
     *
     * Only months they actually bid in carry a rate; a month they sat out is
     * not a month they lost.
     *
     * @param  Collection<int, Bid>  $bids
     * @return array<int, array{label:string, placed:int, won:int, rate:?int}>
     */
    public static function trend(Collection $bids): array
    {
        $out = [];

        for ($back = 11; $back >= 0; $back--) {
            $month = now()->startOfMonth()->subMonths($back);

            $inMonth = $bids->filter(fn (Bid $b) => ($b->submitted_at ?? $b->created_at)
                && ($b->submitted_at ?? $b->created_at)->isSameMonth($month));

            $record = self::record($inMonth);

            $out[] = [
                'label'  => $month->format('M'),
                'placed' => $record['placed'],
                'won'    => $record['won'],
                'rate'   => $record['rate'],
            ];
        }

        return $out;
    }

    /**
     * What their accepted bids had on them that the rest did not.
     *
     * Each line is a field on the bid itself, counted across the ones that
     * were accepted. Not advice: a tally of what this professional actually
     * sent when a client said yes.
     *
     * @param  Collection<int, Bid>  $bids
     * @return array{rows: array<int, array{label:string, pct:int}>, sample: int}
     */
    public static function quality(Collection $bids): array
    {
        $won = $bids->whereIn('status', self::WON);
        $n   = $won->count();

        $share = fn (callable $has) => $n > 0
            ? (int) round($won->filter($has)->count() / $n * 100)
            : 0;

        return [
            'sample' => $n,
            'rows'   => [
                ['label' => 'A priced breakdown', 'pct' => $share(fn (Bid $b) => filled($b->breakdown))],
                ['label' => 'A plan for the day', 'pct' => $share(fn (Bid $b) => filled($b->plan))],
                ['label' => 'Your terms',         'pct' => $share(fn (Bid $b) => filled($b->terms))],
                ['label' => 'A note to the client', 'pct' => $share(fn (Bid $b) => filled($b->note))],
                ['label' => 'A date you confirmed', 'pct' => $share(fn (Bid $b) => (bool) $b->available_confirmed)],
            ],
        ];
    }
}