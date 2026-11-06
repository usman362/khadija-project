<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The two standing requirements Khadijah's My Clients column actually names:
 * whether this client wants an insurance certificate, and how they pay.
 *
 * They were one free text box, which meant the column could not be read at a
 * glance or filtered on. Nullable on purpose: not knowing whether a client
 * wants a certificate is a different answer from knowing they do not.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professional_client_notes', function (Blueprint $table) {
            $table->boolean('wants_insurance')->nullable()->after('requirements');
            $table->string('invoice_terms', 40)->nullable()->after('wants_insurance');
        });
    }

    public function down(): void
    {
        Schema::table('professional_client_notes', function (Blueprint $table) {
            $table->dropColumn(['wants_insurance', 'invoice_terms']);
        });
    }
};
