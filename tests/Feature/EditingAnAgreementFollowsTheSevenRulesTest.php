<?php

namespace Tests\Feature;

use App\Domain\Agreements\EditAgreement;
use App\Domain\Agreements\Workspace;
use App\Models\{AgreementRevision, Bid, Event, Finalization, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Sir Peter's seven rules for editing an agreement.
 *
 * His decision, 11 October: "Replace the existing Request Changes button with
 * Edit Agreement. Do not keep both buttons, because they create overlapping
 * actions and unnecessary confusion." Then seven rules, and rule 7 arrived
 * separately the same evening.
 *
 * Two of them were already true before any of this was written, because a
 * signature here has always been a signature OF A VERSION. Move the terms and
 * the version moves, and the signature simply stops counting. The tests for
 * those are kept here anyway: they are the rules, and they should fail loudly
 * if that model is ever loosened.
 */
class EditingAnAgreementFollowsTheSevenRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;
    private Finalization $fin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client', 'name' => 'Dana Whitfield']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = User::findOrFail($this->client->id);

        $this->pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'Mike T.']);
        $this->pro->assignRole('professional');

        $event = Event::create([
            'title' => 'Johnson Wedding', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'starts_at' => now()->addDays(30), 'city' => 'Baltimore', 'state' => 'MD',
        ]);

        $bid = Bid::create([
            'event_id' => $event->id, 'supplier_id' => $this->pro->id,
            'amount' => 1800, 'status' => 'submitted',
        ]);

        $this->fin = Finalization::create([
            'event_id' => $event->id, 'bid_id' => $bid->id,
            'client_id' => $this->client->id, 'supplier_id' => $this->pro->id,
            'agreed_price' => 1800, 'scope' => 'DJ for the reception',
        ]);
    }

    private function bothSign(): void
    {
        $v = Workspace::version($this->fin);

        $this->fin->forceFill([
            'client_approved_version'   => $v,
            'supplier_approved_version' => $v,
            'client_signed_at'          => now(),
            'client_signed_version'     => $v,
            'supplier_signed_at'        => now(),
            'supplier_signed_version'   => $v,
        ])->save();

        $this->fin->refresh();
    }

    // ── Rule 1 ───────────────────────────────────────────────

    /** Every submitted change records who, when, and what moved. */
    public function test_one_a_change_records_who_what_and_when(): void
    {
        $changed = EditAgreement::submit(
            $this->fin,
            $this->client,
            ['agreed_price' => 2100, 'scope' => 'DJ and lighting'],
            'Added the lighting',
        );

        $this->assertArrayHasKey('agreed_price', $changed);
        $this->assertSame('1800.00', (string) $changed['agreed_price']['from']);
        $this->assertSame(2100, $changed['agreed_price']['to']);

        $rev = AgreementRevision::where('finalization_id', $this->fin->id)->first();

        $this->assertSame($this->client->id, $rev->created_by);
        $this->assertSame('Added the lighting', $rev->summary);
        $this->assertNotNull($rev->created_at);
        $this->assertCount(2, $rev->changed);
    }

    /** Changing nothing is not a change. */
    public function test_one_sending_the_same_terms_changes_nothing(): void
    {
        $changed = EditAgreement::submit($this->fin, $this->client, [
            'agreed_price' => 1800,            // the same, typed differently
            'scope'        => 'DJ for the reception',
        ]);

        $this->assertSame([], $changed);
        $this->assertSame(1, Workspace::version($this->fin->fresh()));
    }

    // ── Rule 2 ───────────────────────────────────────────────

    /**
     * A submitted change invalidates a signature already given.
     *
     * Not by deleting it. The signature records a version, and the version has
     * moved, so it stops counting and both must sign again.
     */
    public function test_two_submitting_invalidates_a_signature(): void
    {
        $this->bothSign();
        $this->assertTrue(Workspace::clientSigned($this->fin));

        EditAgreement::submit($this->fin, $this->client, ['scope' => 'DJ, lighting and MC']);
        $this->fin->refresh();

        $this->assertFalse(Workspace::clientSigned($this->fin));
        $this->assertFalse(Workspace::supplierSigned($this->fin));
        $this->assertNotNull($this->fin->client_signed_at, 'The record of having signed is kept.');
    }

    // ── Rule 3 ───────────────────────────────────────────────

    /** Once both have signed, a further change is an amendment. */
    public function test_three_a_change_after_both_signed_is_an_amendment(): void
    {
        $this->assertFalse(EditAgreement::isAmendment($this->fin));

        $this->bothSign();

        $this->assertTrue(EditAgreement::isAmendment($this->fin));

        EditAgreement::submit($this->fin, $this->client, ['scope' => 'DJ, lighting and MC']);

        // The signed version is kept whole, signatures and all.
        $rev = AgreementRevision::where('finalization_id', $this->fin->id)->where('version', 1)->first();

        $this->assertNotNull($rev->client_signed_at);
        $this->assertNotNull($rev->supplier_signed_at);
        $this->assertSame('DJ for the reception', $rev->terms['scope']);
    }

    // ── Rule 4 ───────────────────────────────────────────────

    /** One side's change is not agreement; signing waits for the other. */
    public function test_four_the_other_side_must_approve_before_signing_opens(): void
    {
        $this->fin->forceFill([
            'client_approved_version'   => 1,
            'supplier_approved_version' => 1,
        ])->save();

        $this->assertTrue(Workspace::signingOpen($this->fin->fresh()));

        EditAgreement::submit($this->fin, $this->client, ['agreed_price' => 2400]);

        $this->assertFalse(
            Workspace::signingOpen($this->fin->fresh()),
            'A change by one side must not carry the other side\'s approval forward.',
        );
    }

    // ── Rule 5 ───────────────────────────────────────────────

    /** Versions accumulate; none is overwritten. */
    public function test_five_every_version_is_kept(): void
    {
        EditAgreement::submit($this->fin, $this->client, ['agreed_price' => 2000], 'First pass');
        EditAgreement::submit($this->fin->fresh(), $this->pro, ['agreed_price' => 2200], 'Second pass');

        $history = EditAgreement::history($this->fin->fresh());

        $this->assertCount(2, $history);
        $this->assertSame([2, 1], $history->pluck('version')->all(), 'Newest first.');
        $this->assertSame('1800.00', (string) $history->last()->terms['agreed_price']);
        $this->assertSame('2000.00', (string) $history->first()->terms['agreed_price']);
    }

    // ── Rule 6 ───────────────────────────────────────────────

    /**
     * Money cannot move after funding.
     *
     * His rule asks for reconciliation and authorisation. There is no
     * reconciliation in this system, so the change is refused and the screen
     * says to settle it between themselves first, rather than taking the edit
     * and leaving the money wrong.
     */
    public function test_six_a_financial_change_after_funding_is_refused(): void
    {
        $this->fin->forceFill(['funded_at' => now()])->save();

        try {
            EditAgreement::submit($this->fin->fresh(), $this->client, ['agreed_price' => 2400]);
            $this->fail('A price change after funding should be refused.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('deposit has already been paid', $e->getMessage());
        }

        $this->assertSame(1, Workspace::version($this->fin->fresh()));
    }

    /** A change that does not touch money still goes through. */
    public function test_six_a_non_financial_change_after_funding_is_allowed(): void
    {
        $this->fin->forceFill(['funded_at' => now()])->save();

        EditAgreement::submit($this->fin->fresh(), $this->client, ['schedule_notes' => 'Load in from the rear door']);

        $this->assertSame(2, Workspace::version($this->fin->fresh()));
    }

    // ── Rule 7 ───────────────────────────────────────────────

    /**
     * Unsent edits are not edits.
     *
     * The whole point of a draft: a signature stands until somebody actually
     * sends something.
     */
    public function test_seven_an_unsent_draft_leaves_signatures_alone(): void
    {
        $this->bothSign();

        EditAgreement::saveDraft($this->fin, $this->client, ['agreed_price' => 9999]);
        $this->fin->refresh();

        $this->assertSame(1, Workspace::version($this->fin), 'A draft must not move the version.');
        $this->assertTrue(Workspace::clientSigned($this->fin));
        $this->assertTrue(Workspace::supplierSigned($this->fin));
        $this->assertSame('1800.00', (string) $this->fin->agreed_price, 'The live terms are untouched.');
        $this->assertSame(9999, $this->fin->draft_terms['agreed_price']);
    }

    /** And sending is what finally moves it. */
    public function test_seven_submitting_clears_the_draft_and_moves_the_version(): void
    {
        EditAgreement::saveDraft($this->fin, $this->client, ['agreed_price' => 2500]);
        EditAgreement::submit($this->fin->fresh(), $this->client, ['agreed_price' => 2500]);

        $this->fin->refresh();

        $this->assertNull($this->fin->draft_terms);
        $this->assertSame(2, Workspace::version($this->fin));
        $this->assertSame('2500.00', (string) $this->fin->agreed_price);
    }

    /** A draft can be thrown away without touching anything. */
    public function test_seven_a_draft_can_be_discarded(): void
    {
        EditAgreement::saveDraft($this->fin, $this->client, ['agreed_price' => 2500]);
        EditAgreement::discardDraft($this->fin->fresh());

        $this->fin->refresh();

        $this->assertNull($this->fin->draft_terms);
        $this->assertSame('1800.00', (string) $this->fin->agreed_price);
    }

    // ── The page ─────────────────────────────────────────────

    /** One button, not two, exactly as he asked. */
    public function test_the_page_offers_edit_agreement_and_not_request_changes(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.finalize.overview', $this->fin))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('Edit Agreement', $html);
        $this->assertStringNotContainsString('Request changes', $html);
        $this->assertStringNotContainsString('Propose changes', $html);
    }

    /**
     * The post reaches its controller.
     *
     * It sits above the /finalize/{finalization}/{step} wildcard, which would
     * otherwise read "edit-agreement" as the name of a step and 404. The
     * overview GET carries the same note for the same reason.
     */
    public function test_the_edit_post_is_not_swallowed_by_the_step_wildcard(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.finalize.edit', $this->fin), [
                'intent' => 'draft',
                'terms'  => ['scope' => 'Something else'],
            ])
            ->assertRedirect();

        $this->assertNotNull($this->fin->fresh()->draft_terms);
    }

    /** Somebody else's agreement is not yours to edit. */
    public function test_only_the_client_on_the_agreement_can_edit_it(): void
    {
        $stranger = User::factory()->create(['primary_role' => 'client']);
        $stranger->assignRole('client');

        $this->actingAs($stranger)
            ->post(route('client.finalize.edit', $this->fin), [
                'intent' => 'draft',
                'terms'  => ['scope' => 'Mine now'],
            ])
            ->assertForbidden();
    }
}
