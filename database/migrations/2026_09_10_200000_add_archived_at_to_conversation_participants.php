<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Archiving a conversation, per person.
 *
 * Sir Peter, 2026-09-10, asked for Freelancer-style chat options, archive among
 * them. The Archived tab already existed and could never show anything: the
 * script behind it hid every row, because there was nowhere to record that a
 * conversation had been archived.
 *
 * On the participant row, not the conversation: archiving tidies one person's
 * inbox. The other side keeps it exactly where it was.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('joined_at');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
