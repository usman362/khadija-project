<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bookmarks on the professional Bidding Board.
 *
 * The board's "Saved" tab and its saved-count stat had no backing at all — the
 * bookmark control was decoration. This is the pivot behind it: one row per
 * (professional, opportunity), so a pro can park a gig and come back before the
 * deadline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // A pro saves an opportunity once; the unique key also makes the
            // toggle safe to double-submit.
            $table->unique(['user_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_events');
    }
};
