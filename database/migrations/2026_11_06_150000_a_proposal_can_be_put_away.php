<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Archiving a proposal: out of the list, not out of the record.
 *
 * Khadijah's Proposals screen offers Archive beside Withdraw. They are
 * different things and the difference matters: withdrawing takes a live offer
 * off the table and the client sees it, while archiving only tidies this
 * professional's own list. Nothing else changes, and nothing is deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('booked_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
