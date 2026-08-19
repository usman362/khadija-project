<?php

namespace App\Http\Controllers\Public;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\Review;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Public "/browse" — the searchable, filterable grid of professionals
 * (users with the `supplier` role). This is where the landing page's
 * hero search, A-Z browse chips, and the events-categories mega-panel
 * all converge. Without this, those entry points dead-end.
 *
 * Filters (all query-string, all optional):
 *   ?q=photographer           keyword — name, headline, bio, skills
 *   ?city=Austin              location match (case-insensitive)
 *   ?rating_min=4.5           4 / 4.5 / 5
 *   ?verified=1               only pros with all 3 verification badges
 *   ?sort=top|rating|newest   default: top
 *   ?page=2                   pagination, 12 per page
 */
class BrowseProfessionalsController extends Controller
{
    public function index(Request $request): View
    {
        $q         = trim((string) $request->query('q', ''));
        $city      = trim((string) $request->query('city', ''));
        // Uppercased and checked against the operating list, so the parameter
        // cannot be used to query a state we do not serve.
        $state     = strtoupper(trim((string) $request->query('state', '')));
        $state     = array_key_exists($state, config('geo.allowed_states', [])) ? $state : '';
        $catSlug   = trim((string) $request->query('category', ''));
        $ratingMin = (float) $request->query('rating_min', 0);
        $verified  = (bool) $request->query('verified', false);
        $insured   = (bool) $request->query('insured', false);
        $available = (bool) $request->query('available', false);
        $rateMax   = (int) $request->query('rate_max', 0);
        $sort      = (string) $request->query('sort', 'top');

        // Base query: only suppliers, with their profile for card details.
        // We eager-load the profile so the card can render city / headline /
        // hourly rate / verification badges without N+1 queries.
        $query = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::PROFESSIONAL->value))
            ->excludingSelf()
            ->with(['profile', 'serviceCategories:id,name,thumbnail']);

        // Rule R38 — a client only matches professionals in their own state,
        // and search HIDES what is ineligible rather than showing it greyed
        // out. This page is login-gated, so there is always a viewer whose
        // state decides the list.
        \App\Support\StateMatching::scopeUsersForViewer($query, $request->user());

        // ── Keyword search across name + profile text fields ──────────
        // skills/portfolio are JSON arrays on UserProfile, so a LIKE on
        // the raw text is good enough for MVP (no fulltext index needed).
        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->where(function (Builder $outer) use ($like) {
                $outer->where('name', 'like', $like)
                    ->orWhereHas('profile', function (Builder $p) use ($like) {
                        $p->where('headline', 'like', $like)
                          ->orWhere('bio', 'like', $like)
                          ->orWhere('skills', 'like', $like)
                          ->orWhere('company_name', 'like', $like);
                    });
            });
        }

        // ── Category filter ─────────────────────────────────────────
        // A real relation, not a keyword guess: ?category=<slug> narrows to the
        // pros who listed that category (or anything under it) as a service
        // they offer. The category landing pages link here with it.
        $category = $catSlug !== ''
            ? Category::active()->where('slug', $catSlug)->first()
            : null;

        if ($category) {
            $branchIds = $this->branchIds($category);
            $query->whereHas('serviceCategories', fn (Builder $c) => $c->whereIn('categories.id', $branchIds));
        }

        // ── Rate ceiling ────────────────────────────────────────────
        // The sidebar slider used to be `disabled` decoration. Pros who haven't
        // published a rate are kept: filtering them out would hide real
        // professionals for not filling in an optional field.
        if ($rateMax > 0) {
            $query->whereHas('profile', fn (Builder $p) => $p
                ->where('hourly_rate', '<=', $rateMax)
                ->orWhereNull('hourly_rate'));
        }

        // ── Insurance ───────────────────────────────────────────────
        if ($insured) {
            $query->whereHas('profile', fn (Builder $p) => $p->insuranceCurrent());
        }

        // ── Currently taking work ───────────────────────────────────
        if ($available) {
            $query->whereHas('profile', fn (Builder $p) => $p->where('availability', 'available'));
        }

        // ── City filter ─────────────────────────────────────────────
        if ($city !== '') {
            $query->whereHas('profile', fn ($p) => $p->where('city', 'like', $city . '%'));
        }

        // ── State filter ────────────────────────────────────────────
        // Added for the homepage's "Where our professionals are" section, so a
        // state card has somewhere real to land.
        if ($state !== '') {
            $query->whereHas('profile', fn ($p) => $p->where('state', $state));
        }

        // ── Verified-only filter ────────────────────────────────────
        // "Verified" here means all three badges (trade license + liability
        // insurance + workers' comp) are admin-approved. Matches the
        // `isTopRated()` policy on the User model.
        if ($verified) {
            $query->whereHas('profile', function (Builder $p) {
                $p->whereNotNull('trade_license_verified_at')
                  ->whereNotNull('workers_comp_verified_at')
                  ->insuranceCurrent();
            });
        }

        // ── Rating aggregate for sort + filter ──────────────────────
        // Pre-compute avg rating + review count on each row. `reviewsReceived`
        // is the relation in User pointing at reviews.reviewee_id, filtered
        // to visible (non-hidden) reviews via a scope on Review.
        $query->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
              ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)]);

        if ($ratingMin > 0) {
            $query->having('reviews_avg', '>=', $ratingMin);
        }

        // ── Sort ────────────────────────────────────────────────────
        match ($sort) {
            'rating' => $query->orderByRaw('reviews_avg IS NULL, reviews_avg DESC')
                              ->orderBy('reviews_count', 'desc'),
            'newest' => $query->latest('users.created_at'),
            default  => // 'top' — verified first, then rating, then review volume
                $query
                    ->orderByRaw('(SELECT CASE WHEN trade_license_verified_at IS NOT NULL
                                                AND liability_insurance_verified_at IS NOT NULL
                                                AND (liability_insurance_expires_on IS NULL
                                                     OR liability_insurance_expires_on >= CURRENT_DATE)
                                                AND workers_comp_verified_at IS NOT NULL
                                           THEN 1 ELSE 0 END
                                  FROM user_profiles WHERE user_profiles.user_id = users.id) DESC')
                    ->orderByRaw('reviews_avg IS NULL, reviews_avg DESC')
                    ->orderBy('reviews_count', 'desc'),
        };

        $locationIssue = null;
        $nearZip = \App\Domain\Geolocation\ZipCentroidTable::normalize((string) $request->query('zip', ''));
        if (\App\Support\RadiusMatching::enabled() && $nearZip !== null) {
            $placed = app(\App\Domain\Geolocation\Geocoder::class)->fromZip($nearZip);
            if (! $placed->isMatchable()) {
                // Q7 — failed place is not an empty marketplace.
                $locationIssue = $placed->message;
            } else {
                $candidateIds = (clone $query)->pluck('users.id');
                $kept = \App\Support\RadiusMatching::filterUsersNearPoint(
                    User::with('profile')->whereIn('id', $candidateIds)->get(),
                    $placed->lat,
                    $placed->lng,
                )->pluck('id')->all();
                $query->whereIn('users.id', $kept ?: [0]);
            }
        }

        /** @var LengthAwarePaginator $pros */
        $pros = $query->paginate(12)->withQueryString();

        // Sidebar data: popular categories (for the filter nav) + a list
        // of distinct cities so we can power a city picker without a
        // separate autocomplete endpoint.
        $categories = Category::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon']);

        // Slider bounds come from the data — a hardcoded $5,000 ceiling meant the
        // handle sat in dead space when nobody charges near it.
        $rateCeiling = (int) ceil(((float) UserProfile::max('hourly_rate') ?: 500) / 50) * 50;

        $cities = UserProfile::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->limit(40)
            ->pluck('city');

        // ── Trending categories ─────────────────────────────────────
        // The row above the results used to be six hard-coded "vibes" with
        // Unsplash photos and keyword guesses. These are real top-level
        // categories that actually have professionals behind them, carrying
        // their own artwork, and each one filters this page by the relation.
        /*
         * The count has to be the count the click produces.
         *
         * This tile prints "12 professionals" and then filters the page — and
         * the page is R38-scoped to the viewer's own state. Counted
         * platform-wide, a Maryland client was shown 12 and landed on one. The
         * same fault the homepage's city section shipped with, so the same
         * fix: count what this viewer can actually reach.
         */
        $inState = fn ($q) => \App\Support\StateMatching::scopeUsersForViewer($q, $request->user());

        $trending = Category::active()
            ->whereNull('parent_id')
            ->whereNotNull('thumbnail')
            ->withCount(['professionals as pros_count' => $inState])
            ->whereHas('professionals', $inState)
            ->orderByDesc('pros_count')
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'slug', 'thumbnail', 'short_description'])
            ->unique('name')
            ->take(6)
            ->values();

        // Fall back to the busiest categories at any depth when no top-level
        // one has pros yet — better an honest short row than an empty strip.
        // Over-fetch and dedupe by name: the legacy tree repeats the same
        // service under many event types, so the raw top-6 was three copies of
        // "DJs & Sound Services".
        if ($trending->isEmpty()) {
            $trending = Category::active()
                ->whereNotNull('thumbnail')
                ->withCount(['professionals as pros_count' => $inState])
                ->whereHas('professionals', $inState)
                ->orderByDesc('pros_count')
                ->limit(60)
                ->get(['id', 'name', 'slug', 'thumbnail', 'short_description'])
                ->unique('name')
                ->take(6)
                ->values();
        }

        // ── Where these pros are ────────────────────────────────────
        // Replaces a decorative map whose pins sat at fixed CSS percentages.
        // Real counts over the current filter, minus paging, so the numbers
        // describe the whole result set rather than page one.
        // Counted in two steps rather than one GROUP BY over $query: that query
        // carries withAvg/withCount subselects and a HAVING on the rating alias,
        // and grouping on top of them trips only_full_group_by. Materialising
        // the filtered ids first keeps the counts correct under every filter.
        $filteredIds = (clone $query)->reorder()->get()->pluck('id');

        //
        // Checklist row 147. The panel used to show the top six cities and
        // stop, so it summed to 7 beside a header reading "Found: 13 Pros" —
        // two numbers over one result set that did not agree. The six rows
        // stay (a sidebar listing forty cities helps nobody), but everything
        // they leave out is now counted into a final row, so the column adds
        // up to the total the page reports. Pros with no city on file land
        // there too, rather than vanishing.
        $allCityCounts = $filteredIds->isEmpty()
            ? collect()
            : UserProfile::whereIn('user_id', $filteredIds)
                ->whereNotNull('city')->where('city', '!=', '')
                ->select('city', DB::raw('COUNT(*) as total'))
                ->groupBy('city')
                ->orderByDesc('total')
                ->pluck('total', 'city');

        $locationCounts = $allCityCounts->take(6);
        $locationOther  = max(0, $filteredIds->count() - (int) $locationCounts->sum());

        // ── Recently viewed ─────────────────────────────────────────
        // Real session history written when a profile is opened, in the order
        // the visitor actually saw them.
        $recentIds  = collect($request->session()->get(ProfessionalProfileShowController::RECENT_KEY, []));
        $recentPros = $recentIds->isEmpty()
            ? collect()
            : User::whereIn('id', $recentIds)->with('profile')->get()
                ->sortBy(fn ($u) => $recentIds->search($u->id))
                ->values();

        // Event context, carried over from the Search page this replaced. A client
        // who arrives while planning something keeps that event with them —
        // "Inviting for: …" — instead of being sent to a separate page with a
        // different set of filters (Peter + Khadijah, 2026-07-30).
        $activeEvent = null;
        $myEvents    = collect();

        if ($user = $request->user()) {
            $wanted = (int) $request->query('event', 0);

            $myEvents = Event::where('client_id', $user->id)
                ->whereIn('status', ['pending', 'published', 'confirmed'])
                ->orderByDesc('starts_at')
                ->take(20)
                ->get(['id', 'title', 'starts_at']);

            $activeEvent = $wanted
                ? $myEvents->firstWhere('id', $wanted)
                : null;
        }

        return view('public.browse', [
            'activeEvent' => $activeEvent,
            'myEvents'    => $myEvents,
            'pros'       => $pros,
            'categories' => $categories,
            'cities'     => $cities,
            'filters'    => [
                'q'          => $q,
                'city'       => $city,
                'state'      => $state,
                'rating_min' => $ratingMin,
                'verified'   => $verified,
                'insured'    => $insured,
                'available'  => $available,
                'rate_max'   => $rateMax,
                'sort'       => $sort,
                'category'   => $category?->slug,
                'zip'        => $nearZip,
            ],
            'activeCategory'  => $category,
            'trending'        => $trending,
            'rateCeiling'     => $rateCeiling,
            'locationCounts'  => $locationCounts,
            'locationOther'   => $locationOther,
            'recentPros'      => $recentPros,
            'badges'     => UserProfile::BADGES,
            'locationIssue' => $locationIssue,
        ]);
    }

    /**
     * The category's own id plus every descendant's, so filtering by a group
     * ("Photography Services") also returns the pros listed under its leaves.
     *
     * @return array<int, int>
     */
    private function branchIds(Category $category): array
    {
        $ids   = [$category->id];
        $level = [$category->id];

        for ($depth = 0; $depth < 8 && $level !== []; $depth++) {
            $level = Category::whereIn('parent_id', $level)->pluck('id')->all();
            $ids   = array_merge($ids, $level);
        }

        return $ids;
    }
}
