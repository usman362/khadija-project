<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The note that goes with each service's hours.
 *
 * Sir Peter's reason for taking the single "Anything they should know about
 * timing?" box off the step was that it "is asked after each service" — so
 * until each service has somewhere to say it, that question has been removed
 * and not replaced, and a client has nowhere to write "setup can start from
 * 3pm". This is that somewhere, and it is also where a rough time goes when
 * the exact one is not settled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_event', function (Blueprint $table) {
            $table->string('timing_note', 150)->nullable()->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('category_event', function (Blueprint $table) {
            $table->dropColumn('timing_note');
        });
    }
};
