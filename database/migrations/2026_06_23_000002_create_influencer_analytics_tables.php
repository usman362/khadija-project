<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Influencer analytics tracking (creator portal — Analytics section).
     * Campaigns + content + daily time-series are real tracked rows; aggregate
     * audience/channel/device breakdowns live in influencers.analytics_meta JSON.
     */
    public function up(): void
    {
        Schema::create('influencer_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('influencer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('active'); // active|paused|ended
            $table->string('channel')->nullable();       // social|email|website|referral
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->decimal('earnings', 12, 2)->default(0);
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->timestamps();
            $table->index(['influencer_id', 'status']);
        });

        Schema::create('influencer_content', function (Blueprint $table) {
            $table->id();
            $table->foreignId('influencer_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('platform')->nullable();   // instagram|youtube|tiktok|blog|...
            $table->string('type')->nullable();       // post|reel|video|article|story
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index('influencer_id');
        });

        Schema::create('influencer_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('influencer_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedInteger('engagements')->default(0);
            $table->decimal('earnings', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['influencer_id', 'date']);
        });

        Schema::table('influencers', function (Blueprint $table) {
            $table->json('analytics_meta')->nullable()->after('profile_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('influencer_daily_stats');
        Schema::dropIfExists('influencer_content');
        Schema::dropIfExists('influencer_campaigns');
        Schema::table('influencers', function (Blueprint $table) {
            $table->dropColumn('analytics_meta');
        });
    }
};
