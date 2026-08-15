<?php

namespace App\Http\Controllers\Public;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Domain\Taxonomy\ServiceIcon;
use App\Domain\Taxonomy\ServiceRelevance;
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

        /*
         * An event type is not something you hire.
         *
         * Every row on this site came through one page, so all 106 event types
         * rendered as "Hire Charity Event" over a count of professionals — the
         * same confusion between the three tiers that the Owner has been
         * pointing at, in its plainest form. You do not hire a Year-End Party;
         * you plan one, and then hire the people for it.
         */
        if ($category->kind === Category::EVENT_TYPE) {
            return $this->eventType($category);
        }

        // Pros who list this category — or anything beneath it, so a visitor
        // landing on a group page still sees the specialists inside it. This
        // used to be a LIKE of the whole category name against free-text
        // headline/bio/skills, which matched nothing: a photographer writes
        // "Fine-Art Wedding Photographer", the category is "Photography
        // Services". Every page showed zero pros as a result.
        $branchIds = $this->branchIds($category);

        /*
         * Rule R38 — both figures on this page describe who the visitor can
         * hire, and the button underneath them goes to /browse, which shows a
         * signed-in client only their own state. Counted platform-wide the
         * page told a Maryland client "34 Pros available" and handed them a
         * shorter list, with the same eight faces on the page unreachable.
         *
         * A signed-out visitor still sees the whole category: this page is
         * public and indexed, and someone with no account has no state yet.
         */
        $inState = fn ($q) => \App\Support\StateMatching::scopeUsersForViewer($q, auth()->user());

        $featured = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::PROFESSIONAL->value))
            ->excludingSelf()
            ->tap($inState)
            ->with('profile')
            ->whereHas('serviceCategories', fn (Builder $c) => $c->whereIn('categories.id', $branchIds))
            ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
            ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)])
            ->orderByRaw('(SELECT CASE WHEN trade_license_verified_at IS NOT NULL
                                        AND liability_insurance_verified_at IS NOT NULL
                                        AND (liability_insurance_expires_on IS NULL
                                             OR liability_insurance_expires_on >= CURRENT_DATE)
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
            ->tap($inState)
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
     * The event-type page: plan first, hire second.
     *
     * The services offered are the ones the Category Masterlist says matter
     * for this occasion — its archetype's service categories, Essential first.
     * That matrix has been in the database since 5 August; this is the second
     * place to read it.
     *
     * Nothing is invented for an occasion the matrix does not rank: the page
     * falls back to every service category rather than guessing an order.
     */
    private function eventType(Category $category): View
    {
        $tiers = ServiceRelevance::tiersByArchetype()[$category->archetype] ?? [];

        $services = Category::active()
            ->ofKind(Category::SERVICE_CATEGORY)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'thumbnail', 'short_description'])
            ->map(fn ($c) => [
                'id'    => $c->id,
                'name'  => $c->name,
                'slug'  => $c->slug,
                'blurb' => $c->short_description,
                'tier'  => $tiers[$c->id] ?? null,
                'icon'  => ServiceIcon::pathFor($c->slug),
            ])
            ->sortBy([
                fn ($a, $b) => ServiceRelevance::rank($a['tier']) <=> ServiceRelevance::rank($b['tier']),
                fn ($a, $b) => $a['name'] <=> $b['name'],
            ])
            ->values();

        // Rule R38 — the same scope the rest of the site uses. A featured
        // professional a visitor cannot hire is not a feature.
        $inState = fn ($q) => \App\Support\StateMatching::scopeUsersForViewer($q, auth()->user());

        $featured = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::PROFESSIONAL->value))
            ->excludingSelf()
            ->tap($inState)
            ->with(['profile', 'serviceCategories:id,name'])
            ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
            ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)])
            ->orderByRaw('reviews_avg IS NULL, reviews_avg DESC')
            ->limit(12)
            ->get();

        /*
         * The filter chips are the categories these professionals actually
         * work in, not a fixed list. A chip for a trade nobody here offers
         * filters to an empty row, and one that says "DJs" when none of the
         * twelve is a DJ is the same broken promise as a count that overstates
         * itself.
         */
        $chips = $featured
            ->flatMap(fn ($p) => $p->serviceCategories->pluck('name'))
            ->countBy()
            ->sortDesc()
            ->keys()
            ->values();

        return view('public.event-type-landing', compact('category', 'services', 'featured', 'chips'));
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
