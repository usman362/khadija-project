<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The category artwork exists — it is just on the wrong tree.
 *
 * 273 pictures were imported from the old live site into the v1 taxonomy. V2
 * replaced v1 as the live tree on 2026-08-05 and the import did not carry the
 * images across, so every card on Explore Event Types has been drawing a
 * tinted tile with the category's initial. Peter reads that as "no images".
 *
 * This copies a v1 row's thumbnail and cover onto the v2 row of the same name.
 * Matching is on the name with punctuation and case removed, and nothing else:
 * a fuzzy match would put a photograph of a wedding on "Divorce Party".
 *
 * Idempotent — a v2 row that already has its own artwork is left alone, so
 * re-running this never overwrites something an admin uploaded.
 */
return new class extends Migration
{
    public function up(): void
    {
        $normalise = fn (?string $name) => preg_replace('/[^a-z0-9]/', '', strtolower((string) $name));

        $art = [];

        foreach (DB::table('categories')->where('taxonomy_version', 'v1')->get() as $row) {
            if ($row->thumbnail === null && $row->cover_image === null) {
                continue;
            }

            // First one wins: v1 has duplicate names across its four levels,
            // and the shallower row is the one that was given the artwork.
            $art[$normalise($row->name)] ??= [
                'thumbnail'   => $row->thumbnail,
                'cover_image' => $row->cover_image,
            ];
        }

        if ($art === []) {
            return;
        }

        $carried = 0;

        foreach (DB::table('categories')->where('taxonomy_version', 'v2')->get() as $row) {
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

    }

    public function down(): void
    {
        // Deliberately not reversed. The images are the same files either way,
        // and stripping them back off v2 would leave the live tree blank again
        // to undo something that only ever added.
    }
};
