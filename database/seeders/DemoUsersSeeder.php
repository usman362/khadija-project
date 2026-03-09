<?php

namespace Database\Seeders;

use App\Domain\Auth\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Client User',
                'email' => 'client@example.com',
                'role' => RoleName::CLIENT->value,
            ],
            [
                'name' => 'Supplier User',
                'email' => 'supplier@example.com',
                'role' => RoleName::SUPPLIER->value,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => 'password',
                ]
            );

            $user->syncRoles([$userData['role']]);
        }
    }
}
