<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where the client's extra precision lands.
 *
 * Khadijah, 2026-09-15: "A selected L4 should be saved as an additional
 * detail/specification on the client's request, while Professional matching
 * should remain at the L3 service level." So the detail rides along on the
 * service the request already asked for, and nothing about matching changes.
 *
 * `service_missing` is the client's own words when the list has no name for
 * what they want. Kept beside the request rather than dropped, so a gap in the
 * taxonomy is visible instead of silently costing a booking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_event', function (Blueprint $table) {
            $table->foreignId('specialty_id')->nullable()->after('category_id')
                ->constrained('categories')->nullOnDelete();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('service_missing', 150)->nullable()->after('event_type');
        });
    }

    public function down(): void
    {
        Schema::table('category_event', function (Blueprint $table) {
            $table->dropConstrainedForeignId('specialty_id');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('service_missing');
        });
    }
};
