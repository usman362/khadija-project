<?php

namespace App\Http\Controllers\Client;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Client — "Find Gigs".
 *
 * The client-side mirror of the professional Bidding Board. Where a professional
 * browses open client jobs to bid on, a CLIENT browses professional GIG LISTINGS
 * (service packages) to book or message. Each card is grounded on a REAL supplier
 * (name + live review average / count); the gig title, category, price band,
 * location and image are representative until pros publish structured gig listings.
 */
class FindGigsController extends Controller
{
    /** Representative service-package catalogue, mapped onto real categories. */
    private const CATALOG = [
        ['Wedding Photography Package',   'Photography',    'SSR', 2125, 2500,  'Full-day ceremony & reception coverage with an edited online gallery and a second shooter.'],
        ['Corporate Event Catering',      'Catering',       'SSR', 3200, 5200,  'Plated or buffet service for conferences and galas, with dietary options included.'],
        ['DJ & Live Sound Setup',         'DJ & Music',     'SSR',  900, 1600,  'Professional DJ, MC and a full sound system for receptions and parties.'],
        ['Floral Design & Décor',         'Floral & Décor', 'SSR',  800, 2400,  'Custom centerpieces, arches and full venue styling for weddings and events.'],
        ['Full-Service Event Planning',   'Event Planning', 'MSR', 3400, 6000,  'End-to-end planning: vendor sourcing, timeline, budget and day-of coordination.'],
        ['Cinematic Videography',         'Videography',    'SSR', 1800, 3200,  'A highlight film and full-length edit, with drone coverage available on request.'],
        ['Venue Styling & Lighting',      'Lighting',       'SSR', 1200, 2600,  'Ambient uplighting, dance-floor wash and custom monogram projection.'],
        ['Photo + Video + DJ Bundle',     'Photography',    'MSR', 5525, 6500,  'A coordinated photo, video and music team for a seamless celebration.'],
        ['Grand Gala Production',         'Event Planning', 'ESR',15300,18000,  'Full production for large galas: catering, AV, lighting and event staffing.'],
        ['Birthday Party Décor',          'Floral & Décor', 'SSR',  510,  900,  'Themed balloon installs, backdrops and table styling with setup and teardown.'],
        ['Conference AV & Staging',       'Lighting',       'MSR', 4420, 5200,  'Stage, screens, sound and technicians for multi-day conferences.'],
        ['Intimate Wedding Package',      'Photography',    'SSR', 1400, 2000,  'Photography and coordination for elopements and micro-weddings.'],
    ];

    private const CITIES = [
        'Miami, FL', 'Austin, TX', 'Los Angeles, CA', 'Seattle, WA', 'Chicago, IL', 'Tampa, FL',
        'New York, NY', 'Dallas, TX', 'Denver, CO', 'Atlanta, GA', 'Nashville, TN', 'San Diego, CA',
    ];

    private const STOCK = [
        'photo-1519741497674-611481863552', 'photo-1511795409834-ef04bbd61622',
        'photo-1530103862676-de8c9debad1d', 'photo-1492684223066-81342ee5ff30',
        'photo-1464366400600-7168b8af9bc3', 'photo-1519225421980-715cb0215aed',
    ];

    public function index(Request $request): View
    {
        // Real suppliers with a live review average + count (same aggregate /browse uses).
        $suppliers = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::SUPPLIER->value))
            ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
            ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)])
            ->orderBy('id')
            ->get(['id', 'name']);

        // Build one gig-listing card per catalogue entry, backed by a real supplier.
        $gigs = collect(self::CATALOG)->values()->map(function ($tpl, $i) use ($suppliers) {
            [$title, $cat, $type, $low, $high, $blurb] = $tpl;
            $pro = $suppliers->get($i % max($suppliers->count(), 1));

            // Real rating where the pro has reviews, else a representative 4.6–5.0.
            $avg   = $pro?->reviews_avg ? round((float) $pro->reviews_avg, 1) : round(4.6 + (($i % 5) * 0.1), 1);
            $count = $pro?->reviews_count ?: (12 + ($i * 7) % 40);

            return [
                'id'      => $i + 1,
                'title'   => $title,
                'type'    => $type,
                'featured'=> $type === 'ESR',
                'pro'     => $pro?->name ?: 'Verified Professional',
                'pro_id'  => $pro?->id,
                'cat'     => $cat,
                'loc'     => self::CITIES[$i % count(self::CITIES)],
                'desc'    => $blurb,
                'price'   => '$' . number_format($low) . ' – $' . number_format($high),
                'price_lo'=> $low,
                'from'    => '$' . number_format($low),
                'rating'  => $avg,
                'reviews' => $count,
                'img'     => self::STOCK[$i % count(self::STOCK)],
                'img_url' => 'https://images.unsplash.com/' . self::STOCK[$i % count(self::STOCK)] . '?w=320&q=70&auto=format&fit=crop',
                'real'    => false,
                'detail_url' => route('client.search.index', ['q' => $cat]),
            ];
        });

        // Real published packages from pros — surfaced ahead of the representative set.
        $realGigs = \App\Models\Package::active()
            ->with(['category:id,name', 'user' => function ($q) {
                $q->select('id', 'name')
                  ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
                  ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)])
                  ->with('profile:user_id,city');
            }])
            ->latest()
            ->get()
            ->values()
            ->map(function ($pkg, $i) {
                $hero = $pkg->heroUrls(1)[0] ?? null;
                return [
                    'id'       => 'pkg-' . $pkg->id,
                    'title'    => $pkg->title,
                    'type'     => 'SSR',
                    'featured' => false,
                    'pro'      => $pkg->user?->name ?: 'Verified Professional',
                    'pro_id'   => $pkg->user_id,
                    'cat'      => $pkg->category?->name ?: 'Services',
                    'loc'      => $pkg->user?->profile?->city ?: 'Location on request',
                    'desc'     => $pkg->description ?: '',
                    'price'    => $pkg->priceLabel(),
                    'price_lo' => $pkg->price,
                    'from'     => $pkg->priceLabel(),
                    'rating'   => $pkg->user?->reviews_avg ? round((float) $pkg->user->reviews_avg, 1) : null,
                    'reviews'  => (int) ($pkg->user?->reviews_count ?? 0),
                    'img'      => self::STOCK[$i % count(self::STOCK)],
                    'img_url'  => $hero ?: 'https://images.unsplash.com/' . self::STOCK[$i % count(self::STOCK)] . '?w=320&q=70&auto=format&fit=crop',
                    'real'     => true,
                    'detail_url' => route('public.package', $pkg->slug),
                ];
            });

        $gigs = $realGigs->concat($gigs)->values();
        $allGigs = $gigs;

        // ── Filters (all query-string, all optional) ──
        $type     = strtoupper((string) $request->query('type', ''));
        $catFilter= trim((string) $request->query('category', ''));
        $q        = trim((string) $request->query('q', ''));
        $loc      = trim((string) $request->query('loc', ''));
        $budgetMin= (int) $request->query('budget_min', 0);
        $sort     = (string) $request->query('sort', 'recommended');

        if (in_array($type, ['SSR', 'MSR', 'ESR'], true)) {
            $gigs = $gigs->where('type', $type);
        }
        if ($catFilter !== '') {
            $gigs = $gigs->where('cat', $catFilter);
        }
        if ($q !== '') {
            $gigs = $gigs->filter(fn ($g) => Str::contains(Str::lower($g['title'] . ' ' . $g['desc'] . ' ' . $g['pro']), Str::lower($q)));
        }
        if ($loc !== '') {
            $gigs = $gigs->filter(fn ($g) => Str::contains(Str::lower($g['loc']), Str::lower($loc)));
        }
        if ($budgetMin > 0) {
            $gigs = $gigs->where('price_lo', '>=', $budgetMin);
        }

        $gigs = match ($sort) {
            'rating'    => $gigs->sortByDesc('rating'),
            'price_low' => $gigs->sortBy('price_lo'),
            'price_high'=> $gigs->sortByDesc('price_lo'),
            default     => $gigs, // 'recommended' — catalogue order
        };

        $gigs = $gigs->values();

        // Type counts from the FULL (unfiltered) merged set for the tab pills.
        $counts = [
            'all' => $allGigs->count(),
            'SSR' => $allGigs->where('type', 'SSR')->count(),
            'MSR' => $allGigs->where('type', 'MSR')->count(),
            'ESR' => $allGigs->where('type', 'ESR')->count(),
        ];

        // Real categories for the filter dropdown (skip seed "Test" junk).
        $categories = Category::active()
            ->orderBy('sort_order')->orderBy('name')
            ->pluck('name')
            ->reject(fn ($n) => Str::startsWith(Str::lower($n), 'test'))
            ->unique()->values();

        // Right-rail insights, computed from the catalogue.
        $catalog = collect(self::CATALOG);
        $topCat = $catalog->countBy(fn ($t) => $t[1])->sortDesc()->keys()->first() ?: 'Photography';
        $avgFrom = (int) round($catalog->avg(fn ($t) => $t[3]));

        return view('client.find-gigs.index', [
            'gigs'       => $gigs,
            'counts'     => $counts,
            'categories' => $categories,
            'filters'    => compact('type', 'catFilter', 'q', 'loc', 'budgetMin', 'sort'),
            'insights'   => [
                ['Most Booked',      $topCat, '🔥'],
                ['Trending',         'Wedding Photography', '📈'],
                ['Top Rated Field',  'Event Décor', '🏆'],
                ['Avg. Starting At', '$' . number_format($avgFrom), '💰'],
            ],
        ]);
    }
}
