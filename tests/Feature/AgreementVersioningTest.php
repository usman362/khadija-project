<?php

namespace Tests\Feature;

use App\Domain\Agreements\Workspace;
use App\Models\Event;
use App\Models\Finalization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Agreement Workspace document, section 7, headed "Critical rule":
 *
 *   "material changes must invalidate prior approvals/signatures for the
 *    changed agreement version... A signature must never silently carry
 *    forward to terms the person did not sign."
 *
 * This is the whole of that rule. Approval is approval OF A VERSION, so the
 * honest question is never "did they approve?" but "approved what?". Change
 * the price after somebody approved and their approval stops counting,
 * because it was approval of a different agreement.
 */
class AgreementVersioningTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->create(['primary_role' => 'client', 'name' => 'Dana Whitfield']);
        $this->pro    = User::factory()->create(['primary_role' => 'professional', 'name' => 'Priya Raghavan']);
    }

    private function agreement(array $attributes = []): Finalization
    {
        $event = Event::create([
            'title'      => 'Johnson Wedding',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(20),
        ]);

        return Finalization::create(array_merge([
            'event_id'     => $event->id,
            'client_id'    => $this->client->id,
            'supplier_id'  => $this->pro->id,
            'agreed_price' => 1200,
            'scope'        => 'A four hour set, lighting included, load in from 3pm.',
        ], $attributes));
    }

    /** Nothing is settled until both sides settled the same thing. */
    public function test_one_sided_approval_is_not_approval(): void
    {
        $f = $this->agreement();

        Workspace::approve($f, $this->client);

        $this->assertTrue(Workspace::clientApproved($f));
        $this->assertFalse(Workspace::bothApproved($f));
        $this->assertSame(Workspace::NEGOTIATING, Workspace::status($f));
    }

    /** Both, on the same version, and the pen comes out. */
    public function test_both_approving_the_same_version_opens_signing(): void
    {
        $f = $this->agreement();

        $this->assertFalse(Workspace::signingOpen($f), 'Nothing is settled, so there is nothing to sign.');

        Workspace::approve($f, $this->client);
        Workspace::approve($f, $this->pro);

        $this->assertTrue(Workspace::signingOpen($f));
        $this->assertSame(Workspace::AWAITING, Workspace::status($f));
    }

    /** The rule itself: a changed price takes both approvals with it. */
    public function test_changing_a_material_term_undoes_both_approvals(): void
    {
        $f = $this->agreement();

        Workspace::approve($f, $this->client);
        Workspace::approve($f, $this->pro);
        $this->assertTrue(Workspace::bothApproved($f));

        $f->update(['agreed_price' => 1500]);
        Workspace::termsChanged($f, ['agreed_price']);

        $this->assertFalse(Workspace::bothApproved($f->fresh()),
            'A price change left the old approvals standing under a new price.');
        $this->assertFalse(Workspace::signingOpen($f->fresh()));
        $this->assertSame(Workspace::NEGOTIATING, Workspace::status($f->fresh()));
    }

    /** A signature does not carry forward to terms nobody showed the signer. */
    public function test_a_signature_does_not_survive_a_changed_price(): void
    {
        $f = $this->agreement();

        Workspace::approve($f, $this->client);
        Workspace::approve($f, $this->pro);

        $f->update([
            'client_signature'        => 'Dana Whitfield',
            'client_signed_at'        => now(),
            'client_signed_version'   => Workspace::version($f),
            'supplier_signature'      => 'Priya Raghavan',
            'supplier_signed_at'      => now(),
            'supplier_signed_version' => Workspace::version($f),
        ]);

        $this->assertTrue(Workspace::bothSigned($f->fresh()));

        $f = $f->fresh();
        $f->update(['agreed_price' => 4000]);
        Workspace::termsChanged($f, ['agreed_price']);

        $f = $f->fresh();

        $this->assertFalse(Workspace::bothSigned($f),
            'The signatures now sit under a price neither party signed.');
        $this->assertNotNull($f->client_signed_at,
            'The record of who signed and when is kept; it simply stops counting.');
        $this->assertSame(Workspace::NEGOTIATING, Workspace::status($f));
    }

    /** A note is not a term. Changing one costs nobody their approval. */
    public function test_an_immaterial_change_leaves_approvals_alone(): void
    {
        $f = $this->agreement();

        Workspace::approve($f, $this->client);
        Workspace::approve($f, $this->pro);

        $f->update(['schedule_notes' => 'Park round the back.']);
        Workspace::termsChanged($f, ['schedule_notes']);

        $this->assertTrue(Workspace::bothApproved($f->fresh()));
    }

    /** Sending it back to be negotiated does the same, with a reason attached. */
    public function test_proposing_changes_reopens_the_negotiation(): void
    {
        $f = $this->agreement();

        Workspace::approve($f, $this->client);
        Workspace::approve($f, $this->pro);

        Workspace::proposeChanges($f, $this->client, 'Can we start at 4pm instead?');

        $f = $f->fresh();

        $this->assertFalse(Workspace::bothApproved($f));
        $this->assertSame('Can we start at 4pm instead?', $f->change_request);
        $this->assertSame($this->client->id, $f->change_requested_by);
        $this->assertSame(Workspace::NEGOTIATING, Workspace::status($f));
    }

    /** Declining ends it, whoever approved what. */
    public function test_declining_ends_the_agreement(): void
    {
        $f = $this->agreement();

        Workspace::approve($f, $this->client);
        Workspace::approve($f, $this->pro);
        Workspace::decline($f, $this->pro);

        $this->assertSame(Workspace::DECLINED, Workspace::status($f->fresh()));
        $this->assertFalse(Workspace::signingOpen($f->fresh()));
    }

    /** A funded agreement is booked, and is not reopened by a late edit. */
    public function test_a_funded_agreement_is_booked(): void
    {
        $f = $this->agreement(['funded_at' => now()]);

        $this->assertSame(Workspace::BOOKED, Workspace::status($f));

        Workspace::proposeChanges($f, $this->client, 'Actually, about the price');

        $this->assertSame(Workspace::BOOKED, Workspace::status($f->fresh()),
            'A booked agreement is not sent back to negotiation by a message.');
    }

    /** Somebody outside the agreement is on neither side of it. */
    public function test_a_stranger_has_no_side(): void
    {
        $f = $this->agreement();

        $this->assertSame('client', Workspace::sideOf($f, $this->client));
        $this->assertSame('supplier', Workspace::sideOf($f, $this->pro));
        $this->assertNull(Workspace::sideOf($f, User::factory()->create()));
    }
}
