<?php

namespace Database\Seeders;

use App\Domain\Auth\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => 'password',
            ]
        );

        $admin->syncRoles([RoleName::ADMIN->value]);
    }
}
