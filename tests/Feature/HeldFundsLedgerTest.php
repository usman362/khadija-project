<?php

namespace Tests\Feature;

use App\Domain\Money\HeldFunds;
use App\Models\Booking;
use App\Models\Event;
use App\Models\HeldFundEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checklist row 181 — the held-funds ledger.
 *
 * Nothing recorded money sitting held. The position between "the client
 * funded a deposit" and "the professional was paid" existed only as an
 * inference, and three features already built lean on it: dispute financial
 * outcomes, cancellation refunds, and release milestones.
 *
 * Append-only is the whole design. Most of these tests exist to prove the
 * ledger cannot be quietly rewritten — because a ledger you can edit is one
 * nobody can rely on in the argument it exists for.
 */
class HeldFundsLedgerTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->create();
        $this->pro    = User::factory()->create();

        $event = Event::create([
            'title' => 'Summer wedding', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'starts_at' => now()->addDays(40),
        ]);

        $this->booking = Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'confirmed', 'price' => 2000, 'currency' => 'USD',
        ]);
    }

    /* ── The position ───────────────────────────────────────── */

    public function test_a_booking_with_no_entries_holds_nothing(): void
    {
        $position = HeldFunds::position($this->booking);

        $this->assertSame(0.0, $position['funded']);
        $this->assertSame(0.0, $position['held']);
    }

    public function test_funding_and_releasing_move_the_position(): void
    {
        HeldFunds::fund($this->booking, 600, HeldFundEntry::DEPOSIT, 'Deposit taken on award.');
        HeldFunds::fund($this->booking, 1400, HeldFundEntry::BALANCE, 'Balance authorised.');

        $this->assertSame(2000.0, HeldFunds::position($this->booking)['held']);

        HeldFunds::release($this->booking, 1400, 'Work delivered and confirmed.');

        $position = HeldFunds::position($this->booking);

        $this->assertSame(1400.0, $position['released']);
        $this->assertSame(600.0, $position['held']);
        $this->assertSame(600.0, $position['deposit']);
    }

    /**
     * A ledger that lets you take out more than went in is not a ledger, and
     * this is the one place the error is cheap to catch. After the processor
     * has moved it, it is not.
     */
    public function test_more_cannot_be_released_than_is_held(): void
    {
        HeldFunds::fund($this->booking, 500, HeldFundEntry::DEPOSIT, 'Deposit.');

        $this->expectException(\RuntimeException::class);

        HeldFunds::release($this->booking, 900, 'Trying to release more than exists.');
    }

    public function test_a_refund_and_a_release_draw_on_the_same_hold(): void
    {
        HeldFunds::fund($this->booking, 1000, HeldFundEntry::BALANCE, 'Funded.');
        HeldFunds::refund($this->booking, 400, 'Partial refund.');

        $this->assertSame(600.0, HeldFunds::position($this->booking)['held']);

        $this->expectException(\RuntimeException::class);

        HeldFunds::release($this->booking, 700, 'More than what is left.');
    }

    /** A negative hold is a bookkeeping error, never a state to display. */
    public function test_the_hold_never_reads_negative(): void
    {
        HeldFunds::fund($this->booking, 100, HeldFundEntry::BALANCE, 'Funded.');

        // Posted directly, bypassing the guard, as a bad import would.
        HeldFundEntry::create([
            'booking_id' => $this->booking->id, 'kind' => HeldFundEntry::RELEASE,
            'direction' => HeldFundEntry::OUT, 'amount' => 500, 'reason' => 'Bad import.',
        ]);

        $this->assertSame(0.0, HeldFunds::position($this->booking)['held']);
    }

    /* ── Append-only ────────────────────────────────────────── */

    public function test_an_entry_cannot_be_edited(): void
    {
        $entry = HeldFunds::fund($this->booking, 600, HeldFundEntry::DEPOSIT, 'Deposit.');

        $this->expectException(\RuntimeException::class);

        $entry->update(['amount' => 5000]);
    }

    public function test_an_entry_cannot_be_deleted(): void
    {
        $entry = HeldFunds::fund($this->booking, 600, HeldFundEntry::DEPOSIT, 'Deposit.');

        $this->expectException(\RuntimeException::class);

        $entry->delete();
    }

    /**
     * A correction is a reversing entry, and both stay visible. "We released
     * it and then took it back" is a different fact from "we never released
     * it", and only one of them is true.
     */
    public function test_a_mistake_is_corrected_by_a_reversal_that_stays_on_the_record(): void
    {
        HeldFunds::fund($this->booking, 1000, HeldFundEntry::BALANCE, 'Funded.');
        $wrong = HeldFunds::release($this->booking, 1000, 'Released to the wrong professional.');

        HeldFunds::reverse($wrong, 'Released in error — wrong service line.');

        $position = HeldFunds::position($this->booking);

        $this->assertSame(1000.0, $position['held'], 'the reversal puts it back');
        $this->assertSame(2, HeldFundEntry::where('booking_id', $this->booking->id)
            ->whereIn('kind', [HeldFundEntry::RELEASE, HeldFundEntry::ADJUSTMENT])->count());
    }

    public function test_a_zero_or_negative_entry_is_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        HeldFunds::fund($this->booking, 0, HeldFundEntry::BALANCE, 'Nothing.');
    }

    /* ── Deciding is not paying (§8) ────────────────────────── */

    /**
     * An entry records what the platform DECIDED. The processor confirming it
     * is a separate fact — one column for both would let a professional be
     * told they were paid because somebody clicked approve.
     */
    public function test_an_entry_starts_pending_until_the_processor_confirms(): void
    {
        $entry = HeldFunds::fund($this->booking, 600, HeldFundEntry::DEPOSIT, 'Deposit.');

        $this->assertSame(HeldFundEntry::PENDING, $entry->state);
        $this->assertSame(0.0, HeldFunds::position($this->booking)['settled_held']);

        HeldFunds::settle($entry, 'pi_test_9001');

        $this->assertTrue($entry->fresh()->isSettled());
        $this->assertSame(600.0, HeldFunds::position($this->booking)['settled_held']);
    }

    public function test_settling_twice_is_harmless(): void
    {
        $entry = HeldFunds::fund($this->booking, 600, HeldFundEntry::DEPOSIT, 'Deposit.');

        HeldFunds::settle($entry, 'pi_first');
        HeldFunds::settle($entry, 'pi_second');

        $this->assertSame('pi_first', $entry->fresh()->processor_reference);
    }

    /* ── Per service line (R12) ─────────────────────────────── */

    public function test_one_bookings_money_never_touches_another(): void
    {
        $other = Booking::create([
            'event_id' => $this->booking->event_id, 'client_id' => $this->client->id,
            'supplier_id' => User::factory()->create()->id, 'created_by' => $this->client->id,
            'status' => 'confirmed', 'price' => 800,
        ]);

        HeldFunds::fund($this->booking, 2000, HeldFundEntry::BALANCE, 'Funded.');

        $this->assertSame(2000.0, HeldFunds::position($this->booking)['held']);
        $this->assertSame(0.0, HeldFunds::position($other)['held']);
    }

    /** The history reads oldest first — what an admin or a dispute reads. */
    public function test_the_ledger_reads_as_a_history(): void
    {
        HeldFunds::fund($this->booking, 600, HeldFundEntry::DEPOSIT, 'Deposit taken on award.');
        HeldFunds::fund($this->booking, 1400, HeldFundEntry::BALANCE, 'Balance authorised.');
        HeldFunds::release($this->booking, 1400, 'Work confirmed.');

        $ledger = HeldFunds::ledger($this->booking);

        $this->assertCount(3, $ledger);
        $this->assertSame(HeldFundEntry::DEPOSIT, $ledger->first()->kind);
        $this->assertSame(HeldFundEntry::RELEASE, $ledger->last()->kind);

        // Every line says why. A movement with no reason is unauditable.
        foreach ($ledger as $entry) {
            $this->assertNotEmpty($entry->reason);
        }
    }
}
