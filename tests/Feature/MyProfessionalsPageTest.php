<?php

namespace Tests\Feature;

use App\Models\{Booking, Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * My Professionals, redesigned (Ali, 2026-09-11: "boht basic lagrehi hai").
 * Everything on it is counted from the client's own bookings and saves.
 */
class MyProfessionalsPageTest extends TestCase
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

    private function booking(User $pro, string $status, float $price, string $title): void
    {
        $event = Event::create([
            'title' => $title, 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'published', 'starts_at' => now()->addMonth(),
        ]);

        Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id, 'supplier_id' => $pro->id,
            'created_by' => $this->client->id, 'status' => $status, 'price' => $price,
        ]);
    }

    public function test_the_page_shows_real_figures(): void
    {
        $pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'Priya Raghavan']);
        $pro->assignRole('professional');

        $this->booking($pro, 'completed', 1200, 'Johnson Wedding');
        $this->booking($pro, 'cancelled', 900, 'Cancelled Party');

        $html = $this->actingAs($this->client)->get(route('client.saved-professionals.index'))
            ->assertOk()->getContent();

        $this->assertStringContainsString('Priya Raghavan', $html);
        $this->assertStringContainsString('Bookings together', $html);
        // Only what was actually agreed: the cancelled booking is left out.
        $this->assertStringContainsString('$1,200', $html);
        $this->assertStringNotContainsString('$2,100', $html);
        $this->assertStringContainsString('Johnson Wedding', $html);
        $this->assertStringContainsString('data-mp-search', $html);
    }

    /** Two bookings in the same second: the later one is the last. */
    public function test_the_last_event_is_the_latest_booking(): void
    {
        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');

        $this->booking($pro, 'completed', 500, 'Johnson Wedding');
        $this->booking($pro, 'confirmed', 700, 'Riverside Gala');

        $html = $this->actingAs($this->client)->get(route('client.saved-professionals.index'))->getContent();

        $this->assertMatchesRegularExpression('/Last worked together.*?on <b>Riverside Gala<\/b>/s', $html);
    }

    public function test_an_empty_page_points_somewhere(): void
    {
        $this->actingAs($this->client)->get(route('client.saved-professionals.index'))
            ->assertOk()
            ->assertSee('No one hired yet')
            ->assertSee('Nothing saved yet')
            ->assertSee(route('client.search.index'), false);
    }
}
