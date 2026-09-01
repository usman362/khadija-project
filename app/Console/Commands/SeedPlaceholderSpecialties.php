<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Placeholder level-4 specialties, so the screens can be built and reviewed
 * before the real list exists.
 *
 * Sir Peter, 2026-08-31: "You are to build the infrastructure… You can start
 * that now with placeholder data — the structure doesn't change based on the
 * names. The actual specialty list comes from Khadijah approval."
 *
 * So these are deliberately obvious as placeholders and deliberately easy to
 * remove: every row is marked, and --clear takes them all away without touching
 * anything approved. Nothing here goes live.
 */
class SeedPlaceholderSpecialties extends Command
{
    protected $signature = 'taxonomy:placeholder-specialties
                            {--clear : Remove the placeholders and stop}
                            {--per=4 : How many to put under each service}';

    protected $description = 'Add clearly-marked placeholder Level 4 specialties for building against';

    /** Written into short_description so a placeholder can never be mistaken for approved content. */
    public const MARKER = 'PLACEHOLDER — awaiting approved specialty list';

    /** Shapes that read like real specialties without pretending to be the list. */
    private const PATTERNS = ['Wedding', 'Corporate', 'Private Party', 'Non-profit', 'Outdoor', 'Small Venue'];

    public function handle(): int
    {
        if ($this->option('clear')) {
            $gone = Category::where('kind', Category::SERVICE_SPECIALTY)
                ->where('short_description', self::MARKER)
                ->delete();

            $this->info("Removed {$gone} placeholder specialt" . ($gone === 1 ? 'y' : 'ies') . '.');

            // Anything approved is left exactly where it is.
            $kept = Category::where('kind', Category::SERVICE_SPECIALTY)->count();
            if ($kept > 0) {
                $this->line("{$kept} non-placeholder specialt" . ($kept === 1 ? 'y' : 'ies') . ' left untouched.');
            }

            return self::SUCCESS;
        }

        $per = max(1, min(6, (int) $this->option('per')));
        $services = Category::where('kind', Category::SERVICE)->orderBy('name')->get();

        if ($services->isEmpty()) {
            $this->error('No level-3 services to hang specialties under.');

            return self::FAILURE;
        }

        $made = 0;
        foreach ($services as $service) {
            foreach (array_slice(self::PATTERNS, 0, $per) as $pattern) {
                $name = $pattern . ' ' . $service->name;
                $slug = Str::slug($service->slug . '-' . $pattern);

                $existing = Category::where('slug', $slug)->first();
                if ($existing) {
                    continue;
                }

                Category::create([
                    'name'              => $name,
                    'slug'              => $slug,
                    'kind'              => Category::SERVICE_SPECIALTY,
                    'parent_id'         => $service->id,
                    'short_description' => self::MARKER,
                    'is_active'         => true,
                ]);

                $made++;
            }
        }

        $this->info("Added {$made} placeholder specialties under {$services->count()} services.");
        $this->newLine();
        $this->warn('These are placeholders. Remove them before the approved list is loaded:');
        $this->line('  php artisan taxonomy:placeholder-specialties --clear');

        return self::SUCCESS;
    }
}
