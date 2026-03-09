<?php

namespace Tests\Feature;

use App\Domain\Auth\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesTableSeeder::class);
    }

    public function test_admin_can_view_user_access_page(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::ADMIN->value);

        $response = $this->actingAs($admin)->get(route('app.users.index'));

        $response->assertOk();
    }

    public function test_client_cannot_view_user_access_page(): void
    {
        $client = User::factory()->create();
        $client->assignRole(RoleName::CLIENT->value);

        $response = $this->actingAs($client)->get(route('app.users.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_update_user_roles_and_permissions(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::ADMIN->value);

        $target = User::factory()->create();

        $response = $this->actingAs($admin)->patch(route('app.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'roles' => [RoleName::SUPPLIER->value],
            'permissions' => ['messages.create'],
        ]);

        $response->assertRedirect();
        $this->assertTrue($target->fresh()->hasRole(RoleName::SUPPLIER->value));
        $this->assertTrue($target->fresh()->hasDirectPermission('messages.create'));
    }
}
