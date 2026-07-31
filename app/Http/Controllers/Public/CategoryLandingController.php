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

        // Pros who list this category — or anything beneath it, so a visitor
        // landing on a group page still sees the specialists inside it. This
        // used to be a LIKE of the whole category name against free-text
        // headline/bio/skills, which matched nothing: a photographer writes
        // "Fine-Art Wedding Photographer", the category is "Photography
        // Services". Every page showed zero pros as a result.
        $branchIds = $this->branchIds($category);

        $featured = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::PROFESSIONAL->value))
            ->excludingSelf()
            ->with('profile')
            ->whereHas('serviceCategories', fn (Builder $c) => $c->whereIn('categories.id', $branchIds))
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

        // Same population as the featured list, uncapped.
        $totalCount = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::PROFESSIONAL->value))
            ->excludingSelf()
            ->whereHas('serviceCategories', fn (Builder $c) => $c->whereIn('categories.id', $branchIds))
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
            'category'         => $category,
            'featured'         => $featured,
            'totalCount'       => $totalCount,
            'subcategoryCount' => $category->children()->where('is_active', true)->count(),
            'siblings'         => $siblings,
        ]);
    }

    /**
     * This category's id plus every descendant's, so a group page ("Photography
     * Services") lists the pros who signed up under its leaves too.
     *
     * @return array<int, int>
     */
    private function branchIds(Category $category): array
    {
        $ids   = [$category->id];
        $level = [$category->id];

        // Four levels in the imported tree; the cap guards against a cycle in
        // imported data rather than bounding real depth.
        for ($depth = 0; $depth < 8 && $level !== []; $depth++) {
            $level = Category::whereIn('parent_id', $level)->pluck('id')->all();
            $ids   = array_merge($ids, $level);
        }

        return $ids;
    }
}
