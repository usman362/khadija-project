<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rule R56 — three wrong passwords in a rolling 24 hours locks the account
 * until a password reset is completed.
 *
 * The counting is a rate limiter's job and stays there. This column is the
 * other half: the lock has to OUTLIVE the counter, because R56 rules out a
 * self-unlock after a cooldown, and every rate limiter unlocks by expiring.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('login_locked_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('login_locked_at');
        });
    }
};
