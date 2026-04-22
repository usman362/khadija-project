<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_features', function (Blueprint $table) {
            // Machine-readable feature code for programmatic gating
            // Examples: 'ai.budget_allocator', 'ai.vendor_matchmaking', 'ai.review_writer'
            $table->string('feature_code', 60)->nullable()->after('feature');

            // Monthly quota for usage-metered features (0 = unlimited, null = not applicable)
            $table->unsignedInteger('quota_monthly')->nullable()->after('feature_code');

            $table->index('feature_code');
        });
    }

    public function down(): void
    {
        Schema::table('plan_features', function (Blueprint $table) {
            $table->dropIndex(['feature_code']);
            $table->dropColumn(['feature_code', 'quota_monthly']);
        });
    }
};
