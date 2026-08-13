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

        // "Where our professionals are" — Khadijah's request, after Bark's
        // Popular Cities section.
        $popularCities = $this->popularCities();

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
            'popularCities',
            'featuredReview',
            'video',
            'cms'
        ));
    }

    /**
     * Real top-level categories that have imagery, as showcase tiles
     * (name, image URL, category landing link).
     */
    /**
     * Cities with enough professionals to be worth a click.
     *
     * The threshold is the whole point. Bark's version works because each of
     * their cities holds hundreds; ours would otherwise print "Philadelphia —
     * 1 professional", which makes the marketplace look emptier than it is. A
     * city appears on its own once it crosses the line, and never before.
     *
     * Set by Khadijah 2026-08-13. One number, in one place, so changing it is
     * a config edit rather than a code change.
     */
    public const CITY_MIN_PROFESSIONALS = 2;

    /** @return array<int, array{city:string, state:string, count:int, link:string}> */
    private function popularCities(): array
    {
        return \App\Models\UserProfile::query()
            ->whereHas('user', fn ($u) => $u->whereHas('roles', fn ($r) => $r->where('name', 'professional')))
            ->whereNotNull('city')->where('city', '!=', '')
            ->whereNotNull('state')->where('state', '!=', '')
            // Only where we actually operate. A city we cannot trade in has no
            // business advertising itself on the front page.
            ->whereIn('state', array_keys(config('geo.allowed_states', [])))
            ->selectRaw('city, state, COUNT(*) as total')
            ->groupBy('city', 'state')
            ->havingRaw('COUNT(*) >= ?', [self::CITY_MIN_PROFESSIONALS])
            ->orderByDesc('total')
            ->orderBy('city')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'city'  => $r->city,
                'state' => $r->state,
                'count' => (int) $r->total,
                'link'  => route('public.browse', ['city' => $r->city]),
            ])
            ->all();
    }

    private function showcaseCategories(): array
    {
        // Deliberately the same query as /events-categories, just capped at
        // eight instead of paginated, so the carousel is the first page of the
        // browse grid rather than a separate selection of its own. No
        // parent_id filter — that had limited it to top-level event types, so
        // the two lists never agreed. Every active category has an image in one
        // of the two columns, so no thumbnail filter is needed either.
        /*
         * Checklist row 121 — only categories that actually HAVE artwork.
         *
         * Without this, a category with neither image renders
         * asset('storage/') — the string "/storage", which is a broken image
         * icon sitting in a row of photographs. The row asks for one
         * consistent style across every thumbnail, and a broken tile fails
         * that more obviously than any illustration would.
         *
         * The showcase is the eight most recent categories that can be shown
         * properly, rather than the eight most recent full stop.
         */
        return Category::active()
            /*
             * Service categories, not event types. Khadijah caught this: the
             * section is headed "Explore Popular Categories" but the query had
             * no kind filter, and because it sorts by sort_order then id, the
             * event types won every slot — the carousel was eight event types
             * under a heading promising categories, duplicating the page that
             * already exists for them at /events-categories.
             *
             * A visitor reading "Categories" here expects the things they can
             * hire someone for.
             */
            ->ofKind(Category::SERVICE_CATEGORY)
            ->where(fn ($q) => $q->whereNotNull('thumbnail')->where('thumbnail', '!=', '')
                                 ->orWhere(fn ($w) => $w->whereNotNull('cover_image')->where('cover_image', '!=', '')))
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
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
