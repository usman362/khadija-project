<?php

namespace App\Domain\Requests;

use App\Models\Bid;
use Illuminate\Support\Collection;

/**
 * What professionals have actually charged for a service.
 *
 * Sir Peter, 26 September: "can get a rough estimator for the the clients so
 * they are not over guessing or something so its more in tune with the prices
 * lists of the current professionals in our databases, so i guess our
 * professionals/influencers will need to provide a price list(range) for each
 * services that we offer."
 *
 * No price list exists, and professionals have never been asked for one. But
 * the platform does hold what they have bid, which is better evidence than a
 * list anyway: a price list is what somebody hopes to charge, a bid is what
 * they offered for a real job.
 *
 * So the guide is built from bids, and it is careful in three ways.
 *
 * It will not speak from too little. Two bids are an anecdote; quoting a
 * "typical" price from them would be inventing a market out of two people.
 * Below the minimum it returns nothing and the screen says nothing.
 *
 * It prefers the client's own state, because what a caterer charges in
 * Maryland is not what one charges in New York, and falls back to everywhere
 * only when the state has too little to speak from. Which of the two it used
 * is reported, so the screen can say so rather than implying local knowledge
 * it does not have.
 *
 * And the middle figure is the median, not the mean. One $9,000 bid among
 * nines of $400 drags an average somewhere no client will ever pay.
 */
final class BudgetGuide
{
    /** Fewer than this and there is nothing honest to say. */
    public const MINIMUM = 3;

    /**
     * @return array{low:float, high:float, typical:float, count:int, scope:string}|null
     */
    public static function forService(int $categoryId, ?string $state = null): ?array
    {
        if ($state) {
            $local = self::amounts($categoryId, $state);

            if ($local->count() >= self::MINIMUM) {
                return self::summarise($local, 'state');
            }
        }

        $all = self::amounts($categoryId, null);

        return $all->count() >= self::MINIMUM ? self::summarise($all, 'everywhere') : null;
    }

    /**
     * The same, for several services at once, so a form with five of them
     * does not make five round trips.
     *
     * @param  array<int>  $categoryIds
     * @return array<int, array{low:float, high:float, typical:float, count:int, scope:string}>
     */
    public static function forServices(array $categoryIds, ?string $state = null): array
    {
        $out = [];

        foreach (array_unique($categoryIds) as $id) {
            if ($guide = self::forService((int) $id, $state)) {
                $out[(int) $id] = $guide;
            }
        }

        return $out;
    }

    /** The bids themselves: real offers on real requests, cancelled ones left out. */
    private static function amounts(int $categoryId, ?string $state): Collection
    {
        return Bid::query()
            ->where('category_id', $categoryId)
            ->where('amount', '>', 0)
            ->whereNotIn('status', ['withdrawn', 'cancelled'])
            ->when($state, fn ($q) => $q->whereHas('event', fn ($e) => $e->where('state', $state)))
            ->pluck('amount')
            ->map(fn ($a) => (float) $a)
            ->sort()
            ->values();
    }

    private static function summarise(Collection $sorted, string $scope): array
    {
        $n = $sorted->count();

        return [
            'low'     => (float) $sorted->first(),
            'high'    => (float) $sorted->last(),
            // The median. An average is moved by one unusual job; this is not.
            'typical' => $n % 2
                ? (float) $sorted[intdiv($n, 2)]
                : (float) (($sorted[$n / 2 - 1] + $sorted[$n / 2]) / 2),
            'count'   => $n,
            'scope'   => $scope,
        ];
    }

    /**
     * How many bids there are for a service, whatever the answer.
     *
     * forService() returns nothing below the minimum, which is right: it must
     * not quote a market it cannot see. But a screen that then says nothing at
     * all is indistinguishable from a broken one, and that is how this looked
     * when it first went out. So the page can ask how little there is and say
     * so.
     */
    public static function bidCount(int $categoryId, ?string $state = null): int
    {
        $local = $state ? self::amounts($categoryId, $state)->count() : 0;

        return max($local, self::amounts($categoryId, null)->count());
    }

    /** Why there is no figure, in the same voice as the figure itself. */
    public static function silence(int $categoryId, ?string $state = null): string
    {
        $n = self::bidCount($categoryId, $state);

        if ($n === 0) {
            return 'No professional has bid on this service yet, so there is nothing to compare your budget with.';
        }

        return 'Only ' . $n . ' ' . \Illuminate\Support\Str::plural('professional', $n)
            . ' ' . ($n === 1 ? 'has' : 'have') . ' bid on this service so far. '
            . 'GigResource waits for ' . self::MINIMUM . ' before quoting a range, '
            . 'because fewer than that is not a guide, it is an anecdote.';
    }

    /** How to say it, in one sentence, without overstating what it is. */
    public static function sentence(array $guide): string
    {
        $where = $guide['scope'] === 'state' ? 'in your state' : 'on GigResource';

        return 'Professionals ' . $where . ' have bid $'
            . number_format($guide['low']) . ' to $' . number_format($guide['high'])
            . ' for this, usually around $' . number_format($guide['typical'])
            . ', across ' . $guide['count'] . ' ' . \Illuminate\Support\Str::plural('bid', $guide['count']) . '.';
    }
}
