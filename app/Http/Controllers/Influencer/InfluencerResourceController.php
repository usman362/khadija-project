<?php

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Models\InfluencerResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Resources section of the influencer portal — a shared content library
 * (guides, videos, templates, articles, courses) read from influencer_resources.
 */
class InfluencerResourceController extends Controller
{
    public function library(): View|RedirectResponse
    {
        return $this->guard(function () {
            $all = InfluencerResource::where('type', '!=', 'course')->get();
            $byType = $all->groupBy('type')->map->count();
            return view('influencer.resources.library', [
                'featured' => $all->where('is_featured', true)->take(5),
                'popular'  => $all->sortByDesc('downloads')->take(6)->values(),
                'recent'   => $all->sortByDesc('published_at')->take(5)->values(),
                'byType'   => $byType,
                'total'    => $all->count(),
            ]);
        });
    }

    public function academy(): View|RedirectResponse
    {
        return $this->guard(function () {
            $courses = InfluencerResource::type('course')->get();
            return view('influencer.resources.academy', [
                'courses'    => $courses,
                'categories' => $courses->groupBy('category')->map->count(),
                'totalLessons' => (int) $courses->sum('lessons'),
                'totalMinutes' => (int) $courses->sum('duration_minutes'),
            ]);
        });
    }

    public function tutorials(): View|RedirectResponse
    {
        return $this->guard(fn () => view('influencer.resources.tutorials', [
            'videos' => InfluencerResource::type('video')->orderByDesc('downloads')->get(),
        ]));
    }

    public function articles(): View|RedirectResponse
    {
        return $this->guard(fn () => view('influencer.resources.articles', [
            'articles' => InfluencerResource::type('article')->orderByDesc('is_featured')->orderByDesc('published_at')->get(),
        ]));
    }

    public function gettingStarted(): View|RedirectResponse
    {
        return $this->guard(fn () => view('influencer.resources.getting-started', [
            'items' => InfluencerResource::where('category', 'Getting Started')->orderBy('type')->get(),
        ]));
    }

    private function guard(\Closure $cb): View|RedirectResponse
    {
        if (! auth()->user()?->influencer) {
            return redirect()->route('influencer.join')->with('error', 'You are not registered as an influencer yet.');
        }
        return $cb();
    }
}
