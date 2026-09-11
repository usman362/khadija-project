<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every badge on one admin page, so Khadijah can review colours and icons
 * without hunting for an account that has earned each one.
 */
class AdminBadgeGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);

        return $u;
    }

    public function test_an_admin_sees_every_badge_both_ways(): void
    {
        $html = $this->actingAs($this->user('admin'))
            ->get(route('app.admin.badges.index'))
            ->assertOk()
            ->getContent();

        foreach (config('badges.client') as $b) {
            $this->assertStringContainsString(e($b['name']), $html);
        }
        $this->assertStringContainsString('Top Rated', $html);
        $this->assertStringContainsString('Verified', $html);

        // Earned and not yet earned, side by side.
        $this->assertStringContainsString('hexb-crest', $html);
        $this->assertStringContainsString('is-locked', $html);
    }

    public function test_it_is_admin_only(): void
    {
        $this->assertNotSame(200, $this->actingAs($this->user('client'))
            ->get(route('app.admin.badges.index'))->status());
    }
}
