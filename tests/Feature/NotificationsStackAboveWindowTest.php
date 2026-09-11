<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 2026-09-11: with notifications and Messages or the AI assistant
 * open together, the notifications sit centred above the window at the same
 * width, close but not overlapping, over a light grey that hides the page.
 */
class NotificationsStackAboveWindowTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_bell_menu_can_be_told_apart(): void
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

        $this->assertSame(1, substr_count($html, 'data-notif-menu>'), 'Only the notifications menu is marked.');
        $this->assertStringContainsString("classList.toggle('gr-stack'", $html);
    }

    public function test_the_stack_lines_up_with_the_window(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/client.blade.php'));
        $dock   = file_get_contents(resource_path('views/partials/_message_dock.blade.php'));

        // Same width and right edge as the window (380 / 24), 12px above it.
        preg_match('/\.md-win \{ position: fixed; right: (\d+)px; bottom: (\d+)px; width: (\d+)px;/', $dock, $w);
        $this->assertCount(4, $w);

        $this->assertStringContainsString("right: {$w[1]}px !important; bottom: calc({$w[2]}px + min(600px, 58vh) + 12px) !important;", $layout);
        $this->assertStringContainsString("width: {$w[3]}px; min-width: {$w[3]}px; max-width: {$w[3]}px;", $layout);

        // Sir Peter: grey only around the two, not over the whole page, in
        // the site's own light grey.
        $this->assertStringNotContainsString('body.gr-stack::before', $layout);
        $this->assertStringContainsString('background: var(--border-color, #e5e7eb); border-radius: 24px;', $layout);
    }

    /** The backing sits under the header (so the bell's menu is above it) and under the windows. */
    public function test_the_backing_sits_under_the_panels(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/client.blade.php'));
        preg_match('/#grStackBack \{ position: fixed; z-index: (\d+);/', $layout, $b);
        preg_match('/\.cl-topbar \{[^}]*z-index: (\d+);/', $layout, $h);

        $this->assertCount(2, $b);
        $this->assertCount(2, $h);
        $this->assertLessThan((int) $h[1], (int) $b[1]);
        $this->assertStringContainsString('<div id="grStackBack" aria-hidden="true"></div>', $layout);
    }
}
