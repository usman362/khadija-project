<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Read-only. Reports where category pictures actually are.
 *
 * Written after a report that uploaded pictures had "gone" following a seeder
 * run. Nothing in this codebase deletes one — CategorySeeder only sets a
 * picture on a row it is CREATING, CategoryArtworkSeeder only fills a blank,
 * and ReclassifyCategoryImagery MOVES a banner-shaped image from `thumbnail`
 * into `cover_image` and leaves the file alone. So a card with no picture is
 * usually a row whose thumbnail moved, or a second row that never had one.
 *
 * This command changes nothing. Run it before deciding anything.
 */
class DiagnoseCategoryImagery extends Command
{
    protected $signature = 'categories:diagnose-imagery';

    protected $description = 'Read-only report on where category pictures are, and what is missing';

    public function handle(): int
    {
        $disk = Storage::disk('public');

        $rows = [];
        foreach (['event_type', 'service_category', 'service', 'service_specialty'] as $kind) {
            $q = Category::where('kind', $kind);

            $rows[] = [
                $kind,
                (clone $q)->count(),
                (clone $q)->whereNotNull('thumbnail')->count(),
                (clone $q)->whereNotNull('cover_image')->count(),
                (clone $q)->whereNull('thumbnail')->whereNotNull('cover_image')->count(),
                (clone $q)->whereNull('thumbnail')->whereNull('cover_image')->count(),
            ];
        }

        $this->info('Where the pictures are');
        $this->table(
            ['Kind', 'Rows', 'Has thumbnail', 'Has cover', 'Cover but NO thumb', 'Neither'],
            $rows
        );

        // "Cover but no thumbnail" is the recoverable case: the file is still
        // there, the thumbnail column was emptied when it was reclassified.
        $recoverable = Category::whereNull('thumbnail')->whereNotNull('cover_image')->count();
        $this->newLine();
        $this->line("Recoverable by restoring thumbnail from cover_image: <fg=yellow>{$recoverable}</>");

        // Files referenced but not on disk — the only genuinely lost case.
        $broken = 0;
        foreach (Category::whereNotNull('thumbnail')->cursor() as $c) {
            if (! $disk->exists($c->thumbnail)) {
                $broken++;
            }
        }
        $this->line("Thumbnails pointing at a file that is NOT on disk: <fg=red>{$broken}</>");

        // Duplicate names are what makes a listing look doubled.
        $dupes = Category::selectRaw('name, kind, count(*) as n')
            ->groupBy('name', 'kind')
            ->havingRaw('count(*) > 1')
            ->get();

        $this->newLine();
        if ($dupes->isEmpty()) {
            $this->line('No duplicate category names.');
        } else {
            $this->warn("Duplicate names ({$dupes->count()}) — this is why a list can look doubled:");
            $this->table(['Name', 'Kind', 'Copies'], $dupes->map(fn ($d) => [$d->name, $d->kind, $d->n])->all());
        }

        $this->newLine();
        $this->line('Nothing was changed. To put moved thumbnails back:');
        $this->line('  php artisan categories:restore-thumbnails          (shows what it would do)');
        $this->line('  php artisan categories:restore-thumbnails --apply  (does it)');

        return self::SUCCESS;
    }
}
