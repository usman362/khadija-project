<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Package;
use App\Services\ImagePipelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Professional "Packages" — a pro bundles their own services into a fixed
 * offering clients can browse and book (distinct from bidding on client jobs).
 */
class ProfessionalPackageController extends Controller
{
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
        return view('professional.packages.create', [
            'categories' => Category::getNestedDropdownList(),
        ]);
    }

    public function store(Request $request, ImagePipelineService $pipeline): RedirectResponse
    {
        $data = $this->validated($request);

        $data['user_id'] = $request->user()->id;
        $data['slug'] = Str::slug($data['title']) . '-' . Str::lower(Str::random(5));
        $data['includes'] = $this->cleanIncludes($request->input('includes'));

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

        return view('professional.packages.create', [
            'package'    => $package,
            'categories' => Category::getNestedDropdownList(),
        ]);
    }

    public function update(Request $request, Package $package, ImagePipelineService $pipeline): RedirectResponse
    {
        abort_unless($package->user_id === $request->user()->id, 403);

        $data = $this->validated($request);
        $data['includes'] = $this->cleanIncludes($request->input('includes'));

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
            'title'       => ['required', 'string', 'max:160'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'type'        => ['required', 'in:solo,co-op'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price'       => ['required', 'integer', 'min:0', 'max:1000000'],
            'price_unit'  => ['required', 'in:flat,from,hourly'],
            'duration'    => ['nullable', 'string', 'max:60'],
            'is_active'   => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }

    private function cleanIncludes($raw): array
    {
        return collect(is_array($raw) ? $raw : [])
            ->map(fn ($i) => trim((string) $i))
            ->filter()
            ->take(15)
            ->values()
            ->all();
    }
}
