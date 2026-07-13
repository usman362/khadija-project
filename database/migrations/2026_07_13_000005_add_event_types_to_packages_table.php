<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Event Types This Package Is Perfect For" — the occasions a package targets
 * (Weddings, Corporate Events, …), captured in the pro's Create-a-Package form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->json('event_types')->nullable()->after('services');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('event_types');
        });
    }
};
