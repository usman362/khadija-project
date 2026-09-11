<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D-2 (Sir Peter, Sep 5): cancelling a whole event applies the time-based
 * cancellation tiers to EACH booking separately. One request, several
 * bookings, so the per-booking quote shown to the client is kept here as it
 * was at the moment they asked, the same way a single booking's quote is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cancellation_requests', function (Blueprint $table) {
            $table->json('quoted_breakdown')->nullable()->after('days_before');
        });
    }

    public function down(): void
    {
        Schema::table('cancellation_requests', function (Blueprint $table) {
            $table->dropColumn('quoted_breakdown');
        });
    }
};
