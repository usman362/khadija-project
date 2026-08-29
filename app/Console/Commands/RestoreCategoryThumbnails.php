<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Puts a category's thumbnail back from its cover_image.
 *
 * `categories:reclassify-imagery` moves banner-shaped pictures out of
 * `thumbnail` and into `cover_image`, then empties `thumbnail`. That is fine
 * for artwork shipped with the repository and wrong for a picture somebody
 * uploaded: the card that used to show their photo shows a coloured letter
 * instead, and it reads as though the upload was lost. It was not — the file
 * is untouched and the path is in the other column.
 *
 * Dry by default. It never overwrites a thumbnail that is already set, and it
 * never points at a file that is not on disk.
 */
class RestoreCategoryThumbnails extends Command
{
    protected $signature = 'categories:restore-thumbnails
                            {--apply : Write the changes. Without this, nothing is saved}
                            {--kind= : Limit to one kind, e.g. event_type}';

    protected $description = 'Restore category thumbnails from cover_image where the thumbnail was emptied';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $disk  = Storage::disk('public');

        $query = Category::whereNull('thumbnail')->whereNotNull('cover_image');

        if ($kind = $this->option('kind')) {
            $query->where('kind', $kind);
        }

        $restored = 0;
        $missing  = 0;
        $preview  = [];

        foreach ($query->cursor() as $cat) {
            // Never restore a path to a file that is not there — that would
            // trade a blank card for a broken image.
            if (! $disk->exists($cat->cover_image)) {
                $missing++;
                continue;
            }

            if (count($preview) < 15) {
                $preview[] = [$cat->id, $cat->kind, $cat->name, $cat->cover_image];
            }

            if ($apply) {
                $cat->forceFill(['thumbnail' => $cat->cover_image])->save();
            }

            $restored++;
        }

        if ($preview !== []) {
            $this->table(['ID', 'Kind', 'Name', 'Picture'], $preview);
            if ($restored > count($preview)) {
                $this->line('… and ' . ($restored - count($preview)) . ' more.');
            }
        }

        $this->newLine();
        $this->line($apply
            ? "Restored: <fg=green>{$restored}</>"
            : "Would restore: <fg=yellow>{$restored}</>  (nothing saved — add --apply)");

        if ($missing > 0) {
            $this->warn("Skipped {$missing}: the file named in cover_image is not on disk.");
        }

        return self::SUCCESS;
    }
}
