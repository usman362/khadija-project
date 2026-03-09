<?php

namespace Tests\Feature;

use App\Domain\Auth\Enums\RoleName;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesPermissionsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_create_permission_role_and_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::ADMIN->value);

        $this->actingAs($admin)->post(route('app.permissions.store'), [
            'name' => 'reports.view_any',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('app.roles.store'), [
            'name' => 'manager',
            'permissions' => ['reports.view_any'],
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('app.users.store'), [
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'roles' => ['manager'],
        ])->assertRedirect();

        $this->assertDatabaseHas('permissions', ['name' => 'reports.view_any']);
        $this->assertDatabaseHas('roles', ['name' => 'manager']);
        $this->assertDatabaseHas('users', ['email' => 'manager@example.com']);
    }

    public function test_client_cannot_access_role_permission_modules(): void
    {
        $client = User::factory()->create();
        $client->assignRole(RoleName::CLIENT->value);

        $this->actingAs($client)->get(route('app.roles.index'))->assertForbidden();
        $this->actingAs($client)->get(route('app.permissions.index'))->assertForbidden();
    }
}
