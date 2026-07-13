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

        // 1) Clear existing categories + references (idempotent reseed).
        $this->clearExisting();

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

            $cat = Category::create([
                'name'              => $r['name'],
                'slug'              => $slug,
                'short_description' => $r['short_description'] ?? null,
                'long_description'  => $r['long_description'] ?? null,
                'thumbnail'         => $copyImage($r['image'] ?? null, 'thumbnails', $thumbDir),
                'cover_image'       => $copyImage($r['cover_image'] ?? null, 'covers', $coverDir),
                'icon'              => $r['icon'] ?? null,
                'parent_id'         => null,
                // Import every category as active — the client wants the full
                // taxonomy live/visible; individual ones can be toggled off in admin.
                'is_active'         => true,
                'sort_order'        => (int)($r['old_id'] ?? 0),
            ]);

            $idMap[$r['old_id']] = $cat->id;
        }

        // 4) Pass 2 — wire up parent_id from the remapped ids.
        foreach ($rows as $r) {
            $oldParent = $r['old_parent_id'] ?? 0;
            if ($oldParent && isset($idMap[$oldParent]) && isset($idMap[$r['old_id']])) {
                Category::whereKey($idMap[$r['old_id']])
                    ->update(['parent_id' => $idMap[$oldParent]]);
            }
        }

        $roots = Category::whereNull('parent_id')->count();
        $this->command?->info("Seeded " . count($idMap) . " categories ({$roots} roots) with images.");
    }

    /**
     * Remove existing categories and anything that references them, without
     * tripping foreign-key constraints.
     */
    private function clearExisting(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('category_event')) {
            DB::table('category_event')->truncate();
        }
        if (Schema::hasTable('events') && Schema::hasColumn('events', 'category_id')) {
            DB::table('events')->update(['category_id' => null]);
        }

        Category::query()->delete();
        DB::statement('ALTER TABLE categories AUTO_INCREMENT = 1');

        Schema::enableForeignKeyConstraints();
    }
}
