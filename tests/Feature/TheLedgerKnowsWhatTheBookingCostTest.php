<?php

namespace Tests\Feature;

use App\Domain\Money\Ledger;
use App\Models\{Bid, Booking, Category, Event, Finalization, LedgerEntry, Package, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The ledger was recording GigResource's fee and not the client's money.
 *
 * `Ledger` says of itself that it is "the only way money is recorded, and the
 * only way it is counted", and `bookingTotal()` answers "what a booking comes
 * to" by summing BOOKING_CHARGE and UPGRADE. No code anywhere wrote a
 * BOOKING_CHARGE. Both paths that create a booking recorded the $4.99
 * agreement fee and nothing else, so the ledger held four dollars and ninety
 * nine cents of a two thousand dollar booking, `bookingTotal()` answered 0.00
 * for every booking on the site, and `margin()` computed a gross made entirely
 * of fees.
 *
 * It had never been seen because nothing reads the ledger yet. That is the
 * reason to fix it now rather than the reason to leave it: the first screen
 * that reads it would have shown every client a total of zero.
 */
class TheLedgerKnowsWhatTheBookingCostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    // ── the finalized-agreement path ─────────────────────────

    public function test_funding_an_agreement_records_the_deposit_and_the_balance(): void
    {
        [$client, $fin] = $this->readyToFund(agreed: 2000, deposit: 500);

        $this->actingAs($client)
            ->post(route('client.finalize.save', [$fin, 'payment']), ['confirm_payment' => 1]);

        $booking = Booking::where('client_id', $client->id)->latest('id')->firstOrFail();
        $charges = Ledger::forBooking($booking)
            ->where('entry_type', LedgerEntry::BOOKING_CHARGE);

        $this->assertCount(2, $charges, 'the deposit and the balance are in two different states');

        // The deposit: the gateway has it.
        $paid = $charges->firstWhere('state', LedgerEntry::SECURED);
        $this->assertNotNull($paid, 'the deposit is money held, not money owed');
        $this->assertEquals(500.00, (float) $paid->amount);

        // The balance: asked for, not received.
        $owed = $charges->firstWhere('state', LedgerEntry::PENDING);
        $this->assertNotNull($owed, 'the unpaid remainder has to be visible as owed');
        $this->assertEquals(1500.00, (float) $owed->amount);
    }

    public function test_the_two_charges_add_up_to_the_price_that_was_agreed(): void
    {
        [$client, $fin] = $this->readyToFund(agreed: 2000, deposit: 500);

        $this->actingAs($client)
            ->post(route('client.finalize.save', [$fin, 'payment']), ['confirm_payment' => 1]);

        $booking = Booking::where('client_id', $client->id)->latest('id')->firstOrFail();

        // The whole point of the ledger: the total is the list added up, and
        // it has to equal the number both sides signed.
        $this->assertEquals(2000.00, Ledger::bookingTotal($booking));
    }

    public function test_a_deposit_of_the_whole_price_leaves_nothing_owed(): void
    {
        [$client, $fin] = $this->readyToFund(agreed: 900, deposit: 900);

        $this->actingAs($client)
            ->post(route('client.finalize.save', [$fin, 'payment']), ['confirm_payment' => 1]);

        $booking = Booking::where('client_id', $client->id)->latest('id')->firstOrFail();
        $charges = Ledger::forBooking($booking)
            ->where('entry_type', LedgerEntry::BOOKING_CHARGE);

        // A zero row would read on a statement as a balance still to pay.
        $this->assertCount(1, $charges);
        $this->assertEquals(LedgerEntry::SECURED, $charges->first()->state);
        $this->assertEquals(900.00, Ledger::bookingTotal($booking));
    }

    public function test_the_agreement_fee_is_not_counted_as_part_of_the_booking(): void
    {
        [$client, $fin] = $this->readyToFund(agreed: 2000, deposit: 500);

        $this->actingAs($client)
            ->post(route('client.finalize.save', [$fin, 'payment']), ['confirm_payment' => 1]);

        $booking = Booking::where('client_id', $client->id)->latest('id')->firstOrFail();

        // GigResource's $4.99 belongs to GigResource. Folding it in would
        // overstate what the client owes the professional by exactly that.
        $this->assertEquals(2000.00, Ledger::bookingTotal($booking));
    }

    // ── the package path ─────────────────────────────────────

    public function test_buying_a_package_records_what_it_cost(): void
    {
        $pro = $this->pro();
        $client = $this->client();
        $package = $this->package($pro, 2400);

        $this->actingAs($client)->post(route('client.packages.book.store', $package), [
            'event_title' => 'Rooftop reception',
            'date'        => now()->addDays(30)->toDateString(),
            'location'    => 'Baltimore, MD',
            'guests'      => 80,
            'agree'       => 1,
        ]);

        $booking = Booking::where('client_id', $client->id)->latest('id')->firstOrFail();

        $this->assertEquals(2400.00, Ledger::bookingTotal($booking));
    }

    public function test_a_package_price_is_owed_and_not_held(): void
    {
        $pro = $this->pro();
        $client = $this->client();
        $package = $this->package($pro, 2400);

        $this->actingAs($client)->post(route('client.packages.book.store', $package), [
            'event_title' => 'Rooftop reception',
            'date'        => now()->addDays(30)->toDateString(),
            'agree'       => 1,
        ]);

        $booking = Booking::where('client_id', $client->id)->latest('id')->firstOrFail();
        $charge = Ledger::forBooking($booking)
            ->firstWhere('entry_type', LedgerEntry::BOOKING_CHARGE);

        // Buying a package opens a request the professional has still to
        // accept, and charges nothing. Calling that money held would promise
        // the professional a payment nobody has made.
        $this->assertNotNull($charge);
        $this->assertEquals(LedgerEntry::PENDING, $charge->state);
    }

    // ── fixtures ─────────────────────────────────────────────

    private function client(): User
    {
        $user = User::factory()->create(['primary_role' => 'client']);
        $user->assignRole('client');
        $user->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($user->id);
    }

    private function pro(): User
    {
        $user = User::factory()->create(['primary_role' => 'professional']);
        $user->assignRole('professional');
        $user->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($user->id);
    }

    private function package(User $pro, int $price): Package
    {
        return Package::create([
            'user_id'     => $pro->id,
            'category_id' => Category::firstOrCreate(
                ['slug' => 'dj-packages'], ['name' => 'DJ']
            )->id,
            'title'       => 'Full day coverage',
            'slug'        => 'full-day-' . uniqid(),
            'type'        => 'solo',
            'price'       => $price,
            'services'    => ['Photography', 'Videography'],
            'event_types' => ['Wedding'],
            'state'       => 'MD',
            'is_active'   => true,
            'status'      => 'active',
        ]);
    }

    /**
     * An agreement both sides have signed, one post away from being funded.
     *
     * @return array{0: User, 1: Finalization}
     */
    private function readyToFund(float $agreed, float $deposit): array
    {
        $client = $this->client();
        $pro = $this->pro();

        $event = Event::create([
            'title' => 'Rivera Wedding', 'client_id' => $client->id,
            'created_by' => $client->id, 'status' => 'published',
            'starts_at' => now()->addDays(30), 'city' => 'Baltimore', 'state' => 'MD',
        ]);

        $category = Category::create(['name' => 'DJ', 'slug' => 'dj-' . uniqid()]);

        $bid = Bid::create([
            'event_id' => $event->id, 'supplier_id' => $pro->id,
            'category_id' => $category->id, 'amount' => (int) $agreed, 'status' => 'submitted',
        ]);

        $fin = Finalization::create([
            'event_id' => $event->id, 'bid_id' => $bid->id,
            'client_id' => $client->id, 'supplier_id' => $pro->id,
            'agreed_price' => $agreed,
            'deposit_percent' => (int) round($deposit / $agreed * 100),
            'deposit_amount' => $deposit,
            'scope' => 'DJ for the reception',
        ]);

        // Every step before the deposit, finished: save() checks the order,
        // so the fixture has to be an agreement that really is ready to fund.
        $fin->forceFill([
            'bid_reviewed_at'    => now(),
            'scope_agreed_at'    => now(),
            'price_agreed_at'    => now(),
            'schedule_agreed_at' => now(),
            'terms_agreed_at'    => now(),
            'terms_version'      => 1,
            'client_approved_version'   => 1,
            'supplier_approved_version' => 1,
            'client_signature'        => $client->name,
            'client_signed_at'        => now(),
            'client_signed_version'   => 1,
            'supplier_signature'      => $pro->name,
            'supplier_signed_at'      => now(),
            'supplier_signed_version' => 1,
        ])->save();

        return [$client, $fin->fresh()];
    }
}
