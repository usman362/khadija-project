<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Transaction detail — audit row 17.
 *
 * The Payments ledger showed an amount and a status per row and nothing else:
 * the client could see a figure but not what it was for, whether any of it had
 * moved, or what had to happen next.
 *
 * The second thing this file pins down is the bug that made the whole page
 * lie. `priceColumn()` looked for `bookings.total_amount` and then
 * `bookings.agreed_price` — neither column has ever existed. The lookup
 * returned null, every sum was skipped, and Payments and Spending both
 * reported $0 while every booking in the database carried a price in `price`.
 * It is the same defect as the Total Spent card: a real number, not read.
 */
class ClientTransactionDetailTest extends TestCase
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

        return $user->fresh();
    }

    private function pro(): User
    {
        $user = User::factory()->create();
        $user->assignRole('professional');

        return $user->fresh();
    }

    private function booking(User $client, User $pro, string $status = 'confirmed', $price = 1800): Booking
    {
        $event = Event::create([
            'client_id'  => $client->id,
            'created_by' => $client->id,
            'title'      => 'Harbor rehearsal dinner',
            'starts_at'  => now()->addDays(20),
            'location'   => 'Baltimore, MD',
            'status'     => 'confirmed',
        ]);

        return Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $client->id,
            'supplier_id' => $pro->id,
            'created_by'  => $client->id,
            'status'      => $status,
            'price'       => $price,
            'currency'    => 'USD',
            'booked_at'   => now()->addDays(20),
            'source'      => 'package',
        ]);
    }

    public function test_the_ledger_row_now_links_to_its_own_page(): void
    {
        $client  = $this->client();
        $booking = $this->booking($client, $this->pro());

        $this->actingAs($client)
            ->get(route('client.payments.index'))
            ->assertSuccessful()
            ->assertSee(route('client.payments.show', $booking));
    }

    public function test_the_detail_page_says_what_the_money_is_for(): void
    {
        $client  = $this->client();
        $pro     = $this->pro();
        $booking = $this->booking($client, $pro);

        $response = $this->actingAs($client)->get(route('client.payments.show', $booking));

        $response->assertSuccessful();
        $response->assertSee('1,800.00');
        $response->assertSee('Harbor rehearsal dinner');
        $response->assertSee($pro->name);
        $response->assertSee('#'.$booking->id);
    }

    /**
     * An agreed booking is not a paid one, and the page has to say so — there
     * is no payment provider connected to this app to have taken anything.
     */
    public function test_a_confirmed_booking_is_not_described_as_paid(): void
    {
        $client  = $this->client();
        $booking = $this->booking($client, $this->pro(), 'confirmed');

        $this->actingAs($client)
            ->get(route('client.payments.show', $booking))
            ->assertSee('Agreed, not yet paid')
            ->assertSee('It has not been collected through GigResource.');
    }

    public function test_a_requested_booking_owes_nothing_yet(): void
    {
        $client  = $this->client();
        $booking = $this->booking($client, $this->pro(), 'requested');

        $this->actingAs($client)
            ->get(route('client.payments.show', $booking))
            ->assertSee('Not agreed yet')
            ->assertSee('Nothing is owed until the professional accepts.');
    }

    public function test_a_cancelled_booking_owes_nothing(): void
    {
        $client  = $this->client();
        $booking = $this->booking($client, $this->pro(), 'cancelled');

        $this->actingAs($client)
            ->get(route('client.payments.show', $booking))
            ->assertSee('Cancelled')
            ->assertSee('Nothing is owed on it.');
    }

    public function test_another_client_cannot_read_the_transaction(): void
    {
        $booking = $this->booking($this->client(), $this->pro());

        $this->actingAs($this->client())
            ->get(route('client.payments.show', $booking))
            ->assertForbidden();
    }

    /**
     * The ghost-column bug: figures existed and were not read.
     *
     * Written against the page rather than the private method, because the
     * defect was only ever visible on the page.
     */
    public function test_payments_and_spending_report_real_booking_money(): void
    {
        $client = $this->client();
        $pro    = $this->pro();

        $this->booking($client, $pro, 'completed', 2000);   // paid
        $this->booking($client, $pro, 'confirmed', 1500);   // agreed, unpaid

        $this->actingAs($client)->get(route('client.payments.index'))
            ->assertSuccessful()
            ->assertSee('$2,000')     // paid
            ->assertSee('$1,500')     // agreed
            ->assertSee('$3,500');    // total agreed

        $this->actingAs($client)->get(route('client.spending.index'))
            ->assertSuccessful()
            ->assertSee('$3,500');
    }
}
