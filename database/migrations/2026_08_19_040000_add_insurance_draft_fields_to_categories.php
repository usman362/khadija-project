<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Insurance matrix fields — draft only.
 *
 * Adopted 2026-08-19 so a broker can fill Required / Conditional / Not Required
 * plus coverage type and Tier A/B/C without anyone inventing live rules.
 * InsuranceRequirement still reads config('compliance.insurance_required_categories')
 * until insurance_matrix_signed_off is flipped after broker + attorney sign-off.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('insurance_requirement', 24)->nullable()->after('cross_fit_alt');
            $table->string('insurance_type', 80)->nullable()->after('insurance_requirement');
            $table->string('insurance_tier', 8)->nullable()->after('insurance_type');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['insurance_requirement', 'insurance_type', 'insurance_tier']);
        });
    }
};
