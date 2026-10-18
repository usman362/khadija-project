<?php

namespace Tests\Feature;

use App\Domain\Money\Ledger;
use App\Models\{Booking, BookingUpgrade, Category, Event, LedgerEntry, User};
use App\Support\Commission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The money ledger (A1), approved by Sir Peter on 16 October 2026.
 *
 * His instruction, Decision Log item 7: track every money event as a discrete,
 * separately auditable record, and never represent the picture by editing a
 * single booking-total field.
 *
 * These are the four rules that make that true rather than aspirational. They
 * should fail loudly if anyone ever adds a stored total, edits a settled entry,
 * or lets an upgrade be owed before its payment is real.
 */
class MoneyIsAListNotATotalTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;
    private Event $event;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');

        $this->pro = User::factory()->create(['primary_role' => 'professional']);
        $this->pro->assignRole('professional');

        $this->event = Event::create([
            'title' => 'Rivera Wedding', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'starts_at' => now()->addDays(30),
        ]);

        $category = Category::create(['name' => 'DJ', 'slug' => 'dj-' . uniqid()]);

        $this->booking = Booking::create([
            'event_id' => $this->event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'category_id' => $category->id,
            'status' => 'confirmed', 'price' => 1800, 'currency' => 'USD',
        ]);
    }

    // ── Rule 1: the total is a sum, never a field ────────────

    public function test_a_booking_total_is_the_sum_of_its_entries(): void
    {
        Ledger::record(
            LedgerEntry::BOOKING_CHARGE, 1800, '+',
            party: $this->client, booking: $this->booking, state: LedgerEntry::SECURED,
        );

        $this->assertSame(1800.0, Ledger::bookingTotal($this->booking));

        Ledger::record(
            LedgerEntry::UPGRADE, 300, '+',
            party: $this->client, booking: $this->booking, state: LedgerEntry::SECURED,
        );

        $this->assertSame(2100.0, Ledger::bookingTotal($this->booking));
    }

    /** There is no column for a total to drift away from. */
    public function test_nothing_stores_a_modified_booking_total(): void
    {
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('bookings');

        foreach (['modified_total', 'total_with_upgrades', 'adjusted_total'] as $banned) {
            $this->assertNotContains(
                $banned,
                $columns,
                "A stored total defeats the whole model. The total is Σ of the ledger.",
            );
        }
    }

    // ── Rule 2: nothing is edited, corrections are new rows ──

    public function test_a_correction_is_a_new_row_and_leaves_the_original_alone(): void
    {
        $charge = Ledger::record(
            LedgerEntry::PLATFORM_FEE, 2.99, '+',
            party: $this->client, event: $this->event, state: LedgerEntry::SECURED,
        );

        $reversal = Ledger::reverse($charge, 'Duplicate charge');

        $charge->refresh();

        $this->assertSame('2.99', (string) $charge->amount, 'The original must not be touched.');
        $this->assertSame('+', $charge->sign);
        $this->assertSame($charge->id, $reversal->reverses_id);
        $this->assertSame('-', $reversal->sign);
        $this->assertSame('Duplicate charge', $reversal->metadata['reason']);
        $this->assertTrue($charge->wasReversed());
    }

    /** And the two net to nothing without either being deleted. */
    public function test_a_reversed_charge_nets_to_nothing(): void
    {
        $charge = Ledger::record(
            LedgerEntry::BOOKING_CHARGE, 1800, '+',
            party: $this->client, booking: $this->booking, state: LedgerEntry::SECURED,
        );

        Ledger::reverse($charge, 'Cancelled');

        $this->assertSame(0.0, Ledger::bookingTotal($this->booking));
        $this->assertSame(2, LedgerEntry::where('booking_id', $this->booking->id)->count());
    }

    public function test_the_same_entry_cannot_be_reversed_twice(): void
    {
        $charge = Ledger::record(LedgerEntry::PLATFORM_FEE, 2.99, '+', event: $this->event);

        Ledger::reverse($charge);

        $this->expectException(\InvalidArgumentException::class);
        Ledger::reverse($charge);
    }

    public function test_a_reversal_cannot_exceed_what_it_corrects(): void
    {
        $charge = Ledger::record(LedgerEntry::PLATFORM_FEE, 2.99, '+', event: $this->event);

        $this->expectException(\InvalidArgumentException::class);
        Ledger::reverse($charge, 'Too much', 5.00);
    }

    // ── Rule 3: an amount is positive; direction is the sign ─

    public function test_an_amount_is_always_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Ledger::record(LedgerEntry::PLATFORM_FEE, -2.99, '+', event: $this->event);
    }

    // ── Rule 4: a settled entry only moves forward ───────────

    public function test_an_entry_moves_forward_and_never_back(): void
    {
        $entry = Ledger::record(LedgerEntry::BOOKING_CHARGE, 1800, '+', booking: $this->booking);

        Ledger::settle($entry, LedgerEntry::SECURED);
        $this->assertSame(LedgerEntry::SECURED, $entry->state);

        $this->expectException(\InvalidArgumentException::class);
        Ledger::settle($entry, LedgerEntry::PENDING);
    }

    /**
     * Money asked for is not money held.
     *
     * Totalling pending and secured together is how a dashboard comes to
     * promise a balance nobody can withdraw, which is the bug the professional
     * dashboard already had once.
     */
    public function test_asked_for_and_held_are_counted_separately(): void
    {
        Ledger::record(LedgerEntry::BOOKING_CHARGE, 1800, '+', party: $this->pro, booking: $this->booking, state: LedgerEntry::SECURED);
        Ledger::record(LedgerEntry::BOOKING_CHARGE, 500, '+', party: $this->pro, booking: $this->booking, state: LedgerEntry::PENDING);

        $standing = Ledger::standing($this->pro);

        $this->assertSame(500.0, $standing['owed']);
        $this->assertSame(1800.0, $standing['held']);
        $this->assertSame(0.0, $standing['earned']);
    }

    // ── B3: an upgrade is a line, and payment comes first ────

    /**
     * Peter's October question, answered by his own August decision.
     *
     * "What happens if the professional wants to get paid after the fact
     * because the client wanted them to stay longer, or added more food."
     */
    public function test_an_upgrade_is_its_own_line_and_never_folded_in(): void
    {
        $upgrade = BookingUpgrade::create([
            'booking_id'      => $this->booking->id,
            'requested_by'    => $this->pro->id,
            'description'     => 'Two extra hours',
            'amount'          => 300,
            'commission_rate' => Commission::rateFor($this->pro),
            'status'          => BookingUpgrade::CONFIRMED,
            'confirmed_at'    => now(),
        ]);

        $this->booking->refresh()->load('upgrades');

        $this->assertSame(2100.0, $this->booking->totalWithUpgrades());
        $this->assertSame('1800.00', (string) $this->booking->price, 'The original price is untouched.');
        $this->assertSame(300.0, (float) $upgrade->amount);
    }

    /** Only a confirmed upgrade is owed. */
    public function test_an_unconfirmed_upgrade_is_not_owed(): void
    {
        foreach ([BookingUpgrade::REQUESTED, BookingUpgrade::APPROVED, BookingUpgrade::PAYMENT_SECURED] as $status) {
            BookingUpgrade::create([
                'booking_id'      => $this->booking->id,
                'requested_by'    => $this->pro->id,
                'description'     => 'Pending work: ' . $status,
                'amount'          => 100,
                'commission_rate' => 5,
                'status'          => $status,
            ]);
        }

        $this->booking->refresh()->load('upgrades');

        $this->assertSame(
            1800.0,
            $this->booking->totalWithUpgrades(),
            'Payment is secured before an upgrade is confirmed, so nothing before that counts.',
        );
    }

    /** No special fee on an upgrade; the pro's ordinary tier rate, per B3. */
    public function test_an_upgrade_takes_the_ordinary_tier_rate(): void
    {
        $upgrade = BookingUpgrade::create([
            'booking_id'      => $this->booking->id,
            'requested_by'    => $this->pro->id,
            'description'     => 'Extra hour',
            'amount'          => 200,
            'commission_rate' => Commission::rateFor($this->pro),
            'status'          => BookingUpgrade::CONFIRMED,
        ]);

        $this->assertSame(Commission::rateFor($this->pro), (float) $upgrade->commission_rate);
        $this->assertSame(
            Commission::on(200, $this->pro),
            $upgrade->commission(),
            'An upgrade must not carry a rate of its own.',
        );
    }

    // ── The calculator Peter asked for ───────────────────────

    /**
     * What GigResource actually keeps, from recorded fees rather than estimates.
     *
     * Peter: "have your developer build a Stripe Fee & Revenue Calculator into
     * the Admin side, showing the gross charge, actual Stripe fees, other
     * payment costs, refunds, and GigResource's net revenue."
     */
    public function test_the_margin_is_read_from_recorded_fees(): void
    {
        Ledger::record(LedgerEntry::BOOKING_CHARGE, 300, '+', party: $this->client, booking: $this->booking, state: LedgerEntry::SECURED);
        Ledger::record(LedgerEntry::PLATFORM_FEE, 4.99, '+', party: $this->client, booking: $this->booking, state: LedgerEntry::SECURED);
        Ledger::record(LedgerEntry::COMMISSION, 15.00, '+', party: $this->pro, booking: $this->booking, state: LedgerEntry::SECURED);
        // 2.9% + $0.30 on each of the two charges.
        Ledger::record(LedgerEntry::PROVIDER_FEE, 9.00, '-', booking: $this->booking, state: LedgerEntry::SECURED);
        Ledger::record(LedgerEntry::PROVIDER_FEE, 0.44, '-', booking: $this->booking, state: LedgerEntry::SECURED);

        $margin = Ledger::margin(Ledger::forBooking($this->booking));

        $this->assertSame(304.99, $margin['gross']);
        $this->assertSame(9.44, $margin['provider_fee']);
        $this->assertSame(10.55, $margin['net'], 'Platform fee plus commission, less what the processor took.');
    }
}
