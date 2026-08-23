<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade parses comments too.
 *
 * A `{{ }}` written inside a `//` comment in a layout is not ignored -- Blade
 * still treats it as an echo, and it desynced the compiler for the rest of the
 * file, so the very next real echo (the header logo's href) shipped to the
 * browser as the literal string "{{ route('landing')); ?>". Every page on the
 * public site had a broken logo link, and no title test caught it because the
 * <title> compiled fine -- the damage was downstream.
 *
 * This renders a page from each layout and asserts no un-compiled Blade leaked
 * into the HTML.
 */
class NoRawBladeInLayoutsTest extends TestCase
{
    use RefreshDatabase;

    private function assertNoRawBlade(string $html, string $where): void
    {
        foreach (['{{ ', '); ?>', '<?php'] as $marker) {
            $this->assertStringNotContainsString($marker, $html,
                "Un-compiled Blade leaked into {$where} (found '{$marker}').");
        }
    }

    public function test_a_public_landing_page_ships_no_raw_blade(): void
    {
        Category::firstOrCreate(
            ['slug' => 'anniversary-party'],
            ['name' => 'Anniversary Party', 'kind' => Category::EVENT_TYPE, 'is_active' => true],
        );

        $html = $this->get(route('public.category', 'anniversary-party'))->assertOk()->getContent();

        $this->assertNoRawBlade($html, 'the landing layout');
        // The exact breakage: the header logo must be a real URL, not Blade source.
        $this->assertStringNotContainsString("route('landing')", $html,
            'the logo href shipped as Blade source instead of a URL');
    }

    public function test_the_event_types_index_ships_no_raw_blade(): void
    {
        $html = $this->get(route('public.event-types'))->assertOk()->getContent();
        $this->assertNoRawBlade($html, 'the event-types page');
    }

    public function test_a_client_portal_page_ships_no_raw_blade(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $u = User::factory()->create();
        $u->assignRole('client');
        $u->givePermissionTo('dashboard.view');

        $html = $this->actingAs($u->fresh())->get(route('client.toolkit.plan'))->assertOk()->getContent();
        $this->assertNoRawBlade($html, 'the client layout');
    }
}
