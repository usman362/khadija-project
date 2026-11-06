<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When this professional last opened their dashboard.
 *
 * Khadijah's screen has a "What's new since your last visit" panel. There was
 * nothing to measure it against: `last_active_at` moves on every request, so
 * by the time the dashboard rendered, "since your last visit" would have meant
 * "in the last few milliseconds" and every count would have read nought.
 *
 * This moves only when the dashboard itself is opened, and the panel reads the
 * value from BEFORE this visit, which is the question the panel actually asks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('dashboard_seen_at')->nullable()->after('last_active_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dashboard_seen_at');
        });
    }
};
