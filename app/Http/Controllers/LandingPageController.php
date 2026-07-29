<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Faq;
use App\Models\MembershipPlan;
use App\Models\PageSection;
use App\Models\Review;
use App\Models\Setting;
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

        // Editable content, keyed by section. `cms('hero')` in the template
        // returns the row or null; every read carries the shipped wording as a
        // fallback, so a missing or emptied section degrades to the original
        // copy rather than to a blank page.
        $cms = collect(array_keys(config('page-sections.landing', [])))
            ->mapWithKeys(fn ($key) => [$key => PageSection::block("landing.{$key}")]);

        // Video: still readable from the older settings keys, so an install that
        // set them before the content editor existed keeps working.
        $videoSection = $cms['video'] ?? null;
        $video = [
            'url'    => trim((string) ($videoSection?->body ?: Setting::get('homepage.video_url', ''))),
            'poster' => $videoSection?->imageUrl()
                ?: (trim((string) Setting::get('homepage.video_poster', ''))
                    ?: 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=800&q=80&auto=format&fit=crop'),
            'title'  => trim((string) ($videoSection?->heading ?: Setting::get('homepage.video_title', ''))) ?: 'GigResource',
        ];

        return view('landing', compact(
            'plans',
            'faqs',
            'categories',
            'showcaseCategories',
            'featuredReview',
            'video',
            'cms'
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
                'name' => $c->name,
                // Thumbnail first. The two columns are not what their names
                // suggest: `thumbnail` is the square 300×300 category artwork
                // every category has, and `cover_image` is a wide 509×149 strip
                // that only one category carries. Preferring the cover meant
                // that single tile rendered as a cropped letterbox while the
                // other 48 were square — the odd one out in the row. This is
                // also the order /events-categories already uses.
                'image' => asset('storage/' . ($c->thumbnail ?: $c->cover_image)),
                'link'  => route('public.category', $c->slug),
            ])
            ->all();
    }
}
