<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The one Fit Score — category 40 · proximity 20 · availability 20 · rating 20.
 *
 * Lifted out of the bidding-board controller when the Opportunity Feed needed
 * the same number. Two copies would be two chances to disagree, and a gig
 * showing 90% on the board and 70% in the feed is the kind of thing that makes
 * a professional stop believing the figure anywhere — the same defect already
 * found between Earnings and Transactions.
 *
 * Rule R61 amended two of the four components on 2026-08-07, both because R38
 * made the old "in-area" test a constant on every visible row:
 *
 *   Category 40 is GRADED — 40 for a service the professional lists, 20 for a
 *   different service under the same category, 0 otherwise. That grading is
 *   also what the feed's "related" block is built on: relatedness is
 *   structural, read off the R45 taxonomy, never a threshold on this score.
 *
 *   In-area 20 became PROXIMITY. City is the finest granularity both sides
 *   record — profiles have a city, events have a location string, neither has
 *   coordinates — so this is city-level, not drive time.
 *
 * Membership tier is not an input and never has been (Q7).
 */
final class FitScore
{
    public const EXACT   = 40;   // a service the professional lists
    public const RELATED = 20;   // same category, a different service
    public const NONE    = 0;

    /** The whole score, 0–100. */
    public static function for(Event $event, ?User $pro): int
    {
        if ($pro === null) {
            return 0;
        }

        return min(100,
            self::categoryPoints($event, $pro)
            + self::proximityPoints($event, $pro)
            + self::availabilityPoints($event, $pro)
            + self::ratingPoints($pro)
        );
    }

    /**
     * How close this request is to what the professional actually does.
     *
     * Their services come from the category_user pivot — what they listed.
     * Reading them off published packages instead, as this once did, meant a
     * professional with no packages scored zero here whatever their trade.
     */
    public static function categoryPoints(Event $event, User $pro): int
    {
        $mine = self::servicesOf($pro);

        if ($mine->isEmpty()) {
            return self::NONE;
        }

        $wanted = $event->categories->pluck('id');

        if ($wanted->intersect($mine)->isNotEmpty()) {
            return self::EXACT;
        }

        return self::parentsOf($wanted)->intersect(self::parentsOf($mine))->isNotEmpty()
            ? self::RELATED
            : self::NONE;
    }

    /** An unlocatable request sits mid — absent information is not a bad answer. */
    public static function proximityPoints(Event $event, User $pro): int
    {
        $city = $pro->profile?->city;

        if (! $event->location || ! $city) {
            return 10;
        }

        return Str::contains(Str::lower($event->location), Str::lower($city)) ? 20 : 8;
    }

    /** Nothing else already booked on that date. */
    public static function availabilityPoints(Event $event, User $pro): int
    {
        if (! $event->starts_at) {
            return 20;   // an undated request cannot clash
        }

        $clash = Booking::where('supplier_id', $pro->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereHas('event', fn ($q) => $q->whereDate('starts_at', $event->starts_at->toDateString()))
            ->exists();

        return $clash ? 0 : 20;
    }

    /**
     * Scaled from the professional's average review; unrated sits mid.
     *
     * The cold-start half of the problem R61's feed decision names: a brand
     * new professional has no reviews, so scoring them zero here would hit
     * them twice — on how many gigs they match and on where those rank.
     */
    public static function ratingPoints(User $pro): int
    {
        $avg = (float) $pro->reviewsReceived()->where('is_hidden', false)->avg('rating');

        return $avg > 0 ? (int) round(($avg / 5) * 20) : 10;
    }

    /** The service categories this professional listed. */
    public static function servicesOf(User $pro): Collection
    {
        return $pro->serviceCategories()->pluck('categories.id');
    }

    /** The Sub categories a set of Sub-Sub services sits under. */
    public static function parentsOf(Collection $categoryIds): Collection
    {
        return Category::whereIn('id', $categoryIds)
            ->pluck('parent_id')->filter()->unique()->values();
    }
}
