<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Package;
use App\Models\SavedSearch;
use App\Support\Availability;
use App\Support\Occasions;
use App\Support\ResponseStats;
use App\Support\StateMatching;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    /** Budget-slider ends. The top one is open — "$20,000+" means no ceiling. */
    public const BUDGET_FLOOR = 1000;

    public const BUDGET_CEILING = 20000;

    /** Guest-count buckets for the left rail. Value = the minimum capacity asked for. */
    public const GUEST_BUCKETS = [
        50   => 'Up to 50 guests',
        100  => '50 – 100 guests',
        200  => '100 – 200 guests',
        350  => '200 – 350 guests',
        500  => '350+ guests',
    ];

    /** How many cards per page the reader may ask for. */
    public const PER_PAGE = [12, 24, 48];

    /** Compare tray size — the rail says "up to 3", so three it is everywhere. */
    public const COMPARE_MAX = 3;

    /**
     * Public "Package Service Search" — pros' multi-service bundles that a client
     * browses (NOT MSRs, which clients post and pros bid on). Supports the
     * Service-Mix Matcher (AND-match), the five other filters on Peter's rail,
     * and sorting.
     */
    public function index(Request $request): View
    {
        $f = $this->readFilters($request);

        $base = $this->query($request, $f);

        $perPage = in_array((int) $request->query('per_page'), self::PER_PAGE, true)
            ? (int) $request->query('per_page')
            : self::PER_PAGE[0];

        $packages = $base->paginate($perPage)->withQueryString();

        /*
         * Left-rail service counts (ignoring the current service selection).
         *
         * Scoped to the viewer for the same reason the list is. These numbers
         * are a promise about what the filter will return, and the filter runs
         * against the R38-scoped list — so counted platform-wide they offered a
         * Maryland client "Photography (14)" and delivered two.
         *
         * They also honour every OTHER filter now. With six filters on the rail,
         * counts that ignored five of them told a client "Photography (34)" when
         * their date and budget left four.
         */
        $countBase = fn () => $this->query($request, array_merge($f, ['selected' => [], 'sort' => 'relevant']));

        $serviceCounts = [];
        $counted = $countBase()->get(['packages.id', 'packages.services']);
        foreach (self::SERVICES as $svc) {
            $serviceCounts[$svc] = $counted
                ->filter(fn ($p) => in_array($svc, $p->services ?? [], true))
                ->count();
        }

        // Right-rail "Where Packages Are Available" — real counts by the
        // professional's city, with the state, because "Washington" alone is
        // three different places in the launch area.
        //
        // Not capped: it used to take(6), which silently dropped four cities, so
        // the list added up to less than the package count printed at the top.
        // Scoped for the same reason as everything else here — the panel is
        // headed "Where Packages Are Available", so listing a city whose
        // packages this client cannot book answers the question wrongly.
        $availability = $this->query($request, array_merge($f, ['sort' => 'relevant']))
            ->with('user.profile:user_id,city,state')
            ->get()
            ->groupBy(function ($p) {
                $city = $p->user?->profile?->city;
                $state = $p->user?->profile?->state ?: $p->state;

                return $city ? trim($city . ($state ? ', ' . $state : '')) : 'Other';
            })
            ->map->count()
            ->sortDesc();

        // Recently viewed (session ids, newest first).
        $recentIds = collect(session('recent_packages', []))->take(4);
        $recent = $recentIds->isNotEmpty()
            ? Package::active()
                ->tap(fn ($qr) => StateMatching::scopeForViewer($qr, $request->user()))
                ->with('user:id,name', 'user.profile:user_id,company_name', 'category:id,name')
                ->whereIn('id', $recentIds)->get()
                ->sortBy(fn ($p) => $recentIds->search($p->id))->values()
            : collect();

        return view('public.packages-index', [
            'packages'       => $packages,
            'total'          => $packages->total(),
            'services'       => self::SERVICES,
            'serviceCounts'  => $serviceCounts,
            'occasions'      => Occasions::labels(),
            'states'         => config('geo.allowed_states', []),
            'guestBuckets'   => self::GUEST_BUCKETS,
            'perPageOptions' => self::PER_PAGE,
            'perPage'        => $perPage,
            'budgetFloor'    => self::BUDGET_FLOOR,
            'budgetCeiling'  => self::BUDGET_CEILING,
            'compareMax'     => self::COMPARE_MAX,
            'availability'   => $availability,
            'recent'         => $recent,
            'savedSearches'  => $this->savedSearches($request),
            'savedIds'       => $this->savedPackageIds($request),
            'card'           => $this->cardFacts($packages->getCollection(), $f['date']),
            'filters'        => $f,
        ]);
    }

    /**
     * Every filter on Peter's rail, read once so the list, the counts and the
     * "Where packages are available" panel can never disagree about what the
     * client asked for.
     *
     * @return array<string, mixed>
     */
    private function readFilters(Request $request): array
    {
        $selected = collect((array) $request->input('services', []))
            ->map(fn ($s) => trim((string) $s))
            ->filter(fn ($s) => in_array($s, self::SERVICES, true))
            ->values();

        $occasion = trim((string) $request->input('event_type', ''));

        $date = trim((string) $request->input('date', ''));
        try {
            // A date in the past cannot be an event date, and a malformed one is
            // not a filter — both are dropped rather than returning nothing.
            $date = $date !== '' && Carbon::parse($date)->startOfDay()->gte(now()->startOfDay())
                ? Carbon::parse($date)->toDateString()
                : '';
        } catch (\Throwable) {
            $date = '';
        }

        $min = (int) $request->input('budget_min', self::BUDGET_FLOOR);
        $max = (int) $request->input('budget_max', self::BUDGET_CEILING);
        $min = max(self::BUDGET_FLOOR, min($min, self::BUDGET_CEILING));
        $max = max($min, min($max, self::BUDGET_CEILING));

        return [
            'selected'   => $selected->all(),
            'provider'   => 'all',
            'q'          => trim((string) $request->input('q', '')),
            'sort'       => (string) $request->input('sort', 'relevant'),
            'event_type' => $occasion !== '' && Occasions::known($occasion) ? $occasion : '',
            'location'   => trim((string) $request->input('location', '')),
            'scope'      => $request->input('scope') === 'city' ? 'city' : 'state',
            'budget_min' => $min,
            'budget_max' => $max,
            'date'       => $date,
            'guests'     => array_key_exists((int) $request->input('guests'), self::GUEST_BUCKETS)
                ? (int) $request->input('guests')
                : 0,
            // The heart has to lead somewhere or it is a save into a drawer
            // nobody can open.
            'saved'      => (bool) $request->input('saved'),
            'view'       => $request->input('view') === 'grid' ? 'grid' : 'list',
        ];
    }

    /** The one query every number on this page is counted from. */
    private function query(Request $request, array $f)
    {
        $query = Package::active()
            ->with([
                'category:id,name,slug',
                'user' => fn ($u) => $u->select('id', 'name')
                    ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
                    ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)])
                    ->with('profile:user_id,city,state,zip_code,company_name'),
            ])
            // Rule R38, Option B — a package is bookable only in its owner's own
            // state, so the catalogue shows a client only what they could
            // actually buy. A signed-out visitor sees everything; the gate is on
            // transacting, and there is no pair to match without a viewer.
            ->tap(fn ($qr) => StateMatching::scopeForViewer($qr, $request->user()))
            ->when($f['q'] !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('title', 'like', "%{$f['q']}%")
                ->orWhere('description', 'like', "%{$f['q']}%")))
            ->when($f['event_type'] !== '', fn ($qr) => Occasions::apply($qr, $f['event_type']));

        // AND-match every selected service against the JSON services column.
        foreach ($f['selected'] as $svc) {
            $query->whereJsonContains('services', $svc);
        }

        // 3. Location. Deliberately not a mile radius: R38 means a package is
        // bookable only inside its professional's own state, so "within 50
        // miles" would describe a marketplace this one is not — the same call
        // the professional profile made on row 235. The scope control offers
        // the two answers the data can actually give.
        if ($f['location'] !== '') {
            $needle = $f['location'];
            $asState = $this->stateCode($needle);

            $query->where(function ($w) use ($needle, $asState, $f) {
                if ($asState !== null) {
                    $w->orWhere('packages.state', $asState);
                }

                $w->orWhereHas('user.profile', function ($p) use ($needle, $f) {
                    $p->where('zip_code', $needle);

                    if ($f['scope'] === 'city') {
                        $p->orWhere('city', 'like', "%{$needle}%");
                    } else {
                        // "Anywhere in this state" — the state the named city
                        // sits in, so typing "Baltimore" returns Maryland.
                        $p->orWhereIn('state', function ($sub) use ($needle) {
                            $sub->from('user_profiles')->select('state')
                                ->where('city', 'like', "%{$needle}%")
                                ->whereNotNull('state');
                        });
                    }
                });
            });
        }

        // 4. Budget. The top of the slider is open, so a package above the
        // ceiling is only excluded when the client pulled the handle down.
        $query->where('price', '>=', $f['budget_min']);
        if ($f['budget_max'] < self::BUDGET_CEILING) {
            $query->where('price', '<=', $f['budget_max']);
        }

        // 5. Availability. "Not committed on GigResource that day" — read from
        // the same calendar My Gigs reads (Availability), because a second
        // source would eventually contradict the professional's own diary.
        if ($f['date'] !== '') {
            $query->whereNotIn('user_id', $this->committedOn($f['date']));
        }

        // Favourites. Signed out there is nothing hearted, so the flag would
        // empty the page rather than explain itself — it is ignored instead.
        if (! empty($f['saved']) && $request->user()) {
            $query->whereIn('packages.id', $this->savedPackageIds($request) ?: [0]);
        }

        // 6. Guest count. Compared against the parsed number, never the prose.
        // A package that never stated a capacity is not one for zero guests, so
        // it stays in the list rather than being silently dropped.
        if ($f['guests'] > 0) {
            $query->where(fn ($w) => $w
                ->whereNull('guests_max')
                ->orWhere('guests_max', '>=', $f['guests']));
        }

        return $query
            ->when($f['sort'] === 'price_low', fn ($qr) => $qr->orderBy('price'))
            ->when($f['sort'] === 'price_high', fn ($qr) => $qr->orderByDesc('price'))
            ->when($f['sort'] === 'savings', fn ($qr) => $qr->orderByDesc('savings_pct'))
            ->when($f['sort'] === 'newest', fn ($qr) => $qr->latest())
            // 'relevant' (default): curated sort_order, then freshest.
            ->when(! in_array($f['sort'], ['price_low', 'price_high', 'savings', 'newest'], true),
                fn ($qr) => $qr->orderByDesc('sort_order')->latest());
    }

    /** Professional ids already committed on a date, per their own calendar. */
    private function committedOn(string $date): array
    {
        $day = Carbon::parse($date);
        $days = max(1, (int) now()->startOfDay()->diffInDays($day, false) + 1);

        // Only professionals who could otherwise appear need checking.
        $ids = Package::active()->distinct()->pluck('user_id');

        return \App\Models\User::whereIn('id', $ids)->get()
            ->filter(fn ($pro) => in_array($date, Availability::busyDates($pro, $days), true))
            ->pluck('id')
            ->all();
    }

    /**
     * The two figures the card states about a professional, for the whole page
     * at once: bookings delivered, and how fast they reply.
     *
     * Both are null when there is nothing behind them. A new professional gets
     * no "0 bookings" and no "responds in —"; the card simply says less about
     * them, which is the truth.
     *
     * The card's third line — "Available on <date>" — needs nothing here: the
     * list is already filtered to professionals free that day, so the view
     * prints the date it filtered on rather than asking the calendar twice and
     * risking two different answers.
     */
    private function cardFacts($packages, string $date): array
    {
        $proIds = collect($packages)->pluck('user_id')->filter()->unique()->values();

        if ($proIds->isEmpty()) {
            return ['bookings' => [], 'responds' => []];
        }

        // "Bookings" on a review site means work delivered, so only completed
        // ones count. A booking sitting at `requested` is not a track record.
        $bookings = Booking::whereIn('supplier_id', $proIds)
            ->where('status', 'completed')
            ->selectRaw('supplier_id, COUNT(*) as c')
            ->groupBy('supplier_id')
            ->pluck('c', 'supplier_id');

        $responds = collect(ResponseStats::forMany($proIds->all()))
            ->map(fn ($s) => $s['hours']);

        return [
            'bookings' => $bookings->all(),
            'responds' => $responds->all(),
        ];
    }

    /** "MD" for "MD", "Maryland" or "maryland "; null for anything else. */
    private function stateCode(string $needle): ?string
    {
        $states = config('geo.allowed_states', []);
        $upper = strtoupper(trim($needle));

        if (array_key_exists($upper, $states)) {
            return $upper;
        }

        foreach ($states as $code => $name) {
            if (strcasecmp(trim($name), trim($needle)) === 0) {
                return $code;
            }
        }

        return null;
    }

    /** @return \Illuminate\Support\Collection<int, SavedSearch> */
    private function savedSearches(Request $request)
    {
        return $request->user()
            ? $request->user()->savedSearches()->forSurface('packages')->latest()->limit(5)->get()
            : collect();
    }

    /** @var array<int, int>|null Memoised — the list, the filter and the card all ask. */
    private ?array $savedIds = null;

    /** Package ids this client has hearted, so the card can show it filled. */
    private function savedPackageIds(Request $request): array
    {
        return $this->savedIds ??= $request->user()?->hasRole('client')
            ? $request->user()->savedPackages()->pluck('packages.id')->all()
            : [];
    }

    /**
     * Side-by-side comparison of up to three packages — the rail's "Compare
     * Packages" tray, opened.
     */
    public function compare(Request $request): View
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($i) => (int) trim($i))
            ->filter()
            ->unique()
            ->take(self::COMPARE_MAX);

        $packages = $ids->isEmpty() ? collect() : Package::active()
            ->tap(fn ($qr) => StateMatching::scopeForViewer($qr, $request->user()))
            ->with(['category:id,name,slug', 'user:id,name', 'user.profile:user_id,city,state,company_name'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($p) => $ids->search($p->id))
            ->values();

        return view('public.packages-compare', [
            'packages'   => $packages,
            'services'   => self::SERVICES,
            'compareMax' => self::COMPARE_MAX,
        ]);
    }

    /** Save the current filter set (the rail's "Save Search"). */
    public function saveSearch(Request $request): RedirectResponse
    {
        $f = $this->readFilters($request);

        $params = array_filter([
            'services'   => $f['selected'],
            'event_type' => $f['event_type'],
            'q'          => $f['q'],
            'location'   => $f['location'],
            'scope'      => $f['location'] !== '' ? $f['scope'] : '',
            'budget_min' => $f['budget_min'] > self::BUDGET_FLOOR ? $f['budget_min'] : '',
            'budget_max' => $f['budget_max'] < self::BUDGET_CEILING ? $f['budget_max'] : '',
            'guests'     => $f['guests'] ?: '',
            'sort'       => $f['sort'] !== 'relevant' ? $f['sort'] : '',
        ], fn ($v) => $v !== '' && $v !== [] && $v !== null);

        if ($params === []) {
            return back()->with('error', 'Choose at least one filter before saving the search.');
        }

        // The date is deliberately NOT saved. A saved search is reused weeks
        // later, and a stored date would quietly filter to a day that has been
        // and gone.
        $request->user()->savedSearches()->create([
            'surface' => 'packages',
            'label'   => $this->describeSearch($f),
            'params'  => $params,
        ]);

        return back()->with('status', 'Search saved.');
    }

    public function deleteSearch(Request $request, SavedSearch $savedSearch): RedirectResponse
    {
        abort_unless($savedSearch->user_id === $request->user()->id, 403);

        $savedSearch->delete();

        return back()->with('status', 'Saved search removed.');
    }

    /** A name a client will recognise a week later, built from what they chose. */
    private function describeSearch(array $f): string
    {
        $parts = [];

        if ($f['selected'] !== []) {
            $parts[] = implode(' + ', array_slice($f['selected'], 0, 3))
                . (count($f['selected']) > 3 ? ' +' . (count($f['selected']) - 3) : '');
        }

        if ($f['event_type'] !== '') {
            $parts[] = $f['event_type'];
        }

        if ($f['location'] !== '') {
            $parts[] = 'in ' . $f['location'];
        }

        if ($f['budget_min'] > self::BUDGET_FLOOR || $f['budget_max'] < self::BUDGET_CEILING) {
            $parts[] = '$' . number_format($f['budget_min']) . '–$' . number_format($f['budget_max']);
        }

        if ($f['guests'] > 0) {
            $parts[] = self::GUEST_BUCKETS[$f['guests']];
        }

        return \Illuminate\Support\Str::limit(implode(' · ', $parts) ?: 'All packages', 118);
    }

    /** Heart / un-heart a package. */
    public function toggleSaved(Request $request, Package $package): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->hasRole('client'), 403);

        if ($user->savedPackages()->where('packages.id', $package->id)->exists()) {
            $user->savedPackages()->detach($package->id);

            return back()->with('status', 'Removed from your favourites.');
        }

        $user->savedPackages()->syncWithoutDetaching([$package->id]);

        return back()->with('status', 'Saved to your favourites.');
    }

    public function show(Package $package): View
    {
        /*
         * Only an active package is publicly visible — draft/paused/archived 404.
         *
         * With one exception: its own professional. "Preview as Client" on the
         * My Packages shelf is how they check a package before it goes live, and
         * a preview that only works once the thing is already published is not a
         * preview. The page states plainly that it is not visible to anyone else.
         */
        $live = ($package->status ?? ($package->is_active ? 'active' : 'draft')) === 'active';
        $owner = auth()->id() === $package->user_id;

        abort_unless($live || $owner, 404);

        $package->load([
            'category:id,name,slug',
            'user' => function ($q) {
                $q->select('id', 'name')
                  ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
                  ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)])
                  ->with('profile:user_id,city,state,headline,company_name');
            },
        ]);

        // A few more packages from the same pro (or category) to keep browsing.
        // Rule R38 — "keep browsing" has to lead somewhere bookable. Suggesting
        // a package from a state this client cannot transact in is a dead end
        // dressed as a recommendation.
        $more = Package::active()
            ->tap(fn ($qr) => StateMatching::scopeForViewer($qr, auth()->user()))
            ->where('id', '!=', $package->id)
            ->where(function ($q) use ($package) {
                $q->where('user_id', $package->user_id)
                  ->orWhere('category_id', $package->category_id);
            })
            ->latest()
            ->limit(3)
            ->get();

        // Track "Recently Viewed" for the Package Service Search rail (newest
        // first, max 8). Not while previewing: the rail is a public surface and
        // would then offer a package nobody but the owner can open.
        if ($live) {
            $recent = collect(session('recent_packages', []))
                ->prepend($package->id)->unique()->take(8)->values()->all();
            session(['recent_packages' => $recent]);
        }

        return view('public.package-show', compact('package', 'more') + [
            // Drives the preview banner. False for every actual client.
            'preview' => ! $live,
        ]);
    }
}
