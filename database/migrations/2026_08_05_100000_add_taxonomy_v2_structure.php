<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Room for Sir Peter's V2 category tree to be built alongside the live one.
 *
 * V2 is not a renamed version of what is there now — it is a different shape:
 *
 *   Event Types (106)      what the client is hosting: Wedding, Christmas Party
 *   Service Categories (27) what they then browse: Catering, DJs, Photography
 *   Services (241)          the bookable thing: Buffet Catering, Wedding DJs
 *
 * A Service Category is NOT owned by one Event Type — nearly every event needs
 * catering. What connects them is the archetype: each Event Type belongs to one
 * of 13 archetypes, and each archetype marks every Service Category as
 * Essential, Common or Occasional. That is `category_relevance`.
 *
 * Everything here is additive and nullable. The 360 live rows are stamped v1
 * and keep working exactly as before; nothing switches over until the taxonomy
 * version in config says so.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Which tree a row belongs to. Existing rows are v1.
            $table->string('taxonomy_version', 8)->default('v1')->after('parent_id');

            // v2 only — v1 rows leave these null.
            $table->string('kind', 24)->nullable()->after('taxonomy_version');
            $table->string('archetype', 80)->nullable()->after('kind');
            $table->string('popularity_tier', 16)->nullable()->after('archetype');
            // Where a service plausibly has a second home, e.g. Ice Cream Carts
            // sits under Bakery & Desserts but also reads as Catering.
            $table->string('cross_fit_alt', 120)->nullable()->after('popularity_tier');

            $table->index(['taxonomy_version', 'kind']);
        });

        // Slugs are only unique within a tree — v1 and v2 both want
        // "catering-food-services", and both must be able to exist at once.
        $indexes = collect(Schema::getIndexes('categories'))->pluck('name');
        if ($indexes->contains('categories_slug_unique')) {
            Schema::table('categories', fn (Blueprint $t) => $t->dropUnique('categories_slug_unique'));
            Schema::table('categories', fn (Blueprint $t) => $t->unique(['taxonomy_version', 'slug']));
        }

        Schema::create('category_relevance', function (Blueprint $table) {
            $table->id();
            $table->string('archetype', 80);
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            // Essential / Common / Occasional — how strongly this service
            // category applies to events of this archetype.
            $table->string('tier', 16);
            // The handful of services worth surfacing first for this pairing.
            $table->text('signature_services')->nullable();
            $table->timestamps();

            $table->unique(['archetype', 'category_id']);
            $table->index('archetype');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_relevance');

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['taxonomy_version', 'kind']);
            $table->dropUnique(['taxonomy_version', 'slug']);
            $table->dropColumn([
                'taxonomy_version', 'kind', 'archetype', 'popularity_tier', 'cross_fit_alt',
            ]);
            $table->unique('slug');
        });
    }
};
