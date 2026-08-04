<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Earnings page and the Transactions page describe the same money. They
 * used to disagree — Earnings returned hardcoded zeros and so reported
 * Pending $0.00 while Transactions, on the same account at the same moment,
 * reported $4,826.00.
 *
 * Both now read App\Support\Earnings. These tests fail if either page grows
 * its own copy of the sums again.
 */
class EarningsAgreeWithTransactionsTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro = User::factory()->create();
        $this->pro->assignRole('professional');
        $this->pro->givePermissionTo('dashboard.view');
        $this->pro->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->pro = $this->pro->fresh();
    }

    private static int $n = 0;

    /** No Booking/Event factories in this project — build the rows directly. */
    private function booking(string $status, float $price): Booking
    {
        $client = User::factory()->create();

        $event = Event::create([
            'title'      => 'Gig ' . (++self::$n),
            'created_by' => $client->id,
            'client_id'  => $client->id,
            'status'     => 'published',
            'starts_at'  => now()->addMonth(),
        ]);

        return Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $client->id,
            'supplier_id' => $this->pro->id,
            'created_by'  => $this->pro->id,
            'status'      => $status,
            'price'       => $price,
            'currency'    => 'USD',
        ]);
    }

    private function statsFrom(string $route): array
    {
        $response = $this->actingAs($this->pro)->get(route($route));
        $response->assertSuccessful();

        return $response->viewData('stats');
    }

    public function test_both_pages_report_the_same_figures(): void
    {
        $this->booking('completed', 5000);
        $this->booking('confirmed', 80);
        Payout::create([
            'user_id' => $this->pro->id, 'amount' => 100,
            'currency' => 'USD', 'status' => 'paid', 'paid_at' => now(),
        ]);

        $earnings     = $this->statsFrom('professional.earnings.index');
        $transactions = $this->statsFrom('professional.transactions.index');

        foreach (['earned', 'gross', 'pending', 'withdrawn', 'available', 'total'] as $key) {
            $this->assertSame(
                $transactions[$key],
                $earnings[$key],
                "the two pages disagree on '{$key}' for one account",
            );
        }
    }

    public function test_the_dashboard_agrees_with_both_pages(): void
    {
        $this->booking('completed', 1000);          // $950 net
        $this->booking('confirmed', 80);            // $76 net, pending

        $earnings  = $this->statsFrom('professional.earnings.index');
        $dashboard = $this->statsFrom('professional.dashboard');

        $this->assertSame($earnings['pending'], $dashboard['pending_payout']);
        $this->assertSame($earnings['available'], $dashboard['available_balance']);
        $this->assertSame(1, $dashboard['pending_count']);
    }

    public function test_the_dashboard_balance_is_net_so_it_cannot_promise_more_than_is_withdrawable(): void
    {
        $this->booking('completed', 1000);

        $dashboard = $this->statsFrom('professional.dashboard');

        $this->assertSame(950.0, $dashboard['available_balance'], 'gross would read $1,000 and overstate the balance');
    }

    public function test_a_confirmed_booking_counts_as_pending_not_zero(): void
    {
        $this->booking('confirmed', 80);

        // The default commission is 5%, so $80 gross leaves $76 net.
        $this->assertSame(76.0, $this->statsFrom('professional.earnings.index')['pending']);
    }

    public function test_earnings_are_net_of_commission_not_gross(): void
    {
        $this->booking('completed', 5000);

        $stats = $this->statsFrom('professional.earnings.index');

        $this->assertSame(5000.0, $stats['gross']);
        $this->assertSame(4750.0, $stats['earned'], 'the professional receives the gross minus commission');
        $this->assertSame(250.0, $stats['commission']);
    }

    public function test_a_payout_cannot_exceed_the_balance_the_page_shows(): void
    {
        $this->booking('completed', 1000);          // $950 net after 5%

        // The gross figure would have let this through; the net must not.
        $this->actingAs($this->pro)
            ->post(route('professional.transactions.payout'), ['amount' => 1000])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, Payout::count(), 'no payout row is written when the guard refuses');

        $this->actingAs($this->pro)
            ->post(route('professional.transactions.payout'), ['amount' => 950])
            ->assertSessionHasNoErrors();
    }

    public function test_an_in_flight_request_reduces_what_is_still_available(): void
    {
        $this->booking('completed', 1000);          // $950 net
        Payout::create([
            'user_id' => $this->pro->id, 'amount' => 400,
            'currency' => 'USD', 'status' => 'requested', 'requested_at' => now(),
        ]);

        $this->assertSame(550.0, $this->statsFrom('professional.earnings.index')['available']);
    }

    public function test_an_account_with_no_bookings_reports_zero_on_both_pages(): void
    {
        $earnings     = $this->statsFrom('professional.earnings.index');
        $transactions = $this->statsFrom('professional.transactions.index');

        $this->assertSame(0.0, $earnings['pending']);
        $this->assertSame($transactions['pending'], $earnings['pending']);
        $this->assertFalse($earnings['hasActivity']);
    }
}
