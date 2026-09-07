<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * OA-136 — the Verified Professional badge was listed as a paid feature.
 *
 * The Pro and Elite plans both advertised "Sealed gigs · Verified Professional
 * badge". DIR-13 is explicit: that badge is tied to submitting a licence and
 * having it approved, never to a paid plan.
 *
 * It is the same untruth as ISSUE-1 seen from the other end. This morning the
 * badge stopped rendering without a document and an approval behind it; the
 * pricing page was still selling it. A professional could have paid for a
 * badge that payment cannot produce.
 *
 * Only the badge clause is removed. "Sealed gigs" is a real plan feature and
 * stays. Done in a migration so it travels with the deploy — the seeder is
 * also corrected, but nothing on production should need a seeder run to stop
 * making a claim like this one.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('plan_features')
            ->where('feature', 'like', '%Verified Professional badge%')
            ->get(['id', 'feature']);

        foreach ($rows as $row) {
            $cleaned = trim(preg_replace(
                '/\s*·?\s*Verified Professional badge\s*/u',
                '',
                $row->feature,
            ), " ·\t\n");

            DB::table('plan_features')->where('id', $row->id)->update([
                // If the badge was the whole line, the line goes rather than
                // being left empty on a pricing page.
                'feature' => $cleaned !== '' ? $cleaned : 'Sealed gigs',
            ]);
        }
    }

    /**
     * Not reversible. Putting the claim back would re-advertise a badge that
     * cannot be bought.
     */
    public function down(): void
    {
        // Intentionally empty.
    }
};
