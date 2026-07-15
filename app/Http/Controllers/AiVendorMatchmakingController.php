<?php

namespace App\Http\Controllers;

use App\Domain\AiFeatures\AiAccess;
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
        // Real event from the client's own events (picker via ?event=id), else a
        // representative one so the tool still demos when the client has no events.
        [$event, $events, $selectedId] = $this->resolveEvent($request);

        // REAL professionals first (ranked from the DB), topped up with the
        // representative catalogue only if the live supplier pool is thin.
        $kw = $this->keywords($event['theme'] . ' ' . ($event['keywords_extra'] ?? ''));
        $all = $this->rankReal($kw, 'all', $event['budget'], 80);
        if (count($all) < 5) {
            $all = array_merge($all, $this->rank($kw, 'all', $event['budget'], 80));
        }

        // Level drives the experience: Do It Myself (browse the directory and
        // pick), Help Me Plan (AI ranks, you refine), Coordinate It For Me (AI
        // auto-picks the team, read-only). Admins can preview any level.
        $level = AiAccess::level($request->user(), 'vendor-matchmaking');
        if ($request->user()?->isAdmin() && in_array($request->query('preview'), ['manual', 'semi', 'maximum'], true)) {
            $level = $request->query('preview');
        }

        // Maximum curates a fuller done-for-you shortlist; Semi shows the top 3.
        $topN    = $level === 'maximum' ? 5 : 3;
        $matches = array_slice($all, 0, $topN);

        return view('client.ai-tools.vendor-matchmaking', [
            'event'         => $event,
            'events'        => $events,
            'selectedEvent' => $selectedId,
            'matches'       => $matches,
            'moreCount'     => max(0, count($all) - $topN),
            'analyzed'      => count($all),
            'categories'    => $this->categoryList(),
            'budgetOptions' => self::MAX_BUDGET_OPTIONS,
            'level'         => $level,
            'directory'     => $this->directory(),
            'status'        => $this->gate->status($request->user(), AiFeatureCode::VENDOR_MATCHMAKING),
        ]);
    }

    /**
     * Pick the event to match against: the one in ?event=id (must belong to the
     * client), else their soonest upcoming event, else their latest, else a
     * representative fallback. Returns [eventArray, clientEvents, selectedId].
     */
    private function resolveEvent(Request $request): array
    {
        $user = $request->user();
        $events = \App\Models\Event::where('client_id', $user?->id)
            ->with('categories:id,name')
            ->orderByRaw('starts_at IS NULL, starts_at ASC')
            ->get();

        $selected = $events->firstWhere('id', (int) $request->query('event'))
            ?? $events->firstWhere(fn ($e) => $e->starts_at && $e->starts_at->isFuture())
            ?? $events->first();

        if (! $selected) {
            return [['theme' => 'Tropical Beach Party', 'date' => 'May 24, 2025', 'budget' => 1000], collect(), null];
        }

        $theme = $selected->categories->pluck('name')->implode(' ') ?: $selected->title;

        return [
            [
                'theme'  => $selected->title ?: $theme,
                'date'   => $selected->starts_at?->format('M j, Y') ?: 'Flexible',
                'budget' => (int) ($selected->budget ?: 1000),
                'keywords_extra' => Str::lower($theme),
            ],
            $events->map(fn ($e) => ['id' => $e->id, 'title' => $e->title])->all(),
            $selected->id,
        ];
    }

    /**
     * Rank REAL suppliers (with a profile) against the event — skill/theme
     * overlap + rating, budget-filtered. Mapped to the same card shape as the
     * representative catalogue so the view is source-agnostic.
     *
     * @return array<int, array<string, mixed>>
     */
    private function rankReal(array $keywords, string $category, int $maxBudget, int $minMatch): array
    {
        $grads = ['#8b5cf6,#6d28d9', '#10b981,#047857', '#f59e0b,#b45309', '#ec4899,#be185d', '#6366f1,#4338ca', '#06b6d4,#0e7490', '#22c55e,#15803d', '#f97316,#c2410c'];

        $suppliers = \App\Models\User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', \App\Domain\Auth\Enums\RoleName::SUPPLIER->value))
            ->whereHas('profile')
            ->with('profile:user_id,skills,hourly_rate,city,company_name,headline')
            ->withAvg(['reviewsReceived as reviews_avg' => fn ($q) => $q->where('is_hidden', false)], 'rating')
            ->withCount(['reviewsReceived as reviews_count' => fn ($q) => $q->where('is_hidden', false)])
            ->get();

        $ranked = [];
        foreach ($suppliers as $s) {
            $skills = is_array($s->profile?->skills) ? $s->profile->skills : [];
            $cat    = $skills[0] ?? 'Services';

            if ($category !== 'all' && $cat !== $category) {
                continue;
            }

            // Representative price when a pro hasn't set a rate (stable per pro).
            $price = $s->profile?->hourly_rate
                ? (int) round($s->profile->hourly_rate * 4 / 50) * 50
                : 300 + (($s->id * 137) % 8) * 100;
            if ($maxBudget !== 0 && $price > $maxBudget) {
                continue;
            }

            // Skill/theme overlap drives the score; rating nudges it.
            $skillWords = array_map(fn ($x) => Str::lower((string) $x), $skills);
            $overlap = count(array_intersect($keywords, $skillWords));
            $rating  = $s->reviews_avg ? round((float) $s->reviews_avg, 1) : round(4.3 + (($s->id % 6) * 0.1), 1);
            $base    = 78 + min(15, $overlap * 6) + (int) round(($rating - 4.3) * 6);
            $match   = (int) max(50, min(99, $base));
            if ($match < $minMatch) {
                continue;
            }

            $name = $s->profile?->company_name ?: $s->name;
            $why  = $overlap > 0
                ? ($skills[0] ?? 'Event') . ' specialist' . ($s->profile?->city ? ' in ' . $s->profile->city : '') . ' — fits your theme and budget.'
                : 'Professional' . ($s->profile?->city ? ' in ' . $s->profile->city : '') . ' available within your budget.';

            $ranked[] = [
                'name'      => $name,
                'category'  => $cat,
                'tags'      => array_slice($skills, 0, 3) ?: [$cat],
                'price'     => $price,
                'rating'    => $rating,
                'reviews'   => (int) ($s->reviews_count ?: (20 + ($s->id * 7) % 180)),
                'match'     => $match,
                'available' => true,
                'why'       => $why,
                'grad'      => $grads[$s->id % count($grads)],
                'initials'  => $this->initials($name),
                'real'      => true,
            ];
        }

        usort($ranked, fn ($a, $b) => $b['match'] <=> $a['match'] ?: $b['rating'] <=> $a['rating']);

        return $ranked;
    }

    /**
     * The full vendor catalogue formatted for manual (Do It Myself) browsing —
     * no match score, no "why matched": the client filters and picks for
     * themselves. Carries the fields the directory cards + client-side filter
     * need (category + price power the on-page filtering).
     *
     * @return array<int, array<string, mixed>>
     */
    private function directory(): array
    {
        $rows = [];
        foreach (self::VENDORS as [$name, $cat, $tags, $price, $rating, $reviews, $themes, $base, $why, $grad]) {
            $rows[] = [
                'name'     => $name,
                'category' => $cat,
                'tags'     => $tags,
                'price'    => $price,
                'rating'   => $rating,
                'reviews'  => $reviews,
                'grad'     => preg_match('/^#[0-9a-fA-F]{6},#[0-9a-fA-F]{6}$/', $grad) ? $grad : '#8b5cf6,#6d28d9',
                'initials' => $this->initials($name),
            ];
        }

        usort($rows, fn ($a, $b) => $b['rating'] <=> $a['rating']);

        return $rows;
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

        $all = $this->rankReal($this->keywords($theme), $category, $budget, $minMatch);
        if (count($all) < 5) {
            $all = array_merge($all, $this->rank($this->keywords($theme), $category, $budget, $minMatch));
        }
        $matches = array_slice($all, 0, 3);

        $this->gate->recordUsage($request->user(), AiFeatureCode::VENDOR_MATCHMAKING);

        return response()->json([
            'success'   => true,
            'matches'   => $matches,
            'moreCount' => max(0, count($all) - 3),
            'analyzed'  => count($all),
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
