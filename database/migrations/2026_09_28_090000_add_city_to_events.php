<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sir Peter, 27 September: "data for the city and state will need to be input
 * by the user, so that the user doesn't give just the street address, which i
 * did and saw it went thru... the street names might overlap."
 *
 * The request asked for one line of free text. "682 kirkcaldy way" went
 * through, and everything downstream had to guess which of them it meant.
 * The state was already a column; the city was not, so it had nowhere to go
 * even when a client typed it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('city', 120)->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('city');
        });
    }
};
