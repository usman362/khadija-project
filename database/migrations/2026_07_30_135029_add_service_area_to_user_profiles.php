<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Service-area status and expansion opt-in (Peter, 2026-07-30).
 *
 * Registration no longer turns anyone away. Everyone completes the form and is
 * told afterwards whether we operate where they are, so these record the
 * answer and whether they want to hear from us when we get there.
 *
 * The status is only ever `supported` or `coming_soon` — never "unsupported".
 * That is a deliberate naming choice: these rows are a demand signal and an
 * expansion waitlist, not a reject pile.
 *
 * Country/state/city already exist on this table; date registered is the
 * user's own created_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('service_area_status', 20)
                ->default('coming_soon')
                ->after('country');

            $table->boolean('expansion_opt_in')
                ->default(false)
                ->after('service_area_status');

            // Expansion reporting is "how many people are waiting, and where" —
            // grouped by state, filtered by status.
            $table->index(['service_area_status', 'state']);
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropIndex(['service_area_status', 'state']);
            $table->dropColumn(['service_area_status', 'expansion_opt_in']);
        });
    }
};
