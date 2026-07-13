<?php

namespace App\Http\Controllers\Professional;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\PackageController;
use App\Models\Category;
use App\Models\Package;
use App\Models\User;
use App\Services\ImagePipelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Professional "Packages" — a pro bundles TWO OR MORE of their own services
 * into a fixed offering clients browse and book in the Package Service Search.
 * Delivered solo (one multi-service pro) or co-op (with a partner pro). This is
 * NOT an MSR (client gig-post pros bid on) — the two are kept separate.
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
            'partners'    => User::whereHas('roles', fn ($r) => $r->where('name', RoleName::SUPPLIER->value))
                ->where('id', '!=', auth()->id())
                ->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function store(Request $request, ImagePipelineService $pipeline): RedirectResponse
    {
        $data = $this->validated($request);

        $data['user_id'] = $request->user()->id;
        $data['slug'] = Str::slug($data['title']) . '-' . Str::lower(Str::random(5));
        $data = array_merge($data, $this->richFields($request));

        if ($request->hasFile('cover_image')) {
            $set = $pipeline->process($request->file('cover_image'), 'packages/' . $request->user()->id);
            if ($set) {
                $data['images'] = [array_merge($set, ['featured' => true])];
            }
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

        if ($request->hasFile('cover_image')) {
            foreach ((array) $package->images as $old) {
                $pipeline->delete($old);
            }
            $set = $pipeline->process($request->file('cover_image'), 'packages/' . $request->user()->id);
            if ($set) {
                $data['images'] = [array_merge($set, ['featured' => true])];
            }
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

    private function validated(Request $request): array
    {
        return $request->validate([
            'title'           => ['required', 'string', 'max:160'],
            'category_id'     => ['nullable', 'exists:categories,id'],
            'type'            => ['required', 'in:solo,co-op'],
            'coop_partner_id' => ['nullable', 'required_if:type,co-op', 'exists:users,id'],
            'description'     => ['nullable', 'string', 'max:2000'],
            'services'        => ['required', 'array', 'min:2'],
            'services.*'      => ['string', 'max:60'],
            'event_types'     => ['nullable', 'array'],
            'event_types.*'   => ['string', 'max:60'],
            'price'           => ['required', 'integer', 'min:0', 'max:10000000'],
            'price_unit'      => ['required', 'in:flat,from,hourly'],
            'duration'        => ['nullable', 'string', 'max:60'],
            'coverage'        => ['nullable', 'string', 'max:80'],
            'team'            => ['nullable', 'array'],
            'team.*'          => ['string', 'max:80'],
            'guest_min'       => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'guest_max'       => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'serves_regions'  => ['nullable', 'string', 'max:120'],
            'availability'    => ['nullable', 'string', 'max:80'],
            'savings_pct'     => ['nullable', 'integer', 'min:0', 'max:90'],
            'is_active'       => ['nullable', 'boolean'],
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
            'coop_partner_id' => $request->input('type') === 'co-op' ? $request->input('coop_partner_id') : null,
            'team'            => $this->cleanList($request->input('team')),
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
