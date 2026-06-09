<?php

namespace App\Http\Controllers\Client;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Client-context professional search. Same underlying query as the public
 * /browse page, but framed by the client's "Active Project" — the event
 * they're currently sourcing vendors for. The right rail surfaces that
 * event's summary, budget allocation, and AI-recommended matches so the
 * client can filter without leaving the search context.
 *
 * Route: GET /client/search
 */
class ClientSearchController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // ── Active project — explicitly selected via ?event= or the
        // user's most recent open event. Falls back to null so the view
        // can render a "no active project" empty state.
        $activeEventId = (int) $request->query('event', 0);
        $activeEvent = $activeEventId
            ? Event::where('id', $activeEventId)->where('client_id', $user->id)->first()
            : Event::where('client_id', $user->id)
                ->whereIn('status', ['pending', 'published', 'confirmed'])
                ->latest('starts_at')
                ->first();

        // ── Filter inputs ──────────────────────────────────────────
        $q          = trim((string) $request->query('q', ''));
        $city       = trim((string) $request->query('city', ''));
        $within     = (int) $request->query('within', 25);  // miles — display only for now
        $eventDate  = trim((string) $request->query('event_date', ''));
        $maxBudget  = (int) $request->query('max_budget', 5000);
        $rateType   = $request->query('rate_type', 'total');  // total | hourly
        $sort       = $request->query('sort', 'cost_asc');
        $view       = $request->query('view', 'grid');  // grid | list

        // ── Base pros query (suppliers only, with profile + rating) ──
        $query = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::SUPPLIER->value))
            ->with(['profile']);

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

        if ($city !== '') {
            $query->whereHas('profile', fn ($p) => $p->where('city', 'like', $city . '%'));
        }

        if ($maxBudget > 0) {
            $query->whereHas('profile', fn ($p) => $p->where('hourly_rate', '<=', $maxBudget)
                                                    ->orWhereNull('hourly_rate'));
        }

        $query->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
              ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)]);

        // Sorting — MySQL doesn't grok NULLS LAST, so emulate it by sorting
        // the IS NULL flag first (NULL rows last) then the actual value.
        match ($sort) {
            'cost_desc'   => $query->orderByRaw('(SELECT hourly_rate FROM user_profiles WHERE user_profiles.user_id = users.id) IS NULL, (SELECT hourly_rate FROM user_profiles WHERE user_profiles.user_id = users.id) DESC'),
            'rating_desc' => $query->orderByRaw('reviews_avg IS NULL, reviews_avg DESC')->orderBy('reviews_count', 'desc'),
            'newest'      => $query->latest('users.created_at'),
            default       => /* cost_asc */ $query->orderByRaw('(SELECT hourly_rate FROM user_profiles WHERE user_profiles.user_id = users.id) IS NULL, (SELECT hourly_rate FROM user_profiles WHERE user_profiles.user_id = users.id) ASC'),
        };

        $pros = $query->paginate(12)->withQueryString();

        // ── Counts for the result-summary chips (Verified / Available / Escrow) ──
        $countVerified = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::SUPPLIER->value))
            ->whereHas('profile', function (Builder $p) {
                $p->whereNotNull('trade_license_verified_at')
                  ->whereNotNull('liability_insurance_verified_at')
                  ->whereNotNull('workers_comp_verified_at');
            })
            ->count();
        $countAvailable = $pros->total();  // proxy — real availability requires per-date check
        $countEscrow    = (int) round($countVerified * 0.85); // proxy until escrow flag wired

        // ── Right-rail data: event summary + budget overview ──────
        $budgetMax = $activeEvent?->budget ?? $maxBudget;
        $avgRate   = (int) UserProfile::whereNotNull('hourly_rate')->avg('hourly_rate') ?: 0;
        $projected = $avgRate * 6; // assume 6-hour avg engagement
        $withinPct = $budgetMax > 0 ? min(100, (int) round(($budgetMax / max(1, $projected)) * 80)) : 0;
        $exceedsPct = 100 - $withinPct;

        // ── Other dropdown data ───────────────────────────────────
        $allEvents = Event::where('client_id', $user->id)
            ->whereIn('status', ['pending', 'published', 'confirmed'])
            ->orderBy('starts_at', 'desc')
            ->take(20)
            ->get(['id', 'title', 'starts_at']);

        $cities = UserProfile::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->limit(40)
            ->pluck('city');

        // AI-style recommendations — naive heuristic for now. Wire to the
        // AI Vendor Matchmaking service once its API is exposed.
        $recommendations = $pros->getCollection()->take(3)->map(fn ($p, $i) => [
            'pro'    => $p,
            'reason' => ['is a top match for your budget', 'has great availability', 'has high reviews this month'][$i] ?? 'matches your event',
            'match'  => [98, 93, 89][$i] ?? 85,
        ]);

        return view('client.search.index', [
            'pros'             => $pros,
            'cities'           => $cities,
            'activeEvent'      => $activeEvent,
            'allEvents'        => $allEvents,
            'filters'          => [
                'q'           => $q,
                'city'        => $city,
                'within'      => $within,
                'event_date'  => $eventDate,
                'max_budget'  => $maxBudget,
                'rate_type'   => $rateType,
                'sort'        => $sort,
                'view'        => $view,
            ],
            'countVerified'    => $countVerified,
            'countAvailable'   => $countAvailable,
            'countEscrow'      => $countEscrow,
            'budgetMax'        => $budgetMax,
            'projected'        => $projected,
            'withinPct'        => $withinPct,
            'exceedsPct'       => $exceedsPct,
            'recommendations'  => $recommendations,
        ]);
    }
}
