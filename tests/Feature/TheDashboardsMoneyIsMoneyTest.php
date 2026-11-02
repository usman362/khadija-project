<?php

namespace Tests\Feature;

use App\Models\{Booking, Category, Event, User};
use App\Support\{Commission, Earnings, ServiceArea};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A figure with a currency sign in front of it has to be currency.
 *
 * Earnings::forProfessional returns 'total' as a COUNT of bookings and the
 * money under 'gross' and 'earned'. The dashboard read 'total' for its Total
 * Booked card and printed it through number_format behind a dollar sign, so a
 * professional with eight completed jobs worth $3,330 was shown "$8.00".
 *
 * It hid for two reasons: the view ignored every one of the controller's
 * figures until the cards were wired up, and on an empty account a count of
 * nothing and a sum of nothing are the same zero. Only an account with real
 * work tells them apart, which is what this does.
 */
class TheDashboardsMoneyIsMoneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_booked_is_the_money_not_the_number_of_jobs(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $pro    = $this->person('professional', 'Hollis Tremaine');
        $client = $this->person('client', 'Imogen Hartwell');

        // Three jobs, deliberately worth far more than three.
        foreach ([450, 540, 2340] as $i => $price) {
            $event = Event::create([
                'title'       => "Booked job {$i}",
                'client_id'   => $client->id,
                'created_by'  => $client->id,
                'supplier_id' => $pro->id,
                'status'      => 'published',
                'starts_at'   => now()->subDays(10 + $i),
            ]);

            Booking::create([
                'event_id'    => $event->id,
                'client_id'   => $client->id,
                'created_by'  => $client->id,
                'supplier_id' => $pro->id,
                'category_id' => $this->service()->id,
                'status'      => 'completed',
                'price'       => $price,
            ]);
        }

        $money    = Earnings::forProfessional($pro);
        $expected = number_format($money['earned'], 2);

        $this->assertSame(3, $money['total'], 'Earnings::total stopped being a count.');
        $this->assertGreaterThan(1000, $money['earned'], 'The fixture is not worth enough to tell the two apart.');

        $html = $this->actingAs($pro)->get(route('professional.dashboard'))
            ->assertSuccessful()->getContent();

        $this->assertStringContainsString('$' . $expected, $html);
        $this->assertStringNotContainsString('$3.00', $html, 'The card printed the number of jobs as money.');
    }

    private function person(string $role, string $name): User
    {
        $u = User::factory()->create(['primary_role' => $role, 'name' => $name]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function service(): Category
    {
        return Category::firstOrCreate(
            ['slug' => 'wedding-dj'],
            ['name' => 'Wedding DJ', 'kind' => Category::SERVICE, 'is_active' => true],
        );
    }
}
