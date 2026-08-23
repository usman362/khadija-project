<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Repair category slugs that the admin screen randomised.
 *
 * store() and update() both built the slug as Str::slug($name) . '-' .
 * Str::random(4), so every save -- including a save that only changed a
 * description -- gave the category a brand-new public URL. On production most
 * event types ended up at addresses like /category/bridal-shower-9mKC, and the
 * clean URL that had been shared and indexed returned 404.
 *
 * This restores the readable slug wherever it is free, and only where the
 * damage is unambiguous: the current slug must be exactly the name's slug plus
 * a 4-character random suffix. Anything a human chose deliberately is left
 * alone. Run with --dry to see the changes before making them.
 */
class RepairCategorySlugs extends Command
{
    protected $signature = 'categories:repair-slugs {--dry : List the changes without saving}';

    protected $description = 'Restore readable category slugs that were overwritten with random suffixes';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');

        $rows = Category::withoutGlobalScopes()->get(['id', 'name', 'slug', 'taxonomy_version']);

        $fixed = 0;
        $blocked = [];

        foreach ($rows as $c) {
            $clean = Str::slug((string) $c->name);

            if ($clean === '' || $c->slug === $clean) {
                continue;
            }

            // Only the exact damage signature: clean slug + '-' + 4 random chars.
            if (! preg_match('/^' . preg_quote($clean, '/') . '-[A-Za-z0-9]{4}$/', (string) $c->slug)) {
                continue;
            }

            $taken = Category::withoutGlobalScopes()
                ->where('taxonomy_version', $c->taxonomy_version)
                ->where('slug', $clean)
                ->whereKeyNot($c->id)
                ->exists();

            if ($taken) {
                // Two categories genuinely share a name. Leave both; renaming one
                // to a counted slug is a decision, not a repair.
                $blocked[] = "{$c->slug}  ->  {$clean} (already taken)";
                continue;
            }

            $this->line(($dry ? '[dry] ' : '') . "{$c->slug}  ->  {$clean}");

            if (! $dry) {
                Category::withoutGlobalScopes()->whereKey($c->id)->update(['slug' => $clean]);
            }

            $fixed++;
        }

        foreach ($blocked as $b) {
            $this->warn('skipped: ' . $b);
        }

        $this->info(($dry ? 'Would repair ' : 'Repaired ') . $fixed . ' slug(s).'
            . ($blocked ? ' ' . count($blocked) . ' skipped — see above.' : ''));

        return self::SUCCESS;
    }
}
