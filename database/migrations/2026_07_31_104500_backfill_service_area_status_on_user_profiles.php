<?php

use App\Support\ServiceArea;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Recompute service_area_status from the location each profile already holds.
 *
 * The column was added with `default('coming_soon')`, which is right for a new
 * row and wrong for every row that already existed — the backfill filed all of
 * them as out-of-area regardless of where they actually are. It did no harm
 * while nothing read the column. It stops being harmless the moment the gate
 * enforces it, which is the same commit as this migration: without this, every
 * existing account is locked out of the marketplace on deploy, including the
 * demo professionals sitting in the seven launch states.
 *
 * Written to be re-runnable: it derives the status rather than assuming one, so
 * running it twice changes nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('user_profiles')
            ->select('id', 'country', 'state', 'service_area_status')
            ->orderBy('id')
            ->chunkById(200, function ($profiles) {
                foreach ($profiles as $profile) {
                    $status = ServiceArea::statusFor($profile->country, $profile->state);

                    if ($status !== $profile->service_area_status) {
                        DB::table('user_profiles')
                            ->where('id', $profile->id)
                            ->update(['service_area_status' => $status]);
                    }
                }
            });
    }

    public function down(): void
    {
        // The previous values carried no information — they were the column
        // default, not a decision. There is nothing to restore.
    }
};
