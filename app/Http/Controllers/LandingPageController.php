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

        // Curated category showcase for the "Explore Popular Categories"
        // carousel. The live Category table is sparse, so we present the
        // marketplace's headline event types with elegant imagery; each tile
        // links into the public browse experience.
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
     * Curated event-type tiles (name, elegant image, browse link).
     */
    private function showcaseCategories(): array
    {
        $browse = route('public.browse');

        return [
            ['name' => 'Wedding',              'image' => 'https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=600&q=80&auto=format&fit=crop', 'link' => $browse],
            ['name' => 'Corporate Events',     'image' => 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=600&q=80&auto=format&fit=crop', 'link' => $browse],
            ['name' => 'Private Parties',      'image' => 'https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=600&q=80&auto=format&fit=crop', 'link' => $browse],
            ['name' => 'Conferences',          'image' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600&q=80&auto=format&fit=crop', 'link' => $browse],
            ['name' => 'Festivals & Concerts', 'image' => 'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?w=600&q=80&auto=format&fit=crop', 'link' => $browse],
            ['name' => 'Virtual Events',       'image' => 'https://images.unsplash.com/photo-1591115765373-5207764f72e7?w=600&q=80&auto=format&fit=crop', 'link' => $browse],
        ];
    }
}
