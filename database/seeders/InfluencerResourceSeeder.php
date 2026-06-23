<?php

namespace Database\Seeders;

use App\Models\InfluencerResource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the shared influencer-program resource library (Resources section).
 * Idempotent — clears and reseeds. Real rows the Resources pages read from.
 */
class InfluencerResourceSeeder extends Seeder
{
    public function run(): void
    {
        InfluencerResource::query()->delete();

        $rows = [
            // [title, type, category, badge, featured, level, lessons, mins, downloads]
            ['Influencer Success Guide',          'guide',    'Getting Started',        'new',      true,  null, 0, 0, 12400],
            ['Social Media Best Practices',       'guide',    'Marketing & Promotion',  'popular',  true,  null, 0, 0, 9800],
            ['Campaign Proposal Template',        'template', 'Tools & Resources',      'template', true,  null, 0, 0, 7300],
            ['Content Strategy Planner',          'template', 'Marketing & Promotion',  'trending', true,  null, 0, 0, 11100],
            ['Live Q&A: Grow & Earn',             'webinar',  'Earnings & Payouts',     'webinar',  true,  null, 0, 0, 0],
            ['How Commissions Work',              'guide',    'Earnings & Payouts',     null,       false, null, 0, 0, 12400],
            ['Platform Policies & Guidelines',    'guide',    'Account & Profile',      null,       false, null, 0, 0, 9800],
            ['Top 10 Ways to Promote Your Link',  'video',    'Marketing & Promotion',  null,       false, null, 0, 14, 15200],
            ['Tax Information for Influencers',    'guide',    'Earnings & Payouts',     null,       false, null, 0, 0, 7300],
            ['Content Calendar Template',         'template', 'Tools & Resources',      null,       false, null, 0, 0, 11100],
            ['Payment & Payout Explained',        'video',    'Earnings & Payouts',     null,       false, null, 0, 9, 8200],
            ['Brand Collaboration Checklist',     'checklist','Marketing & Promotion',  null,       false, null, 0, 0, 5400],
            ['Engagement Boosting Strategies',    'guide',    'Marketing & Promotion',  null,       false, null, 0, 0, 6900],
            ['Email Outreach Templates',          'template', 'Tools & Resources',      null,       false, null, 0, 0, 4800],
            // Articles
            ['5 Habits of Top-Earning Influencers','article', 'Marketing & Promotion',  'popular',  true,  null, 0, 6, 0],
            ['How to Read Your Analytics',         'article', 'Analytics & Insights',   null,       true,  null, 0, 5, 0],
            ['Building Trust With Your Audience',  'article', 'Marketing & Promotion',  'trending', false, null, 0, 7, 0],
            ['Getting Your First Referral',        'article', 'Getting Started',        'new',      false, null, 0, 4, 0],
            // Courses (Academy)
            ['Getting Started on GigResource',    'course',   'Getting Started',        null,       true,  'beginner',     5, 20, 0],
            ['Promote Like a Pro',                'course',   'Marketing & Promotion',  null,       true,  'intermediate', 6, 35, 0],
            ['Understanding Analytics',           'course',   'Analytics & Insights',   null,       true,  'intermediate', 7, 40, 0],
            ['Maximize Your Earnings',            'course',   'Earnings & Payouts',     null,       true,  'advanced',     8, 45, 0],
            ['Account & Profile Mastery',         'course',   'Account & Profile',      null,       false, 'beginner',     3, 18, 0],
            ['Advanced Growth Tactics',           'course',   'Tools & Resources',      null,       false, 'advanced',     6, 38, 0],
        ];

        foreach ($rows as [$title, $type, $cat, $badge, $feat, $level, $lessons, $mins, $dl]) {
            InfluencerResource::create([
                'title' => $title,
                'slug' => Str::slug($title),
                'description' => $this->blurb($type),
                'type' => $type,
                'category' => $cat,
                'level' => $level,
                'lessons' => $lessons,
                'duration_minutes' => $mins,
                'downloads' => $dl,
                'badge' => $badge,
                'is_featured' => $feat,
                'published_at' => now()->subDays(rand(1, 90)),
            ]);
        }
    }

    private function blurb(string $type): string
    {
        return match ($type) {
            'video'    => 'Watch this short video walkthrough to level up fast.',
            'template' => 'Use our ready-made template to save time and look professional.',
            'checklist'=> 'A step-by-step checklist so you never miss a thing.',
            'webinar'  => 'Join our live session and ask questions in real time.',
            'article'  => 'A practical read with actionable tips you can use today.',
            'course'   => 'A structured course to build your skills end to end.',
            default    => 'A practical guide to help you grow your influence and earnings.',
        };
    }
}
