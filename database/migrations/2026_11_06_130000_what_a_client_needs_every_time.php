<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The standing requirements a professional records against a client.
 *
 * Khadijah's My Clients screen has a Key Requirements column: insurance
 * certificate yes or no, invoice terms, and so on. None of it is something
 * the platform knows. It is what this client asks for every time, learned by
 * working for them, so it is the professional's own words beside their own
 * note and nobody else sees it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professional_client_notes', function (Blueprint $table) {
            $table->string('requirements', 300)->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('professional_client_notes', function (Blueprint $table) {
            $table->dropColumn('requirements');
        });
    }
};
