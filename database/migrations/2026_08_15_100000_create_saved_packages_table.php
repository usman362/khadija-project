<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The heart on a package card.
 *
 * It used to be drawn and then removed, because clicking it only toggled a CSS
 * class — there was no table for it to save to, so it promised a shortlist that
 * did not exist. Peter's Package Service Search mockup has it, so this is the
 * table, and the heart comes back wired to it.
 *
 * Deliberately the same shape as saved_professionals: the client owns the row,
 * the pair is unique, and deleting either side takes the save with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['client_id', 'package_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_packages');
    }
};
