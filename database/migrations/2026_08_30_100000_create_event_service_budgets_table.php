<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the client expects to spend on EACH service of a multi-service request.
 *
 * Bids have always been per service — bids.category_id names the one service a
 * professional is bidding on — while the budget was a single figure for the
 * whole event. So on a request for five services with a $10,000 budget, all
 * five professionals were shown $10,000, and a DJ priced against a number that
 * was never meant for them.
 *
 * Khadijah, 2026-08-30: "the budget for an MSR does need a client breakdown at
 * this point in case the multiple services gets separate professionals bidding
 * on separate services."
 *
 * The event keeps its overall budget. This adds the split beneath it, and only
 * where there is something to split — a single-service request needs no row
 * here at all.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('event_service_budgets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            // The service this money is for. Deleting a category must not take
            // the event's budget row with it silently, so this is restricted:
            // a category in use cannot simply vanish.
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();

            $table->decimal('amount', 12, 2);

            $table->timestamps();

            // One figure per service per event.
            $table->unique(['event_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_service_budgets');
    }
};
