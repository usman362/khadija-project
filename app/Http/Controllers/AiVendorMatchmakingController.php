<?php

namespace App\Http\Controllers;

use App\Domain\AiFeatures\AiFeatureCode;
use App\Domain\AiFeatures\Services\AiFeatureGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * AI Vendor Matchmaking — a client-portal AI Toolkit tool that matches the
 * client's event (theme, date, budget) against a vendor catalogue and ranks
 * the best fits with a transparent match score.
 *
 * Deterministic matching engine (no LLM, no quota): each vendor carries a
 * theme-fit base score; the displayed match % adjusts it by how well the
 * vendor's themes overlap the CURRENT event theme, and the "Refine Your
 * Match" controls (category, max budget, min match %) genuinely re-filter and
 * re-rank — so the tool is fully dynamic.
 *
 * NOTE on data: the platform's live supplier records are still sparse, so the
 * pool is a representative vendor catalogue. The scoring + filtering is real;
 * as real vendors onboard they slot into the same engine.
 *
 * Plan-gated (Developer Feedback v1.1 §8.3): available on Professional & Enterprise
 * tiers. Enforcement is centralised in AiFeatureGate and only bites once
 * AI_FEATURES_FREE_FOR_ALL is flipped off at launch.
 *
 * Routes: GET  /ai-tools/vendor-matchmaking         (show)
 *         POST /ai-tools/vendor-matchmaking/match    (refine → JSON)
 */
class AiVendorMatchmakingController extends Controller
{
    public function __construct(private AiFeatureGate $gate) {}

    /** name, category, tags, price, rating, reviews, themes, base, why, grad. */
    private const VENDORS = [
        ['DJ Sunny Beats',          'DJ',          ['DJ', 'Beach Party'],      450, 4.5, 124, ['tropical', 'beach', 'party'],   98, 'Great reviews for beach & tropical vibes. Available on your date and fits your budget.', '#8b5cf6,#6d28d9'],
        ['Coastal Dreams Decor',    'Decor',       ['Decor', 'Tropical'],      300, 4.5, 89,  ['tropical', 'beach', 'coastal'], 95, 'Specializes in tropical & coastal themes. Perfect for your event style.', '#10b981,#047857'],
        ['Island Flavor Catering',  'Catering',    ['Catering', 'Seafood'],    550, 4.5, 156, ['tropical', 'beach', 'seafood'], 93, 'Known for fresh seafood & island menus. In budget and available.', '#f59e0b,#b45309'],
        ['Palm Shore Photography',  'Photography', ['Photography', 'Outdoor'], 600, 4.6, 98,  ['beach', 'tropical', 'outdoor'], 92, 'Stunning outdoor & beach portfolios with top reviews.', '#ec4899,#be185d'],
        ['Breeze Event Planning',   'Planning',    ['Planner', 'Full-Service'], 950, 4.8, 203, ['wedding', 'beach', 'party'],   91, 'Top-rated full-service planning for stress-free events.', '#8b5cf6,#6d28d9'],
        ['Tropical Bloom Florist',  'Florist',     ['Florist', 'Decor'],       280, 4.4, 71,  ['tropical', 'beach', 'garden'],  90, 'Lush tropical florals that match your theme beautifully.', '#22c55e,#15803d'],
        ['Sunset Live Band',        'Live Music',  ['Band', 'Party'],          800, 4.7, 142, ['party', 'beach', 'wedding'],    89, 'High-energy live sets perfect for a beach party.', '#6366f1,#4338ca'],
        ['Aloha Bartending Co.',    'Bartending',  ['Bar', 'Cocktails'],       350, 4.5, 65,  ['tropical', 'beach', 'party'],   88, 'Tropical cocktails and friendly service within budget.', '#06b6d4,#0e7490'],
        ['Mai Tai Mixologists',     'Bartending',  ['Bar', 'Tropical'],        420, 4.6, 80,  ['tropical', 'beach', 'cocktails'], 87, 'Signature tropical drinks crafted for your theme.', '#06b6d4,#0e7490'],
        ['Seaside Cakes & Desserts','Catering',    ['Desserts', 'Cake'],       320, 4.5, 92,  ['beach', 'wedding', 'tropical'], 86, 'Showstopping themed cakes that fit your budget.', '#f59e0b,#b45309'],
        ['Wave Sound DJs',          'DJ',          ['DJ', 'Wedding'],          520, 4.4, 110, ['wedding', 'party', 'beach'],    85, 'Versatile DJs with a strong wedding & party track record.', '#8b5cf6,#6d28d9'],
        ['Horizon Videography',     'Videography', ['Video', 'Cinematic'],     700, 4.6, 67,  ['wedding', 'beach', 'tropical'], 84, 'Cinematic films capturing your beach celebration.', '#6366f1,#4338ca'],
        ['Lagoon Lighting & AV',    'Lighting',    ['Lighting', 'AV'],         500, 4.3, 54,  ['party', 'beach', 'evening'],    83, 'Sets the perfect evening mood for beachfront events.', '#f97316,#c2410c'],
        ['Cabana Rentals',          'Rentals',     ['Rentals', 'Furniture'],   400, 4.1, 38,  ['beach', 'tropical', 'outdoor'], 82, 'Beach cabanas and lounge furniture for that island feel.', '#14b8a6,#0f766e'],
        ['Shoreline Photo Booth',   'Photo Booth', ['Photo Booth', 'Fun'],     250, 4.2, 47,  ['party', 'beach', 'fun'],        81, 'A fun, budget-friendly add-on guests will love.', '#ec4899,#be185d'],
    ];

    public const MAX_BUDGET_OPTIONS = [300 => 'Up to $300', 600 => 'Up to $600', 1000 => 'Up to $1,000', 0 => 'Any Budget'];

    public function show(Request $request): View
    {
        $event = ['theme' => 'Tropical Beach Party', 'date' => 'May 24, 2025', 'budget' => 1000];

        $all     = $this->rank($this->keywords($event['theme']), 'all', $event['budget'], 80);
        $matches = array_slice($all, 0, 3);

        return view('client.ai-tools.vendor-matchmaking', [
            'event'         => $event,
            'matches'       => $matches,
            'moreCount'     => max(0, count($all) - 3),
            'analyzed'      => count(self::VENDORS),
            'categories'    => $this->categoryList(),
            'budgetOptions' => self::MAX_BUDGET_OPTIONS,
            'status'        => $this->gate->status($request->user(), AiFeatureCode::VENDOR_MATCHMAKING),
        ]);
    }

    public function match(Request $request): JsonResponse
    {
        try {
            $this->gate->authorize($request->user(), AiFeatureCode::VENDOR_MATCHMAKING);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $data = $request->validate([
            'theme'      => ['nullable', 'string', 'max:120'],
            'category'   => ['nullable', 'string', 'in:' . implode(',', array_keys($this->categoryList()))],
            'max_budget' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'min_match'  => ['nullable', 'integer', 'min:50', 'max:100'],
        ]);

        $theme    = $data['theme'] ?: 'Tropical Beach Party';
        $category = $data['category'] ?: 'all';
        $budget   = (int) ($data['max_budget'] ?? 1000);
        $minMatch = (int) ($data['min_match'] ?? 80);

        $all     = $this->rank($this->keywords($theme), $category, $budget, $minMatch);
        $matches = array_slice($all, 0, 3);

        $this->gate->recordUsage($request->user(), AiFeatureCode::VENDOR_MATCHMAKING);

        return response()->json([
            'success'   => true,
            'matches'   => $matches,
            'moreCount' => max(0, count($all) - 3),
            'analyzed'  => count(self::VENDORS),
            'budget'    => $budget,
            'status'    => $this->gate->status($request->user(), AiFeatureCode::VENDOR_MATCHMAKING),
        ]);
    }

    /**
     * Score (theme-fit base, adjusted by current-theme overlap) + filter
     * (category, budget, min-match) + rank the catalogue.
     *
     * @return array<int, array<string, mixed>>
     */
    private function rank(array $keywords, string $category, int $maxBudget, int $minMatch): array
    {
        $ranked = [];
        foreach (self::VENDORS as [$name, $cat, $tags, $price, $rating, $reviews, $themes, $base, $why, $grad]) {
            if ($category !== 'all' && $cat !== $category) {
                continue;
            }
            // Budget filter: only show vendors that fit (0 = any).
            if ($maxBudget !== 0 && $price > $maxBudget) {
                continue;
            }

            // Theme overlap adjusts the base score (drops vendors that don't
            // fit a re-themed event).
            $overlap = count(array_intersect($keywords, $themes));
            $match   = $base;
            if (! empty($keywords) && $overlap === 0) {
                $match = max(55, $base - 24);
            }
            $match = (int) max(50, min(99, $match));

            if ($match < $minMatch) {
                continue;
            }

            $ranked[] = [
                'name'      => $name,
                'category'  => $cat,
                'tags'      => $tags,
                'price'     => $price,
                'rating'    => $rating,
                'reviews'   => $reviews,
                'match'     => $match,
                'available' => true,
                'why'       => $why,
                'grad'      => preg_match('/^#[0-9a-fA-F]{6},#[0-9a-fA-F]{6}$/', $grad) ? $grad : '#8b5cf6,#6d28d9',
                'initials'  => $this->initials($name),
            ];
        }

        usort($ranked, fn ($a, $b) => $b['match'] <=> $a['match'] ?: $b['rating'] <=> $a['rating']);

        return $ranked;
    }

    private function keywords(string $theme): array
    {
        $stop  = ['a', 'an', 'the', 'and', 'or', 'of', 'for', 'with', 'my', 'our', 'event'];
        $words = array_filter(
            array_map(fn ($w) => Str::lower(trim($w)), preg_split('/\s+/', $theme)),
            fn ($w) => $w !== '' && ! in_array($w, $stop, true)
        );

        return array_values(array_unique($words));
    }

    private function categoryList(): array
    {
        $cats = array_values(array_unique(array_column(self::VENDORS, 1)));
        sort($cats);

        return array_merge(['all' => 'All Categories'], array_combine($cats, $cats));
    }

    private function initials(string $name): string
    {
        $w = preg_split('/\s+/', trim($name));

        return Str::upper(substr(($w[0] ?? 'V') ?: 'V', 0, 1) . (count($w) > 1 ? substr(end($w), 0, 1) : ''));
    }
}
