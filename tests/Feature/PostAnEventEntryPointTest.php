<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 2026-08-31: "Post an Event" behaved differently depending on where
 * it was clicked. The top navigation sent a client to /client/post-event and
 * the sidebar and dashboard sent them to /client/post-event/choose — the same
 * words landing them in two different flows, one of which skipped the question
 * of what kind of request they were making.
 *
 * Every entry point goes to the chooser now.
 */
class PostAnEventEntryPointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function client(): User
    {
        $u = User::factory()->create();
        $u->assignRole('client');
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $u->fresh();
    }

    public function test_the_top_navigation_sends_a_client_to_the_chooser(): void
    {
        $html = $this->actingAs($this->client())->get('/')->assertSuccessful()->getContent();

        $this->assertStringContainsString(route('client.post-event.choose'), $html);
    }

    /** A guest is asked to make an account first, as before. */
    public function test_a_guest_is_sent_to_register(): void
    {
        $html = $this->get('/')->assertSuccessful()->getContent();

        $this->assertStringContainsString(route('register', ['role' => 'client']), $html);
        $this->assertStringNotContainsString(route('client.post-event.choose'), $html);
    }

    /** All three ways in agree. */
    public function test_the_sidebar_agrees_with_the_top_navigation(): void
    {
        $html = $this->actingAs($this->client())
            ->get(route('client.dashboard'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString(route('client.post-event.choose'), $html);
    }
}
