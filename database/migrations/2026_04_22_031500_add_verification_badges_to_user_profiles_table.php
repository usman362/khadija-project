<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Adds "BestPickPro-style" trust badges to professional profiles:
     *
     *   1. Trade License
     *   2. General Liability Insurance
     *   3. Workers' Compensation
     *
     * Each badge has three states represented by two columns:
     *   - {badge}_doc:   path to the uploaded proof document (null = not uploaded)
     *   - {badge}_verified_at: timestamp of admin approval (null = pending/rejected)
     *
     * UX reads this as:
     *   doc null + verified_at null            → "Not submitted"
     *   doc present + verified_at null         → "Pending review"
     *   doc present + verified_at not null     → "Verified ✔" (show badge to clients)
     *
     * Separate *_number columns store the policy/license number (public-safe ID).
     */
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            // Trade License
            $table->string('trade_license_number', 100)->nullable()->after('certifications');
            $table->string('trade_license_doc')->nullable()->after('trade_license_number');
            $table->timestamp('trade_license_verified_at')->nullable()->after('trade_license_doc');

            // General Liability Insurance
            $table->string('liability_insurance_number', 100)->nullable()->after('trade_license_verified_at');
            $table->string('liability_insurance_doc')->nullable()->after('liability_insurance_number');
            $table->timestamp('liability_insurance_verified_at')->nullable()->after('liability_insurance_doc');

            // Workers' Compensation
            $table->string('workers_comp_number', 100)->nullable()->after('liability_insurance_verified_at');
            $table->string('workers_comp_doc')->nullable()->after('workers_comp_number');
            $table->timestamp('workers_comp_verified_at')->nullable()->after('workers_comp_doc');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'trade_license_number',
                'trade_license_doc',
                'trade_license_verified_at',
                'liability_insurance_number',
                'liability_insurance_doc',
                'liability_insurance_verified_at',
                'workers_comp_number',
                'workers_comp_doc',
                'workers_comp_verified_at',
            ]);
        });
    }
};
