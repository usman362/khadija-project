<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * OA-114 / OA-115 / OA-140 — the two columns that disagreed.
 *
 * An event carries both a `status` and an `is_published` flag, and they answer
 * overlapping questions. Where they disagree the screens disagreed too: a row
 * whose badge read "Published" offered "Continue Draft" and "Publish", because
 * the badge reads status and the buttons read the flag.
 *
 * The reading is fixed in code — Event::stage() resolves the pair, and status
 * wins, because a request professionals have already answered is not something
 * you can go back and finish writing. This clears the contradiction out of the
 * data as well, so the columns stop saying two things.
 *
 * Narrow on purpose: only rows that have moved past draft — published,
 * confirmed or completed — with the flag still unset. Nothing else is touched.
 * A genuine draft has status 'pending' and the flag unset, and that pair is
 * correct.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('events')
            ->whereIn('status', ['published', 'confirmed', 'completed'])
            ->where('is_published', false)
            ->update(['is_published' => true]);
    }

    /**
     * Not reversible. Restoring the contradiction would put "Continue Draft"
     * back on confirmed bookings.
     */
    public function down(): void
    {
        // Intentionally empty.
    }
};
