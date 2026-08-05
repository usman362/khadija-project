<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The trademark came back through the new category tree.
 *
 * Sir Peter ruled on 2026-08-03 that "Comic-Con" cannot be used — it is a
 * registered trademark, not a description of an event type — and a migration
 * renamed it in the live tree. But the V2 sheet is dated 2026-08-02, a day
 * earlier, so it still carried "Comic-Con / Pop Culture Event", and importing
 * it put the trademark straight back on the site.
 *
 * Renamed here rather than re-imported so the row keeps its id. The source
 * file is fixed too, so a re-import does not undo this.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('categories')
            ->where('taxonomy_version', 'v2')
            ->where('slug', 'comic-con-pop-culture-event')
            ->update([
                'name' => 'Pop Culture Event',
                'slug' => 'pop-culture-event',
            ]);
    }

    public function down(): void
    {
        // Not reversed: it would put a trademark back on a public page.
    }
};
