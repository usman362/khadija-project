<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Geographic matching V1 — dedicated Service Origin (not billing, not the
 * business-address block) plus coordinates on the request.
 *
 * Precision is stored, never inferred at read time: exact | zip | unresolved.
 * Unresolved rows have no coordinates on purpose — a failed geocode must not
 * silently become an eligible match.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('service_origin_line', 255)->nullable()->after('zip_code');
            $table->string('service_origin_city', 100)->nullable()->after('service_origin_line');
            $table->string('service_origin_state', 2)->nullable()->after('service_origin_city');
            $table->string('service_origin_zip', 20)->nullable()->after('service_origin_state');
            $table->decimal('origin_lat', 10, 7)->nullable()->after('service_origin_zip');
            $table->decimal('origin_lng', 10, 7)->nullable()->after('origin_lat');
            $table->string('origin_precision', 16)->nullable()->after('origin_lng');
            $table->unsignedSmallInteger('travel_radius_miles')->nullable()->after('origin_precision');

            $table->index(['origin_lat', 'origin_lng']);
            $table->index('origin_precision');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->decimal('location_lat', 10, 7)->nullable()->after('state');
            $table->decimal('location_lng', 10, 7)->nullable()->after('location_lat');
            $table->string('location_precision', 16)->nullable()->after('location_lng');
            $table->string('location_zip', 20)->nullable()->after('location_precision');

            $table->index(['location_lat', 'location_lng']);
            $table->index('location_precision');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropIndex(['origin_lat', 'origin_lng']);
            $table->dropIndex(['origin_precision']);
            $table->dropColumn([
                'service_origin_line',
                'service_origin_city',
                'service_origin_state',
                'service_origin_zip',
                'origin_lat',
                'origin_lng',
                'origin_precision',
                'travel_radius_miles',
            ]);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['location_lat', 'location_lng']);
            $table->dropIndex(['location_precision']);
            $table->dropColumn([
                'location_lat',
                'location_lng',
                'location_precision',
                'location_zip',
            ]);
        });
    }
};
