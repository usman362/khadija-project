<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Client "My Professionals / Saved Vendors" — the retention surface the docs
 * flag as NEEDED (the mirror of the pro's client registry). A client keeps pros
 * they want to re-invite / re-book here. Worked-with pros are derived from
 * bookings; this table holds explicit saves (a pro not yet hired, or pinning a
 * favorite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_professionals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->string('note', 200)->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'professional_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_professionals');
    }
};
