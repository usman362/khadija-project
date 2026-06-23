<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creator-analytics fields for the influencer portal dashboard
     * (followers, engagement, profile score). Campaign/content/audience
     * metrics live in their own tables (built in the Analytics phase).
     */
    public function up(): void
    {
        Schema::table('influencers', function (Blueprint $table) {
            $table->unsignedBigInteger('followers_count')->default(0)->after('monthly_reach');
            $table->decimal('engagement_rate', 5, 2)->default(0)->after('followers_count');
            $table->unsignedTinyInteger('profile_score')->default(0)->after('engagement_rate');
        });
    }

    public function down(): void
    {
        Schema::table('influencers', function (Blueprint $table) {
            $table->dropColumn(['followers_count', 'engagement_rate', 'profile_score']);
        });
    }
};
