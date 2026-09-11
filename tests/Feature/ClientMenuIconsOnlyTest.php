<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 2026-09-12: on a PC or laptop the left menu can fold to just its
 * icons, like the Messages page's right column folds away.
 */
class ClientMenuIconsOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_menu_can_fold_to_icons(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $client = User::factory()->create(['primary_role' => 'client']);
        $client->assignRole('client');
        $client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        $html = $this->actingAs($client->fresh())->get(route('client.dashboard'))->assertOk()->getContent();

        // The switch, in the menu.
        $this->assertStringContainsString('data-side-mini', $html);
        $this->assertStringContainsString('Collapse menu', $html);

        // Remembered, and applied before the page paints so it does not jump.
        $this->assertStringContainsString("localStorage.getItem('cl-side')==='mini'", $html);
        $this->assertLessThan(strpos($html, '<body'), strpos($html, "getItem('cl-side')"));
    }

    public function test_it_uses_the_collapsed_width_and_only_on_a_computer(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/client.blade.php'));

        $this->assertStringContainsString('html.cl-side-mini .cl-sidebar { width: var(--sidebar-collapsed); }', $layout);
        $this->assertStringContainsString('html.cl-side-mini .cl-main { margin-left: var(--sidebar-collapsed); }', $layout);
        // Inside the desktop media query, and hidden on a phone.
        $this->assertMatchesRegularExpression('/@media \(min-width: 769px\) \{\s*html\.cl-side-mini \.cl-sidebar/', $layout);
        $this->assertStringContainsString('@media (max-width: 768px) { .cl-side-toggle { display: none; } }', $layout);
    }
}
