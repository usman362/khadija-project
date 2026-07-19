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
                // Canonical pricing (reconciled workflows 2026-07-19):
                // Starter $4.99 / Professional $39.99 / Elite $59.99 per month.
                'description' => 'Perfect for new professionals building their business.',
                'price' => 4.99,
                'billing_cycle' => 'monthly',
                'duration_days' => 30,
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
                    '5% booking commission per finalized contract',
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
                    ['feature' => 'Review Builder (10 / month)', 'feature_code' => 'ai.review_writer', 'quota_monthly' => 10],
                    ['feature' => 'Budget Planner', 'feature_code' => 'ai.budget_allocator', 'is_included' => false],
                    ['feature' => 'Smart Match', 'feature_code' => 'ai.vendor_matchmaking', 'is_included' => false],
                ],
            ],
            [
                'name' => 'Pro',
                'slug' => 'professional',
                'description' => 'For growing event businesses.',
                'price' => 39.99,
                'billing_cycle' => 'monthly',
                'duration_days' => 30,
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
                    '3% booking commission per finalized contract',
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
                    ['feature' => 'Review Builder (unlimited)', 'feature_code' => 'ai.review_writer', 'quota_monthly' => 0],
                    ['feature' => 'Budget Planner (30 / month)', 'feature_code' => 'ai.budget_allocator', 'quota_monthly' => 30],
                    ['feature' => 'Smart Match (30 / month)', 'feature_code' => 'ai.vendor_matchmaking', 'quota_monthly' => 30],
                ],
            ],
            [
                'name' => 'Elite',
                'slug' => 'enterprise',
                'description' => 'Built for high-performing professionals & agencies.',
                'price' => 59.99,
                'billing_cycle' => 'monthly',
                'duration_days' => 30,
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
                    '1.5% booking commission per finalized contract',
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
                    ['feature' => 'Review Builder (unlimited)', 'feature_code' => 'ai.review_writer', 'quota_monthly' => 0],
                    ['feature' => 'Budget Planner (unlimited)', 'feature_code' => 'ai.budget_allocator', 'quota_monthly' => 0],
                    ['feature' => 'Smart Match (unlimited)', 'feature_code' => 'ai.vendor_matchmaking', 'quota_monthly' => 0],
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
