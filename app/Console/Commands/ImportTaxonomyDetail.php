<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Level 4 and the two lookups the picker needs, from Khadijah's sheets.
 *
 * Three things land here, all of them keyed on the level 3 service that
 * already exists. Nothing in this command creates or renames a service: if a
 * name in the sheet is not already in the tree it is reported and skipped,
 * because a typo must not quietly fork the taxonomy.
 *
 *   level 4 choices   the optional detail a client adds to a service
 *   search terms      the words clients type, so "cover band for hire" finds
 *                     Live Bands
 *   event coverage    which services are offered for which event type
 *
 * Safe to run again: everything is matched and updated in place.
 */
class ImportTaxonomyDetail extends Command
{
    protected $signature = 'taxonomy:import-detail
                            {--prune : remove level 4 rows the sheet no longer lists}';

    protected $description = 'Import level 4 choices, search terms and event coverage for the service picker';

    private const PATH = 'database/seeders/data/taxonomy_v2_detail.json';

    public function handle(): int
    {
        $path = base_path(self::PATH);

        if (! is_file($path)) {
            $this->error('Detail file not found: ' . self::PATH);

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        // Services are keyed "Category|Service": the same service name sits
        // under more than one category, so the name alone picks the wrong one.
        $services = [];
        foreach (Category::anyTaxonomy()->where('taxonomy_version', 'v2')
            ->where('kind', Category::SERVICE)->with('parent')->get() as $service) {
            $services[$service->parent?->name . '|' . $service->name] = $service;
        }

        $eventTypes = Category::anyTaxonomy()->where('taxonomy_version', 'v2')
            ->where('kind', Category::EVENT_TYPE)->get()->keyBy('name');

        $counts = ['specialties' => 0, 'terms' => 0, 'pairs' => 0];
        $missing = [];

        DB::transaction(function () use ($data, $services, $eventTypes, &$counts, &$missing) {
            $counts['specialties'] = $this->importSpecialties($data['specialties'] ?? [], $services, $missing);
            $counts['terms']       = $this->importSearchTerms($data['search_terms'] ?? [], $services, $missing);
            $counts['pairs']       = $this->importCoverage($data['event_services'] ?? [], $services, $eventTypes, $missing);
        });

        foreach (array_keys($missing) as $name) {
            $this->warn("Not in the tree, skipped: {$name}");
        }

        $this->info("Level 4 choices: {$counts['specialties']}");
        $this->info("Services given search terms: {$counts['terms']}");
        $this->info("Event type to service pairs: {$counts['pairs']}");

        return self::SUCCESS;
    }

    /**
     * Level 4 rows hang off their service, exactly like the main importer
     * writes them, so both commands produce the same shape of row.
     */
    private function importSpecialties(array $rows, array $services, array &$missing): int
    {
        $made = 0;
        $position = [];
        $keep = [];

        foreach ($rows as $row) {
            $key = $row['category'] . '|' . $row['service'];
            $service = $services[$key] ?? null;

            if (! $service) {
                $missing[$key] = true;

                continue;
            }

            $slug = Str::slug($row['category'] . '-' . $row['service'] . '-' . $row['name']);
            $position[$service->id] = ($position[$service->id] ?? 0) + 1;
            $keep[] = $slug;

            $existing = Category::anyTaxonomy()->where('taxonomy_version', 'v2')->where('slug', $slug)->first();

            $attributes = [
                'name'       => $row['name'],
                'kind'       => Category::SERVICE_SPECIALTY,
                'parent_id'  => $service->id,
                'sort_order' => $position[$service->id],
                'is_active'  => true,
            ];

            $existing
                ? $existing->update($attributes)
                : Category::create($attributes + ['slug' => $slug, 'taxonomy_version' => 'v2']);

            $made++;
        }

        if ($this->option('prune')) {
            $stale = Category::anyTaxonomy()->where('taxonomy_version', 'v2')
                ->where('kind', Category::SERVICE_SPECIALTY)
                ->whereNotIn('slug', $keep)
                ->delete();

            if ($stale > 0) {
                $this->warn("Pruned {$stale} level 4 row(s) no longer in the sheet.");
            }
        }

        return $made;
    }

    private function importSearchTerms(array $rows, array $services, array &$missing): int
    {
        $done = 0;

        foreach ($rows as $row) {
            $key = $row['category'] . '|' . $row['service'];
            $service = $services[$key] ?? null;

            if (! $service) {
                $missing[$key] = true;

                continue;
            }

            $service->update(['search_terms' => implode('; ', $row['terms'])]);
            $done++;
        }

        return $done;
    }

    /**
     * Rebuilt per event type rather than merged, so a service dropped from an
     * event in the sheet is dropped here too. Whole-table truncation would take
     * every event down with one bad row.
     */
    private function importCoverage(array $rows, array $services, $eventTypes, array &$missing): int
    {
        $pairs = 0;

        foreach ($rows as $row) {
            $eventType = $eventTypes[$row['event_type']] ?? null;

            if (! $eventType) {
                $missing[$row['event_type']] = true;

                continue;
            }

            $ids = [];

            foreach ($row['services'] as $category => $names) {
                foreach ($names as $name) {
                    $service = $services[$category . '|' . $name] ?? null;

                    if (! $service) {
                        $missing[$category . '|' . $name] = true;

                        continue;
                    }

                    $ids[] = $service->id;
                }
            }

            $eventType->services()->sync($ids);
            $pairs += count($ids);
        }

        return $pairs;
    }
}
