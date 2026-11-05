<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A professional's service can be rested without being dropped.
 *
 * Khadijah's My Services & Pricing screen marks each service Active or
 * Inactive, and counts both. There was no way to say it: the only way to stop
 * offering a service was to take it off the profile, which also threw away its
 * price, its hours and its extra-hour rate. Somebody who stops doing school
 * dances for a season should not have to type all of that again in March.
 *
 * Default true, because every row that exists today is a service the
 * professional is offering. An inactive service stays on this page and stays
 * out of everything a client sees.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_user', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('extra_hour_price');
        });
    }

    public function down(): void
    {
        Schema::table('category_user', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
