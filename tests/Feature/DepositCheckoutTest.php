<?php

namespace Tests\Feature;

use App\Domain\Payments\DepositCheckout;
use App\Models\Finalization;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Paying the booking deposit on Stripe's own page.
 *
 * Ali asked for real card entry so the payment journey can be tested. The card
 * fields are Stripe's rather than ours on purpose: a card number typed into a
 * form on this server becomes our problem the moment it arrives — PCI scope,
 * logs, error reports, backups — and a field added "just for testing" is the
 * field still there at launch.
 *
 * Nothing in these tests reaches the network. What is asserted is the part
 * that is ours: which route is taken, and what is refused.
 */
class DepositCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
        ]);

        $this->client = User::findOrFail($u->id);
    }

    /**
     * Written through the model's own setter, not with updateOrCreate.
     * Setting::get caches for an hour, and a row written round the back is a
     * row the code never sees — the first version of this helper left every
     * key looking unset.
     */
    private function key(?string $value): void
    {
        Setting::set('payment.stripe_secret_key', $value);
        Setting::set('payment.mode', 'test');
    }

    private function finalization(): Finalization
    {
        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');

        $event = \App\Models\Event::create([
            'title' => 'A booking', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'open',
        ]);

        return Finalization::create([
            'event_id' => $event->id,
            'client_id' => $this->client->id,
            'supplier_id' => $pro->id,
            'status' => 'in_progress',
            'deposit_amount' => 500,
            'total_amount' => 2000,
        ]);
    }

    /* ── Which route is taken ───────────────────────────────── */

    public function test_with_no_keys_the_existing_test_mode_is_untouched(): void
    {
        $this->key(null);

        $this->assertFalse(DepositCheckout::isConfigured());

        $f = $this->finalization();

        $this->actingAs($this->client)
            ->post(route('client.finalize.save', [$f, 'payment']), ['confirm_payment' => 1])
            ->assertRedirect(route('client.finalize.step', [$f, 'payment']));

        // Recorded as a test payment, exactly as before — no card, no charge.
        $deposit = Payment::where('user_id', $this->client->id)->firstOrFail();

        $this->assertSame('test', $deposit->gateway);
        $this->assertEqualsWithDelta(500.0, (float) $deposit->amount, 0.01);
    }

    /**
     * A live secret key must not be usable before launch, whatever the mode
     * says. PaymentGuard is the choke point and this proves the deposit route
     * goes through it rather than round it.
     */
    public function test_a_live_key_is_refused_before_launch(): void
    {
        $this->key('sk_live_pretend');

        config(['payments.go_live' => false]);

        $this->expectException(\App\Domain\Payments\Exceptions\PaymentsNotLiveException::class);

        DepositCheckout::begin($this->finalization(), 'https://example.test/ok', 'https://example.test/no');
    }

    /* ── The return leg ─────────────────────────────────────── */

    /**
     * The success address is somewhere a browser lands, so it proves nothing
     * on its own. Opened without a session it must write nothing at all —
     * otherwise a client could mark their own booking paid by visiting a URL.
     */
    public function test_returning_without_a_session_pays_for_nothing(): void
    {
        $this->key('sk_test_pretend');

        $f = $this->finalization();

        $this->actingAs($this->client)
            ->get(route('client.finalize.paid', $f))
            ->assertRedirect(route('client.finalize.step', [$f, 'payment']));

        $this->assertSame(0, Payment::count());
    }

    /** An unverifiable session is the same answer: nothing written. */
    public function test_an_unknown_session_pays_for_nothing(): void
    {
        $this->key('sk_test_pretend');

        $f = $this->finalization();

        $this->actingAs($this->client)
            ->get(route('client.finalize.paid', $f).'?session_id=cs_test_madeup')
            ->assertRedirect(route('client.finalize.step', [$f, 'payment']));

        $this->assertSame(0, Payment::count());
    }

    /** And it is not a door into somebody else's booking. */
    public function test_another_client_cannot_use_the_return_address(): void
    {
        $this->key('sk_test_pretend');

        $f = $this->finalization();

        $stranger = User::factory()->create(['primary_role' => 'client']);
        $stranger->assignRole('client');

        $status = $this->actingAs(User::findOrFail($stranger->id))
            ->get(route('client.finalize.paid', $f).'?session_id=cs_test_madeup')
            ->getStatusCode();

        $this->assertContains($status, [403, 404, 302]);
        $this->assertSame(0, Payment::count());
    }

    /* ── What gets recorded ─────────────────────────────────── */

    /**
     * The deposit and the request fee stay separate rows. The deposit belongs
     * to the professional and the fee to GigResource, and the Bookings page
     * reads deposits to work out what a client still owes — folded together,
     * every deposit reads $2.99 too high.
     */
    public function test_the_fee_is_its_own_row_not_added_to_the_deposit(): void
    {
        $this->key(null);
        config(['payments.client_request_fee' => 2.99]);

        $f = $this->finalization();

        $this->actingAs($this->client)
            ->post(route('client.finalize.save', [$f, 'payment']), ['confirm_payment' => 1]);

        $kinds = Payment::all()->mapWithKeys(fn ($p) => [$p->metadata['kind'] => (float) $p->amount]);

        $this->assertEqualsWithDelta(500.0, $kinds['booking_deposit'], 0.01);
        $this->assertEqualsWithDelta(2.99, $kinds['client_request_fee'], 0.01);
    }
}
