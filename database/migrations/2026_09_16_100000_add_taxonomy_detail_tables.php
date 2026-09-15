<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The two halves of the service picker the taxonomy sheets carry but the
 * category tree had nowhere to put.
 *
 * `search_terms` holds the words clients actually type. "Cover band for hire"
 * has to find Live Bands, and matching on the service name alone never would,
 * so the synonyms live beside the row they lead to.
 *
 * `event_type_service` is which services are offered for which event type
 * (Khadijah's level 1 to level 3 coverage). A wedding and a trade show do not
 * shop from the same list; without this the picker can only show all 241.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->text('search_terms')->nullable()->after('long_description');
        });

        Schema::create('event_type_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_type_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['event_type_id', 'service_id']);
            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_type_service');

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('search_terms');
        });
    }
};
