<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier pricing on a package, from Khadijah's 5 November sprint checklist:
 * "Package builder workflow, tier pricing options, service inclusion toggles."
 *
 * The builder already had three PRICE BASES, flat, starting-at and per hour,
 * which answer "how is this number read" and not "what else could I buy". A
 * tier is the second question: Silver, Gold, Platinum, each its own price and
 * its own short line on what makes it different.
 *
 * A column rather than a table because a tier has no life of its own. It is
 * never booked, searched, or reported on by itself; it is three short rows a
 * client reads on the package page before picking one, and it is written and
 * replaced whole every time the package is saved. The same reasoning the
 * `includes`, `services` and `images` columns on this table already follow.
 *
 * `price` stays the headline and stays the column every listing, filter and
 * sort reads. When tiers exist the controller keeps it equal to the cheapest
 * of them, so a package can never be listed at one figure and sold at another.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->json('tiers')->nullable()->after('price_unit');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('tiers');
        });
    }
};
