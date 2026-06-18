<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Address verification state (Developer Feedback v1.1 §7.3–7.5).
     * Tracks the risk-based verification outcome per professional/business so
     * the dashboard can show a status label and the admin can triage the
     * manual-review / home-address edge-case queue.
     */
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('address_status', 40)->default('pending')->after('zip_code');
            $table->unsignedTinyInteger('address_verification_attempts')->default(0)->after('address_status');
            $table->boolean('address_flagged_home')->default(false)->after('address_verification_attempts');
            $table->timestamp('address_verified_at')->nullable()->after('address_flagged_home');
            $table->timestamp('address_locked_at')->nullable()->after('address_verified_at');
            $table->json('address_verification_meta')->nullable()->after('address_locked_at');

            $table->index('address_status');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropIndex(['address_status']);
            $table->dropColumn([
                'address_status',
                'address_verification_attempts',
                'address_flagged_home',
                'address_verified_at',
                'address_locked_at',
                'address_verification_meta',
            ]);
        });
    }
};
