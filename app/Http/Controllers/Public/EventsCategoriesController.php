<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public "Explore by Category" browser — the visitor-facing counterpart to
 * the admin Categories screen: a full category tree down the left, a real
 * paginated card grid on the right.
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
        // Parent categories with their full subtree — feeds the sidebar tree
        // and the marketing rows further down the page.
        $allCategories = Category::active()
            ->whereNull('parent_id')
            ->with('allChildren')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

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
        }

        if ($search !== '') {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where('name', 'like', "%{$escaped}%");
        }

        /** @var LengthAwarePaginator $categories */
        $categories = $query
            ->orderByRaw('parent_id IS NOT NULL')   // main categories first
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            // Land back on the grid rather than the top of the page — the
            // browser sits well below the hero.
            ->fragment('ec-browse');

        return view('events-categories', [
            'allCategories' => $allCategories,
            'categories'    => $categories,
            'descCounts'    => $this->descendantCounts(),
            'branch'        => $branch,
            'search'        => $search,
            'stats'         => [
                'total'         => Category::active()->count(),
                'parents'       => Category::active()->whereNull('parent_id')->count(),
                'subcategories' => Category::active()->whereNotNull('parent_id')->count(),
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
