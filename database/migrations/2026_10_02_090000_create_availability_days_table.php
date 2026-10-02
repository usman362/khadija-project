<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sir Peter, 24 September, of the calendar legend: "i noticed that it says
 * unavailable at the bottom, but why not Available as well?"
 *
 * Because nothing recorded it. This is where it is recorded: one row per
 * person per day, and no row at all for a day nobody has said anything about.
 * A day with no row is not "available" and not "unavailable"; it is unknown,
 * which is the truth and is what the calendar shows.
 *
 * The table is per user rather than per client, because the same question
 * will be asked of professionals the day their side is open, and a second
 * table for the same fact is how two answers appear.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            // 'available' or 'unavailable'. Blocking a date is saying you are
            // unavailable on it, so it is not a third state.
            $table->string('state', 16);
            $table->string('note', 140)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'day']);
            $table->index(['user_id', 'day', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_days');
    }
};
