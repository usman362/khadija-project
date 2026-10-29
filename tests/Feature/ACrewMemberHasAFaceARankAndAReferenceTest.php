<?php

namespace Tests\Feature;

use App\Domain\PointOfContact;
use App\Models\{Booking, Category, Event, StaffMember, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * What a crew member needs before a client ever sees them.
 *
 * Sir Peter, 29 October, settling the staff question: named team members, no
 * logins, "built properly, with the flexibility to add accounts later".
 *
 * Three of his requirements land on the record: a photo, because a name
 * alone is not much use to somebody looking round a venue for a stranger; a
 * supervisor rank, kept SEPARATE from on-site contact status; and a
 * reference of their own, so an account can be attached later without the
 * rest being rebuilt.
 *
 * The separation is the one worth pinning. A supervisor is a rank the person
 * holds. A point of contact is a job for one event. The supervisor is often
 * not the one the client rings, and collapsing the two would make that
 * impossible to say.
 */
class ACrewMemberHasAFaceARankAndAReferenceTest extends TestCase
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
            'category_id' => Category::firstOrCreate(
                ['slug' => 'wedding-dj'],
                ['name' => 'Wedding DJ', 'kind' => Category::SERVICE, 'is_active' => true],
            )->id,
            'status'      => 'confirmed',
            'price'       => 4000,
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

    private function crew(array $attributes = []): StaffMember
    {
        return StaffMember::create($attributes + [
            'supplier_id' => $this->professional->id,
            'name'        => 'Dana Reyes',
            'status'      => 'active',
        ]);
    }

    // ── A reference of their own ─────────────────────────────

    /**
     * Every crew member has one from the day they are added.
     *
     * Not granted later to whoever needs it: a reference only some rows have
     * is one more thing to check before it can be used for anything, which
     * is the opposite of what it was added for.
     */
    public function test_a_crew_member_is_given_a_reference_on_the_spot(): void
    {
        $staff = $this->crew();

        $this->assertNotNull($staff->public_id);
        $this->assertStringStartsWith(StaffMember::PREFIX . '-', $staff->public_id);
    }

    public function test_two_crew_members_do_not_share_one(): void
    {
        $this->assertNotSame(
            $this->crew()->public_id,
            $this->crew(['name' => 'Sam Okafor'])->public_id,
        );
    }

    /**
     * It is not the row id dressed up, which is what it exists to avoid.
     *
     * Sequential references leak how many crew a company has and roughly
     * when each was added. Two made one after the other should not come out
     * one apart.
     */
    public function test_the_reference_is_not_the_row_id(): void
    {
        $first  = $this->crew();
        $second = $this->crew(['name' => 'Sam Okafor']);

        $digits = fn (StaffMember $s) => (int) substr($s->public_id, strlen(StaffMember::PREFIX) + 1);

        $this->assertNotSame($first->id, $digits($first));
        $this->assertNotSame($digits($first) + 1, $digits($second));
    }

    // ── A face ───────────────────────────────────────────────

    public function test_a_crew_member_without_a_photo_has_no_photo_rather_than_a_placeholder(): void
    {
        $this->assertNull($this->crew()->photoUrl());
    }

    public function test_the_photo_reaches_the_client(): void
    {
        $staff = $this->crew(['photo' => 'staff/dana.jpg', 'role' => 'Lead engineer']);

        PointOfContact::set($this->booking, $staff, $this->professional);

        $shown = PointOfContact::forClient($this->booking->fresh());

        // A route, not a storage path: the file is private and the
        // controller decides who may look. See StaffPhotoController.
        $this->assertSame(route('staff.photo', $staff), $shown['photo']);
    }

    public function test_the_professional_can_upload_one(): void
    {
        Storage::fake('private');

        $this->actingAs($this->professional)
            ->post(route('professional.team.staff.store'), [
                'name'  => 'Sam Okafor',
                'role'  => 'Bartender',
                'photo' => UploadedFile::fake()->image('sam.jpg'),
            ])
            ->assertRedirect();

        $staff = StaffMember::where('name', 'Sam Okafor')->firstOrFail();

        $this->assertNotNull($staff->photo);
        Storage::disk('private')->assertExists($staff->photo);
    }

    // ── A rank, kept apart from the job ──────────────────────

    public function test_a_crew_member_is_not_a_supervisor_unless_said(): void
    {
        $this->assertFalse($this->crew()->is_supervisor);
    }

    public function test_the_professional_can_mark_one(): void
    {
        $this->actingAs($this->professional)
            ->post(route('professional.team.staff.store'), [
                'name'          => 'Sam Okafor',
                'is_supervisor' => '1',
            ])
            ->assertRedirect();

        $this->assertTrue(StaffMember::where('name', 'Sam Okafor')->firstOrFail()->is_supervisor);
    }

    /**
     * The two are set apart and stay apart. This is Sir Peter's point 9.
     *
     * One person can hold both on one job, and that has to be a thing the
     * record says rather than a thing it cannot tell apart.
     */
    public function test_being_in_charge_and_being_the_contact_are_two_different_records(): void
    {
        $boss    = $this->crew(['name' => 'Ada Lin', 'is_supervisor' => true]);
        $contact = $this->crew(['name' => 'Sam Okafor']);

        PointOfContact::putInCharge($this->booking, $boss, $this->professional);
        PointOfContact::set($this->booking->fresh(), $contact, $this->professional);

        $booking = $this->booking->fresh();

        $this->assertSame($boss->id, $booking->supervisor_id);
        $this->assertSame($contact->id, $booking->point_of_contact_id);
    }

    /** Putting somebody in charge tells the client nothing, because it is not theirs. */
    public function test_the_client_is_not_shown_who_is_in_charge(): void
    {
        $boss = $this->crew(['name' => 'Ada Lin', 'is_supervisor' => true]);

        PointOfContact::putInCharge($this->booking, $boss, $this->professional);

        $this->assertNull(PointOfContact::forClient($this->booking->fresh()));

        $this->actingAs($this->client)
            ->get(route('client.bookings.index'))
            ->assertSuccessful()
            ->assertDontSee('Ada Lin');
    }

    /** Only somebody who actually holds the rank can be put in charge. */
    public function test_a_crew_member_who_is_not_a_supervisor_cannot_be_put_in_charge(): void
    {
        $this->expectException(ValidationException::class);

        PointOfContact::putInCharge($this->booking, $this->crew(), $this->professional);
    }

    public function test_another_companys_supervisor_cannot_be_put_in_charge(): void
    {
        $theirs = StaffMember::create([
            'supplier_id'   => $this->person('professional')->id,
            'name'          => 'Someone Else',
            'is_supervisor' => true,
            'status'        => 'active',
        ]);

        $this->expectException(ValidationException::class);

        PointOfContact::putInCharge($this->booking, $theirs, $this->professional);
    }

    public function test_nobody_in_charge_is_an_allowed_answer(): void
    {
        $boss = $this->crew(['is_supervisor' => true]);

        PointOfContact::putInCharge($this->booking, $boss, $this->professional);
        PointOfContact::putInCharge($this->booking->fresh(), null, $this->professional);

        $this->assertNull($this->booking->fresh()->supervisor_id);
    }

    // ── The screens ──────────────────────────────────────────

    /** The team page shows the crew. It could add people and never list them. */
    public function test_the_team_page_lists_the_crew_with_their_rank_and_reference(): void
    {
        $staff = $this->crew(['role' => 'Lead engineer', 'is_supervisor' => true, 'phone' => '410-555-0134']);

        $this->actingAs($this->professional)
            ->get(route('professional.team.index'))
            ->assertSuccessful()
            ->assertSee('Dana Reyes', false)
            ->assertSee('Lead engineer', false)
            ->assertSee('Supervisor', false)
            ->assertSee($staff->public_id, false);
    }

    /** The in-charge control appears only once there is a supervisor to pick. */
    public function test_the_gig_page_offers_no_supervisor_until_there_is_one(): void
    {
        $this->crew();

        $this->actingAs($this->professional)
            ->get(route('professional.gigs.show', $this->event))
            ->assertSuccessful()
            ->assertDontSee('In charge on site');

        $this->crew(['name' => 'Ada Lin', 'is_supervisor' => true]);

        $this->actingAs($this->professional)
            ->get(route('professional.gigs.show', $this->event))
            ->assertSuccessful()
            ->assertSee('In charge on site', false);
    }

    public function test_the_professional_can_put_somebody_in_charge_from_the_gig_page(): void
    {
        $boss = $this->crew(['name' => 'Ada Lin', 'is_supervisor' => true]);

        $this->actingAs($this->professional)
            ->post(route('professional.gigs.supervisor', $this->event), ['staff_id' => $boss->id])
            ->assertRedirect();

        $this->assertSame($boss->id, $this->booking->fresh()->supervisor_id);
    }
}
