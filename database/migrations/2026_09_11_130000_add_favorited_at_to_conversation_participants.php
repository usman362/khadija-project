<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Favorites in the message dock (Sir Peter's mockup, 2026-09-11).
 *
 * Per participant, like archived_at and muted_at: starring a conversation is
 * one person's choice and says nothing to the other side.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->timestamp('favorited_at')->nullable()->after('muted_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropColumn('favorited_at');
        });
    }
};
