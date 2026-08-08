<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rule R47 — each professional account carries a licence for ITS state.
 *
 * The trade licence was stored as a number and a document with nothing saying
 * which jurisdiction issued it, so a Maryland account could be verified on a
 * Delaware licence and nothing would notice. R47 asks each account for "proof
 * of that state's licensing", which needs the state recorded alongside it.
 *
 * Existing licences are backfilled to the account's own registered state:
 * that is the state they were submitted under, and it is what an admin
 * approving them would have assumed. Rows whose account has no state stay
 * NULL — unknown, which `licenceCoversAccountState()` reports as null rather
 * than as a pass or a failure.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('trade_license_state', 2)->nullable()->after('trade_license_number');
        });

        DB::table('user_profiles')
            ->whereNotNull('trade_license_number')
            ->whereNotNull('state')
            ->where('state', '<>', '')
            ->update(['trade_license_state' => DB::raw('UPPER(state)')]);
    }

    public function down(): void
    {
        Schema::table('user_profiles', fn (Blueprint $table) => $table->dropColumn('trade_license_state'));
    }
};
