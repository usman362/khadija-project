<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Save Search" on the Package Service Search rail.
 *
 * A client who has narrowed six filters down to the mix they actually want
 * should not have to rebuild it next visit. The row stores the filter values,
 * not a rendered URL, so a saved search keeps working if the query string ever
 * changes shape.
 *
 * `surface` is here so the next search page that grows this button (find
 * professionals, the bidding board) can share the table instead of adding its
 * own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('surface', 40)->default('packages');
            $table->string('label', 120);
            $table->json('params');
            $table->timestamps();

            $table->index(['user_id', 'surface']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
    }
};
