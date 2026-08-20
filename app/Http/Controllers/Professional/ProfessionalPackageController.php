<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\PackageController;
use App\Models\Category;
use App\Models\Package;
use App\Services\ImagePipelineService;
use App\Support\PackageProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Professional "Packages" — a pro bundles TWO OR MORE of their own services
 * into a fixed offering clients browse and book in the Package Service Search.
 * Delivered solo (one multi-service pro). This is NOT an MSR (client gig-post
 * pros bid on) — the two are kept separate.
 */
class ProfessionalPackageController extends Controller
{
    /** Occasions a package can target (Create-a-Package step 1). */
    public const EVENT_TYPES = [
        'Weddings', 'Engagements', 'Anniversaries', 'Corporate Events',
        'Birthday Parties', 'Social Events', 'Other',
    ];

    /**
     * The five states a package occupies on the My Packages shelf, in the order
     * the tiles and the tabs show them. PackageProgress decides which one a
     * package is in; see the note there on why "ready" is derived.
     */
    public const SHELF = [
        'published'   => ['Published',        'Visible in Package Search'],
        'draft'       => ['Drafts',           'Still being completed'],
        'ready'       => ['Ready to Publish',  'Finished and ready to go live'],
        'unpublished' => ['Unpublished',      'Completed but hidden'],
        'archived'    => ['Archived',         'Kept for your records'],
    ];

    public function index(Request $request): View
    {
        $mine = fn () => Package::where('user_id', $request->user()->id);

        $q    = trim((string) $request->query('q', ''));
        $tab  = array_key_exists((string) $request->query('tab'), self::SHELF)
            ? (string) $request->query('tab')
            : 'all';
        $sort = in_array($request->query('sort'), ['oldest', 'price_high', 'price_low', 'title'], true)
            ? (string) $request->query('sort')
            : 'newest';

        /*
         * The tiles count states, and a state is decided in PHP (a draft with
         * every step finished is "ready"), so the whole shelf is loaded once and
         * grouped rather than counted with five queries the database cannot
         * answer correctly.
         *
         * A professional's own shelf is small by construction — these are
         * offerings they wrote by hand, not a feed.
         */
        $all = $mine()->with('category:id,name')->get();
        $byState = $all->groupBy(fn ($p) => PackageProgress::shelfState($p));

        $counts = ['all' => $all->count()];
        foreach (array_keys(self::SHELF) as $state) {
            $counts[$state] = $byState->get($state, collect())->count();
        }

        $shown = $tab === 'all' ? $all : $byState->get($tab, collect());

        if ($q !== '') {
            $needle = Str::lower($q);
            $shown = $shown->filter(fn ($p) => str_contains(Str::lower($p->title), $needle)
                || str_contains(Str::lower((string) $p->description), $needle)
                || collect($p->services ?: [])->contains(fn ($s) => str_contains(Str::lower($s), $needle)));
        }

        $shown = match ($sort) {
            'oldest'     => $shown->sortBy('created_at'),
            'price_high' => $shown->sortByDesc('price'),
            'price_low'  => $shown->sortBy('price'),
            'title'      => $shown->sortBy(fn ($p) => Str::lower($p->title)),
            default      => $shown->sortByDesc('created_at'),
        };
        $shown = $shown->values();

        $perPage = 10;
        $page = max(1, (int) $request->query('page', 1));
        $packages = new \Illuminate\Pagination\LengthAwarePaginator(
            $shown->forPage($page, $perPage)->values(),
            $shown->count(),
            $perPage,
            $page,
            ['path' => route('professional.packages.index'), 'query' => $request->query()],
        );

        return view('professional.packages.index', [
            'packages' => $packages,
            'counts'   => $counts,
            'shelf'    => self::SHELF,
            'filters'  => ['q' => $q, 'tab' => $tab, 'sort' => $sort],
            // The rail's "Duplicate a Package" needs something to duplicate.
            'duplicatable' => $all->sortByDesc('created_at')->take(8)->values(),
        ]);
    }

    /**
     * Copy a package as a fresh draft.
     *
     * Always a draft, never live: a duplicate is a starting point, and
     * publishing a copy the professional has not read yet would put two nearly
     * identical offerings in front of the same client.
     *
     * The images are shared by reference rather than re-processed — the copy
     * points at the same stored files. Deleting the copy must therefore not
     * delete them, which is why destroy() checks first.
     */
    public function duplicate(Request $request, Package $package): RedirectResponse
    {
        abort_unless($package->user_id === $request->user()->id, 403);

        $copy = $package->replicate(['slug', 'created_at', 'updated_at']);
        // 60 is the form's own limit, so a copy is never longer than a title
        // the professional would be allowed to type.
        $copy->title = Str::limit($package->title . ' (Copy)', 60, '');
        $copy->slug = Str::slug($copy->title) . '-' . Str::lower(Str::random(5));
        $copy->status = 'draft';
        $copy->is_active = false;
        $copy->save();

        return redirect()
            ->route('professional.packages.edit', $copy)
            ->with('status', 'Copied. This is a draft — nothing is live until you publish it.');
    }

    public function create(): View
    {
        return view('professional.packages.create', $this->formData());
    }

    /** Shared reference data for the Create/Edit package form. */
    private function formData(): array
    {
        return [
            'categories'  => Category::getNestedDropdownList(),
            'serviceList' => PackageController::SERVICES,
            'eventTypes'  => self::EVENT_TYPES,
        ];
    }

    public function store(Request $request, ImagePipelineService $pipeline): RedirectResponse
    {
        $data = $this->validated($request);

        $data['user_id'] = $request->user()->id;
        $data['slug'] = Str::slug($data['title']) . '-' . Str::lower(Str::random(5));
        // Option B (Peter, approved): a package is advertised in the
        // professional's own state only. Derived, not submitted — a free-text
        // field here let a professional claim states they cannot serve, and the
        // seed data had an LA professional "serving NY, NJ, CT".
        $data['serves_regions'] = $request->user()->profile?->state;
        $data = array_merge($data, $this->richFields($request));

        $images = $this->syncImages($request, $pipeline, $request->user()->id, []);
        if ($images !== null) {
            $data['images'] = $images;
        }

        Package::create($data);

        return redirect()->route('professional.packages.index')->with('status', 'Package created.');
    }

    public function edit(Request $request, Package $package): View
    {
        abort_unless($package->user_id === $request->user()->id, 403);

        return view('professional.packages.create', array_merge($this->formData(), ['package' => $package]));
    }

    public function update(Request $request, Package $package, ImagePipelineService $pipeline): RedirectResponse
    {
        abort_unless($package->user_id === $request->user()->id, 403);

        $data = $this->validated($request);
        $data = array_merge($data, $this->richFields($request));

        $images = $this->syncImages($request, $pipeline, $request->user()->id, (array) $package->images);
        if ($images !== null) {
            $data['images'] = $images;
        }

        // Re-derived on every save, so moving state moves the package with it.
        $data['serves_regions'] = $request->user()->profile?->state;

        $package->update($data);

        return redirect()->route('professional.packages.index')->with('status', 'Package updated.');
    }

    public function destroy(Request $request, Package $package, ImagePipelineService $pipeline): RedirectResponse
    {
        abort_unless($package->user_id === $request->user()->id, 403);

        /*
         * Duplicates share their images with the original by reference rather
         * than re-processing them, so deleting either copy would otherwise take
         * the pictures out from under the other one. Only delete a file no
         * other package of theirs still points at.
         */
        $stillUsed = Package::where('user_id', $request->user()->id)
            ->where('id', '!=', $package->id)
            ->pluck('images')
            ->flatMap(fn ($set) => collect($set ?: [])->pluck('hero'))
            ->filter()
            ->all();

        foreach ((array) $package->images as $img) {
            if (! in_array($img['hero'] ?? null, $stillUsed, true)) {
                $pipeline->delete($img);
            }
        }
        $package->delete();

        return back()->with('status', 'Package removed.');
    }

    /**
     * Build images[] from kept existing sets + newly uploaded files, honouring
     * removals and the chosen cover. The cover is stored FIRST (heroUrls() reads
     * array order) and flagged featured. Returns null to leave images untouched.
     *
     * Cover / remove ids: existing images are "e{originalIndex}", new uploads
     * are "n{fileIndex}".
     *
     * @return array<int,array>|null
     */
    private function syncImages(Request $request, ImagePipelineService $pipeline, int $userId, array $existing): ?array
    {
        $remove = array_map('strval', (array) $request->input('remove_images', []));
        $cover  = (string) $request->input('cover', '');
        $touched = $request->hasFile('gallery_images') || $request->hasFile('cover_image') || ! empty($remove);

        if (! $touched) {
            return null;
        }

        $final = [];
        $coverKey = null;

        foreach ($existing as $i => $img) {
            if (in_array('e' . $i, $remove, true)) {
                $pipeline->delete($img);
                continue;
            }
            $img['featured'] = false;
            $final[] = $img;
            if ($cover === 'e' . $i) {
                $coverKey = count($final) - 1;
            }
        }

        $files = array_values(array_filter((array) $request->file('gallery_images', [])));
        if ($request->hasFile('cover_image')) {
            $files[] = $request->file('cover_image');
        }
        foreach ($files as $j => $file) {
            $set = $pipeline->process($file, 'packages/' . $userId);
            if ($set) {
                $set['featured'] = false;
                $final[] = $set;
                if ($cover === 'n' . $j) {
                    $coverKey = count($final) - 1;
                }
            }
        }

        if (empty($final)) {
            return [];
        }

        if ($coverKey === null) {
            $coverKey = 0;
        }
        $coverImg = array_splice($final, $coverKey, 1)[0];
        $coverImg['featured'] = true;
        array_unshift($final, $coverImg);

        return array_values($final);
    }

    /**
     * Two rule sets, because "Save as Draft" means saving something unfinished.
     *
     * It used to mean one set: title, two services and a price were required
     * whichever button was pressed, so a professional who wanted to stop
     * halfway could not — the form simply refused. Step 2 of "How Publishing
     * Works" says save a draft at any point, so a draft asks for a title and
     * nothing else, and the full rules apply when the package goes live.
     */
    private function validated(Request $request): array
    {
        $publishing = $request->boolean('is_active', true);

        $rules = [
            'title'           => ['required', 'string', 'max:60'],
            // One line saying what the package delivers — not the paragraph of
            // selling copy, which is `description`.
            'purpose'         => ['nullable', 'string', 'max:160'],
            'category_id'     => ['nullable', 'exists:categories,id'],
            'description'     => ['nullable', 'string', 'max:500'],
            'services'        => ['nullable', 'array'],
            'services.*'      => ['string', 'max:60'],
            'event_types'     => ['nullable', 'array'],
            'event_types.*'   => ['string', 'max:60'],
            'price'           => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'price_unit'      => ['nullable', 'in:flat,from,hourly'],
            'duration'        => ['nullable', 'string', 'max:60'],
            'coverage'        => ['nullable', 'string', 'max:80'],
            'guest_min'       => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'guest_max'       => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'availability'    => ['nullable', 'string', 'max:80'],
            'savings_pct'     => ['nullable', 'integer', 'min:0', 'max:90'],
            'is_active'       => ['nullable', 'boolean'],
            'gallery_images'   => ['nullable', 'array', 'max:10'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'cover'            => ['nullable', 'string', 'max:20'],
            'remove_images'    => ['nullable', 'array'],
            'remove_images.*'  => ['string', 'max:20'],
            // legacy single-file field (kept for back-compat)
            'cover_image'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
        ];

        if ($publishing) {
            // A live package is one clients can buy, so it needs the things a
            // client decides on: what is in it, and what it costs.
            $rules['services']   = ['required', 'array', 'min:2'];
            $rules['price']      = ['required', 'integer', 'min:1', 'max:10000000'];
            $rules['price_unit'] = ['required', 'in:flat,from,hourly'];
        }

        $data = $request->validate($rules);

        /*
         * This mapping used to sit below an unconditional `return`, so it never
         * ran: `status` fell through to its column default of 'active' and
         * "Save as Draft" published the package into Package Search. The two
         * fields move together — is_active is the fast "publicly visible" flag
         * and must stay === (status === 'active').
         */
        // array_merge, not `+`: an empty price arrives as null and `+` keeps
        // the left-hand null, which the NOT NULL column then rejects.
        return array_merge($data, [
            'is_active'  => $publishing,
            'status'     => $publishing ? 'active' : 'draft',
            'price'      => (int) ($data['price'] ?? 0),
            'price_unit' => $data['price_unit'] ?? 'flat',
        ]);
    }

    /**
     * Move a package through its lifecycle: active → paused → active, or
     * archive. Publish/unpublish keep is_active in step so the public scope and
     * the 404 guard stay coherent.
     */
    public function setStatus(Request $request, Package $package): \Illuminate\Http\RedirectResponse
    {
        abort_unless($package->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', Package::STATUSES)],
        ]);

        $package->update([
            'status'    => $data['status'],
            'is_active' => $data['status'] === 'active',
        ]);

        // "Package active." was the column value read out loud. These say what
        // just happened to the thing the professional was looking at.
        return back()->with('status', match ($data['status']) {
            'active'   => 'Published. Clients can find this package in Package Search now.',
            'paused'   => 'Unpublished. It is hidden from clients and kept here for you.',
            'archived' => 'Archived. It stays on your shelf for your records.',
            default    => 'Moved back to drafts.',
        });
    }

    /** Normalise the array/derived fields the base validator leaves as raw input. */
    private function richFields(Request $request): array
    {
        // Solo packages have no partner; only keep valid palette services.
        $services = collect((array) $request->input('services'))
            ->map(fn ($s) => trim((string) $s))->filter()
            ->intersect(PackageController::SERVICES)->values()->all();

        $eventTypes = collect((array) $request->input('event_types'))
            ->map(fn ($s) => trim((string) $s))->filter()
            ->intersect(self::EVENT_TYPES)->values()->all();

        return [
            'services'        => $services,
            'event_types'     => $eventTypes,
            // Packages are solo-only (Team/Co-Op combined-force removed platform-wide).
            // coop_partner_id used to be forced to null here; the column is gone.
            'type'            => 'solo',
            'team'            => [],
            'includes'        => $this->cleanList($request->input('includes')),
            'guests'          => $this->guestLabel($request->integer('guest_min'), $request->integer('guest_max')),
        ];
    }

    /** Turn min/max guest inputs into a display label, e.g. "50–150" / "Up to 150". */
    private function guestLabel(?int $min, ?int $max): ?string
    {
        return match (true) {
            $min && $max => number_format($min) . '–' . number_format($max),
            (bool) $max  => 'Up to ' . number_format($max),
            (bool) $min  => number_format($min) . '+',
            default      => null,
        };
    }

    private function cleanList($raw): array
    {
        return collect(is_array($raw) ? $raw : [])
            ->map(fn ($i) => trim((string) $i))
            ->filter()
            ->take(20)
            ->values()
            ->all();
    }
}
