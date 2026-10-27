<?php

namespace Tests\Feature;

use App\Domain\Agreements\{EditAgreement, Workspace};
use App\Models\{Category, Event, Finalization, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Nobody pays before both names are on the agreement.
 *
 * Sir Peter, 27 October, asked whether a client may pay the deposit before the
 * professional has signed: "I believe no the client should never paid them
 * directly before the agreement."
 *
 * The gate for that was already in Finalization::STEPS, where the payment step
 * waits on the contract step. What defeated it was the client's own signing,
 * which stamped supplier_signature, supplier_signed_at, supplier_signed_version
 * AND supplier_approved_version in the professional's name. The comment
 * explaining it was honest: the professional had no way to sign.
 *
 * They have one now, and taking the stand-in out exposed a second hole the
 * stand-in had been hiding. The contract step was read from supplier_signed_at
 * alone, which was safe only while both signatures arrived in the same save.
 * Once they arrive separately, a professional signing first opens the deposit
 * step on an agreement the CLIENT has not signed.
 *
 * Removing the stand-in broke no existing test, which is the other thing worth
 * recording: the deposit gate had never been covered.
 */
class TheAgreementNeedsBothSignaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $professional;
    private Finalization $agreement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Notification::fake();

        $this->client       = $this->person('client');
        $this->professional = $this->person('professional');

        $service = Category::firstOrCreate(
            ['slug' => 'wedding-dj'],
            ['name' => 'Wedding DJ', 'kind' => Category::SERVICE, 'is_active' => true],
        );

        $event = Event::create([
            'title'      => 'Rivera Wedding',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(40),
        ]);

        // Every step before the contract already settled, so these tests are
        // about the signatures and not about the wizard.
        $this->agreement = Finalization::create([
            'event_id'           => $event->id,
            'category_id'        => $service->id,
            'client_id'          => $this->client->id,
            'supplier_id'        => $this->professional->id,
            'terms_version'      => 1,
            'agreed_price'       => 4000,
            'deposit_percent'    => 25,
            'deposit_amount'     => 1000,
            'scope'              => 'Six hours of music.',
            'payment_terms'      => 'Balance before the date.',
            'service_start'      => now()->addDays(40)->setTime(21, 0),
            'service_end'        => now()->addDays(40)->setTime(23, 0),
            'bid_reviewed_at'    => now(),
            'scope_agreed_at'    => now(),
            'price_agreed_at'    => now(),
            'schedule_agreed_at' => now(),
            'terms_agreed_at'    => now(),
        ]);
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

    private function bothApprove(): void
    {
        Workspace::approve($this->agreement, $this->professional);
        Workspace::approve($this->agreement->fresh(), $this->client);
    }

    private function clientSigns(): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->client)->post(
            route('client.finalize.save', [$this->agreement, 'contract']),
            ['client_signature' => $this->client->name, 'agree' => '1'],
        );
    }

    private function professionalSigns(): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->professional)->post(
            route('professional.agreements.sign', $this->agreement),
            ['supplier_signature' => $this->professional->name, 'agree' => '1'],
        );
    }

    /** Whether the client's deposit step will open. */
    private function depositIsOpen(): bool
    {
        $response = $this->actingAs($this->client)
            ->get(route('client.finalize.step', [$this->agreement->fresh(), 'payment']));

        // Closed steps bounce the client back to the step they owe.
        return $response->baseResponse->getStatusCode() === 200;
    }

    // ── The client's signing stands for the client only ───────

    public function test_the_clients_signature_no_longer_signs_for_the_professional(): void
    {
        $this->bothApprove();

        $this->clientSigns();

        $f = $this->agreement->fresh();

        $this->assertNotNull($f->client_signed_at, 'The client did not get signed in at all.');
        $this->assertNull($f->supplier_signed_at, "The client's signing still stamps the professional.");
        $this->assertNull($f->supplier_signature);
    }

    /** Nor does it approve on their behalf. */
    public function test_the_clients_signing_does_not_approve_for_the_professional(): void
    {
        Workspace::approve($this->agreement, $this->professional);
        Workspace::approve($this->agreement->fresh(), $this->client);

        // Start again with neither approval, to be sure the signing itself is
        // not what put them there.
        $this->agreement->forceFill([
            'client_approved_version'   => null,
            'supplier_approved_version' => null,
        ])->save();

        $this->bothApprove();

        $this->clientSigns();

        $this->assertSame(
            1,
            (int) $this->agreement->fresh()->supplier_approved_version,
            'This should be the approval the professional gave, not one stamped for them.',
        );
    }

    // ── Peter's rule ─────────────────────────────────────────

    public function test_the_deposit_does_not_open_on_the_clients_signature_alone(): void
    {
        $this->bothApprove();
        $this->clientSigns();

        $this->assertFalse(
            $this->depositIsOpen(),
            'The client can pay before the professional has signed.',
        );
    }

    /**
     * And not on the professional's alone either.
     *
     * This is the hole the stand-in was hiding. The contract step was read
     * from supplier_signed_at, so a professional signing first marked it done
     * and opened the deposit on an agreement the client had not signed.
     */
    public function test_the_deposit_does_not_open_on_the_professionals_signature_alone(): void
    {
        $this->bothApprove();
        $this->professionalSigns();

        $this->assertNotNull($this->agreement->fresh()->supplier_signed_at);

        $this->assertFalse(
            $this->depositIsOpen(),
            'The client can pay an agreement they never signed.',
        );
    }

    public function test_the_deposit_opens_once_both_have_signed(): void
    {
        $this->bothApprove();
        $this->clientSigns();
        $this->professionalSigns();

        $this->assertTrue(Workspace::bothSigned($this->agreement->fresh()));
        $this->assertTrue($this->depositIsOpen(), 'Both have signed and the deposit is still shut.');
    }

    /** In either order. */
    public function test_the_order_the_two_sign_in_does_not_matter(): void
    {
        $this->bothApprove();
        $this->professionalSigns();
        $this->clientSigns();

        $this->assertTrue($this->depositIsOpen());
    }

    // ── The pen opens only on settled terms ──────────────────

    public function test_the_professional_cannot_sign_before_both_approved(): void
    {
        Workspace::approve($this->agreement, $this->professional);

        $this->professionalSigns()->assertStatus(409);

        $this->assertNull($this->agreement->fresh()->supplier_signed_at);
    }

    public function test_a_stranger_cannot_sign(): void
    {
        $this->bothApprove();

        $this->actingAs($this->person('professional'))
            ->post(route('professional.agreements.sign', $this->agreement), [
                'supplier_signature' => 'Somebody Else', 'agree' => '1',
            ])
            ->assertForbidden();

        $this->assertNull($this->agreement->fresh()->supplier_signed_at);
    }

    public function test_signing_needs_a_name_and_a_tick(): void
    {
        $this->bothApprove();

        $this->actingAs($this->professional)
            ->post(route('professional.agreements.sign', $this->agreement), ['agree' => '1'])
            ->assertSessionHasErrors('supplier_signature');

        $this->actingAs($this->professional)
            ->post(route('professional.agreements.sign', $this->agreement), ['supplier_signature' => 'A Name'])
            ->assertSessionHasErrors('agree');

        $this->assertNull($this->agreement->fresh()->supplier_signed_at);
    }

    /**
     * The version signed is recorded, and a later edit reopens the contract.
     *
     * "A signature must never silently carry forward to terms the person did
     * not sign" (Sir Peter, 27 September). bothSigned is version-aware, so
     * the deposit shuts again when the terms move under it.
     */
    public function test_an_edit_after_signing_shuts_the_deposit_again(): void
    {
        $this->bothApprove();
        $this->clientSigns();
        $this->professionalSigns();

        $this->assertTrue($this->depositIsOpen());

        EditAgreement::submit(
            $this->agreement->fresh(),
            $this->client,
            ['agreed_price' => 3200] + EditAgreement::current($this->agreement->fresh()),
            'Lower budget.',
        );

        $this->assertFalse(
            Workspace::bothSigned($this->agreement->fresh()),
            'Signatures carried forward onto terms nobody signed.',
        );
        $this->assertFalse($this->depositIsOpen(), 'The deposit is open on terms neither of them signed.');
    }

    // ── What each side is told while it waits ────────────────

    public function test_the_client_is_told_it_waits_on_the_professional(): void
    {
        $this->bothApprove();
        $this->clientSigns();

        $this->actingAs($this->client)
            ->get(route('client.finalize.step', [$this->agreement->fresh(), 'contract']))
            ->assertSuccessful()
            ->assertSee('It now waits on', false)
            ->assertDontSee('counter-signed');
    }

    public function test_the_professional_is_offered_the_pen_once_both_approved(): void
    {
        $this->bothApprove();

        $this->actingAs($this->professional)
            ->get(route('professional.agreements.show', $this->agreement->fresh()))
            ->assertSuccessful()
            ->assertSee('Sign as version 1', false);
    }

    public function test_the_professional_sees_their_own_signature_afterwards(): void
    {
        $this->bothApprove();
        $this->professionalSigns();

        $this->actingAs($this->professional)
            ->get(route('professional.agreements.show', $this->agreement->fresh()))
            ->assertSuccessful()
            ->assertSee('You signed on', false)
            ->assertSee('waits on', false);
    }

    // ── Counter-proposing (Wednesday) ────────────────────────

    /**
     * A professional answering a change request can say what they want.
     *
     * Asking for "something different" without saying what asks the client to
     * guess, so this is the same edit the client makes, through the same
     * EditAgreement::submit.
     */
    public function test_the_professional_can_send_terms_back(): void
    {
        $this->bothApprove();

        $this->actingAs($this->professional)
            ->post(route('professional.agreements.request-changes', $this->agreement), [
                'terms'   => ['agreed_price' => 4600] + EditAgreement::current($this->agreement),
                'summary' => 'The date is a Saturday in peak season.',
            ])
            ->assertRedirect(route('professional.agreements.show', $this->agreement));

        $f = $this->agreement->fresh();

        $this->assertSame('4600.00', (string) $f->agreed_price);
        $this->assertSame(2, Workspace::version($f));
        $this->assertSame('The date is a Saturday in peak season.', $f->change_request);
    }

    /** And it costs them their own approval, because the terms moved. */
    public function test_sending_terms_back_clears_both_approvals(): void
    {
        $this->bothApprove();

        $this->actingAs($this->professional)->post(
            route('professional.agreements.request-changes', $this->agreement),
            [
                'terms'   => ['agreed_price' => 4600] + EditAgreement::current($this->agreement),
                'summary' => 'Peak season.',
            ],
        );

        $f = $this->agreement->fresh();

        $this->assertFalse(Workspace::supplierApproved($f));
        $this->assertFalse(Workspace::clientApproved($f));
        $this->assertFalse(Workspace::signingOpen($f), 'The pen is out on terms nobody has approved.');
    }

    public function test_sending_terms_back_needs_a_reason(): void
    {
        $this->actingAs($this->professional)
            ->post(route('professional.agreements.request-changes', $this->agreement), [
                'terms' => ['agreed_price' => 4600] + EditAgreement::current($this->agreement),
            ])
            ->assertSessionHasErrors('summary');
    }

    public function test_a_stranger_cannot_send_terms_back(): void
    {
        $this->actingAs($this->person('professional'))
            ->post(route('professional.agreements.request-changes', $this->agreement), [
                'terms' => ['agreed_price' => 1] + EditAgreement::current($this->agreement),
                'summary' => 'Mine now.',
            ])
            ->assertForbidden();

        $this->assertSame('4000.00', (string) $this->agreement->fresh()->agreed_price);
    }

    /** Sending back terms that are no different changes nothing. */
    public function test_sending_back_the_same_terms_changes_nothing(): void
    {
        $this->bothApprove();

        $this->actingAs($this->professional)->post(
            route('professional.agreements.request-changes', $this->agreement),
            ['terms' => EditAgreement::current($this->agreement), 'summary' => 'No change.'],
        );

        $f = $this->agreement->fresh();

        $this->assertSame(1, Workspace::version($f));
        $this->assertTrue(Workspace::supplierApproved($f), 'An edit that changed nothing threw away an approval.');
    }
}
