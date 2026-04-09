<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Canonical permission list for the system.
     *
     * @return array<int, string>
     */
    public static function list(): array
    {
        return [
            'dashboard.view',
            'events.view_any',
            'events.view',
            'events.create',
            'events.update',
            'events.delete',
            'events.publish',
            'bookings.view_any',
            'bookings.view',
            'bookings.create',
            'bookings.update',
            'messages.view_any',
            'messages.view',
            'messages.create',
            'agreement_log.view_any',
            'users.view_any',
            'users.create',
            'users.update',
            'users.delete',
            'users.update_roles_permissions',
            'roles.view_any',
            'roles.create',
            'roles.update',
            'roles.delete',
            'permissions.view_any',
            'permissions.create',
            'permissions.update',
            'permissions.delete',
            'membership_plans.view_any',
            'membership_plans.subscribe',
            'membership_plans.create',
            'membership_plans.update',
            'membership_plans.delete',
            'agreements.view_any',
            'agreements.generate',
            'agreements.accept',
            'payment_settings.manage',
            'payments.view',
            // Influencer module
            'influencers.view_any',
            'influencers.view',
            'influencers.approve',
            'influencers.reject',
            'influencers.manage_payouts',
            'influencer.dashboard.view',
            'influencer.referrals.view',
            'influencer.payouts.request',
            'influencer.payouts.view',
        ];
    }

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::list() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }
}
