<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use App\Models\Faq;
use App\Models\MembershipPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(): View
    {
        $plans = MembershipPlan::query()
            ->active()
            ->ordered()
            ->with('features')
            ->get();

        $faqs = Faq::active()->ordered()->get();

        // Top-level categories for hero chips + A-Z browse grid.
        $categories = Category::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon']);

        // Split into 4 buckets for the A-Z expander (GigSalad-style).
        // If we have fewer than 4 categories, just pass what we have.
        $categoryBuckets = $categories->chunk((int) ceil(max($categories->count(), 1) / 4));

        // Real trust stats so the hero pill isn't a lie.
        $stats = [
            'reviews_count'        => Review::where('is_hidden', false)->count(),
            'reviews_avg'          => (float) Review::where('is_hidden', false)->avg('rating'),
            'professionals_count'  => User::whereHas('roles', fn ($q) => $q->where('name', 'supplier'))->count(),
            'events_booked_count'  => Event::whereIn('status', ['confirmed', 'completed', 'in_progress'])->count(),
        ];

        // Featured testimonial for the pull-quote section. Pick the most
        // recent 5-star visible review that has some substance to it.
        $featuredReview = Review::query()
            ->where('is_hidden', false)
            ->where('rating', 5)
            ->whereNotNull('comment')
            ->with(['reviewer:id,name', 'reviewee:id,name'])
            ->latest()
            ->first();

        return view('landing', compact(
            'plans',
            'faqs',
            'categories',
            'categoryBuckets',
            'stats',
            'featuredReview'
        ));
    }
}
