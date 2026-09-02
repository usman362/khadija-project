<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop events.characteristic.
 *
 * The "Request characteristic" picker — Standard / Urgent / Recurring /
 * High-Value — came from the BSR mockup and was built with it. Nothing was
 * ever written to read it: it reached no professional, changed no matching, no
 * deadline and no fee, and every one of the 76 events on the site had it
 * empty. Sir Peter asked on 2026-08-31 why a required field did nothing; it
 * was made optional then, and removed now.
 *
 * The column goes with the field rather than being left dormant. Retiring
 * Team Mode taught that the hard way — its columns were left behind as
 * harmless, and later code read them and believed what it found. A column
 * nothing fills is a trap for whoever next writes a query against it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('events', 'characteristic')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('characteristic');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('events', 'characteristic')) {
            Schema::table('events', function (Blueprint $table) {
                $table->string('characteristic', 20)->nullable();
            });
        }
    }
};
