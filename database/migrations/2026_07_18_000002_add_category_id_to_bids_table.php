<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-service bidding: in an MSR each requested service is its own gig,
        // bid on / awarded / paid separately (Peter's core rule). A bid may
        // target a specific category; null = a whole-event (SSR) bid.
        Schema::table('bids', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('event_id')
                ->constrained()->nullOnDelete();
            // One bid per pro per service (was: per pro per event).
            $table->dropUnique(['event_id', 'supplier_id']);
            $table->unique(['event_id', 'supplier_id', 'category_id'], 'bids_event_supplier_category_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bids', function (Blueprint $table) {
            $table->dropUnique('bids_event_supplier_category_unique');
            $table->dropConstrainedForeignId('category_id');
            $table->unique(['event_id', 'supplier_id']);
        });
    }
};
