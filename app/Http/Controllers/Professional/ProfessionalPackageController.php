<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\PackageController;
use App\Models\Category;
use App\Models\Package;
use App\Services\ImagePipelineService;
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

    public function index(Request $request): View
    {
        $packages = Package::where('user_id', $request->user()->id)
            ->with('category:id,name')
            ->latest()
            ->paginate(12);

        return view('professional.packages.index', compact('packages'));
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

        $package->update($data);

        return redirect()->route('professional.packages.index')->with('status', 'Package updated.');
    }

    public function destroy(Request $request, Package $package, ImagePipelineService $pipeline): RedirectResponse
    {
        abort_unless($package->user_id === $request->user()->id, 403);

        foreach ((array) $package->images as $img) {
            $pipeline->delete($img);
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

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'           => ['required', 'string', 'max:160'],
            'category_id'     => ['nullable', 'exists:categories,id'],
            'description'     => ['nullable', 'string', 'max:2000'],
            'services'        => ['required', 'array', 'min:2'],
            'services.*'      => ['string', 'max:60'],
            'event_types'     => ['nullable', 'array'],
            'event_types.*'   => ['string', 'max:60'],
            'price'           => ['required', 'integer', 'min:0', 'max:10000000'],
            'price_unit'      => ['required', 'in:flat,from,hourly'],
            'duration'        => ['nullable', 'string', 'max:60'],
            'coverage'        => ['nullable', 'string', 'max:80'],
            'guest_min'       => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'guest_max'       => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'serves_regions'  => ['nullable', 'string', 'max:120'],
            'availability'    => ['nullable', 'string', 'max:80'],
            'savings_pct'     => ['nullable', 'integer', 'min:0', 'max:90'],
            'is_active'       => ['nullable', 'boolean'],
            'gallery_images'   => ['nullable', 'array', 'max:8'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'cover'            => ['nullable', 'string', 'max:20'],
            'remove_images'    => ['nullable', 'array'],
            'remove_images.*'  => ['string', 'max:20'],
            // legacy single-file field (kept for back-compat)
            'cover_image'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
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
            'type'            => 'solo',
            'coop_partner_id' => null,
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
