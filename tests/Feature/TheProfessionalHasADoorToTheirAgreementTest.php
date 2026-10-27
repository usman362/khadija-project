<?php

namespace Tests\Feature;

use App\Domain\Agreements\Workspace;
use App\Models\{Category, Event, Finalization, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The professional can reach the agreement they are named in.
 *
 * Week of 26 October, day one: the routes and the authorisation.
 *
 * Until now the professional had no address for an agreement at all. The
 * client has had /client/finalize since the wizard was built, and the schema
 * under it was written for both sides from the start: supplier_approved_version,
 * supplier_signature, supplier_signed_at and supplier_signed_version are all
 * columns that exist and are all unused, and Workspace already answered
 * supplierApproved(), supplierSigned() and sideOf(). What was missing was the
 * door, which is why the client's own signing stamps a counter-signature on
 * the professional's behalf.
 *
 * This is Finalization, not the AI agreements at /app/agreements. Those are a
 * separate model the professional could already reach, and a document rather
 * than the contract being negotiated.
 */
class TheProfessionalHasADoorToTheirAgreementTest extends TestCase
{
    use RefreshDatabase;

    private User $professional;
    private User $client;
    private Finalization $agreement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client       = $this->person('client');
        $this->professional = $this->person('professional');

        $this->agreement = $this->agreementBetween($this->client, $this->professional, 'Rivera Wedding');
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

    private function agreementBetween(User $client, User $supplier, string $title): Finalization
    {
        $service = Category::firstOrCreate(
            ['slug' => 'wedding-dj'],
            ['name' => 'Wedding DJ', 'kind' => Category::SERVICE, 'is_active' => true],
        );

        $event = Event::create([
            'title'      => $title,
            'client_id'  => $client->id,
            'created_by' => $client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(40),
        ]);

        return Finalization::create([
            'event_id'      => $event->id,
            'category_id'   => $service->id,
            'client_id'     => $client->id,
            'supplier_id'   => $supplier->id,
            'terms_version' => 1,
            'agreed_price'  => 4000,
            'scope'         => 'Six hours of music and the ceremony sound.',
        ]);
    }

    // ── The door exists ──────────────────────────────────────

    public function test_the_professional_can_open_their_agreements(): void
    {
        $this->actingAs($this->professional)
            ->get(route('professional.agreements.index'))
            ->assertSuccessful()
            ->assertSee('Rivera Wedding', false);
    }

    public function test_the_professional_can_read_the_agreement_itself(): void
    {
        $this->actingAs($this->professional)
            ->get(route('professional.agreements.show', $this->agreement))
            ->assertSuccessful()
            ->assertSee('Six hours of music', false)
            ->assertSee('4,000', false);
    }

    /** And it is their own portal, not the admin dashboard. */
    public function test_the_agreement_is_drawn_in_the_professionals_own_portal(): void
    {
        $html = $this->actingAs($this->professional)
            ->get(route('professional.agreements.show', $this->agreement))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('Gig Operations Hub', $html, 'This is not the professional layout.');
    }

    /** There is a way in from the sidebar, which is half of having a door. */
    public function test_the_sidebar_links_to_it(): void
    {
        $this->actingAs($this->professional)
            ->get(route('professional.dashboard'))
            ->assertSuccessful()
            ->assertSee(route('professional.agreements.index'), false);
    }

    // ── And only to their own ────────────────────────────────

    public function test_another_professional_is_refused(): void
    {
        $other = $this->person('professional');

        $this->actingAs($other)
            ->get(route('professional.agreements.show', $this->agreement))
            ->assertForbidden();
    }

    /**
     * The client on this very agreement is refused here too.
     *
     * Not because they may not see it, but because they have their own screen
     * for it. Two doors onto one record is how the two drift apart.
     */
    public function test_the_client_is_sent_to_their_own_screen_not_this_one(): void
    {
        $this->actingAs($this->client)
            ->get(route('professional.agreements.show', $this->agreement))
            ->assertForbidden();
    }

    public function test_the_list_holds_only_their_own(): void
    {
        $this->agreementBetween($this->person('client'), $this->person('professional'), 'Somebody Else Gala');

        $this->actingAs($this->professional)
            ->get(route('professional.agreements.index'))
            ->assertSuccessful()
            ->assertSee('Rivera Wedding', false)
            ->assertDontSee('Somebody Else Gala');
    }

    // ── Whose move it is ─────────────────────────────────────

    public function test_an_unapproved_agreement_is_the_professionals_move(): void
    {
        $this->assertTrue(Workspace::waitingOn($this->agreement, $this->professional));

        $this->actingAs($this->professional)
            ->get(route('professional.agreements.index'))
            ->assertSee('Your move', false);
    }

    /**
     * Approved by them, not yet by the client, is NOT their move.
     *
     * This is the case the first version of the check got wrong. It asked
     * "approved and signed?", and an agreement the professional had approved
     * but could not yet sign came back as waiting on them. Signing opens only
     * once both sides approve the same version, so until the client approves
     * there is nothing for the professional to do and nothing to sign.
     */
    public function test_waiting_for_the_client_is_not_the_professionals_move(): void
    {
        Workspace::approve($this->agreement, $this->professional);

        $f = $this->agreement->fresh();

        $this->assertTrue(Workspace::supplierApproved($f));
        $this->assertFalse(Workspace::signingOpen($f), 'The pen should not be out before the client approves.');
        $this->assertFalse(Workspace::waitingOn($f, $this->professional));
    }

    /** Once both approved, the unsigned side is waiting on whoever has not signed. */
    public function test_both_approved_and_unsigned_is_both_their_moves(): void
    {
        Workspace::approve($this->agreement, $this->professional);
        Workspace::approve($this->agreement->fresh(), $this->client);

        $f = $this->agreement->fresh();

        $this->assertTrue(Workspace::signingOpen($f));
        $this->assertTrue(Workspace::waitingOn($f, $this->professional));
        $this->assertTrue(Workspace::waitingOn($f, $this->client));
    }

    /** A declined or funded agreement is nobody's move. */
    public function test_a_finished_agreement_is_nobodys_move(): void
    {
        $this->agreement->forceFill(['declined_at' => now(), 'declined_by' => $this->client->id])->save();

        $this->assertFalse(Workspace::waitingOn($this->agreement->fresh(), $this->professional));
        $this->assertFalse(Workspace::waitingOn($this->agreement->fresh(), $this->client));
    }

    /** Somebody who is on neither side is waiting on nothing. */
    public function test_a_stranger_is_not_waiting_on_anything(): void
    {
        $this->assertFalse(Workspace::waitingOn($this->agreement, $this->person('professional')));
    }

    // ── Honest about what is not built ───────────────────────

    /**
     * The pen is not held out before there is something settled to sign.
     *
     * This assertion has been rewritten twice as the week built under it, and
     * both earlier versions were right at the time. On Monday it checked that
     * nothing was offered at all and the page said so. On Tuesday, that
     * approving and declining were offered and signing was not. Now signing is
     * built, so what it checks is the rule that governs it: signing opens only
     * once both sides approved the same version, and this agreement has no
     * approvals on it at all.
     */
    public function test_the_screen_does_not_hold_out_the_pen_too_early(): void
    {
        $html = $this->actingAs($this->professional)
            ->get(route('professional.agreements.show', $this->agreement))
            ->assertSuccessful()
            ->assertSee('Not ready to sign', false)
            ->getContent();

        $this->assertSame(
            0,
            preg_match_all('/action="[^"]*professional\/agreements\/\d+\/sign"/i', $html),
            'The screen is offering a signature on terms nobody has approved.',
        );
    }
}
