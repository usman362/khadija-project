<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rule R38 — same-state matching. The rule's own note says it "needs a new
 * `state` field on every request type"; this is that field.
 *
 * Events already carry a free-text `location`, which is not usable for this:
 * R9 requires locations to come from the 7-jurisdiction dropdown, and a rule
 * that turns on string equality cannot be built on a field a user can type
 * "Baltimore area" into. The state is stored as its own two-letter column so
 * the predicate is an index lookup and not a LIKE.
 *
 * Backfill takes the state from the account that owns the row — the client
 * for an event, the professional for a package — which is exactly what R38
 * means by "the registered state of the acting account". Rows whose owner has
 * no state on file stay NULL, and NULL matches nobody, so no existing row is
 * silently granted eligibility it was never checked for.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('state', 2)->nullable()->after('location')->index();
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->string('state', 2)->nullable()->after('serves_regions')->index();
        });

        // Written as a per-state loop rather than one UPDATE...JOIN: that
        // syntax is MySQL's, and the test suite runs on SQLite, so a
        // migration using it would pass in production and fail every test.
        $this->backfill('events', 'client_id');
        $this->backfill('packages', 'user_id');
    }

    /** Copy each owner's state onto the rows they own. */
    private function backfill(string $table, string $ownerColumn): void
    {
        $states = DB::table('user_profiles')
            ->whereNotNull('state')->where('state', '<>', '')
            ->select('user_id', 'state')->get()
            ->groupBy(fn ($row) => strtoupper($row->state));

        foreach ($states as $state => $rows) {
            DB::table($table)
                ->whereNull('state')
                ->whereIn($ownerColumn, $rows->pluck('user_id'))
                ->update(['state' => $state]);
        }
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $table) => $table->dropColumn('state'));
        Schema::table('packages', fn (Blueprint $table) => $table->dropColumn('state'));
    }
};
