<?php

namespace Database\Seeders;

use App\Models\Influencer;
use App\Models\InfluencerCampaign;
use App\Models\InfluencerContent;
use App\Models\InfluencerDailyStat;
use Illuminate\Database\Seeder;

/**
 * Seeds demo analytics data for the influencer portal's Analytics section.
 * Idempotent per influencer (clears + reseeds). Targets all influencers, or
 * pass --class with a specific one. Real rows the Analytics pages read from.
 */
class InfluencerAnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        Influencer::all()->each(fn (Influencer $inf) => $this->seedFor($inf));
    }

    public function seedFor(Influencer $inf): void
    {
        $inf->campaigns()->delete();
        $inf->content()->delete();
        $inf->dailyStats()->delete();

        // ── Campaigns ──
        $campaigns = [
            ['Summer Music Fest',   'social',   'active'],
            ['Tech Conference 2026','email',    'active'],
            ['Art Expo Launch',     'website',  'active'],
            ['Wedding Showcase',    'social',   'paused'],
            ['Brand Collaboration', 'referral', 'ended'],
            ['Holiday Special',     'social',   'active'],
        ];
        foreach ($campaigns as [$name, $channel, $status]) {
            $clicks = rand(4000, 26000);
            $conv = (int) round($clicks * (rand(60, 110) / 1000));
            InfluencerCampaign::create([
                'influencer_id' => $inf->id, 'name' => $name, 'status' => $status, 'channel' => $channel,
                'clicks' => $clicks, 'conversions' => $conv, 'earnings' => round($conv * rand(35, 60) / 10, 2),
                'started_at' => now()->subDays(rand(20, 90)), 'ended_at' => $status === 'ended' ? now()->subDays(rand(1, 15)) : null,
            ]);
        }

        // ── Content ──
        $content = [
            ['5 Tips for Planning the Perfect Wedding', 'instagram', 'reel'],
            ['Behind the Scenes: Tech Conference',      'youtube',   'video'],
            ['How I Find the Best Event Vendors',        'blog',      'article'],
            ['Top Music Festivals This Summer',          'tiktok',    'video'],
            ['My Event Planning Toolkit',                'instagram', 'post'],
            ['Live from the Art Expo',                   'instagram', 'story'],
            ['Why GigResource Changed My Workflow',      'blog',      'article'],
        ];
        foreach ($content as [$title, $platform, $type]) {
            $views = rand(8000, 180000);
            $clicks = (int) round($views * (rand(8, 25) / 1000));
            InfluencerContent::create([
                'influencer_id' => $inf->id, 'title' => $title, 'platform' => $platform, 'type' => $type,
                'views' => $views, 'clicks' => $clicks, 'conversions' => (int) round($clicks * (rand(60, 120) / 1000)),
                'engagement_rate' => rand(20, 95) / 10, 'published_at' => now()->subDays(rand(1, 60)),
            ]);
        }

        // ── 30 days of daily stats (gentle upward trend) ──
        for ($i = 29; $i >= 0; $i--) {
            $base = 1.0 + (29 - $i) * 0.03; // trend up
            $clicks = (int) (rand(2500, 5500) * $base);
            $conv = (int) round($clicks * (rand(60, 100) / 1000));
            InfluencerDailyStat::create([
                'influencer_id' => $inf->id, 'date' => now()->subDays($i)->toDateString(),
                'clicks' => $clicks, 'conversions' => $conv,
                'views' => (int) (rand(40000, 90000) * $base), 'engagements' => (int) (rand(2000, 6000) * $base),
                'earnings' => round($conv * rand(35, 60) / 10, 2),
            ]);
        }

        // ── Aggregate audience/device/channel breakdowns (JSON) ──
        $inf->update(['analytics_meta' => [
            'gender'    => ['female' => 68, 'male' => 32],
            'age'       => ['18–24' => 14, '25–34' => 32, '35–44' => 28, '45–54' => 18, '55+' => 8],
            'locations' => [
                ['name' => 'United States', 'pct' => 38], ['name' => 'India', 'pct' => 16],
                ['name' => 'United Kingdom', 'pct' => 9], ['name' => 'Canada', 'pct' => 6], ['name' => 'Australia', 'pct' => 5],
            ],
            'interests' => [
                ['name' => 'Music', 'pct' => 72], ['name' => 'Business', 'pct' => 58], ['name' => 'Technology', 'pct' => 48],
                ['name' => 'Travel', 'pct' => 42], ['name' => 'Health & Fitness', 'pct' => 36],
            ],
            'devices'   => ['mobile' => 68, 'desktop' => 24, 'tablet' => 8],
            'channels'  => ['Social Media' => 45.6, 'Email' => 25.3, 'Website' => 18.7, 'Referrals' => 10.4],
        ]]);
    }
}
