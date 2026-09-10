<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The floating buttons in the corner never hide the page's last row.
 *
 * Ali, 2026-09-11, on the live Messages page: the messages button and the AI
 * assistant's bubble sat over the details column, hiding Archive and the
 * figures beside it.
 */
class FloatingButtonsLeaveRoomTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $u = User::factory()->create();
        $u->assignRole('client');

        return $u->fresh();
    }

    /** On the messages page the messages shortcut is redundant, and it covered the panel. */
    public function test_the_messages_button_is_not_on_the_messages_page(): void
    {
        $client = $this->client();

        $this->assertStringNotContainsString('id="msgDock"',
            $this->actingAs($client)->get(route('client.chat.index'))->assertOk()->getContent());

        // Everywhere else it still is.
        $this->assertStringContainsString('id="msgDock"',
            $this->actingAs($client)->get(route('client.dashboard'))->assertOk()->getContent());
    }

    /**
     * Nor the assistant's bubble: on a smaller laptop it covered the chat
     * options however the page was laid out (Ali, 2026-09-11).
     */
    public function test_the_assistant_bubble_is_not_on_the_messages_page(): void
    {
        // The widget renders only when a key is set; a placeholder, in memory.
        config(['services.openai.key' => 'test-placeholder']);
        $client = $this->client();

        $this->assertStringNotContainsString('class="aic-bubble"',
            $this->actingAs($client)->get(route('client.chat.index'))->assertOk()->getContent());

        $this->assertStringContainsString('class="aic-bubble"',
            $this->actingAs($client)->get(route('client.dashboard'))->assertOk()->getContent());
    }

    /** The room at the foot of the page is worked out from the buttons' own sizes. */
    public function test_the_page_ends_below_the_buttons(): void
    {
        $bot  = file_get_contents(resource_path('views/partials/_ai_chatbot_widget.blade.php'));
        $dock = file_get_contents(resource_path('views/partials/_message_dock.blade.php'));

        preg_match('/\.aic-bubble\s*\{[^}]*bottom:\s*(\d+)px;[^}]*height:\s*(\d+)px/s', $bot, $b);
        preg_match('/body:has\(\.aic-bubble\) \.md \{ right: \d+px; bottom: (\d+)px; \}/', $dock, $d);
        preg_match('/\.md-launch \{ width: (\d+)px/', $dock, $l);
        $this->assertCount(3, $b, 'Could not read the bubble size.');
        $this->assertCount(2, $d, 'Could not read where the messages button sits.');
        $this->assertCount(2, $l, 'Could not read the messages button size.');

        $gap = 28;   // the page's own foot padding
        $this->assertStringContainsString('body:has(.aic-bubble) .cl-content { padding-bottom: ' . ((int) $b[1] + (int) $b[2] + $gap) . 'px; }', $bot);
        $this->assertStringContainsString('body:has(.aic-bubble):has(#msgDock) .cl-content { padding-bottom: ' . ((int) $d[1] + (int) $l[1] + $gap) . 'px; }', $bot);
    }
}
