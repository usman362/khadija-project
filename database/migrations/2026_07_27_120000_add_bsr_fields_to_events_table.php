<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields the BSR create wizard collects that the events table had nowhere to
 * put. Everything here is asked for on a screen; nothing is speculative.
 *
 * The important one is proposal_deadline. Until now a request had an event
 * date but no answer to "when do proposals close", so the board's countdowns
 * were derived from the event date — which is a different thing entirely, and
 * is the exact gap the R37 Admin bidding-window page exists to configure.
 * Storing it per request means the deadline can be set today from config and
 * driven by that page later without another migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('event_type', 80)->nullable()->after('description');
            $table->string('organization_type', 20)->nullable()->after('event_type');

            // standard | urgent | recurring | high_value. NOT "emergency" —
            // emergency is the ESR request type, not a characteristic.
            $table->string('characteristic', 20)->nullable()->after('organization_type');

            // The mockup asks for a range. `budget` stays as the single figure
            // the rest of the app already reads, kept in step with the minimum.
            $table->decimal('budget_min', 10, 2)->nullable()->after('budget');
            $table->decimal('budget_max', 10, 2)->nullable()->after('budget_min');

            $table->dateTime('proposal_deadline')->nullable()->after('ends_at');

            // Sealed is the platform default and the copy says so everywhere;
            // the toggle exists because the wizard offers it.
            $table->boolean('sealed_proposals')->default(true)->after('proposal_deadline');
            $table->boolean('questions_enabled')->default(true)->after('sealed_proposals');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'event_type', 'organization_type', 'characteristic',
                'budget_min', 'budget_max', 'proposal_deadline',
                'sealed_proposals', 'questions_enabled',
            ]);
        });
    }
};
