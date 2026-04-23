<?php

namespace App\Http\Controllers\Public;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Review;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
        $ratingMin = (float) $request->query('rating_min', 0);
        $verified  = (bool) $request->query('verified', false);
        $sort      = (string) $request->query('sort', 'top');

        // Base query: only suppliers, with their profile for card details.
        // We eager-load the profile so the card can render city / headline /
        // hourly rate / verification badges without N+1 queries.
        $query = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::SUPPLIER->value))
            ->with(['profile']);

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

        // ── City filter ─────────────────────────────────────────────
        if ($city !== '') {
            $query->whereHas('profile', fn ($p) => $p->where('city', 'like', $city . '%'));
        }

        // ── Verified-only filter ────────────────────────────────────
        // "Verified" here means all three badges (trade license + liability
        // insurance + workers' comp) are admin-approved. Matches the
        // `isTopRated()` policy on the User model.
        if ($verified) {
            $query->whereHas('profile', function (Builder $p) {
                $p->whereNotNull('trade_license_verified_at')
                  ->whereNotNull('liability_insurance_verified_at')
                  ->whereNotNull('workers_comp_verified_at');
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
                                                AND workers_comp_verified_at IS NOT NULL
                                           THEN 1 ELSE 0 END
                                  FROM user_profiles WHERE user_profiles.user_id = users.id) DESC')
                    ->orderByRaw('reviews_avg IS NULL, reviews_avg DESC')
                    ->orderBy('reviews_count', 'desc'),
        };

        /** @var LengthAwarePaginator $pros */
        $pros = $query->paginate(12)->withQueryString();

        // Sidebar data: popular categories (for the filter nav) + a list
        // of distinct cities so we can power a city picker without a
        // separate autocomplete endpoint.
        $categories = Category::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon']);

        $cities = UserProfile::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->limit(40)
            ->pluck('city');

        return view('public.browse', [
            'pros'       => $pros,
            'categories' => $categories,
            'cities'     => $cities,
            'filters'    => [
                'q'          => $q,
                'city'       => $city,
                'rating_min' => $ratingMin,
                'verified'   => $verified,
                'sort'       => $sort,
            ],
            'badges'     => UserProfile::BADGES,
        ]);
    }
}
