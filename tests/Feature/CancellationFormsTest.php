<?php

namespace Tests\Feature;

use App\Domain\Cancellations\CancellationPolicy;
use App\Models\Booking;
use App\Models\CancellationRequest;
use App\Models\Event;
use App\Models\Finalization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checklist row 155 — the gap an earlier brief called "the single most
 * important" one: a professional had no way to report a client who never
 * turned up.
 *
 * Both directions, fed by the Cancellation & Refund Policy v1_0719. The rule
 * that governs almost every test here: THE DEPOSIT IS NEVER REFUNDED, at any
 * notice period, and every figure is computed on the held balance alone.
 */
class CancellationFormsTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = $this->account('client');
        $this->pro    = $this->account('professional');
    }

    private function account(string $role): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    /** A booking with signed terms: $2,000 agreed, 30% deposit. */
    private function booking(int $daysAway = 60, array $overrides = []): Booking
    {
        $event = Event::create([
            'title'      => 'Autumn wedding',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays($daysAway),
        ]);

        $booking = Booking::create(array_merge([
            'event_id'    => $event->id,
            'client_id'   => $this->client->id,
            'supplier_id' => $this->pro->id,
            'created_by'  => $this->client->id,
            'status'      => 'confirmed',
            'price'       => 2000,
        ], $overrides));

        Finalization::create([
            'event_id'       => $event->id,
            'client_id'      => $this->client->id,
            'supplier_id'    => $this->pro->id,
            'booking_id'     => $booking->id,
            'status'         => 'signed',
            'agreed_price'   => 2000,
            'deposit_percent' => 30,
            'deposit_amount' => 600,
        ]);

        return $booking->fresh();
    }

    /* ── The policy's arithmetic ────────────────────────────── */

    public function test_more_than_thirty_days_refunds_the_whole_balance(): void
    {
        $quote = CancellationPolicy::quote($this->booking(60));

        $this->assertSame(2000.0, $quote['agreed']);
        $this->assertSame(600.0, $quote['deposit']);
        $this->assertSame(1400.0, $quote['balance']);
        $this->assertSame(1400.0, $quote['refund']);
    }

    public function test_fourteen_to_thirty_days_refunds_half_the_balance(): void
    {
        $this->assertSame(700.0, CancellationPolicy::quote($this->booking(20))['refund']);
    }

    public function test_inside_fourteen_days_refunds_nothing(): void
    {
        $this->assertSame(0.0, CancellationPolicy::quote($this->booking(5))['refund']);
    }

    /**
     * The boundary. At exactly 30 days the client is in the 14–30 band, not
     * the "more than 30" one — the tiers are read longest-notice-first so the
     * two can never both match.
     */
    public function test_exactly_thirty_days_is_the_half_band(): void
    {
        $this->assertSame(700.0, CancellationPolicy::quote($this->booking(30))['refund']);
        $this->assertSame(1400.0, CancellationPolicy::quote($this->booking(31))['refund']);
    }

    /** The rule the whole policy turns on. */
    public function test_the_deposit_is_never_refunded_at_any_notice_period(): void
    {
        foreach ([90, 31, 30, 14, 13, 1] as $days) {
            $quote = CancellationPolicy::quote($this->booking($days));

            $this->assertLessThanOrEqual($quote['balance'], $quote['refund'], "at {$days} days");
            $this->assertGreaterThanOrEqual($quote['deposit'], $quote['retained'], "at {$days} days");
        }
    }

    /**
     * A booking with no signed terms has no agreed deposit. Falling back to
     * the 30% default would quote a client a figure nobody agreed to.
     */
    public function test_a_booking_with_no_signed_terms_has_no_deposit(): void
    {
        $event = Event::create([
            'title' => 'Unsigned job', 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'published', 'starts_at' => now()->addDays(60),
        ]);
        $booking = Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id, 'supplier_id' => $this->pro->id,
            'created_by' => $this->client->id, 'status' => 'confirmed', 'price' => 900,
        ]);

        $quote = CancellationPolicy::quote($booking);

        $this->assertSame(0.0, $quote['deposit']);
        $this->assertSame(900.0, $quote['refund']);
        $this->assertFalse($quote['has_terms']);
    }

    /**
     * No event date means no notice period, and no notice period falls into
     * the least generous band. Guessing "probably plenty of notice" would
     * refund money against an assumption.
     */
    public function test_a_booking_with_no_date_gets_the_strictest_tier(): void
    {
        $event = Event::create([
            'title' => 'Undated', 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'published',
        ]);
        $booking = Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id, 'supplier_id' => $this->pro->id,
            'created_by' => $this->client->id, 'status' => 'confirmed', 'price' => 900,
        ]);

        $this->assertNull(CancellationPolicy::daysBefore($booking));
        $this->assertSame(0.0, CancellationPolicy::quote($booking)['refund']);
    }

    /** The locked 15–50% band from 2026-07-19c. */
    public function test_the_deposit_band_is_bounded(): void
    {
        $this->assertTrue(CancellationPolicy::depositPercentAllowed(30));
        $this->assertTrue(CancellationPolicy::depositPercentAllowed(15));
        $this->assertTrue(CancellationPolicy::depositPercentAllowed(50));
        $this->assertFalse(CancellationPolicy::depositPercentAllowed(10));
        $this->assertFalse(CancellationPolicy::depositPercentAllowed(60));
    }

    /* ── The client's form ──────────────────────────────────── */

    public function test_the_client_sees_the_refund_before_committing(): void
    {
        $this->booking(60);

        $page = $this->actingAs($this->client)->get(route('cancellations.create'));

        $page->assertOk();
        $page->assertSee('What you would get back', false);
        $page->assertSee('1,400.00', false);
        $page->assertSee('Deposit (not refundable)', false);
    }

    public function test_a_client_cancellation_snapshots_the_quote(): void
    {
        $booking = $this->booking(60);

        $this->actingAs($this->client)->post(route('cancellations.store'), [
            'booking_id' => $booking->id,
            'kind'       => CancellationRequest::CLIENT_CANCELS,
            'reason'     => 'Our venue fell through and the whole event is off.',
            'certified'  => '1',
        ])->assertRedirect();

        $record = CancellationRequest::firstOrFail();

        $this->assertSame('client', $record->raised_role);
        $this->assertSame('1400.00', (string) $record->quoted_refund);
        $this->assertSame('600.00', (string) $record->quoted_deposit);
        $this->assertStringStartsWith('CR-', $record->reference);
    }

    /**
     * The snapshot is the point: moving the event date afterwards must not
     * rewrite the figure the client was actually shown, because that is the
     * number they would dispute.
     */
    public function test_a_later_date_change_does_not_rewrite_the_quoted_refund(): void
    {
        $booking = $this->booking(60);

        $this->actingAs($this->client)->post(route('cancellations.store'), [
            'booking_id' => $booking->id,
            'kind'       => CancellationRequest::CLIENT_CANCELS,
            'reason'     => 'Our venue fell through and the whole event is off.',
            'certified'  => '1',
        ]);

        $booking->event->update(['starts_at' => now()->addDays(3)]);

        $this->assertSame('1400.00', (string) CancellationRequest::firstOrFail()->quoted_refund);
    }

    /** The certification is a statement about money. An unsigned form is not filed. */
    public function test_the_certification_is_required(): void
    {
        $booking = $this->booking(60);

        $this->actingAs($this->client)->post(route('cancellations.store'), [
            'booking_id' => $booking->id,
            'kind'       => CancellationRequest::CLIENT_CANCELS,
            'reason'     => 'Our venue fell through and the whole event is off.',
        ])->assertSessionHasErrors('certified');

        $this->assertDatabaseCount('cancellation_requests', 0);
    }

    /* ── The professional's form — row 155's actual gap ─────── */

    public function test_a_professional_can_report_a_client_no_show(): void
    {
        $booking = $this->booking(1);

        $this->actingAs($this->pro)->post(route('cancellations.store'), [
            'booking_id'     => $booking->id,
            'kind'           => CancellationRequest::CLIENT_NO_SHOW,
            'reason'         => 'Arrived at the venue at 9am as agreed and nobody was there.',
            'occurred_at'    => now()->subHours(4)->format('Y-m-d H:i:s'),
            'waited_minutes' => 90,
            'certified'      => '1',
        ])->assertRedirect();

        $record = CancellationRequest::firstOrFail();

        $this->assertSame('professional', $record->raised_role);
        $this->assertSame(CancellationRequest::CLIENT_NO_SHOW, $record->kind);
        $this->assertSame(90, $record->waited_minutes);
    }

    /**
     * The professional's report carries NO refund figure. The policy covers
     * client cancellations and puts professional-side money out of scope with
     * no spec written — quoting a number would be inventing a refund rule.
     */
    public function test_a_professionals_report_quotes_no_money(): void
    {
        $booking = $this->booking(1);

        $this->actingAs($this->pro)->post(route('cancellations.store'), [
            'booking_id' => $booking->id,
            'kind'       => CancellationRequest::CLIENT_NO_SHOW,
            'reason'     => 'Arrived at the venue at 9am as agreed and nobody was there.',
            'certified'  => '1',
        ]);

        $record = CancellationRequest::firstOrFail();

        $this->assertNull($record->quoted_refund);
        $this->assertFalse($record->hasQuote());

        $this->actingAs($this->pro)
            ->get(route('cancellations.show', $record))
            ->assertDontSee('The refund, as quoted', false);
    }

    /** The professional's form offers no way to cancel the client's booking. */
    public function test_the_professional_form_does_not_offer_the_clients_option(): void
    {
        $this->booking(30);

        $page = $this->actingAs($this->pro)->get(route('cancellations.create'));

        $page->assertOk();
        $page->assertSee('The client did not turn up', false);
        $page->assertDontSee('I need to cancel this booking', false);
        $page->assertDontSee('What you would get back', false);
    }

    /** Neither side may file the other's form under their own name. */
    public function test_a_professional_cannot_file_a_client_cancellation(): void
    {
        $booking = $this->booking(60);

        $this->actingAs($this->pro)->post(route('cancellations.store'), [
            'booking_id' => $booking->id,
            'kind'       => CancellationRequest::CLIENT_CANCELS,
            'reason'     => 'I would like this booking cancelled on the client behalf.',
            'certified'  => '1',
        ])->assertForbidden();
    }

    public function test_a_client_cannot_file_a_no_show_report(): void
    {
        $booking = $this->booking(60);

        $this->actingAs($this->client)->post(route('cancellations.store'), [
            'booking_id' => $booking->id,
            'kind'       => CancellationRequest::CLIENT_NO_SHOW,
            'reason'     => 'Reporting myself as not having turned up to my own event.',
            'certified'  => '1',
        ])->assertForbidden();
    }

    /* ── Scope and access ───────────────────────────────────── */

    /** Per-service (R12) — one booking, never a whole event. */
    public function test_cancelling_one_booking_leaves_the_others_alone(): void
    {
        $first  = $this->booking(60);
        $second = Booking::create([
            'event_id' => $first->event_id, 'client_id' => $this->client->id,
            'supplier_id' => $this->account('professional')->id, 'created_by' => $this->client->id,
            'status' => 'confirmed', 'price' => 800,
        ]);

        $this->actingAs($this->client)->post(route('cancellations.store'), [
            'booking_id' => $first->id,
            'kind'       => CancellationRequest::CLIENT_CANCELS,
            'reason'     => 'We no longer need this particular service for the day.',
            'certified'  => '1',
        ]);

        $this->assertDatabaseCount('cancellation_requests', 1);
        $this->assertSame('confirmed', $second->fresh()->status);
    }

    /** A finished booking is the dispute module's business, not a refund tier's. */
    public function test_a_completed_booking_cannot_be_cancelled(): void
    {
        $booking = $this->booking(-2, ['status' => 'completed']);

        $this->assertFalse(CancellationPolicy::cancellable($booking));

        $this->actingAs($this->client)->post(route('cancellations.store'), [
            'booking_id' => $booking->id,
            'kind'       => CancellationRequest::CLIENT_CANCELS,
            'reason'     => 'The work was done but I was not happy with any of it.',
            'certified'  => '1',
        ])->assertForbidden();
    }

    public function test_a_stranger_cannot_report_on_someone_elses_booking(): void
    {
        $booking  = $this->booking(60);
        $outsider = $this->account('client');

        $this->actingAs($outsider)->post(route('cancellations.store'), [
            'booking_id' => $booking->id,
            'kind'       => CancellationRequest::CLIENT_CANCELS,
            'reason'     => 'This booking has nothing at all to do with my account.',
            'certified'  => '1',
        ])->assertForbidden();
    }

    public function test_both_parties_see_the_record_and_nobody_else_does(): void
    {
        $booking = $this->booking(60);

        $this->actingAs($this->pro)->post(route('cancellations.store'), [
            'booking_id' => $booking->id,
            'kind'       => CancellationRequest::CLIENT_NO_SHOW,
            'reason'     => 'Arrived at the venue at 9am as agreed and nobody was there.',
            'certified'  => '1',
        ]);

        $record = CancellationRequest::firstOrFail();

        $this->actingAs($this->pro)->get(route('cancellations.show', $record))->assertOk();
        $this->actingAs($this->client)->get(route('cancellations.show', $record))->assertOk();
        $this->actingAs($this->account('client'))->get(route('cancellations.show', $record))->assertForbidden();
    }

    /** Withdrawing keeps the record, marked. A pattern of file-and-withdraw is a fact. */
    public function test_withdrawing_keeps_the_record(): void
    {
        $booking = $this->booking(60);

        $this->actingAs($this->pro)->post(route('cancellations.store'), [
            'booking_id' => $booking->id,
            'kind'       => CancellationRequest::CLIENT_NO_SHOW,
            'reason'     => 'Arrived at the venue at 9am as agreed and nobody was there.',
            'certified'  => '1',
        ]);

        $record = CancellationRequest::firstOrFail();

        $this->actingAs($this->pro)->post(route('cancellations.withdraw', $record))->assertRedirect();

        $this->assertSame('withdrawn', $record->fresh()->status);
        $this->assertDatabaseHas('cancellation_requests', ['id' => $record->id]);
    }

    public function test_the_other_party_cannot_withdraw_your_report(): void
    {
        $booking = $this->booking(60);

        $this->actingAs($this->pro)->post(route('cancellations.store'), [
            'booking_id' => $booking->id,
            'kind'       => CancellationRequest::CLIENT_NO_SHOW,
            'reason'     => 'Arrived at the venue at 9am as agreed and nobody was there.',
            'certified'  => '1',
        ]);

        $record = CancellationRequest::firstOrFail();

        $this->actingAs($this->client)->post(route('cancellations.withdraw', $record))->assertForbidden();
    }
}
