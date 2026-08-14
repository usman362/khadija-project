<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\CategoryRelevance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Builds Sir Peter's V2 category tree from the approved sheet.
 *
 * Safe to run repeatedly: it only ever touches rows stamped v2, and it matches
 * on slug, so re-running after the sheet changes updates in place rather than
 * duplicating. The live v1 tree is never read or written here.
 */
class ImportTaxonomyV2 extends Command
{
    protected $signature = 'taxonomy:import-v2 {--prune : delete v2 rows the sheet no longer lists}';

    protected $description = "Import the V2 category tree (106 event types, 27 service categories, 241 services)";

    private const PATH = 'database/seeders/data/taxonomy_v2.json';

    public function handle(): int
    {
        $path = base_path(self::PATH);

        if (! is_file($path)) {
            $this->error('Taxonomy file not found: ' . self::PATH);

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data) {
            $seen = [];

            $seen[] = $this->importEventTypes($data['event_types']);
            [$categoryIds, $categorySlugs] = $this->importServiceCategories($data['service_categories']);
            $seen[] = $categorySlugs;
            $seen[] = $this->importServices($data['services'], $categoryIds);

            $this->importRelevance($data['relevance'], $categoryIds);

            if ($this->option('prune')) {
                $this->prune(array_merge(...$seen));
            }
        });

        // The archetype and tier maps are cached forever, so a reimport that
        // did not clear them would leave the picker sorting by the taxonomy it
        // just replaced — correct data on disk, stale answers on screen.
        \App\Domain\Taxonomy\ServiceRelevance::forget();

        $this->report();

        return self::SUCCESS;
    }

    /**
     * Event Types sit at the top with no parent. They are not the parents of
     * service categories — the archetype is what links the two.
     */
    private function importEventTypes(array $types): array
    {
        $slugs = [];

        foreach (array_values($types) as $i => $type) {
            $slugs[] = $slug = Str::slug($type['name']);

            $this->upsert($slug, [
                'name'      => $type['name'],
                'kind'      => Category::EVENT_TYPE,
                'archetype' => $type['archetype'],
                'parent_id' => null,
                'sort_order' => $i + 1,
            ]);
        }

        return $slugs;
    }

    /** @return array{0: array<string,int>, 1: array<int,string>} names→ids, and the slugs */
    private function importServiceCategories(array $categories): array
    {
        $ids = [];
        $slugs = [];

        foreach (array_values($categories) as $i => $category) {
            $slugs[] = $slug = Str::slug($category['name']);

            $ids[$category['name']] = $this->upsert($slug, [
                'name'              => $category['name'],
                'kind'              => Category::SERVICE_CATEGORY,
                'parent_id'         => null,
                'short_description' => $category['rationale'] ?? null,
                'sort_order'        => $i + 1,
            ]);
        }

        return [$ids, $slugs];
    }

    /**
     * Services hang under their service category. Two categories can each hold
     * a service of the same name — "Bartenders (Staffing Only)" against
     * "Professional Bartenders" — so the slug carries the parent.
     */
    private function importServices(array $services, array $categoryIds): array
    {
        $slugs = [];
        $position = [];

        foreach ($services as $service) {
            $parentId = $categoryIds[$service['category']] ?? null;

            if ($parentId === null) {
                $this->warn("Skipped '{$service['name']}' — unknown category '{$service['category']}'");

                continue;
            }

            $slugs[] = $slug = Str::slug($service['category'] . '-' . $service['name']);
            $position[$parentId] = ($position[$parentId] ?? 0) + 1;

            $this->upsert($slug, [
                'name'            => $service['name'],
                'kind'            => Category::SERVICE,
                'parent_id'       => $parentId,
                'popularity_tier' => $service['tier'] ?? null,
                'cross_fit_alt'   => $service['cross_fit'] ?? null,
                'sort_order'      => $position[$parentId],
            ]);
        }

        return $slugs;
    }

    private function importRelevance(array $rows, array $categoryIds): void
    {
        foreach ($rows as $row) {
            $categoryId = $categoryIds[$row['category']] ?? null;

            if ($categoryId === null) {
                $this->warn("Skipped relevance for unknown category '{$row['category']}'");

                continue;
            }

            CategoryRelevance::updateOrCreate(
                ['archetype' => $row['archetype'], 'category_id' => $categoryId],
                ['tier' => $row['tier'], 'signature_services' => $row['signature'] ?? null],
            );
        }
    }

    /** Insert or update one v2 row, matched on slug within the v2 tree. */
    private function upsert(string $slug, array $attributes): int
    {
        $category = Category::anyTaxonomy()
            ->where('taxonomy_version', 'v2')
            ->where('slug', $slug)
            ->first();

        $attributes += ['is_active' => true];

        if ($category) {
            $category->update($attributes);

            return $category->id;
        }

        return Category::create($attributes + [
            'slug'             => $slug,
            'taxonomy_version' => 'v2',
        ])->id;
    }

    /**
     * Drop v2 rows the sheet has stopped listing. Off by default: a mistyped
     * or half-written sheet should not silently delete the tree.
     */
    private function prune(array $keepSlugs): void
    {
        $stale = Category::anyTaxonomy()
            ->where('taxonomy_version', 'v2')
            ->whereNotIn('slug', $keepSlugs)
            ->get();

        foreach ($stale as $category) {
            $this->line("  pruned {$category->name}");
            $category->delete();
        }

        if ($stale->isNotEmpty()) {
            $this->warn("Pruned {$stale->count()} row(s) no longer in the sheet.");
        }
    }

    private function report(): void
    {
        $count = fn (string $kind) => Category::anyTaxonomy()
            ->where('taxonomy_version', 'v2')->where('kind', $kind)->count();

        $this->newLine();
        $this->table(['Tier', 'Rows'], [
            ['Event Types',        $count(Category::EVENT_TYPE)],
            ['Service Categories', $count(Category::SERVICE_CATEGORY)],
            ['Services',           $count(Category::SERVICE)],
            ['Archetype relevance', CategoryRelevance::count()],
        ]);

        $live = config('taxonomy.version');
        $this->info($live === 'v2'
            ? 'v2 is live.'
            : "Imported, but not live — the site is still reading {$live}. Run `php artisan taxonomy:switch` when ready.");
    }
}
