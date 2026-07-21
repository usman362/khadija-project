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

        $q        = trim((string) $request->query('q', ''));
        $sort     = (string) $request->query('sort', 'relevant');
        $occasion = trim((string) $request->query('event_type', ''));

        // Packages are solo-only (Team/Co-Op combined-force removed platform-wide).
        $base = Package::active()
            ->with([
                'category:id,name,slug',
                'user' => fn ($u) => $u->select('id', 'name')
                    ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
                    ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)])
                    ->with('profile:user_id,city,state'),
            ])
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%")))
            ->when($occasion !== '' && \App\Support\Occasions::known($occasion),
                fn ($qr) => \App\Support\Occasions::apply($qr, $occasion));

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

        // Left-rail service counts (ignoring the current service selection).
        $serviceCounts = [];
        foreach (self::SERVICES as $svc) {
            $serviceCounts[$svc] = Package::active()
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
            'recent'         => $recent,
            'filters'        => [
                'selected' => $selected->all(),
                'provider' => 'all',
                'q'        => $q,
                'sort'     => $sort,
                'event_type' => $occasion,
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
