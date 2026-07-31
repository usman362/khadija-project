<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop packages.coop_partner_id — the last of the Team / Co-Op "combined force"
 * model, retired platform-wide on 2026-07-15.
 *
 * The behaviour went at the time: the create form stopped offering a partner,
 * the controller forces the column to null on every save, and the public
 * listing is solo-only. The column itself stayed, and with it four rows still
 * naming a partner from before the decision — data that no longer means
 * anything, sitting where a future query could still pick it up and quietly
 * resurrect a feature we removed.
 *
 * down() puts the column back so the migration that created it can still roll
 * back in order. It does not restore the four values; they describe a product
 * that no longer exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Same four rows also still carry type = 'co-op'. Nothing renders it —
        // the badge that did was deleted with the feature — so it is invisible
        // rather than wrong, which is exactly how it survived two weeks past
        // the decision. Normalised so no row describes a product we retired.
        DB::table('packages')->where('type', 'co-op')->update(['type' => 'solo']);

        Schema::table('packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coop_partner_id');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->foreignId('coop_partner_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();
        });
    }
};
