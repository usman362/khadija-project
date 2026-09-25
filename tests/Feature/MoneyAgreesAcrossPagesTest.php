<?php

namespace Tests\Feature;

use App\Domain\Finance\ClientTotals;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One client's money, read on four pages, has to be one answer.
 *
 * The dashboard's "Total Spent" summed the payments table while every other
 * finance surface reads App\Domain\Finance\ClientTotals, which counts the
 * bookings that were completed. A booking is completed on this platform
 * without a payment row necessarily existing, so a client who had paid for
 * finished work saw $0.00 on the dashboard and the real figure everywhere
 * else. The card had read $0.00 for a different reason once before; a figure
 * with its own private source is how that keeps happening.
 */
class MoneyAgreesAcrossPagesTest extends TestCase
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
        $this->client = User::findOrFail($this->client->id);

        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');
        $service = Category::create([
            'name' => 'Full Catering', 'slug' => 'money-agrees-catering',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        // Paid, still owed, and called off.
        foreach ([['completed', 500], ['confirmed', 300], ['cancelled', 900]] as $i => [$status, $price]) {
            $event = Event::create([
                'title' => 'Event '.$i, 'status' => 'published', 'is_published' => true,
                'client_id' => $this->client->id, 'created_by' => $this->client->id,
                'starts_at' => now()->addDays(10 + $i), 'budget' => 1000,
            ]);

            Booking::create([
                'event_id' => $event->id, 'client_id' => $this->client->id, 'supplier_id' => $pro->id,
                'created_by' => $this->client->id, 'category_id' => $service->id,
                'status' => $status, 'price' => $price,
            ]);
        }
    }

    public function test_the_dashboard_total_spent_is_the_money_that_left(): void
    {
        $this->assertSame(500.0, ClientTotals::paid($this->client));

        $html = strip_tags($this->actingAs($this->client)->get('/client/dashboard')->assertOk()->getContent());

        $this->assertMatchesRegularExpression('/Total Spent.{0,200}?\$500\.00/s', $html,
            'The dashboard disagrees with what the client actually paid.');
        $this->assertDoesNotMatchRegularExpression('/Total Spent.{0,200}?\$0\.00/s', $html);
    }

    public function test_my_events_agrees_with_the_same_calculation(): void
    {
        $payment = $this->actingAs($this->client)->get('/client/events')->assertOk()->viewData('payment');

        // Booked is what stands; the cancelled booking is in neither figure.
        $this->assertSame(ClientTotals::agreed($this->client), (float) $payment['total']);
        $this->assertSame(ClientTotals::paid($this->client), (float) $payment['paid']);
        $this->assertSame(900.0, ClientTotals::cancelled($this->client));
    }

    /** Nothing computes the client's money from its own private source. */
    public function test_the_finance_pages_read_one_calculation(): void
    {
        $dashboard = file_get_contents(base_path('resources/views/client/dashboard.blade.php'));

        $this->assertStringNotContainsString(
            "\\App\\Models\\Payment::where('user_id', \$user->id)->where('status', 'completed')",
            $dashboard,
            'The dashboard is summing the payments table again.',
        );
        $this->assertStringContainsString('ClientTotals::base($user)', $dashboard);
    }
}
