<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Spending toolbar was decoration: a search box that did nothing when you
 * typed in it, and two <button> elements — "Date Range" and "Filter" — with no
 * form and no handler. Nothing on the page ever read a query parameter, so a
 * client with forty bookings saw eight at a time, in one order, and could not
 * narrow them at all.
 */
class SpendingFiltersTest extends TestCase
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

    private function booking(string $proName, string $status, ?string $on = null): Booking
    {
        $pro = User::factory()->create(['name' => $proName]);
        // Deliberately NOT named after the professional: the page prints the
        // active event's title in its header, so an event called "Halloway
        // Sound Event" would keep the string on screen after the professional
        // had been filtered out — and the test would be reading the header,
        // not the table.
        $event = Event::create([
            'title' => 'Harbour Gala', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'starts_at' => now()->addDays(20),
        ]);

        $booking = Booking::create([
            'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'supplier_id' => $pro->id, 'event_id' => $event->id,
            'status' => $status, 'price' => 500,
        ]);

        if ($on) {
            $booking->forceFill(['created_at' => $on])->save();
        }

        return $booking;
    }

    private function rows(array $query = []): string
    {
        return $this->actingAs($this->client->fresh())
            ->get(route('client.spending.index', $query))
            ->assertSuccessful()
            ->getContent();
    }

    public function test_searching_narrows_the_list_to_that_professional(): void
    {
        $this->booking('Halloway Sound', 'confirmed');
        $this->booking('Vestry Floral', 'confirmed');

        $html = $this->rows(['q' => 'Halloway']);

        $this->assertStringContainsString('Halloway Sound', $html);
        $this->assertStringNotContainsString('Vestry Floral', $html);
    }

    public function test_the_status_filter_selects_one_status(): void
    {
        $this->booking('Halloway Sound', 'completed');
        $this->booking('Vestry Floral', 'cancelled');

        $html = $this->rows(['status' => 'cancelled']);

        $this->assertStringContainsString('Vestry Floral', $html);
        $this->assertStringNotContainsString('Halloway Sound', $html);
    }

    public function test_a_date_range_keeps_only_what_falls_inside_it(): void
    {
        $this->booking('Halloway Sound', 'confirmed', now()->subDays(40)->toDateTimeString());
        $this->booking('Vestry Floral', 'confirmed', now()->subDays(2)->toDateTimeString());

        $html = $this->rows([
            'from' => now()->subDays(7)->toDateString(),
            'to'   => now()->toDateString(),
        ]);

        $this->assertStringContainsString('Vestry Floral', $html);
        $this->assertStringNotContainsString('Halloway Sound', $html);
    }

    /** A range entered backwards is a typo, not an empty page. */
    public function test_a_reversed_date_range_is_read_the_way_it_was_meant(): void
    {
        $this->booking('Vestry Floral', 'confirmed', now()->subDays(2)->toDateTimeString());

        $html = $this->rows([
            'from' => now()->toDateString(),
            'to'   => now()->subDays(7)->toDateString(),
        ]);

        $this->assertStringContainsString('Vestry Floral', $html);
    }

    /** A filter that finds nothing must still show what was searched for. */
    public function test_the_search_is_echoed_back_when_it_finds_nothing(): void
    {
        $this->booking('Halloway Sound', 'confirmed');

        $html = $this->rows(['q' => 'Nobodyhere']);

        $this->assertStringContainsString('value="Nobodyhere"', $html);
        $this->assertStringNotContainsString('Halloway Sound', $html);
    }

    /** An invented status is ignored rather than queried. */
    public function test_a_status_that_does_not_exist_is_ignored(): void
    {
        $this->booking('Halloway Sound', 'confirmed');

        $this->assertStringContainsString('Halloway Sound', $this->rows(['status' => 'wizardry']));
    }

    public function test_the_toolbar_is_a_real_form_not_two_dead_buttons(): void
    {
        $html = $this->rows();

        $this->assertStringContainsString('<form method="GET"', $html);
        foreach (['name="q"', 'name="from"', 'name="to"', 'name="status"'] as $field) {
            $this->assertStringContainsString($field, $html);
        }
    }

    /** Ali asked for this one gone. */
    public function test_quick_actions_is_gone(): void
    {
        $this->assertStringNotContainsString('Quick Actions', $this->rows());
    }
}
