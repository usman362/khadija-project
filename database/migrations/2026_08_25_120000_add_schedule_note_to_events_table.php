<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The client's scheduling note from the request wizard's availability step.
 *
 * Kept out of `description` on purpose: the description is what the client
 * wants done, this is when they need it and how firm that is. A professional
 * reads them for different reasons, and folding one into the other would make
 * the timing note invisible the moment the description runs long.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->text('schedule_note')->nullable()->after('proposal_deadline');
        });
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $table) => $table->dropColumn('schedule_note'));
    }
};
