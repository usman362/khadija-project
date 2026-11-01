<?php

use App\Models\StaffMember;
use App\Support\GigResourceId;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The references were only given to crew members added after the column.
 *
 * StaffMember says what the rule is: "Every crew member has a reference from
 * the day they are added, rather than one granted later to whoever happens to
 * need it. A reference that only some rows have is one more thing to check
 * before it can be used for anything."
 *
 * The model keeps that promise for new rows, in a `creating` hook. The
 * migration that added the column on 29 October did not, so every crew member
 * already on the site has none. The team page hides the gap rather than
 * breaking, because it filters empties out of the line it prints, which is
 * why nothing failed and nobody noticed.
 *
 * compose() is random rather than sequential and the column is unique, so a
 * clash is retried instead of being allowed to fail a deploy halfway through.
 */
return new class extends Migration
{
    private const TRIES = 20;

    public function up(): void
    {
        $taken = DB::table('staff_members')->whereNotNull('public_id')
            ->pluck('public_id')->flip();

        DB::table('staff_members')->whereNull('public_id')
            ->orderBy('id')->select('id')->chunkById(200, function ($rows) use ($taken) {
                foreach ($rows as $row) {
                    for ($try = 0; $try < self::TRIES; $try++) {
                        $id = GigResourceId::compose(StaffMember::PREFIX);

                        if ($taken->has($id)) {
                            continue;
                        }

                        $taken[$id] = true;
                        DB::table('staff_members')->where('id', $row->id)
                            ->update(['public_id' => $id]);

                        continue 2;
                    }

                    // Twenty clashes in a row is not luck, it is a bug, and a
                    // crew member silently left without a reference is the
                    // thing this migration exists to stop.
                    throw new RuntimeException(
                        "Could not find a free reference for staff member {$row->id}."
                    );
                }
            });
    }

    /**
     * Not reversed. The references are what the client sees beside a crew
     * member's name, and taking them back would change what a booking's
     * paperwork says.
     */
    public function down(): void
    {
    }
};
