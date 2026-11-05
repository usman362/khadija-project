<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A professional's own note and tags against one of their clients.
 *
 * Khadijah's My Clients screen carries a Tags column and a Quick Note beside
 * each client. Neither had anywhere to live, and the only honest way to fill
 * them is for the professional to write them: nothing on the platform records
 * that a client is "high value" or "prefers evening setup".
 *
 * Deliberately not on the client's own record. This is one professional's
 * private note about somebody they have worked for, and the client must never
 * see it, so it belongs to the pair rather than to the person.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_client_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->json('tags')->nullable();
            $table->string('note', 400)->nullable();
            $table->timestamps();

            $table->unique(['supplier_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_client_notes');
    }
};
