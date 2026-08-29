<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The "Browsing — pick an event to keep it with you" bar sits directly beneath
 * the hero on Find Professionals. It carried a bottom margin and no top one, so
 * it read as stuck to the hero with nothing separating them.
 *
 * The page's rhythm is .br-main's 18px top padding; the bar now matches it.
 */
class BrowseEventBarSpacingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function clientWithAnEvent(): User
    {
        $user = User::factory()->create(['primary_role' => 'client']);
        $user->assignRole('client');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        Event::create([
            'title' => 'Harbour Anniversary', 'client_id' => $user->id, 'created_by' => $user->id,
            'status' => 'published', 'starts_at' => now()->addDays(40),
        ]);

        return $user->fresh();
    }

    public function test_the_event_bar_is_shown_to_a_client_who_has_an_event(): void
    {
        $this->actingAs($this->clientWithAnEvent())
            ->get(route('public.browse'))
            ->assertSuccessful()
            ->assertSee('pick an event to keep it with you');
    }

    public function test_the_event_bar_is_not_flush_against_the_hero(): void
    {
        $html = $this->actingAs($this->clientWithAnEvent())
            ->get(route('public.browse'))
            ->assertSuccessful()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/\.br-forevent\s*\{[^}]*margin:\s*(?!0)\d+px/',
            $html,
            'The event bar has no top margin, so it sits flush against the hero.'
        );
    }
}
