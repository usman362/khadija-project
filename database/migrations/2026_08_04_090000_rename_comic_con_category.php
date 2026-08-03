<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Comic-Con / Pop Culture" becomes "Pop Culture Event".
 *
 * Sir Peter, 2026-08-03: the old name cannot be used — Comic-Con is a
 * registered trademark, not a generic description of the event type.
 *
 * The slug changes with it. Leaving `comic-con-...` in place would keep the
 * trademark in the address bar and in every category link, which is the part a
 * visitor actually sees, so renaming only the label would miss the point.
 * Descriptions are scrubbed too — the name appeared inside three of them.
 *
 * Also fixed in the seeder's source data, so a re-seed does not bring it back.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('categories')
            ->where('slug', 'comic-con-/-pop-culture')
            ->orWhere('slug', 'comic-con-pop-culture')
            ->update([
                'name' => 'Pop Culture Event',
                'slug' => 'pop-culture-event',
            ]);

        // The trademark also sat inside category copy, on this row and on the
        // performer categories that referenced the event by name.
        foreach (['short_description', 'long_description'] as $column) {
            DB::table('categories')
                ->where($column, 'like', '%Comic%')
                ->get(['id', $column])
                ->each(function ($row) use ($column) {
                    $clean = str_ireplace(
                        ['Comic-Con / Pop Culture', 'Comic-Con', 'Comic Con'],
                        'pop culture',
                        $row->$column,
                    );
                    DB::table('categories')->where('id', $row->id)->update([$column => $clean]);
                });
        }
    }

    public function down(): void
    {
        DB::table('categories')
            ->where('slug', 'pop-culture-event')
            ->update([
                'name' => 'Comic-Con / Pop Culture',
                'slug' => 'comic-con-/-pop-culture',
            ]);
        // Descriptions are not restored: they carried a trademark we should not
        // be putting back.
    }
};
