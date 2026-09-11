<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 2026-09-11: the AI assistant is an icon in the client header,
 * beside the light/dark toggle, not a floating bubble.
 */
class AiAssistantInHeaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        config(['services.openai.key' => 'test-key-for-rendering']);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return $u->fresh();
    }

    public function test_the_client_header_opens_the_assistant_and_there_is_no_bubble(): void
    {
        $html = $this->actingAs($this->user('client'))->get(route('client.dashboard'))
            ->assertOk()->getContent();

        $this->assertStringContainsString('data-ai-open', $html);
        $this->assertStringContainsString('id="aiChatPanel"', $html);
        $this->assertStringNotContainsString('id="aiChatBubble"', $html);

        // The header's Messages icon opens the Messages window, and there is
        // no corner button any more (Ali, 2026-09-11).
        $this->assertMatchesRegularExpression('/class="tb-icon-btn" title="Messages"\s*data-md-open/', $html);
        $this->assertStringContainsString('id="msgDock"', $html);
        // The button itself, not the script that would handle it.
        $this->assertStringNotContainsString('class="md-launch"', $html);

        // Beside the theme toggle, in the header.
        $this->assertMatchesRegularExpression('/id="theme-toggle".*?data-ai-open/s', $html);
    }

    public function test_no_icon_when_the_assistant_is_off(): void
    {
        config(['services.openai.key' => null]);

        $this->actingAs($this->user('client'))->get(route('client.dashboard'))
            ->assertOk()->assertDontSee('data-ai-open', false);
    }
}
