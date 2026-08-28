<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proof that a user was shown the location/state rules and agreed to them.
 *
 * Sir Peter's disclosure (26 Aug 2026) asks for a record of acceptance —
 * who, when, which version, and from what address — because the thing being
 * agreed to is a legal limit on who they may work with, and "we showed
 * everyone a checkbox" is not evidence about any one person.
 *
 * Versioned on purpose. The limit is temporary: when a state opens up for
 * cross-state work the wording changes, and a row must say which wording that
 * user actually saw, not whichever is current.
 *
 * One row per user per version — re-accepting the same version updates the
 * row rather than stacking duplicates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_disclosures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('version', 64);
            $table->timestamp('accepted_at');

            // Nullable: a request can legitimately arrive without one, and a
            // missing address must not cost someone their account.
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();
            $table->unique(['user_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_disclosures');
    }
};
