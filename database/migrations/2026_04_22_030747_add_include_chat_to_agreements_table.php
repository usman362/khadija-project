<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Adds the "include_chat" flag to agreements.
     *
     * When true, the AgreementGeneratorService injects the full client ↔
     * professional conversation into the OpenAI prompt — producing a more
     * detailed, chat-aware agreement. When false, the agreement is generated
     * from booking context only (cleaner / less repetitive).
     *
     * Default: true, so existing code paths keep working.
     */
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->boolean('include_chat')->default(true)->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn('include_chat');
        });
    }
};
