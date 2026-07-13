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
    /** Public "Shop Packages" catalogue — every active package, filterable + sortable. */
    public function index(Request $request): View
    {
        $catSlug = trim((string) $request->query('category', ''));
        $q       = trim((string) $request->query('q', ''));
        $sort    = (string) $request->query('sort', 'trending');

        $packages = Package::active()
            ->with([
                'category:id,name,slug',
                'user' => function ($u) {
                    $u->select('id', 'name')
                      ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
                      ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)])
                      ->with('profile:user_id,city');
                },
            ])
            ->when($catSlug !== '', fn ($qr) => $qr->whereHas('category', fn ($c) => $c->where('slug', $catSlug)))
            ->when($q !== '', fn ($qr) => $qr->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%")))
            ->when($sort === 'price_low', fn ($qr) => $qr->orderBy('price'))
            ->when($sort === 'price_high', fn ($qr) => $qr->orderByDesc('price'))
            ->when($sort === 'newest', fn ($qr) => $qr->latest())
            // 'trending' (default): freshest active packages, most recent first (representative
            // until per-package view/booking activity is tracked).
            ->when(! in_array($sort, ['price_low', 'price_high', 'newest'], true),
                fn ($qr) => $qr->orderByDesc('sort_order')->latest())
            ->paginate(12)
            ->withQueryString();

        // Only categories that actually have active packages, for the filter rail.
        $categories = Category::whereHas('packages', fn ($p) => $p->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->reject(fn ($c) => Str::startsWith(Str::lower($c->name), 'test'))
            ->values();

        return view('public.packages-index', [
            'packages'   => $packages,
            'categories' => $categories,
            'filters'    => compact('catSlug', 'q', 'sort'),
            'total'      => $packages->total(),
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

        return view('public.package-show', compact('package', 'more'));
    }
}
