<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rich Package Service Search fields. A package bundles MULTIPLE services in
 * one event — delivered solo (one multi-service pro) or as a co-op partnership
 * (two+ pros). These columns power the Service-Mix Matcher (AND-match on
 * services), provider-type filter, and the detailed package cards.
 *
 * NOTE: packages are NOT MSRs. MSRs are client gig-posts that pros bid on;
 * packages are pro-authored bundles a client browses. Kept deliberately apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            // Services this package bundles (slugs/labels) — drives the AND-match matcher.
            $table->json('services')->nullable()->after('category_id');
            // Co-op partner: the second professional collaborating on the package (null = solo).
            $table->foreignId('coop_partner_id')->nullable()->after('user_id')
                ->constrained('users')->nullOndelete();
            // Card facts.
            $table->string('coverage')->nullable()->after('duration');        // "Up to 10 Hours"
            $table->json('team')->nullable()->after('coverage');              // ["1 Lead Photographer", ...]
            $table->string('guests')->nullable()->after('team');             // "Up to 150"
            $table->string('serves_regions')->nullable()->after('guests');   // "NY, NJ, CT"
            $table->string('availability')->nullable()->after('serves_regions'); // "Available Weekends"
            $table->unsignedTinyInteger('savings_pct')->nullable()->after('availability'); // 15 => "Save up to 15%"
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coop_partner_id');
            $table->dropColumn(['services', 'coverage', 'team', 'guests', 'serves_regions', 'availability', 'savings_pct']);
        });
    }
};
