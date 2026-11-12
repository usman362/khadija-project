<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Check-in on the day, from Khadijah's 5 November sprint checklist:
 * "Live event management, active gig status, client check-in."
 *
 * Two timestamps and not one, because the useful fact is not "somebody said
 * it happened", it is WHO said so. A professional marking themselves on site
 * is a claim; the client agreeing is a confirmation; and the gap between the
 * two is the only part anybody argues about later. One shared column would
 * record the claim and lose the confirmation.
 *
 * Nullable and nothing else: no status, no state machine, no new row per
 * event. A check-in is one moment that either happened or did not, and the
 * booking it belongs to already carries its own status.
 *
 * Deliberately NOT here: a check-OUT. Nobody has asked for one, hours are not
 * billed from these timestamps, and a second pair of columns that nothing
 * reads is the kind of thing this project has had to go back and drop before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('supplier_checked_in_at')->nullable()->after('status');
            $table->timestamp('client_checked_in_at')->nullable()->after('supplier_checked_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['supplier_checked_in_at', 'client_checked_in_at']);
        });
    }
};
