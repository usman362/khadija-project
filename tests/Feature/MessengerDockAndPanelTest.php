<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\MessengerAccess;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter's Ideas 2, 3 and 4.
 *
 *  2 — a messenger that pops up from the bottom of the screen, so a
 *      conversation can be carried around the site instead of living on one
 *      page.
 *  3 — that pop-up can be a benefit of the highest professional membership,
 *      WITHOUT forking the messages: same conversations, same endpoints, same
 *      history. Only the way in changes.
 *  4 — the conversation's details panel collapses, and which tier gets it is
 *      configurable.
 *
 * The condition the Owner was explicit about: a client never buys a
 * membership to answer a professional who has this.
 */
class MessengerDockAndPanelTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return $user->fresh();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    /* ── Idea 2 ─────────────────────────────────────────────── */

    public function test_the_dock_is_on_the_page_while_browsing(): void
    {
        $client = $this->account('client');

        $html = $this->actingAs($client)
            ->get(route('client.dashboard'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('id="msgDock"', $html,
            'The messenger is not reachable from anywhere but the Messages page.');
    }

    /** It is a way in, not a second messaging system. */
    public function test_it_uses_the_same_conversations_as_the_messages_page(): void
    {
        $client = $this->account('client');

        $html = $this->actingAs($client)->get(route('client.dashboard'))->getContent();

        // The URLs are printed through @json, which escapes the slashes.
        $this->assertStringContainsString(trim(json_encode(route('conversations.index')), '"'), $html);
        $this->assertStringContainsString('conversations\\/__ID__', $html);
    }

    /** Closing puts it away; it does not end or delete anything. */
    public function test_close_and_minimise_are_display_only(): void
    {
        $client = $this->account('client');

        $html = $this->actingAs($client)->get(route('client.dashboard'))->getContent();

        $this->assertStringContainsString('data-md-min', $html);
        $this->assertStringContainsString('data-md-close', $html);

        // Neither is wired to anything that changes the thread.
        $this->assertStringNotContainsString('conversations/__ID__/delete', $html);
    }

    public function test_a_signed_out_visitor_gets_no_dock(): void
    {
        $this->get(route('public.packages'))
            ->assertSuccessful()
            ->assertDontSee('id="msgDock"', false);
    }

    /* ── Idea 3 ─────────────────────────────────────────────── */

    /** With no tier configured, every professional has it — so it can be judged. */
    public function test_by_default_everyone_has_it(): void
    {
        config(['messaging.dock.plans' => []]);

        $this->assertTrue(MessengerAccess::dock($this->account('client')));
        $this->assertTrue(MessengerAccess::dock($this->account('professional')));
    }

    /** Configure a tier and a professional without it loses the dock. */
    public function test_a_tier_can_be_required_of_professionals(): void
    {
        config(['messaging.dock.plans' => ['enterprise']]);

        $this->assertFalse(
            MessengerAccess::dock($this->account('professional')),
            'A professional with no subscription still got a membership feature.',
        );
    }

    /** And a client never has to buy one to reply. */
    public function test_a_client_is_never_charged_for_it(): void
    {
        config(['messaging.dock.plans' => ['enterprise']]);

        $this->assertTrue(
            MessengerAccess::dock($this->account('client')),
            'A client was asked for a membership to answer a professional.',
        );

        $html = $this->actingAs($this->account('client'))
            ->get(route('client.dashboard'))
            ->getContent();

        $this->assertStringContainsString('id="msgDock"', $html);
    }

    /** The whole thing can be switched off without touching a view. */
    public function test_it_can_be_turned_off_in_config(): void
    {
        config(['messaging.dock.enabled' => false]);

        $this->assertFalse(MessengerAccess::dock($this->account('client')));

        $html = $this->actingAs($this->account('client'))
            ->get(route('client.dashboard'))
            ->getContent();

        $this->assertStringNotContainsString('id="msgDock"', $html);
    }

    /* ── Idea 4 ─────────────────────────────────────────────── */

    public function test_the_details_panel_is_collapsible_and_remembered(): void
    {
        $client = $this->account('client');

        $html = $this->actingAs($client)
            ->get(route('client.chat.index'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('cm-info-toggle', $html);

        // Hidden and then found back open on the next conversation is the same
        // as not being collapsible at all.
        $this->assertStringContainsString('gr.chat.info', $html);
    }

    /** Its eligibility is configurable in the same way. */
    public function test_the_panel_can_be_put_behind_a_tier(): void
    {
        config(['messaging.panel.plans' => ['enterprise']]);

        $this->assertFalse(MessengerAccess::panel($this->account('professional')));
        $this->assertTrue(MessengerAccess::panel($this->account('client')));
    }

    /** Staff always have both — they are the ones answering support. */
    public function test_staff_always_have_both(): void
    {
        config(['messaging.dock.plans' => ['enterprise'], 'messaging.panel.plans' => ['enterprise']]);

        $admin = User::factory()->create(['primary_role' => 'admin']);
        $admin->assignRole('admin');

        $this->assertTrue(MessengerAccess::dock($admin->fresh()));
        $this->assertTrue(MessengerAccess::panel($admin->fresh()));
    }
}
