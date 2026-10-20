<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The landing page stops claiming a verification nobody performs.
 *
 * On 15 October every view that said GigResource verifies professionals was
 * corrected. The live landing page went on saying "Verified Professionals /
 * Profile & business verification" afterwards, and the reason is worth
 * recording because it will happen again otherwise.
 *
 * That strip is served from the `page_sections` table, not from the blade. The
 * array in landing.blade.php is only the fallback for when no row exists, and
 * locally there was no row, so the view looked like the source. On production
 * the row exists and wins.
 *
 * Which also means the guard test could not have caught it: it scans
 * resources/views, and this sentence does not live there. The same is true of
 * every other page served from page_sections.
 *
 * The seeder carries the corrected wording, but seeders do not run on a live
 * site, so the fix has to travel as a migration to reach the deployed row. It
 * rewrites only the one item and leaves anything an admin has edited since
 * alone, matching on the old text rather than replacing the whole payload.
 */
return new class extends Migration
{
    public function up(): void
    {
        self::rewrite(
            'Verified Professionals',
            'Professional Profiles',
            'Profiles, reviews and past work',
        );
    }

    public function down(): void
    {
        self::rewrite(
            'Professional Profiles',
            'Verified Professionals',
            'Profile & business verification',
        );
    }

    private static function rewrite(string $findTitle, string $title, string $text): void
    {
        $row = DB::table('page_sections')->where('key', 'trust_bar')->first();

        if (! $row) {
            return;
        }

        $payload = json_decode($row->payload ?? '[]', true);

        if (! is_array($payload) || empty($payload['items'])) {
            return;
        }

        $changed = false;

        foreach ($payload['items'] as $i => $item) {
            if (($item['title'] ?? null) === $findTitle) {
                $payload['items'][$i]['title'] = $title;
                $payload['items'][$i]['text']  = $text;
                $changed = true;
            }
        }

        if ($changed) {
            DB::table('page_sections')
                ->where('id', $row->id)
                ->update(['payload' => json_encode($payload), 'updated_at' => now()]);
        }
    }
};
