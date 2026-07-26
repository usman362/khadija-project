<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * The legacy import dropped two different asset styles into `thumbnail`:
 * square photo thumbnails (~293x244 / 300x300) for occasion categories, and
 * wide 1280x800 promo banners with the category name baked in as huge text for
 * service categories. Small cards then rendered a banner shrunk to 150px tall,
 * which reads as a shouty graphic rather than a thumbnail.
 *
 * This moves every wide asset to `cover_image` (where a banner belongs) and
 * clears it from `thumbnail`, so the columns mean what they say. Re-runnable —
 * the client can import more categories and run it again.
 */
class ReclassifyCategoryImagery extends Command
{
    protected $signature = 'categories:reclassify-imagery {--dry-run : Report only, change nothing}';

    protected $description = 'Move banner-shaped category images out of thumbnail and into cover_image';

    /** Anything at least this wide relative to its height is a banner, not a thumbnail. */
    private const BANNER_RATIO = 1.45;

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $disk = Storage::disk('public');

        $moved = $kept = $missing = $unreadable = 0;

        foreach (Category::whereNotNull('thumbnail')->cursor() as $cat) {
            $path = $cat->thumbnail;

            if (! $disk->exists($path)) {
                $missing++;
                continue;
            }

            $size = @getimagesize($disk->path($path));
            if (! $size || ! $size[1]) {
                $unreadable++;
                continue;
            }

            if (($size[0] / $size[1]) < self::BANNER_RATIO) {
                $kept++;
                continue;
            }

            // Banner-shaped: belongs in cover_image. Don't clobber an existing cover.
            if (! $dry) {
                $cat->forceFill([
                    'cover_image' => $cat->cover_image ?: $path,
                    'thumbnail'   => null,
                ])->save();
            }
            $moved++;
        }

        $this->table(
            ['Moved to cover_image', 'Kept as thumbnail', 'File missing', 'Unreadable'],
            [[$moved, $kept, $missing, $unreadable]]
        );

        if ($dry) {
            $this->info('Dry run — nothing was written.');
        }

        return self::SUCCESS;
    }
}
