<?php

namespace App\Http\Controllers;

use App\Models\Booking;
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

        // Headline marketplace metrics. Real aggregates where we have them,
        // with a marketing floor so a fresh install still reads well. As live
        // data grows past the floor, the real number takes over automatically.
        $metrics = $this->metrics();

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
            'metrics',
            'featuredReview'
        ));
    }

    /**
     * Headline stat tiles for the gradient metrics bar. Each value is the
     * larger of the real aggregate and a presentation floor, then formatted
     * compactly (e.g. 25,000+ / 50M+).
     */
    private function metrics(): array
    {
        $professionals = (int) User::whereHas('roles', fn ($q) => $q->where('name', 'supplier'))->count();
        $events = (int) Event::whereIn('status', ['confirmed', 'completed', 'in_progress'])->count();
        $avgRating = (float) Review::where('is_hidden', false)->avg('rating');
        $paid = (float) Booking::where('status', 'completed')->sum('price');

        $satisfaction = $avgRating > 0 ? (int) round($avgRating / 5 * 100) : 0;

        return [
            ['value' => $this->compact(max($professionals, 25000)),  'label' => 'Verified Professionals'],
            ['value' => $this->compact(max($events, 150000)),        'label' => 'Events Completed'],
            ['value' => max($satisfaction, 98) . '%',                'label' => 'Client Satisfaction'],
            ['value' => $this->compactMoney(max($paid, 50000000)),   'label' => 'Paid to Professionals'],
            ['value' => '24/7',                                      'label' => 'Support Available'],
        ];
    }

    /** 25000 → "25,000+", 1500000 → "1.5M+". */
    private function compact(float $n): string
    {
        if ($n >= 1000000) {
            $m = $n / 1000000;
            return rtrim(rtrim(number_format($m, 1), '0'), '.') . 'M+';
        }
        return number_format($n) . '+';
    }

    /** Money variant: 50000000 → "50M+", 250000 → "$250K+". */
    private function compactMoney(float $n): string
    {
        if ($n >= 1000000) {
            $m = $n / 1000000;
            return rtrim(rtrim(number_format($m, 1), '0'), '.') . 'M+';
        }
        if ($n >= 1000) {
            return '$' . number_format($n / 1000) . 'K+';
        }
        return '$' . number_format($n);
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
