<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Threads is hidden while Messages is the one inbox professionals use. Both
 * listed the same conversations, so two nav entries only split a
 * professional's replies across two places.
 *
 * The page itself is not deleted — the routes redirect, so old links still
 * land somewhere useful.
 */
class ThreadsHiddenTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro = User::factory()->create();
        $this->pro->assignRole('professional');
        $this->pro->givePermissionTo(['dashboard.view', 'messages.view_any', 'messages.view']);
        $this->pro->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->pro = $this->pro->fresh();
    }

    public function test_the_sidebar_no_longer_offers_threads(): void
    {
        $response = $this->actingAs($this->pro)->get(route('professional.chat.index'));

        $response->assertSuccessful();
        $response->assertDontSee(route('professional.threads.index'));
        $response->assertSee(route('professional.chat.index'));
    }

    public function test_an_old_threads_link_lands_on_messages(): void
    {
        $this->actingAs($this->pro)
            ->get(route('professional.threads.index'))
            ->assertRedirect(route('professional.chat.index'));
    }

    public function test_an_old_thread_link_keeps_its_conversation(): void
    {
        $this->actingAs($this->pro)
            ->get('/professional/threads/7')
            ->assertRedirect(route('professional.chat.show', 7));
    }
}
