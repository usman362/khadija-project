<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat, part 2: mute, block and when someone was last active.
 *
 * Sir Peter asked for Freelancer-style chat options on 2026-09-10; Ali started
 * this part the same day.
 *
 * - Mute is one person's choice about one conversation, so it sits on the
 *   participant row, like archive.
 * - Block is between two people, not one conversation: once blocked, no
 *   conversation between them, old or new, carries messages.
 * - last_active_at is a timestamp, written at most every five minutes. It says
 *   when someone was last here. It is not presence and is never shown as
 *   "online".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->timestamp('muted_at')->nullable()->after('archived_at');
        });

        Schema::create('user_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['blocker_id', 'blocked_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_active_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('last_active_at'));
        Schema::dropIfExists('user_blocks');
        Schema::table('conversation_participants', fn (Blueprint $t) => $t->dropColumn('muted_at'));
    }
};
