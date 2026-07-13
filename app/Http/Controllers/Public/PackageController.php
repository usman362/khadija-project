<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\View\View;

class PackageController extends Controller
{
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
