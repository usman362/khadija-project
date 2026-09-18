<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Other days the client could hold the event on.
 *
 * Sir Peter, 2026-09-16: the preferred date is picked once, and the client can
 * then offer backup dates, "bc the professionals might be only available on
 * certain times or dates". A short list of dates that is only ever read with
 * its request, so it sits on the event itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('backup_dates')->nullable()->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('backup_dates');
        });
    }
};
