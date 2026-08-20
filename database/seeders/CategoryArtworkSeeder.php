<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Carry the old site's category artwork onto the live tree.
 *
 * 273 pictures were imported from the old live site into the v1 taxonomy. V2
 * replaced v1 as the live tree and the import did not bring them, so every
 * card drew a tinted tile with the category's initial.
 *
 * This is a seeder rather than only a migration because of the order things
 * happen in on a fresh server: migrations run before any category exists, so a
 * migration alone has nothing to copy. The migration is kept for databases
 * that were already seeded, and calls straight into here — one implementation,
 * so the two cannot drift.
 *
 * Idempotent twice over: a v2 row that already has artwork is left alone, and
 * copying the same path onto the same row a second time changes nothing.
 */
class CategoryArtworkSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Carried ' . self::carry() . ' picture(s) onto the live tree.');
    }

    /** @return int how many rows gained artwork */
    public static function carry(): int
    {
        $normalise = fn (?string $name) => preg_replace('/[^a-z0-9]/', '', strtolower((string) $name));

        $art = [];

        foreach (DB::table('categories')->where('taxonomy_version', 'v1')->get() as $row) {
            if ($row->thumbnail === null && $row->cover_image === null) {
                continue;
            }

            // First one wins: v1 repeats names across its four levels, and the
            // shallower row is the one that was given the artwork.
            $art[$normalise($row->name)] ??= [
                'thumbnail'   => $row->thumbnail,
                'cover_image' => $row->cover_image,
            ];
        }

        if ($art === []) {
            return 0;
        }

        $carried = 0;

        foreach (DB::table('categories')->where('taxonomy_version', 'v2')->get() as $row) {
            // Matched on the name with punctuation and case stripped, and
            // nothing more. A fuzzy match would put a photograph of a wedding
            // on "Divorce Party".
            $match = $art[$normalise($row->name)] ?? null;

            if ($match === null) {
                continue;
            }

            $update = [];

            if ($row->thumbnail === null && $match['thumbnail'] !== null) {
                $update['thumbnail'] = $match['thumbnail'];
            }

            if ($row->cover_image === null && $match['cover_image'] !== null) {
                $update['cover_image'] = $match['cover_image'];
            }

            if ($update !== []) {
                DB::table('categories')->where('id', $row->id)->update($update);
                $carried++;
            }
        }

        return $carried;
    }
}
