<?php

namespace Tests\Feature;

use App\Domain\Agreements\EditAgreement;
use App\Domain\Agreements\Workspace;
use App\Domain\PointOfContact;
use App\Models\{Booking, Category, Event, Finalization, StaffMember, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The strongest answer must never be the one nobody gave.
 *
 * "Who attends" has three answers and PointOfContact says what the first one
 * costs: "PROFESSIONAL is a promise, not a default. It is the answer that
 * stops a staff member being sent." It was the default anyway, in three
 * places at once — the column, the backfill that read a boolean nobody had
 * answered either, and the fallback an unrecognised submission landed on.
 *
 * None of that is theoretical, because no agreement is created with the
 * field: `Award::openFinalization` writes price, scope and terms and nothing
 * else. So every agreement on the site was born holding its professional to
 * attending in person, the contract printed that clause to the client, and
 * naming a crew member was then refused on the strength of it.
 *
 * What these tests pin is narrow: an answer nobody gave is not a promise, and
 * a form that submits nothing recognisable changes nothing at all.
 */
class NobodyPromisedToAttendInPersonTest extends TestCase
{
    use RefreshDatabase;

    private User $professional;
    private User $client;
    private Event $event;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client       = $this->person('client', 'Imogen Hartwell');
        $this->professional = $this->person('professional', 'Hollis Tremaine');

        $this->event = Event::create([
            'title'       => 'Hartwell Anniversary',
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => $this->professional->id,
            'status'      => 'published',
            'starts_at'   => now()->addDays(40),
        ]);

        $this->booking = Booking::create([
            'event_id'    => $this->event->id,
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => $this->professional->id,
            'category_id' => $this->service()->id,
            'status'      => 'confirmed',
            'price'       => 2600,
        ]);
    }

    private function person(string $role, string $name): User
    {
        // Named rather than faked: a faker name with an apostrophe in it is
        // escaped by the page and only some seeds produce one.
        $u = User::factory()->create(['primary_role' => $role, 'name' => $name]);
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
            ['slug' => 'event-photography'],
            ['name' => 'Event Photography', 'kind' => Category::SERVICE, 'is_active' => true],
        );
    }

    /**
     * An agreement the way the application really makes one: Award writes the
     * price, the scope and the payment terms, and never touches attendance.
     */
    private function agreementAsOpened(bool $clientSigned = false): Finalization
    {
        return Finalization::create([
            'event_id'         => $this->event->id,
            'category_id'      => $this->service()->id,
            'supplier_id'      => $this->professional->id,
            'client_id'        => $this->client->id,
            'booking_id'       => $this->booking->id,
            'terms_version'    => 1,
            'agreed_price'     => 2600,
            'scope'            => 'Six hours of coverage.',
            'client_signature' => $clientSigned ? $this->client->name : null,
            'client_signed_at' => $clientSigned ? now() : null,
            'client_signed_version' => $clientSigned ? 1 : null,
        ]);
    }

    private function crewMember(): StaffMember
    {
        return StaffMember::create([
            'supplier_id' => $this->professional->id,
            'name'        => 'Marguerite Vaskov',
            'role'        => 'Second shooter',
            'phone'       => '410-555-0188',
            'status'      => 'active',
        ]);
    }

    // ── The column ───────────────────────────────────────────

    public function test_an_agreement_nobody_answered_does_not_say_the_professional_attends(): void
    {
        $agreement = $this->agreementAsOpened()->fresh();

        $this->assertSame(
            PointOfContact::LATER,
            $agreement->attendance,
            'A newly opened agreement recorded a personal-attendance promise nobody made.'
        );
    }

    public function test_the_promise_is_still_available_when_somebody_chooses_it(): void
    {
        // The point is not that PROFESSIONAL is wrong. It is that it has to
        // be chosen. Signed, because a promise binds from the signature.
        $agreement = $this->agreementAsOpened(clientSigned: true);
        $agreement->update(['attendance' => PointOfContact::PROFESSIONAL]);

        $this->assertTrue(PointOfContact::promisedInPerson($this->booking->fresh()));
    }

    // ── What it costs the professional ───────────────────────

    public function test_a_crew_member_can_be_named_on_an_agreement_nobody_answered(): void
    {
        $this->agreementAsOpened(clientSigned: true);

        PointOfContact::set($this->booking, $this->crewMember(), $this->professional);

        $this->assertNotNull($this->booking->fresh()->point_of_contact_id);
    }

    // ── What it tells the client ─────────────────────────────

    public function test_the_contract_does_not_claim_a_promise_nobody_made(): void
    {
        $lines = implode(' ', PointOfContact::contractLines($this->agreementAsOpened()->fresh()));

        $this->assertStringNotContainsString('will attend personally.', $lines);
        $this->assertStringContainsString('has not yet said whether they will attend', $lines);
    }

    // ── The third route in: an unrecognised submission ───────

    public function test_an_answer_nobody_offered_changes_nothing(): void
    {
        $agreement = $this->agreementAsOpened();
        $agreement->update(['attendance' => PointOfContact::STAFF]);
        $agreement->refresh();

        $before = Workspace::version($agreement);

        $changed = EditAgreement::submit($agreement, $this->professional, [
            'attendance' => 'whatever-the-form-was-made-to-send',
        ]);

        $agreement->refresh();

        $this->assertSame([], $changed, 'An unrecognised answer was recorded as a change.');
        $this->assertSame(PointOfContact::STAFF, $agreement->attendance);
        $this->assertSame($before, Workspace::version($agreement), 'The version moved over a non-answer.');
    }

    public function test_an_empty_submission_does_not_become_the_promise(): void
    {
        $agreement = $this->agreementAsOpened();
        $agreement->update(['attendance' => PointOfContact::LATER]);
        $agreement->refresh();

        EditAgreement::submit($agreement, $this->professional, ['attendance' => '']);

        $this->assertSame(PointOfContact::LATER, $agreement->fresh()->attendance);
    }

    public function test_a_value_with_no_label_reads_as_unset_rather_than_relabelled(): void
    {
        // A revision keeps whatever it was given. An answer since retired has
        // no label, and must not borrow the first one on today's list.
        $this->assertSame('not set', EditAgreement::readable('attendance', 'retired-answer'));
        $this->assertSame(
            PointOfContact::ANSWERS[PointOfContact::LATER],
            EditAgreement::readable('attendance', PointOfContact::LATER)
        );
    }
}
