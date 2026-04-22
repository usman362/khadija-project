<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                // Priced at $1 (not free) on purpose — a nominal card charge is
                // enough friction to discourage throwaway / fraudulent accounts
                // while staying effectively free for real users.
                'description' => 'Get started for just $1 — full access to core features for 6 months.',
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
                    'Up to 3 events',
                    'Up to 5 bookings',
                    'Basic chat support',
                    'Email notifications',
                    'Standard templates',
                ],
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Ideal for growing businesses — 12-month partnership.',
                'price' => 299.99,
                'billing_cycle' => '12_month',
                'duration_days' => 365,
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
                    'Up to 25 events',
                    'Up to 50 bookings',
                    'Full chat with attachments',
                    'Email & SMS notifications',
                    'Custom templates',
                    'Analytics dashboard',
                    'Export reports',
                ],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For large organizations — 18-month commitment with unlimited access.',
                'price' => 1499.99,
                'billing_cycle' => '18_month',
                'duration_days' => 540,
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
                    'Unlimited events',
                    'Unlimited bookings',
                    'Full chat with attachments',
                    'Priority support 24/7',
                    'Email, SMS & push notifications',
                    'Custom templates & branding',
                    'Advanced analytics',
                    'Export reports (PDF, Excel)',
                    'API access',
                    'Dedicated account manager',
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

            // Clear existing features and re-create
            $plan->features()->delete();
            foreach ($features as $index => $feature) {
                $plan->features()->create([
                    'feature' => $feature,
                    'is_included' => true,
                    'sort_order' => $index,
                ]);
            }
        }
    }
}
