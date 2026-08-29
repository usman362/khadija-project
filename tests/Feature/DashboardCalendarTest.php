<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The dashboard calendar lost Saturday.
 *
 * The grid was repeat(7, 1fr), and a grid column's default minimum is its own
 * content — so the day cells would not shrink below about 56px. Under roughly
 * 430px of card width the seven columns stopped fitting and the last one was
 * cut off at the card's edge. Anyone on a narrow screen, or simply zoomed in,
 * was looking at a week with six days in it.
 *
 * Measured in a browser before and after: at 400px the old grid overflowed and
 * Saturday was clipped; the new one is still whole at 290px.
 */
class DashboardCalendarTest extends TestCase
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
        $user = User::factory()->create();
        $user->assignRole('client');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    public function test_the_calendar_columns_can_shrink(): void
    {
        $html = $this->actingAs($this->client())->get('/client/dashboard')->assertSuccessful()->getContent();

        // The fix itself. `repeat(7, 1fr)` is what cut Saturday off.
        $this->assertMatchesRegularExpression(
            '/\.od-cal\s*\{[^}]*grid-template-columns:\s*repeat\(7,\s*minmax\(0,\s*1fr\)\)/s',
            $html,
            'The calendar grid is back to repeat(7, 1fr) and will clip Saturday on a narrow card.'
        );
    }

    public function test_all_seven_days_are_rendered(): void
    {
        $html = $this->actingAs($this->client())->get('/client/dashboard')->assertSuccessful()->getContent();

        foreach (['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'] as $day) {
            $this->assertStringContainsString('>' . $day . '<', $html);
        }
    }

    /** Six weeks of cells, so a month that spans six rows is whole. */
    public function test_the_month_grid_is_complete(): void
    {
        $client = $this->client();
        Event::create([
            'title' => 'Harbour Gala', 'client_id' => $client->id, 'created_by' => $client->id,
            'status' => 'published', 'starts_at' => now()->startOfMonth()->addDays(19),
        ]);

        $html = $this->actingAs($client)->get('/client/dashboard')->assertSuccessful()->getContent();

        $cells = substr_count($html, 'class="od-cal-day');
        $this->assertGreaterThanOrEqual(35, $cells, 'The month grid is missing rows.');
        $this->assertStringContainsString('Harbour Gala', $html);
    }
}
