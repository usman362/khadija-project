<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The rules a professional prices by, and the extras they sell alongside.
 *
 * Khadijah's My Services & Pricing screen has tabs for Pricing Rules,
 * Add-ons & Extras, Travel Pricing and Quick Bid Settings. Each was a tab
 * with nothing behind it: a service carried a price and nothing else, so a
 * weekend surcharge, a travel charge or a minimum booking had nowhere to be
 * written down and the professional typed them into every quote by hand.
 *
 * One row per professional for the rules, because they apply across their
 * work, and one row per extra, because they sell several.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // What they will not go below, whatever the job.
            $table->decimal('minimum_booking', 10, 2)->nullable();

            // Added on top, as a percentage of the service price.
            $table->unsignedTinyInteger('weekend_surcharge_pct')->nullable();
            $table->unsignedTinyInteger('rush_surcharge_pct')->nullable();

            // Travel: free inside the radius, charged per mile beyond it.
            $table->unsignedSmallInteger('travel_free_miles')->nullable();
            $table->decimal('travel_rate_per_mile', 8, 2)->nullable();
            $table->unsignedSmallInteger('travel_max_miles')->nullable();

            // Quick Bid answers a request with their own prices. Off until
            // they say so: a bid sent on their behalf is still their bid.
            $table->boolean('quick_bid_enabled')->default(false);
            $table->string('quick_bid_terms', 600)->nullable();

            // What they are aiming to earn in a month. Null until they name
            // one, and the dashboard shows nothing rather than a target
            // nobody set.
            $table->decimal('monthly_revenue_goal', 10, 2)->nullable();

            $table->timestamps();
        });

        Schema::create('professional_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('price_unit', 20)->default('flat');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_addons');
        Schema::dropIfExists('professional_pricing_rules');
    }
};
