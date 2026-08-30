<?php

namespace App\Domain\Budget;

use App\Domain\Taxonomy\ServiceRelevance;
use App\Models\Category;

/**
 * A starting point for splitting a budget across the services a client picked.
 *
 * The Budget Planner tool could not be reused: it allocates across its own
 * fixed headings ("Venue & Rentals", "Catering & Bar") rather than the services
 * on the request, and mapping one onto the other would be guesswork dressed as
 * advice.
 *
 * So the weighting comes from something the taxonomy already asserts. Each
 * service sits under a service category, and the Category Masterlist ranks
 * every category for an occasion as Essential, Common or Occasional. A service
 * the occasion treats as essential gets a larger share than one it treats as
 * occasional.
 *
 * Nothing here invents a price. It divides the client's own number, and it is
 * offered as a suggestion they overwrite.
 */
class ServiceBudgetSuggester
{
    /** How much more an Essential service is weighted over an Occasional one. */
    private const WEIGHTS = [
        'Essential'  => 3.0,
        'Common'     => 2.0,
        'Occasional' => 1.0,
    ];

    /** A service the occasion does not rank at all still needs a share. */
    private const UNRANKED = 1.0;

    /**
     * @param  array<int, int>  $serviceIds  the services the client chose
     * @return array<int, float>             service id => suggested amount
     */
    public function suggest(array $serviceIds, float $total, ?string $archetype): array
    {
        $serviceIds = array_values(array_unique(array_filter(array_map('intval', $serviceIds))));

        // Nothing to divide, or nothing to divide up.
        if (count($serviceIds) < 2 || $total <= 0) {
            return [];
        }

        $tiers   = $archetype ? (ServiceRelevance::tiersByArchetype()[$archetype] ?? []) : [];
        $parents = Category::whereIn('id', $serviceIds)->pluck('parent_id', 'id');

        $weights = [];
        foreach ($serviceIds as $id) {
            $tier = $tiers[(int) ($parents[$id] ?? 0)] ?? null;
            $weights[$id] = self::WEIGHTS[$tier] ?? self::UNRANKED;
        }

        $sum = array_sum($weights);

        if ($sum <= 0) {
            return [];
        }

        // Whole units. A suggestion with pennies in it reads as a calculation
        // rather than a starting point.
        $out = [];
        foreach ($weights as $id => $weight) {
            $out[$id] = floor($total * ($weight / $sum));
        }

        /*
         * Rounding down leaves a remainder. It goes to the largest share rather
         * than leaving the split short of the client's own total — a breakdown
         * that does not add up to the number above it is the first thing they
         * would notice.
         */
        $short = $total - array_sum($out);

        if ($short > 0) {
            $largest = array_keys($out, max($out))[0];
            $out[$largest] += $short;
        }

        return $out;
    }
}
