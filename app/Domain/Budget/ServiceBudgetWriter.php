<?php

namespace App\Domain\Budget;

use App\Models\Category;
use App\Models\Event;
use Illuminate\Support\Collection;

/**
 * Save a client's per-service budget split, wherever the request came from.
 *
 * Sir Peter, 2026-09-02: a request naming several services has to say what
 * each one is worth. The Bidding Request wizard learned that on 2026-08-30;
 * Direct Request and Emergency Request did not, so the same client asking for
 * the same five services saw the breakdown on one screen and a single figure
 * on the other two.
 *
 * The rule underneath it is the same in all three, and it is the reason the
 * feature exists rather than a nicety: bids are per service. A professional
 * bids on ONE of the services. Given a $10,000 total across five, five
 * different professionals were each shown $10,000 and priced against a number
 * that was never meant for them.
 *
 * This lives here rather than in the three controllers because three copies of
 * a money rule drift, and the one that drifts is discovered by a professional
 * quoting against the wrong figure.
 */
class ServiceBudgetWriter
{
    /**
     * Replace an event's split with the figures given.
     *
     * @param  array<int|string, mixed>  $split      category id => amount
     * @param  array<int, int>           $services   the services actually requested
     */
    public static function save(Event $event, array $split, array $services): void
    {
        $services = array_values(array_filter(array_map('intval', $services)));

        $event->serviceBudgets()->delete();

        // Nothing to divide on a single-service request, and nothing given.
        if (count($services) < 2 || $split === []) {
            return;
        }

        foreach ($split as $categoryId => $amount) {
            $categoryId = (int) $categoryId;

            // A figure may only attach to a service actually being requested.
            // Without this, a stale field left in a posted form could put money
            // against a service the client removed two steps earlier.
            if (! in_array($categoryId, $services, true)) {
                continue;
            }

            if ($amount === null || $amount === '' || ! is_numeric($amount)) {
                continue;
            }

            $event->serviceBudgets()->create([
                'category_id' => $categoryId,
                'amount' => (float) $amount,
            ]);
        }
    }

    /**
     * The services to show a breakdown for, or an empty collection when there
     * is no breakdown to make.
     *
     * One service has one budget. Offering to divide it is a field that cannot
     * do anything, which is worse than not offering.
     *
     * @param  array<int, mixed>  $serviceIds
     * @return Collection<int, Category>
     */
    public static function splittableServices(array $serviceIds): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $serviceIds))));

        if (count($ids) < 2) {
            return collect();
        }

        return Category::whereIn('id', $ids)->orderBy('name')->get(['id', 'name']);
    }
}
