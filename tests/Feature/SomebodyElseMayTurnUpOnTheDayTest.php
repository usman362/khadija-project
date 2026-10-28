<?php

namespace Tests\Feature;

use App\Domain\Agreements\EditAgreement;
use App\Domain\Agreements\Workspace;
use App\Domain\PointOfContact;
use App\Models\{Booking, Category, Event, Finalization, StaffMember, User};
use App\Notifications\SomebodyElseIsComing;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Who the client will meet on the day, and whether they were warned.
 *
 * Sir Peter, 28 October: a professional may send a worker or supervisor as
 * the main point of contact at the site, and the client should be told —
 * unless it was already said during the agreement. Then the rule the rest
 * hangs on: "The Professional remains responsible for fulfilling the
 * agreement, even when a staff member attends instead."
 *
 * So nothing here transfers anything. The booking keeps its supplier, the
 * agreement is not reassigned, and the staff member gains no standing. All
 * that is recorded is a name and a number for the client to look for.
 */
class SomebodyElseMayTurnUpOnTheDayTest extends TestCase
{
    use RefreshDatabase;

    private User $professional;
    private User $client;
    private Event $event;
    private Booking $booking;
    private StaffMember $deputy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client       = $this->person('client');
        $this->professional = $this->person('professional');

        $this->event = Event::create([
            'title'       => 'Rivera Wedding',
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => $this->professional->id,
            'status'      => 'published',
            'starts_at'   => now()->addDays(30),
        ]);

        $this->booking = Booking::create([
            'event_id'    => $this->event->id,
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => $this->professional->id,
            'category_id' => $this->service()->id,
            'status'      => 'confirmed',
            'price'       => 4000,
        ]);

        $this->deputy = StaffMember::create([
            'supplier_id' => $this->professional->id,
            'name'        => 'Dana Reyes',
            'role'        => 'Lead engineer',
            'phone'       => '410-555-0134',
            'status'      => 'active',
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

    private function service(): Category
    {
        return Category::firstOrCreate(
            ['slug' => 'wedding-dj'],
            ['name' => 'Wedding DJ', 'kind' => Category::SERVICE, 'is_active' => true],
        );
    }

    /** An agreement on this booking, signed by the client or not. */
    private function agreement(bool $clientSigned, bool $staffMayAttend = false): Finalization
    {
        // One agreement per event, supplier and service: the table says so.
        // Tests that want it in a second shape change this one.
        return Finalization::updateOrCreate([
            'event_id'         => $this->event->id,
            'category_id'      => $this->service()->id,
            'supplier_id'      => $this->professional->id,
        ], [
            'client_id'        => $this->client->id,
            'booking_id'       => $this->booking->id,
            'terms_version'    => 1,
            'agreed_price'     => 4000,
            'scope'            => 'Six hours of music.',
            'staff_may_attend' => $staffMayAttend,
            'client_signature' => $clientSigned ? $this->client->name : null,
            'client_signed_at' => $clientSigned ? now() : null,
            'client_signed_version' => $clientSigned ? 1 : null,
        ]);
    }

    // ── Naming somebody ──────────────────────────────────────

    public function test_the_professional_can_name_a_crew_member(): void
    {
        PointOfContact::set($this->booking, $this->deputy, $this->professional);

        $fresh = $this->booking->fresh();

        $this->assertSame($this->deputy->id, $fresh->point_of_contact_id);
        $this->assertNotNull($fresh->point_of_contact_set_at);
    }

    /** And the booking is still the professional's. Nothing was handed over. */
    public function test_naming_somebody_transfers_nothing(): void
    {
        PointOfContact::set($this->booking, $this->deputy, $this->professional);

        $fresh = $this->booking->fresh();

        $this->assertSame($this->professional->id, $fresh->supplier_id);
        $this->assertSame($this->professional->id, $this->event->fresh()->supplier_id);
    }

    public function test_clearing_it_puts_the_professional_back(): void
    {
        PointOfContact::set($this->booking, $this->deputy, $this->professional);
        PointOfContact::set($this->booking->fresh(), null, $this->professional);

        $fresh = $this->booking->fresh();

        $this->assertNull($fresh->point_of_contact_id);
        $this->assertNull($fresh->point_of_contact_set_at, 'With nobody named there is no moment it was decided.');
    }

    // ── And only their own ───────────────────────────────────

    public function test_another_professional_cannot_name_somebody_on_this_booking(): void
    {
        $this->expectException(ValidationException::class);

        PointOfContact::set($this->booking, null, $this->person('professional'));
    }

    public function test_another_companys_staff_cannot_be_put_in_front_of_the_client(): void
    {
        $theirs = StaffMember::create([
            'supplier_id' => $this->person('professional')->id,
            'name' => 'Someone Else', 'status' => 'active',
        ]);

        $this->expectException(ValidationException::class);

        PointOfContact::set($this->booking, $theirs, $this->professional);
    }

    // ── When the client is told ──────────────────────────────

    /**
     * Before the agreement is signed, no notice.
     *
     * The client is about to read the terms anyway, and a notice for every
     * adjustment made while the agreement is still being built is noise in
     * the one place that must not be noisy.
     */
    public function test_naming_somebody_before_signing_is_not_announced(): void
    {
        Notification::fake();
        $this->agreement(clientSigned: false);

        PointOfContact::set($this->booking, $this->deputy, $this->professional);

        Notification::assertNothingSent();
    }

    /** After signing, the client is told. This is Sir Peter's ask. */
    public function test_a_swap_after_signing_is_announced(): void
    {
        Notification::fake();
        $this->agreement(clientSigned: true);

        PointOfContact::set($this->booking, $this->deputy, $this->professional);

        Notification::assertSentTo($this->client, SomebodyElseIsComing::class);
    }

    /**
     * Unless the agreement already said it might happen.
     *
     * "unless it was already said during the agreement" — then the client
     * agreed to exactly this, and telling them is not news.
     */
    public function test_an_agreed_swap_needs_no_announcement(): void
    {
        Notification::fake();
        $this->agreement(clientSigned: true, staffMayAttend: true);

        PointOfContact::set($this->booking, $this->deputy, $this->professional);

        Notification::assertNothingSent();
    }

    /** Silence in the agreement is not that consent. */
    public function test_an_agreement_that_never_mentioned_staff_has_not_agreed_to_them(): void
    {
        $f = $this->agreement(clientSigned: true);

        $this->assertFalse((bool) $f->staff_may_attend);
        $this->assertTrue(PointOfContact::tellTheClient($this->booking->fresh()));
    }

    /** Setting the same person twice is not a change, so nobody is told twice. */
    public function test_naming_the_same_person_again_announces_nothing(): void
    {
        $this->agreement(clientSigned: true);
        PointOfContact::set($this->booking, $this->deputy, $this->professional);

        Notification::fake();
        PointOfContact::set($this->booking->fresh(), $this->deputy, $this->professional);

        Notification::assertNothingSent();
    }

    /** The notice says the professional still owes the work. */
    public function test_the_notice_says_who_is_still_responsible(): void
    {
        $this->agreement(clientSigned: true);

        $sentence = (new SomebodyElseIsComing($this->booking, $this->deputy, $this->professional->name))
            ->sentence();

        $this->assertStringContainsString('Dana Reyes', $sentence);
        $this->assertStringContainsString('still responsible', $sentence);
    }

    // ── What the client sees ─────────────────────────────────

    public function test_the_client_is_shown_the_name_role_and_number(): void
    {
        PointOfContact::set($this->booking, $this->deputy, $this->professional);

        $this->actingAs($this->client)
            ->get(route('client.bookings.index'))
            ->assertSuccessful()
            ->assertSee('Dana Reyes', false)
            ->assertSee('Lead engineer', false)
            ->assertSee('410-555-0134', false)
            ->assertSee('is still responsible for the work', false);
    }

    /**
     * With nobody named, the line is absent rather than naming the
     * professional.
     *
     * A card that always prints somebody under "your point of contact"
     * cannot be read: the client could not tell the day a different person
     * was sent from the day nobody was.
     */
    public function test_with_nobody_named_the_client_sees_no_such_line(): void
    {
        $this->assertNull(PointOfContact::forClient($this->booking));

        $this->actingAs($this->client)
            ->get(route('client.bookings.index'))
            ->assertSuccessful()
            ->assertDontSee('will be your point of contact on the day');
    }

    // ── The agreement itself ─────────────────────────────────

    /** The responsibility rule is on the document, whichever way it is set. */
    public function test_the_responsibility_rule_is_in_the_agreement_either_way(): void
    {
        foreach ([false, true] as $agreed) {
            $lines = PointOfContact::contractLines($this->agreement(false, $agreed));

            $this->assertStringContainsString(
                'remains responsible for fulfilling this agreement',
                implode(' ', $lines),
                'The rule cannot depend on whether staff are expected.',
            );
        }
    }

    public function test_the_agreement_says_which_of_the_two_was_agreed(): void
    {
        $this->assertStringContainsString(
            'may send a member of their staff',
            implode(' ', PointOfContact::contractLines($this->agreement(false, true))),
        );

        $this->assertStringContainsString(
            'is expected to attend',
            implode(' ', PointOfContact::contractLines($this->agreement(false, false))),
        );
    }

    // ── It is a term, so it behaves like one ─────────────────

    /**
     * Turning it on after somebody approved moves the version.
     *
     * It changes what the client is agreeing to, so an approval given
     * without it cannot carry over to terms that have it.
     */
    public function test_agreeing_to_staff_invalidates_an_approval_given_without_it(): void
    {
        $f = $this->agreement(clientSigned: false);
        Workspace::approve($f, $this->client);

        $f = $f->fresh();
        $this->assertTrue(Workspace::clientApproved($f));

        EditAgreement::submit($f, $this->professional, ['staff_may_attend' => '1'], 'I may send my lead engineer.');

        $f = $f->fresh();

        $this->assertTrue((bool) $f->staff_may_attend);
        $this->assertSame(2, Workspace::version($f));
        $this->assertFalse(Workspace::clientApproved($f), 'They approved terms that did not say this.');
    }

    /** Submitting the same answer again is not a change. */
    public function test_resubmitting_the_same_answer_changes_nothing(): void
    {
        $f = $this->agreement(clientSigned: false);

        $this->assertSame([], EditAgreement::submit($f, $this->professional, ['staff_may_attend' => '0'], 'No change.'));
        $this->assertSame(1, Workspace::version($f->fresh()));
    }

    /**
     * An unticked box means no, and the hidden 0 is what carries it.
     *
     * Without that, an unticked checkbox sends nothing and "nothing" reads
     * as unchanged rather than as the answer given.
     */
    public function test_unticking_it_turns_it_off(): void
    {
        $f = $this->agreement(clientSigned: false, staffMayAttend: true);

        $changed = EditAgreement::submit($f, $this->professional, ['staff_may_attend' => '0'], 'I will come myself.');

        $this->assertArrayHasKey('staff_may_attend', $changed);
        $this->assertFalse((bool) $f->fresh()->staff_may_attend);
    }

    public function test_it_reads_as_yes_or_no_rather_than_one_or_zero(): void
    {
        $this->assertSame('yes', EditAgreement::readable('staff_may_attend', true));
        $this->assertSame('no', EditAgreement::readable('staff_may_attend', false));
        $this->assertSame('no', EditAgreement::readable('staff_may_attend', null));
    }

    // ── The professional's screen ────────────────────────────

    public function test_the_gig_page_offers_the_crew(): void
    {
        $this->actingAs($this->professional)
            ->get(route('professional.gigs.show', $this->event))
            ->assertSuccessful()
            ->assertSee('Point of contact', false)
            ->assertSee('Dana Reyes', false);
    }

    public function test_the_professional_can_set_it_from_the_gig_page(): void
    {
        $this->actingAs($this->professional)
            ->post(route('professional.gigs.point-of-contact', $this->event), [
                'staff_id' => $this->deputy->id,
            ])
            ->assertRedirect();

        $this->assertSame($this->deputy->id, $this->booking->fresh()->point_of_contact_id);
    }

    public function test_a_professional_without_this_gig_cannot_set_it(): void
    {
        $other = $this->person('professional');

        $this->actingAs($other)
            ->post(route('professional.gigs.point-of-contact', $this->event), ['staff_id' => null])
            ->assertNotFound();
    }
}
