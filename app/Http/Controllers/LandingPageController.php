<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Faq;
use App\Models\MembershipPlan;
use App\Models\Review;
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

        // Top-level categories (kept for any downstream use / browse links).
        $categories = Category::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon']);

        // Category showcase for the "Explore Popular Categories" carousel —
        // real top-level categories with their imagery, linking to each
        // category's public landing page.
        $showcaseCategories = $this->showcaseCategories();

        // Featured testimonial — newest substantive 5-star review, if any.
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
            'showcaseCategories',
            'featuredReview'
        ));
    }

    /**
     * Real top-level categories that have imagery, as showcase tiles
     * (name, image URL, category landing link).
     */
    private function showcaseCategories(): array
    {
        return Category::active()
            ->whereNull('parent_id')
            ->whereNotNull('thumbnail')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(8)
            ->get(['name', 'slug', 'thumbnail', 'cover_image'])
            ->map(fn (Category $c) => [
                'name'  => $c->name,
                'image' => asset('storage/' . ($c->cover_image ?: $c->thumbnail)),
                'link'  => route('public.category', $c->slug),
            ])
            ->all();
    }
}
