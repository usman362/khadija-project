<?php

namespace App\Domain\Reviews;

use App\Models\Review;
use Illuminate\Support\Collection;

/**
 * The two right-rail panels on the client's Reviews page, derived from the
 * client's own reviews instead of invented.
 *
 * Checklist row 100. Both panels used to be hardcoded. "Trust & Verification"
 * multiplied the review count by fixed fractions — 0.67, 0.33, 0.83 — and
 * printed the fraction as a percentage beside it, so a client with no reviews
 * read "Secure Payment 0 (67%)". Nought of anything is nought per cent.
 * "Review Highlights" was worse: four confident lines of derived-looking
 * insight ("Most Mentioned: Communication, Punctuality") on a page whose own
 * total said zero reviews. Fabricated content presented as a finding is worse
 * than a blank panel, because a blank panel does not mislead.
 *
 * Both are now computed, and both return an empty result when there is
 * nothing to compute from. The page renders an empty state in that case.
 */
class ReviewInsights
{
    /**
     * Verification checks across the DISTINCT professionals this client has
     * reviewed — the denominator is people, not reviews, because a client who
     * reviewed one professional twice has verified one professional.
     *
     * @return array{denominator:int, checks:array<int, array{label:string, count:int, pct:int}>}
     */
    public static function trust(int $clientId): array
    {
        $reviewees = Review::where('reviewer_id', $clientId)
            ->with(['reviewee:id,email_verified_at', 'reviewee.profile'])
            ->get()
            ->pluck('reviewee')
            ->filter()
            ->unique('id');

        $total = $reviewees->count();

        if ($total === 0) {
            return ['denominator' => 0, 'checks' => []];
        }

        $counts = [
            'Email verified'      => $reviewees->filter(fn ($u) => $u->email_verified_at !== null)->count(),
            'Address verified'    => $reviewees->filter(fn ($u) => $u->profile?->address_verified_at !== null)->count(),
            'Trade licence'       => $reviewees->filter(fn ($u) => $u->profile?->trade_license_verified_at !== null)->count(),
            'Liability insurance' => $reviewees->filter(fn ($u) => $u->profile?->liability_insurance_verified_at !== null)->count(),
        ];

        $checks = [];
        foreach ($counts as $label => $count) {
            $checks[] = [
                'label' => $label,
                'count' => $count,
                // Derived from the count it sits beside, so the two can never
                // disagree — which is the whole of this row's complaint.
                'pct'   => (int) round(($count / $total) * 100),
            ];
        }

        return ['denominator' => $total, 'checks' => $checks];
    }

    /**
     * What this client's own review comments actually mention.
     *
     * Keyword counting, not sentiment analysis — an honest, checkable rule a
     * reader can reproduce by eye. A theme has to be mentioned at least once
     * to be listed, and nothing is listed at all when no comment mentions
     * anything.
     *
     * @return array{themes:array<int, array{theme:string, mentions:int}>, strength:?string, watch:?string}
     */
    public static function highlights(int $clientId): array
    {
        $comments = Review::where('reviewer_id', $clientId)
            ->whereNotNull('comment')
            ->get(['rating', 'comment']);

        if ($comments->isEmpty()) {
            return ['themes' => [], 'strength' => null, 'watch' => null];
        }

        $mentions = [];
        foreach (self::THEMES as $theme => $words) {
            $count = $comments->filter(fn ($r) => self::mentions($r->comment, $words))->count();
            if ($count > 0) {
                $mentions[$theme] = $count;
            }
        }

        arsort($mentions);

        $themes = [];
        foreach (array_slice($mentions, 0, 3, true) as $theme => $count) {
            $themes[] = ['theme' => $theme, 'mentions' => $count];
        }

        return [
            'themes'   => $themes,
            // The theme named in the client's four-and-five-star reviews, and
            // the one named in their one-and-two-star reviews. Either can be
            // absent, and is left out when it is.
            'strength' => self::topThemeIn($comments->filter(fn ($r) => $r->rating >= 4)),
            'watch'    => self::topThemeIn($comments->filter(fn ($r) => $r->rating <= 2)),
        ];
    }

    /** The vocabulary each theme is counted from. */
    private const THEMES = [
        'Communication'  => ['communicat', 'responsive', 'replied', 'kept me posted', 'in touch'],
        'Punctuality'    => ['punctual', 'on time', 'early', 'prompt', 'late'],
        'Professionalism'=> ['professional', 'courteous', 'polite', 'respectful'],
        'Quality'        => ['quality', 'excellent', 'beautiful', 'delicious', 'superb'],
        'Value'          => ['value', 'price', 'budget', 'worth', 'affordable', 'expensive'],
        'Preparation'    => ['prepared', 'organis', 'organiz', 'setup', 'set up', 'planning'],
    ];

    private static function mentions(?string $comment, array $words): bool
    {
        $haystack = mb_strtolower((string) $comment);

        foreach ($words as $word) {
            if ($haystack !== '' && str_contains($haystack, $word)) {
                return true;
            }
        }

        return false;
    }

    private static function topThemeIn(Collection $reviews): ?string
    {
        if ($reviews->isEmpty()) {
            return null;
        }

        $best = null;
        $bestCount = 0;

        foreach (self::THEMES as $theme => $words) {
            $count = $reviews->filter(fn ($r) => self::mentions($r->comment, $words))->count();
            if ($count > $bestCount) {
                $best = $theme;
                $bestCount = $count;
            }
        }

        return $best;
    }
}
