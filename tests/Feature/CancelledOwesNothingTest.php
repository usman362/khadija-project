<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Two figures that contradicted the pages beside them (23 September 2026).
 *
 * OA-167: a cancelled booking read "Outstanding $5,000" while the money
 *         summary on the same page left it out. Nothing is owed on work that
 *         was called off.
 * OA-169: Messages said "Due $80 (across 2 bookings)" when the $80 came from
 *         one of them; the other was cancelled and carried no price.
 */
class CancelledOwesNothingTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $pro;

    private Category $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();

        $this->pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'Priya Raghavan']);
        $this->pro->assignRole('professional');

        $this->service = Category::create([
            'name' => 'Event Photography', 'slug' => 'cancelled-owes-photo',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);
    }

    private function book(string $status, ?float $price): Booking
    {
        $event = Event::create([
            'title' => 'Garden Reception', 'status' => 'published', 'is_published' => true,
            'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'starts_at' => now()->addDays(20), 'budget' => 1500,
        ]);

        return Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id, 'supplier_id' => $this->pro->id,
            'created_by' => $this->client->id, 'category_id' => $this->service->id,
            'status' => $status, 'price' => $price,
        ]);
    }

    public function test_a_cancelled_booking_shows_nothing_outstanding(): void
    {
        $this->book('cancelled', 5000);

        $page = $this->actingAs($this->client)->get(route('client.bookings.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Outstanding</span>
                                <span class="v ">$5,000', $page);
        $this->assertMatchesRegularExpression('/Outstanding<\/span>\s*<span class="v muted">\$0/', $page);
    }

    public function test_messages_counts_the_bookings_the_money_came_from(): void
    {
        $this->book('confirmed', 80);
        $this->book('cancelled', 5000);
        // Confirmed but no price agreed yet: it owes nothing to count either.
        $this->book('confirmed', null);

        $stats = $this->actingAs($this->client)->get(route('client.chat.index'))->assertOk()->viewData('stats');

        $this->assertSame(80.0, (float) $stats['unpaid']);
        $this->assertSame(1, $stats['unpaid_bookings']);
    }
}
