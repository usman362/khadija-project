<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B6 — a professional awarded two services on one request gets one booking.
 *
 * Bids already know their service: `bids` is unique on
 * (event_id, supplier_id, category_id). Bookings and finalizations threw that
 * away and keyed on (event_id, supplier_id), so a pro who won photography AND
 * catering on the same event collapsed into a single row -- the second award
 * either did nothing (firstOrCreate matched the first) or overwrote the first
 * one's price (updateOrCreate). This gives both tables the same service
 * dimension the bid always had.
 *
 * Nullable on purpose: a whole-event (SSR) award has no single service, and its
 * rows keep category_id = null exactly as the matching bid does. MySQL treats
 * NULLs as distinct in a unique index, so the null case is held together by the
 * application's firstOrCreate (which matches `category_id IS NULL`), the same
 * way the bids table already relies on it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('event_id')
                ->constrained('categories')->nullOnDelete();
            // One award per service per professional per event.
            $table->unique(['event_id', 'supplier_id', 'category_id'], 'bookings_event_supplier_category_unique');
        });

        Schema::table('finalizations', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('event_id')
                ->constrained('categories')->nullOnDelete();
            // Order matters on MySQL: the old (event, supplier) unique is the
            // index the event_id foreign key leans on, and MySQL refuses to
            // drop an index a FK still needs. The new (event, supplier,
            // category) unique is also event_id-leftmost, so add it FIRST --
            // then the FK has a replacement index and the old one can go. On
            // SQLite the order is harmless.
            $table->unique(['event_id', 'supplier_id', 'category_id'], 'finalizations_event_supplier_category_unique');
            $table->dropUnique('finalizations_event_id_supplier_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('bookings_event_supplier_category_unique');
            $table->dropConstrainedForeignId('category_id');
        });
        Schema::table('finalizations', function (Blueprint $table) {
            // Same MySQL ordering rule in reverse: restore the old event_id-
            // leftmost unique before dropping the one the FK is leaning on.
            $table->unique(['event_id', 'supplier_id']);
            $table->dropUnique('finalizations_event_supplier_category_unique');
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
