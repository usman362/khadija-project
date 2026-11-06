<?php

namespace Tests\Feature;

use App\Models\{Booking, Category, Event, LedgerEntry, User};
use App\Support\Earnings;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Payments & Payouts, against Khadijah's 28 October screen.
 *
 * Three claims on her screen are not true of this platform, and a money screen
 * is the last place to imply otherwise. Each one has a test here:
 *
 *   "Payouts are processed automatically based on your settings"
 *   "No manual withdrawal needed"   payouts here are requested and approved.
 *
 *   "Est. Payout Date ... (Estimated)"
 *                                   nothing schedules a next payout, so there
 *                                   is no date to estimate.
 *
 *   "Rate is locked for that transaction and does not change even if you
 *    upgrade or downgrade later"    no rate is stored per booking, so the
 *                                   rate that applies is today's.
 *
 * The rest is about the arithmetic agreeing with itself: the totals at the top
 * and the rows underneath are the same money counted once.
 */
class ThePaymentsPageMatchesTheDesignTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;
    private Category $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-11-06 12:00:00'));

        $this->pro    = $this->person('professional');
        $this->client = $this->person('client');

        $this->service = Category::create([
            'name' => 'DJ Services', 'slug' => 'dj-services-payments',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function person(string $role): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function booking(string $status = 'completed', float $price = 5000, array $event = []): Booking
    {
        $e = Event::create(array_merge([
            'title'        => 'Direct Offer Event',
            'client_id'    => $this->client->id,
            'created_by'   => $this->client->id,
            'status'       => 'published',
            'is_published' => true,
            'event_type'   => 'Wedding',
            'starts_at'    => now()->addDays(30),
        ], $event));

        $e->categories()->sync([$this->service->id]);

        return Booking::create([
            'event_id'    => $e->id,
            'client_id'   => $this->client->id,
            'supplier_id' => $this->pro->id,
            'created_by'  => $this->client->id,
            'status'      => $status,
            'price'       => $price,
        ]);
    }

    private function page(array $query = []): string
    {
        return $this->actingAs($this->pro)
            ->get(route('professional.earnings.index', $query))
            ->assertOk()
            ->getContent();
    }

    // ---------------------------------------------------------------- layout

    /** Her four cards, with the words she wrote under the first one. */
    public function test_the_four_cards_carry_her_labels(): void
    {
        $this->booking();

        $html = $this->page();

        foreach ([
            'Total Net Earnings', 'Sum of all net earnings from completed and eligible transactions.',
            'Paid Out', 'Pending Earnings', 'Available for Payout',
        ] as $copy) {
            $this->assertStringContainsString($copy, $html, "The card copy \"{$copy}\" is missing.");
        }
    }

    /** Her nine column headings. */
    public function test_the_table_has_her_columns(): void
    {
        $this->booking();

        $html = $this->page();

        foreach (['Date', 'Event / Client', 'Booking Status', 'Payment Status',
                  'Client Paid', '(Your Service)', 'Platform Fee', '(Actual Rate)',
                  'Net Earnings', 'Actions'] as $head) {
            $this->assertStringContainsString($head, $html, "The column \"{$head}\" is missing.");
        }
    }

    /** The tier band names the tier, the rate and the arithmetic. */
    public function test_the_tier_band_shows_the_rate_and_what_it_took(): void
    {
        $this->booking(price: 5000);

        $html = $this->page();

        $this->assertStringContainsString('Starter Tier (5%)', $html);
        $this->assertStringContainsString('How Fee Rates Work', $html);
        $this->assertStringContainsString('Your Tier Earnings', $html);
        $this->assertStringContainsString('$5,000.00', $html, 'The gross over the window.');
        $this->assertStringContainsString('$250.00', $html, '5% of $5,000.');
        $this->assertStringContainsString('$4,750.00', $html, 'What is left.');
    }

    /**
     * The window the band reports over is the one the picker is showing.
     *
     * Asserted on the figures rather than on the page, because the table
     * below the band is not windowed: the picker narrows what the tier is
     * measured over, not which transactions are listed.
     */
    public function test_the_period_picker_changes_the_band(): void
    {
        $old = $this->booking(price: 1000);
        $old->forceFill(['created_at' => now()->subDays(60)])->saveQuietly();

        $this->assertSame(1000.0, Earnings::window($this->pro, now()->subDays(90), now())['gross']);
        $this->assertSame(0.0, Earnings::window($this->pro, now()->subDays(30), now())['gross']);

        // And the page says which window it is reporting.
        $this->assertStringContainsString('Last 90 Days', $this->page(['window' => '90']));
        $this->assertStringContainsString('Last 30 Days', $this->page(['window' => '30']));
    }

    // ------------------------------------------ claims that are not true here

    /** Payouts are requested here, so the page does not promise otherwise. */
    public function test_the_page_does_not_claim_payouts_are_automatic(): void
    {
        $this->booking();

        $html = $this->page();

        $this->assertStringNotContainsString('processed automatically', $html);
        $this->assertStringNotContainsString('No manual withdrawal needed', $html);
        $this->assertStringContainsString('Payouts are paid once you request one and it is approved.', $html);
    }

    /** Nothing schedules a payout, so no date is estimated. */
    public function test_no_payout_date_is_estimated(): void
    {
        $this->booking();

        $html = $this->page();

        $this->assertStringNotContainsString('(Estimated)', $html);
        $this->assertStringContainsString('Not scheduled', $html);
    }

    /** A payout that actually happened carries its own date. */
    public function test_a_real_payout_shows_its_real_date(): void
    {
        $booking = $this->booking();

        LedgerEntry::create([
            'booking_id'  => $booking->id,
            'event_id'    => $booking->event_id,
            'entry_type'  => 'payout',
            'amount'      => 4750,
            'sign'        => '+',
            'state'       => 'released',
            'occurred_at' => now()->subDays(3),
        ]);

        $html = $this->page();

        $this->assertStringContainsString('Paid Out', $html);
        $this->assertStringContainsString('Nov 3, 2026', $html);
        $this->assertStringContainsString('(Paid Out)', $html);
    }

    /** No rate is stored per booking, so the page does not claim one is. */
    public function test_the_page_does_not_claim_the_rate_is_locked_to_the_booking(): void
    {
        $this->booking();

        $html = $this->page();

        $this->assertStringNotContainsString('Rate is locked for that transaction', $html);
        $this->assertStringContainsString('Nothing is locked to the day a booking was accepted.', $html);
    }

    // ----------------------------------------------------------- arithmetic

    /** Every row counts the same money the cards at the top count. */
    public function test_a_row_cannot_disagree_with_the_card_above_it(): void
    {
        // Agreed and under way: pending, not earned, and never "not earned".
        $this->booking('in_progress', 2000);

        $html = $this->page();

        $this->assertStringContainsString('Pending', $html);
        $this->assertStringContainsString('Event not completed yet.', $html);

        // The card and the row report the same net on the same booking.
        $stats = Earnings::forProfessional($this->pro);
        $this->assertSame(1900.0, $stats['pending'], '5% of $2,000 leaves $1,900.');
        $this->assertStringContainsString('$1,900.00', $html);
    }

    /** A request nobody accepted is worth nothing, and says so. */
    public function test_an_unaccepted_request_is_not_counted_as_money(): void
    {
        $this->booking('requested', 9000);

        $html = $this->page();

        $this->assertStringContainsString('Not Earned', $html);
        $this->assertStringContainsString('Not accepted yet.', $html);
        $this->assertStringNotContainsString('$8,550.00', $html, '9000 less 5% must not appear.');
    }

    /**
     * A cancelled booking is worth what the signed terms let you keep.
     *
     * The fee is then charged on that, not on the original price, which is the
     * difference between a fee a professional can check and one they have to
     * take on trust.
     */
    public function test_a_cancelled_booking_is_worth_what_the_terms_retain(): void
    {
        $booking = $this->booking('cancelled', 5000);

        $booking->finalizations()->create([
            'event_id'       => $booking->event_id,
            'client_id'      => $this->client->id,
            'supplier_id'    => $this->pro->id,
            'agreed_price'   => 5000,
            'deposit_amount' => 1000,
            'status'         => 'signed',
        ]);

        $t = Earnings::transaction($booking->fresh('finalizations'), $this->pro);

        $this->assertGreaterThan(0, $t['basis'], 'Signed terms retain a deposit.');
        $this->assertSame(round($t['basis'] - $t['fee'], 2), $t['net']);
        $this->assertLessThan(0, $t['adjust'], 'A cancellation only ever takes money off.');
    }

    /** With no signed terms there is no agreed deposit, so nothing is kept. */
    public function test_a_cancellation_with_no_signed_terms_retains_nothing(): void
    {
        $booking = $this->booking('cancelled', 5000);

        $t = Earnings::transaction($booking, $this->pro);

        $this->assertSame(0.0, $t['basis']);
        $this->assertSame(0.0, $t['net']);
        $this->assertSame('Not Earned', $t['state']);
        $this->assertStringContainsString('no agreed deposit', (string) $t['cancel_note']);
    }

    /** The minus sign goes in front of the currency, not inside the number. */
    public function test_a_negative_amount_reads_as_money(): void
    {
        $booking = $this->booking('cancelled', 3500);

        $this->assertStringNotContainsString('$-3,500.00', $this->page());
        $this->assertNotNull($booking->fresh());
    }

    // ------------------------------------------------------------ filtering

    /** Each dropdown narrows the table for real. */
    public function test_the_filters_narrow_the_table(): void
    {
        $this->booking('completed', 5000, ['title' => 'Completed Gala', 'event_type' => 'Corporate Event']);
        $this->booking('cancelled', 800, ['title' => 'Cancelled Party', 'event_type' => 'Birthday Party']);

        $byBooking = $this->page(['booking' => 'cancelled']);
        $this->assertStringContainsString('Cancelled Party', $byBooking);
        $this->assertStringNotContainsString('Completed Gala', $byBooking);

        $byType = $this->page(['type' => 'Corporate Event']);
        $this->assertStringContainsString('Completed Gala', $byType);
        $this->assertStringNotContainsString('Cancelled Party', $byType);

        $byPayment = $this->page(['payment' => 'not_earned']);
        $this->assertStringContainsString('Cancelled Party', $byPayment);
        $this->assertStringNotContainsString('Completed Gala', $byPayment);

        $bySearch = $this->page(['search' => 'Gala']);
        $this->assertStringContainsString('Completed Gala', $bySearch);
        $this->assertStringNotContainsString('Cancelled Party', $bySearch);
    }

    /** The export is the same filtered set, with the arithmetic in it. */
    public function test_the_table_exports_as_a_file(): void
    {
        $this->booking('completed', 5000, ['title' => 'Completed Gala']);

        $csv = $this->actingAs($this->pro)
            ->get(route('professional.earnings.export'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Amount fee charged on', $csv);
        $this->assertStringContainsString('Completed Gala', $csv);
        $this->assertStringContainsString('250', $csv, 'The 5% fee on $5,000.');
        $this->assertStringContainsString('4750', $csv);
    }

    /** The arrows on her Date heading sort the table for real. */
    public function test_the_date_heading_sorts_the_table(): void
    {
        $first = $this->booking('completed', 100, ['title' => 'Older Booking']);
        $first->forceFill(['created_at' => now()->subDays(20)])->saveQuietly();

        $this->booking('completed', 200, ['title' => 'Newer Booking']);

        $newest = $this->page();
        $oldest = $this->page(['sort' => 'oldest']);

        $this->assertLessThan(
            strpos($newest, 'Older Booking'),
            strpos($newest, 'Newer Booking'),
            'Newest first by default.',
        );

        $this->assertLessThan(
            strpos($oldest, 'Newer Booking'),
            strpos($oldest, 'Older Booking'),
            'Oldest first when asked for.',
        );
    }

    /** An account with nothing on it says so rather than showing zeros. */
    public function test_an_account_with_no_transactions_says_so(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('No transactions yet', $html);
        $this->assertStringContainsString('A transaction appears here once a client books you.', $html);
    }
}
