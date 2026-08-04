<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The details a certificate of insurance actually has to carry.
 *
 * Until now a professional uploaded a file and a policy number, an admin
 * stamped liability_insurance_verified_at, and that was the end of it. Nothing
 * recorded who the insurer was, how much cover the policy carried, or — the
 * one that matters — when it runs out.
 *
 * Without an expiry date the "Insured" badge is permanent: a policy verified
 * in 2025 still reads as insured in 2027, on a page a client uses to decide
 * who to hand their event to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('liability_insurance_insurer', 160)->nullable()
                ->after('liability_insurance_number');
            // Stored in whole dollars; policies are not quoted in cents.
            $table->unsignedBigInteger('liability_insurance_coverage')->nullable()
                ->after('liability_insurance_insurer');
            $table->date('liability_insurance_effective_from')->nullable()
                ->after('liability_insurance_coverage');
            $table->date('liability_insurance_expires_on')->nullable()
                ->after('liability_insurance_effective_from');

            // Lets the admin queue sort by what lapses first.
            $table->index('liability_insurance_expires_on');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropIndex(['liability_insurance_expires_on']);
            $table->dropColumn([
                'liability_insurance_insurer',
                'liability_insurance_coverage',
                'liability_insurance_effective_from',
                'liability_insurance_expires_on',
            ]);
        });
    }
};
