<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Seeds the canonical event category taxonomy imported from the original
 * GigResource site (175 categories, up to 3 levels deep) together with their
 * cover/thumbnail images.
 *
 * Data lives in database/seeders/data/legacy_categories.json and the images in
 * database/seeders/assets/categories/ — both ship with the repo so a fresh
 * `php artisan db:seed` (or migrate:fresh --seed) reproduces the exact tree.
 *
 * Re-runnable: it clears the categories table (and its references) first, so it
 * can be run any time to reset the taxonomy to the canonical set.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath  = database_path('seeders/data/legacy_categories.json');
        $assetDir  = database_path('seeders/assets/categories');

        if (!File::exists($jsonPath)) {
            $this->command?->error("Missing {$jsonPath} — cannot seed categories.");
            return;
        }

        $rows = json_decode(File::get($jsonPath), true) ?? [];
        if (!$rows) {
            $this->command?->warn('legacy_categories.json is empty — nothing to seed.');
            return;
        }

        /*
         * Nothing is cleared.
         *
         * This used to delete every category and null out events.category_id
         * first. On an empty database that reads as "idempotent reseed"; on the
         * server it would cut every client's event loose from its category and
         * empty the category_event pivot — and `db:seed` is now run on every
         * deploy. Rows are matched on their natural key instead.
         */
        /*
         * v1, hardcoded — this file IS the v1 tree.
         *
         * legacy_categories.json is the 360-category import from the old live
         * site. Reading TAXONOMY_VERSION here put all 360 of them into v2
         * alongside the real 340-row live tree, so the taxonomy had 700 rows
         * and browse listed both. The version a seeder writes is a fact about
         * its data, not about which tree happens to be switched on.
         */
        $version = 'v1';

        /*
         * Refuse to run against a live tree this data does not belong to.
         *
         * Matching is on (taxonomy_version, slug). Every row on a v2 site is
         * v2, so nothing here ever matches one — and `firstOrNew` creates a
         * second copy of all 360 categories instead. That is what happened on
         * production: 106 event types became 153, each real one shadowed by a
         * seeded twin, and the client believed their uploaded pictures had been
         * wiped. Nothing had been; a duplicate had been laid on top.
         *
         * A deploy may run `db:seed`. It must not be able to do that by
         * accident, so this stops unless somebody says the version out loud.
         */
        $live = config('taxonomy.version');

        if ($live !== $version && ! app()->runningUnitTests() && ! env('SEED_LEGACY_TAXONOMY', false)) {
            $this->command?->warn(
                "CategorySeeder holds the {$version} tree and this site is running {$live}. "
                . 'Skipped — running it would add a second copy of every category. '
                . "Set SEED_LEGACY_TAXONOMY=true only if you are deliberately restoring {$version}."
            );

            return;
        }

        // 2) Copy images into the public disk (storage/app/public/categories/...).
        $thumbDir = storage_path('app/public/categories/thumbnails');
        $coverDir = storage_path('app/public/categories/covers');
        File::ensureDirectoryExists($thumbDir);
        File::ensureDirectoryExists($coverDir);

        $copyImage = function (?string $file, string $sub, string $destDir) use ($assetDir) {
            if (!$file) return null;
            $src = "{$assetDir}/{$file}";
            if (!File::exists($src)) return null;
            $dest = "{$destDir}/{$file}";
            if (!File::exists($dest)) File::copy($src, $dest);
            return "categories/{$sub}/{$file}";
        };

        // 3) Pass 1 — insert every category with parent_id = null, remembering old→new id.
        //    sort_order keeps the original legacy id so the admin can reproduce the
        //    live site's "newest first" card order (orderByDesc), while the tree
        //    itself is rendered alphabetically by name.
        $idMap = [];   // old_id => new_id
        $usedSlugs = [];

        foreach ($rows as $r) {
            // Always URL-safe: some legacy slugs contain slashes/invalid chars.
            $slug = Str::slug($r['slug'] ?: $r['name']) ?: Str::slug($r['name']);
            // guarantee uniqueness
            $base = $slug; $i = 2;
            while (isset($usedSlugs[$slug])) { $slug = $base . '-' . $i++; }
            $usedSlugs[$slug] = true;

            /*
             * Matched on (taxonomy_version, slug) — the pair the unique index
             * is on — so a second run updates the row it wrote the first time.
             *
             * taxonomy_version is written explicitly rather than left to the
             * model's creating hook: a seeder must produce the same rows
             * whether it is run on its own or from DatabaseSeeder, and a hook
             * is one `WithoutModelEvents` away from not running.
             *
             * is_active and parent_id are NOT overwritten on an existing row.
             * An admin who deactivated a category, or the v2 import that set
             * the parents, would otherwise have their work undone by a deploy.
             */
            $cat = Category::withoutGlobalScopes()->firstOrNew([
                'taxonomy_version' => $version,
                'slug'             => $slug,
            ]);

            $attributes = [
                'name'              => $r['name'],
                'short_description' => $r['short_description'] ?? null,
                'long_description'  => $r['long_description'] ?? null,
                'icon'              => $r['icon'] ?? null,
                'sort_order'        => (int) ($r['old_id'] ?? 0),
            ];

            /*
             * The taxonomy file is reference data, but an existing picture is
             * editorial content. A deploy may run `db:seed`; it must never
             * replace a picture that someone uploaded in the admin with the
             * repository's fallback image. New rows still receive the bundled
             * artwork, so a clean install looks complete.
             */
            if (! $cat->exists) {
                $attributes['thumbnail'] = $copyImage($r['image'] ?? null, 'thumbnails', $thumbDir);
                $attributes['cover_image'] = $copyImage($r['cover_image'] ?? null, 'covers', $coverDir);
            }

            $cat->fill($attributes);

            if (! $cat->exists) {
                // Import every category as active — the client wants the full
                // taxonomy live/visible; individual ones can be toggled off in admin.
                $cat->is_active = true;
                $cat->parent_id = null;
            }

            $cat->taxonomy_version = $version;
            $cat->save();

            $idMap[$r['old_id']] = $cat->id;
        }

        // 4) Pass 2 — wire up parent_id from the remapped ids.
        foreach ($rows as $r) {
            $oldParent = $r['old_parent_id'] ?? 0;
            if ($oldParent && isset($idMap[$oldParent]) && isset($idMap[$r['old_id']])) {
                Category::withoutGlobalScopes()->whereKey($idMap[$r['old_id']])
                    ->update(['parent_id' => $idMap[$oldParent]]);
            }
        }

        $roots = Category::withoutGlobalScopes()
            ->where('taxonomy_version', $version)->whereNull('parent_id')->count();
        $this->command?->info("Seeded " . count($idMap) . " categories ({$roots} roots) with images.");
    }

}
