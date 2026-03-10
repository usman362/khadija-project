<?php

namespace Database\Seeders;

use App\Domain\Auth\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = PermissionSeeder::list();

        $adminRole = Role::findOrCreate(RoleName::ADMIN->value, 'web');
        $clientRole = Role::findOrCreate(RoleName::CLIENT->value, 'web');
        $supplierRole = Role::findOrCreate(RoleName::SUPPLIER->value, 'web');

        $adminRole->syncPermissions(
            Permission::query()->where('guard_name', 'web')->whereIn('name', $permissions)->get()
        );

        $clientPermissions = [
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
            'membership_plans.view_any',
            'membership_plans.subscribe',
            'agreements.view_any',
            'agreements.generate',
            'agreements.accept',
            'payments.view',
        ];

        $supplierPermissions = [
            'dashboard.view',
            'events.view_any',
            'events.view',
            'events.update',
            'bookings.view_any',
            'bookings.view',
            'bookings.update',
            'messages.view_any',
            'messages.view',
            'messages.create',
            'agreement_log.view_any',
            'membership_plans.view_any',
            'membership_plans.subscribe',
            'agreements.view_any',
            'agreements.generate',
            'agreements.accept',
            'payments.view',
        ];

        $clientRole->syncPermissions(
            Permission::query()->where('guard_name', 'web')->whereIn('name', $clientPermissions)->get()
        );

        $supplierRole->syncPermissions(
            Permission::query()->where('guard_name', 'web')->whereIn('name', $supplierPermissions)->get()
        );
    }
}
