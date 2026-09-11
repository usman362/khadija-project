<?php

namespace Tests\Feature;

use App\Models\{Booking, Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 2026-09-11: however many badges there end up being, a user needs
 * one place to see them all, and they have to stay current as badges are
 * earned or lost.
 */
class ClientSeeAllBadgesTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = $this->client->fresh();
    }

    public function test_every_badge_is_listed_with_progress(): void
    {
        $html = $this->actingAs($this->client)->get(route('client.badges.index'))
            ->assertOk()->getContent();

        foreach (config('badges.client') as $b) {
            $this->assertStringContainsString(e($b['name']), $html);
        }
        $this->assertStringContainsString('0 of 4 badges earned', $html);
        $this->assertStringContainsString('0 of 5', $html);           // Frequent Planner
        $this->assertStringContainsString(route('client.verification.show'), $html);
    }

    /** Earned as soon as the record says so, with no step in between. */
    public function test_it_updates_as_badges_are_earned(): void
    {
        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');

        foreach (range(1, 5) as $i) {
            $event = Event::create([
                'title' => "Party {$i}", 'client_id' => $this->client->id, 'created_by' => $this->client->id,
                'status' => 'completed', 'starts_at' => now()->subMonths($i),
            ]);
            Booking::create([
                'event_id' => $event->id, 'client_id' => $this->client->id, 'supplier_id' => $pro->id,
                'created_by' => $this->client->id, 'status' => 'completed', 'price' => 500,
            ]);
        }

        $this->actingAs($this->client)->get(route('client.badges.index'))
            ->assertOk()->assertSee('1 of 4 badges earned', false);
    }

    public function test_the_profile_links_to_it(): void
    {
        $this->actingAs($this->client)->get(route('client.profile.index'))
            ->assertOk()
            ->assertSee(route('client.badges.index'), false)
            ->assertSee('See all badges', false);
    }
}
