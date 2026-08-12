<?php

namespace Database\Seeders;

use App\Domain\Auth\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Checklist row 179 — no placeholder identities anywhere.
         *
         * These are the demo LOGINS, so the emails stay exactly as they are.
         * Only the names change: a marketplace that greets you as "Client
         * User" and lists "Professional User" in its search results reads as
         * unfinished on the first screen anybody sees.
         */
        $users = [
            [
                'name' => 'Dana Whitfield',   // was 'Client User' — row 179
                'email' => 'client@example.com',
                'role' => RoleName::CLIENT->value,
                'city' => 'Baltimore', 'state' => 'MD',
            ],
            [
                'name' => 'Marcus Hale',      // was 'Supplier User' — row 179
                'email' => 'supplier@example.com',
                'role' => RoleName::PROFESSIONAL->value,
                'city' => 'Baltimore', 'state' => 'MD',
            ],
            [
                'name' => 'Priya Raghavan',   // was 'Professional User' — row 179
                'email' => 'professional@example.com',
                'role' => RoleName::PROFESSIONAL->value,
                'city' => 'Baltimore', 'state' => 'MD',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => 'password',
                    // Authoritative landing role — the client demo must log into the
                    // CLIENT portal, the supplier demo into the professional portal.
                    'primary_role' => $userData['role'],
                ]
            );

            // syncRoles is authoritative: strips any stray role (e.g. a client demo
            // that wrongly picked up the supplier role) so login routing is correct.
            $user->syncRoles([$userData['role']]);

            // R9/R26: every demo location must be in-area. These two also share a
            // state on purpose, so a same-state client↔professional booking can be
            // demonstrated end to end.
            $user->getOrCreateProfile()->update([
                'city'  => $userData['city'],
                'state' => $userData['state'],
            ]);
        }
    }
}
