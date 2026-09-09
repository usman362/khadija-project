<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How is the food getting there?
 *
 * Sir Peter, 2026-09-09, on affiliating with a courier: "if the users want the
 * option of delivering not by the clients or the professionals then maybe we
 * can be affiliated with ubereats.com or DoorDash.com."
 *
 * Nobody has ever been asked, so nobody knows how many clients want a third
 * option. This column is the question, asked on catering requests only. In a
 * month it is a number instead of a guess, and the integration — which carries
 * real food-safety and liability questions — is decided on demand that exists
 * rather than demand we hope for.
 *
 * Nullable, and it stays nullable: a request that is not about food has no
 * answer to give, and neither does one raised before we started asking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('delivery_mode', 24)->nullable()->after('guest_count');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('delivery_mode');
        });
    }
};
