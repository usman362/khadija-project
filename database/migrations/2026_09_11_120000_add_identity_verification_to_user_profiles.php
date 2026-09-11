<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Account verification for clients: they upload a government ID, the team
 * reviews it, and approval earns the Verified Client badge (PM-14).
 *
 * Named like the professional badges ({badge}_number, _doc, _verified_at) so
 * the existing admin verification queue handles it as one more badge. The
 * document lives on the PRIVATE disk: it is a government ID, never a public
 * file and never shown to professionals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('identity_number', 100)->nullable();   // which kind of ID, e.g. "Passport"
            $table->string('identity_doc')->nullable();            // path on the private disk
            $table->timestamp('identity_submitted_at')->nullable();
            $table->timestamp('identity_verified_at')->nullable();
            $table->string('identity_rejected_note', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'identity_number', 'identity_doc', 'identity_submitted_at',
                'identity_verified_at', 'identity_rejected_note',
            ]);
        });
    }
};
