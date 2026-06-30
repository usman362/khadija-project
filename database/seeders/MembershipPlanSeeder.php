<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        // Memberships sell the SIX value areas (visibility, opportunities, AI,
        // revenue, trust, automation) — not just limits — and present AI as
        // branded GigResource IQ™ suites rather than individual tools
        // (Peter / ChatGPT membership restructure).
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                // Priced at $1 (not free) on purpose — a nominal card charge is
                // enough friction to discourage throwaway / fraudulent accounts
                // while staying effectively free for real users.
                'description' => 'Perfect for new professionals building their business.',
                'price' => 1,
                'billing_cycle' => '6_month',
                'duration_days' => 180,
                'max_events' => 3,
                'max_bookings' => 5,
                'has_chat' => true,
                'has_priority_support' => false,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'badge_text' => null,
                'badge_color' => null,
                'features' => [
                    'Marketplace access',
                    'Up to 3 active events',
                    'Up to 5 active bookings',
                    'Up to 2 service categories',
                    'Standard marketplace visibility',
                    'Standard proposal templates',
                    'Unlimited event bookmarks',
                    'Custom cover photo · basic profile',
                    'Email notifications · basic chat support',
                    'GigResource IQ™ Planning Suite (manual helpers only)',
                    'Accept deposits up to $500',
                    'Follow up to 5 clients',
                    // AI gating (AiFeatureGate) — Starter = manual assistance only.
                    ['feature' => 'AI Review Writer (10 / month)', 'feature_code' => 'ai.review_writer', 'quota_monthly' => 10],
                    ['feature' => 'AI Budget Allocator', 'feature_code' => 'ai.budget_allocator', 'is_included' => false],
                    ['feature' => 'AI Vendor Matchmaking', 'feature_code' => 'ai.vendor_matchmaking', 'is_included' => false],
                ],
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For growing event businesses.',
                'price' => 29.99,
                'billing_cycle' => '6_month',
                'duration_days' => 180,
                'max_events' => 25,
                'max_bookings' => 50,
                'has_chat' => true,
                'has_priority_support' => false,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'badge_text' => 'Most Popular',
                'badge_color' => 'primary',
                'features' => [
                    'Up to 25 active events',
                    'Up to 50 active bookings',
                    'Up to 20 service categories',
                    'High marketplace visibility · priority search placement',
                    'Access to client phone numbers',
                    'Sealed gigs · Verified Professional badge',
                    'Full chat with file attachments',
                    'Email & SMS notifications',
                    'Analytics dashboard · export reports',
                    'Booking statistics · bid insights',
                    'GigResource IQ™ Business Suite (Manual + Semi-Assisted AI)',
                    'Early access to new SSRs / MSRs / ESRs (after 60 min)',
                    'Accept deposits up to $1,000',
                    '20 client followings · video & audio samples',
                    'Unlock marketplace rewards',
                    // AI gating — Professional = Manual + Semi-Assisted.
                    ['feature' => 'AI Review Writer (unlimited)', 'feature_code' => 'ai.review_writer', 'quota_monthly' => 0],
                    ['feature' => 'AI Budget Allocator (30 / month)', 'feature_code' => 'ai.budget_allocator', 'quota_monthly' => 30],
                    ['feature' => 'AI Vendor Matchmaking (30 / month)', 'feature_code' => 'ai.vendor_matchmaking', 'quota_monthly' => 30],
                ],
            ],
            [
                'name' => 'Elite',
                'slug' => 'enterprise',
                'description' => 'Built for high-performing professionals & agencies.',
                'price' => 99.99,
                'billing_cycle' => '6_month',
                'duration_days' => 180,
                'max_events' => null,
                'max_bookings' => null,
                'has_chat' => true,
                'has_priority_support' => true,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
                'badge_text' => 'Best Value',
                'badge_color' => 'success',
                'features' => [
                    'Unlimited events, bookings & categories',
                    'Highest marketplace visibility · premium search placement',
                    'High-value event access & bidding',
                    'Sealed gigs · Verified Professional badge',
                    'Immediate early access to new SSRs / MSRs / ESRs',
                    'Advanced analytics · booking conversion reports',
                    'Invoice & payment tracking · client engagement analytics',
                    'Export PDF & Excel · API access',
                    'Full chat, email, SMS & push notifications',
                    'Live support · dedicated account manager',
                    'GigResource IQ™ Complete Suite (Manual + Semi + Maximum AI)',
                    'Accept deposits up to $2,000',
                    'Unlimited client followings · call button on bid cards',
                    'Custom branding',
                    // AI gating — Elite = full unlimited suite.
                    ['feature' => 'AI Review Writer (unlimited)', 'feature_code' => 'ai.review_writer', 'quota_monthly' => 0],
                    ['feature' => 'AI Budget Allocator (unlimited)', 'feature_code' => 'ai.budget_allocator', 'quota_monthly' => 0],
                    ['feature' => 'AI Vendor Matchmaking (unlimited)', 'feature_code' => 'ai.vendor_matchmaking', 'quota_monthly' => 0],
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $features = $planData['features'];
            unset($planData['features']);

            $plan = MembershipPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );

            // Clear existing features and re-create. A feature entry is either a
            // plain string (human-readable, always included) or an array carrying
            // an AI feature_code + quota_monthly so the AiFeatureGate can read it
            // (Developer Feedback v1.1 §8.3 — AI tools assigned to tiers).
            $plan->features()->delete();
            foreach ($features as $index => $feature) {
                $row = is_array($feature) ? $feature : ['feature' => $feature];

                $plan->features()->create(array_merge([
                    'is_included'   => true,
                    'feature_code'  => null,
                    'quota_monthly' => null,
                    'sort_order'    => $index,
                ], $row));
            }
        }
    }
}
