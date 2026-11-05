<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Support\Commission;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payments & Payouts says which rate produced the totals above it.
 *
 * The page showed four figures that were all net of commission and never
 * named the commission. Khadijah's 28 October screen carries the tier band,
 * and the arithmetic in it has to be the same arithmetic the rows below use,
 * so both read App\Support\Commission rather than each doing their own.
 */
class ThePayoutPageNamesTheRateItAppliedTest extends TestCase
{
    use RefreshDatabase;

    private function pro(): User
    {
        $u = User::factory()->create(['primary_role' => 'professional']);
        $u->assignRole('professional');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_the_band_names_the_tier_and_its_rate(): void
    {
        $this->actingAs($this->pro())
            ->get(route('professional.earnings.index'))
            ->assertOk()
            ->assertSee('Your current tier')
            // No subscription means Starter terms, and the page should say so
            // rather than leave the rate unstated.
            ->assertSee('Starter (5%)');
    }

    /**
     * Starter is the DEAREST tier. The page used to tell a Starter account
     * it was on "the lowest rate we charge" whenever there was no money to
     * measure a saving against.
     */
    public function test_a_starter_with_no_earnings_is_not_told_it_pays_the_least(): void
    {
        $this->actingAs($this->pro())
            ->get(route('professional.earnings.index'))
            ->assertOk()
            ->assertDontSee('This is the lowest rate we charge')
            ->assertSee('Fees start once a booking is completed and paid.');
    }

    public function test_the_fees_shown_are_the_fees_commission_would_take(): void
    {
        $pro    = $this->pro();
        $client = User::factory()->create(['primary_role' => 'client']);
        $client->assignRole('client');

        $event = Event::create([
            'title'        => 'Harbour Lights Reception',
            'client_id'    => $client->id,
            'created_by'   => $client->id,
            'supplier_id'  => $pro->id,
            'status'       => 'published',
            'is_published' => true,
            'starts_at'    => now()->subDays(10),
            'location'     => 'Baltimore, MD',
        ]);

        Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $client->id,
            'created_by'  => $client->id,
            'supplier_id' => $pro->id,
            'price'       => 2000,
            'status'      => 'completed',
        ]);

        $expected = number_format(Commission::on(2000.0, $pro), 2);

        $this->actingAs($pro)
            ->get(route('professional.earnings.index'))
            ->assertOk()
            ->assertSee('Platform fees')
            ->assertSee($expected)
            // With money billed, the other tiers are worth naming. On an
            // empty account they are not, and the guard in
            // AnEmptyProfessionalSeesNoFiguresTest holds them back.
            ->assertSee('Pro 3%');
    }
}
