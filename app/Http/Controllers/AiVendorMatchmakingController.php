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

    public const MAX_BUDGET_OPTIONS = [300 => 'Up to $300', 600 => 'Up to $600', 1000 => 'Up to $1,000', 0 => 'Any Budget'];

    public function show(Request $request): View
    {
        // Real event from the client's own events (picker via ?event=id), else a
        // representative one so the tool still demos when the client has no events.
        [$event, $events, $selectedId] = $this->resolveEvent($request);

        // Real professionals only. The list used to be topped up from a
        // representative catalogue whenever fewer than five real ones matched.
        // Those filler rows carried a name, a rating and a price and sat in the
        // same list as real people — the only thing separating them was a
        // missing button. A short honest list beats a padded one.
        $kw  = $this->keywords($event['theme'] . ' ' . ($event['keywords_extra'] ?? ''));
        $all = $this->rankReal($kw, 'all', $event['budget'], 80);

        // Level drives the experience: Starter (browse the directory and
        // pick), Semi (ranked by the scoring rules, you refine), Maximum (the
        // rules pick the team for you, read-only). Admins can preview any level.
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
            // No event, no assumed one. This used to stand in "Tropical Beach
            // Party" — invisible in the page, but it fed the keyword scoring and
            // quietly tilted every match toward beach-themed professionals.
            return [['theme' => '', 'date' => null, 'budget' => 0], collect(), null];
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

        /*
         * Rule R38 — this tool does not print a number, it names people and
         * says hire them. Unscoped it recommended professionals the client is
         * not allowed to transact with, and the Direct Offer at the end of
         * that recommendation would have been refused with a 422 after they
         * had chosen someone. A recommendation you are forbidden to act on is
         * worse than no recommendation.
         */
        $suppliers = \App\Models\User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', \App\Domain\Auth\Enums\RoleName::PROFESSIONAL->value))
            ->excludingSelf()
            ->whereHas('profile')
            ->tap(fn ($q) => \App\Support\StateMatching::scopeUsersForViewer($q, auth()->user()))
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

            // A price only when the pro actually set a rate. This used to
            // invent one from the user id and print it beside a real person's
            // name, where a client would read it as that pro's price.
            $price = $s->profile?->hourly_rate
                ? (int) round($s->profile->hourly_rate * 4 / 50) * 50
                : null;

            // Budget filters what it can price. A pro with no published rate is
            // not silently dropped for being over a budget nobody knows.
            if ($maxBudget !== 0 && $price !== null && $price > $maxBudget) {
                continue;
            }

            // Skill/theme overlap drives the score; rating nudges it.
            $skillWords = array_map(fn ($x) => Str::lower((string) $x), $skills);
            $overlap = count(array_intersect($keywords, $skillWords));
            // Null until somebody actually reviews them — never a seeded 4.3.
            $rating  = $s->reviews_avg ? round((float) $s->reviews_avg, 1) : null;
            // A professional with no reviews sits at the baseline. Scoring them
            // BELOW it would drop every new pro under the 80% threshold and hide
            // them from the client entirely — a silent ban for being new.
            $base = 80 + min(15, $overlap * 6) + ($rating !== null ? (int) round(($rating - 4.3) * 6) : 0);
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
                // The real count, including zero. A pro with no reviews used
                // to be given up to 180 of them.
                'reviews'   => (int) $s->reviews_count,
                'match'     => $match,
                'available' => true,
                'why'       => $why,
                'grad'      => $grads[$s->id % count($grads)],
                'initials'  => $this->initials($name),
                // The id is what separates a person you can send an offer to
                // from a catalogue entry that only looks like one. The filler
                // rows below carry neither, and the card checks for it.
                'id'        => $s->id,
                'real'      => true,
            ];
        }

        usort($ranked, fn ($a, $b) => $b['match'] <=> $a['match'] ?: $b['rating'] <=> $a['rating']);

        return $ranked;
    }

    /**
     * The full vendor catalogue formatted for Starter-level browsing —
     * no match score, no "why matched": the client filters and picks for
     * themselves. Carries the fields the directory cards + client-side filter
     * need (category + price power the on-page filtering).
     *
     * @return array<int, array<string, mixed>>
     */
    private function directory(): array
    {
        // Starter level: the client browses and picks for themselves. This used
        // to list a catalogue of invented vendors — names, ratings and prices a
        // client could compare and choose between, none of whom existed. It now
        // lists real professionals the client is allowed to work with, and says
        // plainly when it has nothing to show.
        return array_map(
            fn (array $r) => [
                'name'     => $r['name'],
                'category' => $r['category'],
                'tags'     => $r['tags'] ?? '',
                'price'    => $r['price'],
                'rating'   => $r['rating'],
                'reviews'  => $r['reviews'],
                'grad'     => $r['grad'],
                'initials' => $r['initials'],
                'id'       => $r['id'] ?? null,
            ],
            $this->rankReal([], 'all', 0, 0)
        );
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

        $theme    = ($data['theme'] ?? '') ?: 'Tropical Beach Party';
        $category = ($data['category'] ?? '') ?: 'all';
        $budget   = (int) ($data['max_budget'] ?? 1000);
        $minMatch = (int) ($data['min_match'] ?? 80);

        // Real professionals only — see show().
        $all = $this->rankReal($this->keywords($theme), $category, $budget, $minMatch);
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

    private function keywords(string $theme): array
    {
        $stop  = ['a', 'an', 'the', 'and', 'or', 'of', 'for', 'with', 'my', 'our', 'event'];
        $words = array_filter(
            array_map(fn ($w) => Str::lower(trim($w)), preg_split('/\s+/', $theme)),
            fn ($w) => $w !== '' && ! in_array($w, $stop, true)
        );

        return array_values(array_unique($words));
    }

    /**
     * The filter offers only categories that are actually on the page. It used
     * to list the invented catalogue's categories, so a client could filter to
     * one and be told nothing matched.
     *
     * @param  array<int, array<string, mixed>>|null  $rows
     */
    private function categoryList(?array $rows = null): array
    {
        $rows ??= $this->directory();
        $cats = array_values(array_unique(array_filter(array_column($rows, 'category'))));
        sort($cats);

        return array_merge(['all' => 'All Categories'], array_combine($cats, $cats));
    }

    private function initials(string $name): string
    {
        $w = preg_split('/\s+/', trim($name));

        return Str::upper(substr(($w[0] ?? 'V') ?: 'V', 0, 1) . (count($w) > 1 ? substr(end($w), 0, 1) : ''));
    }
}
