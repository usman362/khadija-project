<?php

namespace App\Http\Controllers\Public;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

/**
 * SEO-focused category landing pages — one branded URL per service
 * category (e.g. /category/photographers, /category/wedding-djs).
 *
 * Pulls featured vendors for that category, the category's marketing
 * blurb, and a couple of trust signals. Acts as the long-form,
 * indexable entry point that funnels visitors to /browse.
 */
class CategoryLandingController extends Controller
{
    public function show(string $slug): View
    {
        $category = Category::active()->where('slug', $slug)->firstOrFail();

        // Featured pros — same query shape as Browse, but limited to the
        // top 8 in this category sorted by verified-first, then rating.
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $category->name) . '%';

        $featured = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::SUPPLIER->value))
            ->with('profile')
            ->where(function (Builder $outer) use ($like) {
                $outer->whereHas('profile', function (Builder $p) use ($like) {
                    $p->where('headline', 'like', $like)
                      ->orWhere('bio', 'like', $like)
                      ->orWhere('skills', 'like', $like);
                });
            })
            ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
            ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)])
            ->orderByRaw('(SELECT CASE WHEN trade_license_verified_at IS NOT NULL
                                        AND liability_insurance_verified_at IS NOT NULL
                                        AND workers_comp_verified_at IS NOT NULL
                                   THEN 1 ELSE 0 END
                          FROM user_profiles WHERE user_profiles.user_id = users.id) DESC')
            ->orderByRaw('reviews_avg IS NULL, reviews_avg DESC')
            ->orderBy('reviews_count', 'desc')
            ->limit(8)
            ->get();

        // Lightweight stats for the hero. Counts only — no expensive joins.
        $totalCount = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::SUPPLIER->value))
            ->whereHas('profile', function (Builder $p) use ($like) {
                $p->where('headline', 'like', $like)
                  ->orWhere('bio', 'like', $like)
                  ->orWhere('skills', 'like', $like);
            })
            ->count();

        // Sibling / sub-categories for cross-linking (helps SEO and UX).
        $siblings = Category::active()
            ->when($category->parent_id, fn ($q) => $q->where('parent_id', $category->parent_id)->where('id', '!=', $category->id))
            ->when(!$category->parent_id, fn ($q) => $q->where('parent_id', $category->id))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'slug', 'icon']);

        return view('public.category-landing', [
            'category'   => $category,
            'featured'   => $featured,
            'totalCount' => $totalCount,
            'siblings'   => $siblings,
        ]);
    }
}
