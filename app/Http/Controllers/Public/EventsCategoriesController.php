<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public "Explore by Category" browser — the visitor-facing counterpart to
 * the admin Categories screen: event types down the left, a paginated card
 * grid on the right. On V2, service categories sit in their own Services
 * list — they are not mixed into Event Types (they share parent_id = null).
 *
 * The tree drills in (?in=<slug> scopes the grid to that branch) and the
 * search box filters by name. Both survive paging via withQueryString().
 */
class EventsCategoriesController extends Controller
{
    /** Cards per page — 12, same rhythm as the admin grid. */
    private const PER_PAGE = 12;

    public function __invoke(Request $request): View
    {
        // V2 has two kinds of root: Event Types (the occasion) and Service
        // Categories (Catering, Bakery, …). They are siblings, not a tree —
        // the archetype links them, not parent_id. Listing every root under
        // one "Categories" heading mixed services into the event list.
        $isV2 = config('taxonomy.version') === 'v2';

        $eventQuery = Category::active()
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('name');

        if ($isV2) {
            $eventQuery->ofKind(Category::EVENT_TYPE);
        }

        $allCategories = $eventQuery->get();

        $serviceCategories = $isV2
            ? Category::active()
                ->ofKind(Category::SERVICE_CATEGORY)
                ->with('allChildren')
                ->orderBy('name')
                ->get()
            : collect();

        $search = trim((string) $request->query('q', ''));
        $inSlug = (string) $request->query('in', '');

        // Drilling into a branch scopes the grid to that category and every
        // category beneath it, however deep the chain runs.
        $branch = $inSlug !== ''
            ? Category::active()->where('slug', $inSlug)->first()
            : null;

        $query = Category::active()->with('parent:id,name,slug');

        if ($branch) {
            $query->whereIn('id', $this->branchIds($branch));
        } elseif ($search === '' && $isV2) {
            // Unfiltered browse is the event-type wall. Services appear when
            // the visitor picks one from the Services list, or searches.
            $query->ofKind(Category::EVENT_TYPE);
        }

        if ($search !== '') {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where('name', 'like', "%{$escaped}%");
        }

        /** @var LengthAwarePaginator $categories */
        $categories = $query
            // Same order as the admin card grid: newest first.
            ->orderByDesc('sort_order')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            // Land back on the grid rather than the top of the page — the
            // browser sits well below the hero.
            ->fragment('ec-browse');

        return view('events-categories', [
            'allCategories'     => $allCategories,
            'serviceCategories' => $serviceCategories,
            'categories'        => $categories,
            'descCounts'        => $this->descendantCounts(),
            'branch'            => $branch,
            'search'            => $search,
            'isV2'              => $isV2,
            'stats'             => [
                'total'         => Category::active()->count(),
                'parents'       => $isV2
                    ? Category::active()->ofKind(Category::EVENT_TYPE)->count()
                    : Category::active()->whereNull('parent_id')->count(),
                'subcategories' => $isV2
                    ? Category::active()->ofKind(Category::SERVICE_CATEGORY)->count()
                    : Category::active()->whereNotNull('parent_id')->count(),
                'showing'       => $categories->total(),
            ],
        ]);
    }

    /**
     * Descendant count for every active category, keyed by id.
     *
     * Direct-child counts are useless here: the imported tree is a chain of
     * thin levels (occasion → group → sub-group → services), so almost every
     * category has exactly one child. What a visitor wants to know is how many
     * things sit under it in total. One query over the id/parent pairs beats a
     * withCount per level.
     *
     * @return array<int, int>
     */
    private function descendantCounts(): array
    {
        $pairs = Category::active()->get(['id', 'parent_id']);

        $childrenOf = [];
        foreach ($pairs as $row) {
            $childrenOf[$row->parent_id ?? 0][] = $row->id;
        }

        $counts = [];
        $walk = function (int $id) use (&$walk, $childrenOf, &$counts): int {
            if (isset($counts[$id])) {
                return $counts[$id];
            }
            $counts[$id] = 0;   // set first: a cycle in imported data can't recurse forever
            $total = 0;
            foreach ($childrenOf[$id] ?? [] as $childId) {
                $total += 1 + $walk($childId);
            }

            return $counts[$id] = $total;
        };

        foreach ($pairs as $row) {
            $walk($row->id);
        }

        return $counts;
    }

    /**
     * The branch's own id plus every descendant id. Walks level by level so a
     * deep chain costs a handful of queries instead of one per node.
     *
     * @return array<int, int>
     */
    private function branchIds(Category $branch): array
    {
        $ids   = [$branch->id];
        $level = [$branch->id];

        // The tree is four levels deep; the cap is a guard against a cycle in
        // imported data, not a real depth limit.
        for ($depth = 0; $depth < 8 && $level !== []; $depth++) {
            $level = Category::whereIn('parent_id', $level)->pluck('id')->all();
            $ids   = array_merge($ids, $level);
        }

        return $ids;
    }
}
