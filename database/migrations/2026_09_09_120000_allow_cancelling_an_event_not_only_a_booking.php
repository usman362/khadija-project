<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A cancellation can be about an event that was never booked.
 *
 * Sir Peter / Ali, 2026-09-09: the client can cancel a posted request as well
 * as a booking, and either goes to an administrator for approval.
 *
 * booking_id was NOT NULL, which made "cancel this event" impossible to
 * record: a request posted to the board and never taken up has no booking to
 * point at, and inventing one to satisfy a column would put a booking in the
 * client's history that never happened.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cancellation_requests', function (Blueprint $table) {
            $table->foreignId('booking_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Rows added while this was open would have no booking to restore, so
        // the column is left nullable rather than dropping them to reverse.
    }
};
