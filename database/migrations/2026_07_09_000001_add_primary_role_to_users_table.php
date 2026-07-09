<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * primary_role = the user's "home" account type, set at registration and never
 * changed by the client/professional mode switch. Login always lands the user
 * in their primary role, so a professional who switched to client and logged
 * out still logs back in as a professional (and vice-versa).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('primary_role')->nullable()->after('email');
        });

        // Backfill existing users from their assigned Spatie roles.
        // Single-role users get that role; dual client+supplier users default
        // to supplier (a professional business identity is the stronger "home",
        // matching the product owner's expectation) — going forward the switch
        // no longer affects this.
        $rows = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->select('model_has_roles.model_id', 'roles.name')
            ->get()
            ->groupBy('model_id');

        foreach ($rows as $userId => $roleRows) {
            $names = $roleRows->pluck('name')->all();

            if (in_array('admin', $names, true)) {
                $primary = 'admin';
            } elseif (in_array('supplier', $names, true)) {
                $primary = 'supplier';
            } elseif (in_array('client', $names, true)) {
                $primary = 'client';
            } elseif (in_array('influencer', $names, true)) {
                $primary = 'influencer';
            } else {
                $primary = $names[0] ?? null;
            }

            if ($primary) {
                DB::table('users')->where('id', $userId)->update(['primary_role' => $primary]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('primary_role');
        });
    }
};
