<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The bar above the ledger named ONE event while every figure on the page
 * covered EVERY booking. A client read "$5,080 Total Agreed" as that event's
 * total, when the ledger below it might not hold a single row belonging to it —
 * which is exactly what the screenshot showed.
 *
 * It is a filter now. Cards, table and bar all answer for the same event.
 */
class MoneyPageEventFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
    }

    private function eventWithBooking(string $title, string $proName, float $price): Event
    {
        $event = Event::create([
            'title' => $title, 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'published', 'starts_at' => now()->addDays(30),
        ]);

        $pro = User::factory()->create(['name' => $proName]);

        Booking::create([
            'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'supplier_id' => $pro->id, 'event_id' => $event->id,
            'status' => 'confirmed', 'price' => $price,
        ]);

        return $event;
    }

    /** @return array<string, array{0: string}> */
    public static function moneyPages(): array
    {
        return ['payments' => ['client.payments.index'], 'spending' => ['client.spending.index']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('moneyPages')]
    public function test_choosing_an_event_narrows_the_page_to_it(string $route): void
    {
        $gala = $this->eventWithBooking('Harbour Gala', 'Halloway Sound', 2000);
        $this->eventWithBooking('Rooftop Party', 'Vestry Floral', 3000);

        $html = $this->actingAs($this->client->fresh())
            ->get(route($route, ['event' => $gala->id]))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('Halloway Sound', $html);
        $this->assertStringNotContainsString('Vestry Floral', $html);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('moneyPages')]
    public function test_the_totals_follow_the_chosen_event(string $route): void
    {
        $gala = $this->eventWithBooking('Harbour Gala', 'Halloway Sound', 2000);
        $this->eventWithBooking('Rooftop Party', 'Vestry Floral', 3000);

        // All events: 2000 + 3000.
        $all = $this->actingAs($this->client->fresh())->get(route($route))->assertSuccessful();
        $this->assertSame(5000.0, (float) $all->viewData('stats')['total_agreed']);

        // One event: only its own.
        $one = $this->actingAs($this->client->fresh())->get(route($route, ['event' => $gala->id]))->assertSuccessful();
        $this->assertSame(2000.0, (float) $one->viewData('stats')['total_agreed']);
    }

    /** Somebody else's event id must select nothing, not reveal that it exists. */
    #[\PHPUnit\Framework\Attributes\DataProvider('moneyPages')]
    public function test_another_clients_event_cannot_be_selected(string $route): void
    {
        $this->eventWithBooking('Harbour Gala', 'Halloway Sound', 2000);

        $stranger = User::factory()->create();
        $stranger->assignRole('client');
        $theirs = Event::create([
            'title' => 'Someone Elses Wedding', 'client_id' => $stranger->id,
            'created_by' => $stranger->id, 'status' => 'published', 'starts_at' => now()->addDays(10),
        ]);

        $response = $this->actingAs($this->client->fresh())
            ->get(route($route, ['event' => $theirs->id]))
            ->assertSuccessful();

        $this->assertNull($response->viewData('activeEvent'));
        $response->assertDontSee('Someone Elses Wedding');

        // And it falls back to showing everything rather than nothing.
        $this->assertSame(2000.0, (float) $response->viewData('stats')['total_agreed']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('moneyPages')]
    public function test_the_bar_is_a_selector_not_a_caption(string $route): void
    {
        $this->eventWithBooking('Harbour Gala', 'Halloway Sound', 2000);

        $html = $this->actingAs($this->client->fresh())->get(route($route))->assertSuccessful()->getContent();

        $this->assertStringContainsString('name="event"', $html);
        $this->assertStringContainsString('All events', $html);
    }

    /** A client with no events gets no selector rather than an empty one. */
    #[\PHPUnit\Framework\Attributes\DataProvider('moneyPages')]
    public function test_no_events_means_no_selector(string $route): void
    {
        $html = $this->actingAs($this->client->fresh())->get(route($route))->assertSuccessful()->getContent();

        $this->assertStringNotContainsString('name="event"', $html);
    }
}
