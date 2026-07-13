<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PackageController extends Controller
{
    /**
     * The service palette for the Service-Mix Matcher (left rail). A package
     * bundles two or more of these; the matcher AND-matches on the selected set.
     * Order mirrors Peter's mockup.
     */
    public const SERVICES = [
        'Photography', 'Videography', 'Floral Design', 'Catering / Food',
        'Decor & Design', 'Lighting & Tech', 'DJ / Entertainment',
        'Planning / Coordination', 'Rentals', 'Transportation',
        'Beauty & Hair', 'Invitations / Stationery',
    ];

    /**
     * Public "Package Service Search" — pros' multi-service bundles that a client
     * browses (NOT MSRs, which clients post and pros bid on). Supports the
     * Service-Mix Matcher (AND-match), provider-type filter, and sorting.
     */
    public function index(Request $request): View
    {
        // Selected services (AND match) — a package must include EVERY one.
        $selected = collect((array) $request->query('services', []))
            ->map(fn ($s) => trim((string) $s))
            ->filter(fn ($s) => in_array($s, self::SERVICES, true))
            ->values();

        $provider = (string) $request->query('provider', 'all');   // all | solo | coop
        $q        = trim((string) $request->query('q', ''));
        $sort     = (string) $request->query('sort', 'relevant');

        $base = Package::active()
            ->with([
                'category:id,name,slug',
                'user' => fn ($u) => $u->select('id', 'name')
                    ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
                    ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)])
                    ->with('profile:user_id,city,state'),
                'coopPartner:id,name',
            ])
            ->when($provider === 'solo', fn ($qr) => $qr->where('type', 'solo'))
            ->when($provider === 'coop', fn ($qr) => $qr->where('type', 'co-op'))
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%")));

        // AND-match every selected service against the JSON services column.
        foreach ($selected as $svc) {
            $base->whereJsonContains('services', $svc);
        }

        $base->when($sort === 'price_low', fn ($qr) => $qr->orderBy('price'))
            ->when($sort === 'price_high', fn ($qr) => $qr->orderByDesc('price'))
            ->when($sort === 'savings', fn ($qr) => $qr->orderByDesc('savings_pct'))
            ->when($sort === 'newest', fn ($qr) => $qr->latest())
            // 'relevant' (default): curated sort_order, then freshest.
            ->when(! in_array($sort, ['price_low', 'price_high', 'savings', 'newest'], true),
                fn ($qr) => $qr->orderByDesc('sort_order')->latest());

        $packages = $base->paginate(12)->withQueryString();

        // Left-rail service counts (respecting provider filter, ignoring service selection).
        $serviceCounts = [];
        foreach (self::SERVICES as $svc) {
            $serviceCounts[$svc] = Package::active()
                ->when($provider === 'solo', fn ($qr) => $qr->where('type', 'solo'))
                ->when($provider === 'coop', fn ($qr) => $qr->where('type', 'co-op'))
                ->whereJsonContains('services', $svc)
                ->count();
        }

        // Right-rail "Where Packages Are Available" — real counts by pro city.
        $availability = Package::active()
            ->with('user.profile:user_id,city')
            ->get()
            ->groupBy(fn ($p) => $p->user?->profile?->city ?: 'Other')
            ->map->count()
            ->sortDesc()
            ->take(6);

        // Provider-type totals for the radio labels.
        $providerCounts = [
            'all'  => Package::active()->count(),
            'solo' => Package::active()->where('type', 'solo')->count(),
            'coop' => Package::active()->where('type', 'co-op')->count(),
        ];

        // Recently viewed (session ids, newest first), excluding what's on-page already.
        $recentIds = collect(session('recent_packages', []))->take(4);
        $recent = $recentIds->isNotEmpty()
            ? Package::active()->whereIn('id', $recentIds)->get()
                ->sortBy(fn ($p) => $recentIds->search($p->id))->values()
            : collect();

        return view('public.packages-index', [
            'packages'       => $packages,
            'total'          => $packages->total(),
            'services'       => self::SERVICES,
            'serviceCounts'  => $serviceCounts,
            'availability'   => $availability,
            'providerCounts' => $providerCounts,
            'recent'         => $recent,
            'filters'        => [
                'selected' => $selected->all(),
                'provider' => in_array($provider, ['all', 'solo', 'coop'], true) ? $provider : 'all',
                'q'        => $q,
                'sort'     => $sort,
                'view'     => $request->query('view') === 'grid' ? 'grid' : 'list',
            ],
        ]);
    }

    public function show(Package $package): View
    {
        abort_unless($package->is_active, 404);

        $package->load([
            'category:id,name,slug',
            'user' => function ($q) {
                $q->select('id', 'name')
                  ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
                  ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)])
                  ->with('profile:user_id,city,headline,company_name');
            },
        ]);

        // A few more packages from the same pro (or category) to keep browsing.
        $more = Package::active()
            ->where('id', '!=', $package->id)
            ->where(function ($q) use ($package) {
                $q->where('user_id', $package->user_id)
                  ->orWhere('category_id', $package->category_id);
            })
            ->latest()
            ->limit(3)
            ->get();

        // Track "Recently Viewed" for the Package Service Search rail (newest first, max 8).
        $recent = collect(session('recent_packages', []))
            ->prepend($package->id)->unique()->take(8)->values()->all();
        session(['recent_packages' => $recent]);

        return view('public.package-show', compact('package', 'more'));
    }
}
