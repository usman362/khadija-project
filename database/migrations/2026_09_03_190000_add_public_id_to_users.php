<?php

use App\Models\User;
use App\Support\GigResourceId;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idea 1 — a permanent public reference for every account.
 *
 * Sir Peter, 2026-09-03: CL-482731, PRO-100482, INF-900117 — quoted in
 * support, verification, transactions, reports, disputes and messaging, and
 * the way to tell two people with the same name apart.
 *
 * Separate from users.id on purpose. The database id says how many accounts
 * exist and can be walked; a public reference should say neither.
 *
 * Unique index, not a uniqueness check in code: two registrations in the same
 * moment must not be able to take the same number, and only the index can
 * actually decide that.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('public_id', 24)->nullable()->unique()->after('id');
        });

        // Every account that already exists gets one, in id order, so the
        // backfill is repeatable and nobody is left without a reference the
        // first time support asks for it.
        User::withTrashed()->whereNull('public_id')->orderBy('id')->chunkById(200, function ($users) {
            foreach ($users as $user) {
                GigResourceId::assign($user);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
