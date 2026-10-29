<?php

namespace Tests\Feature;

use App\Domain\PointOfContact;
use App\Mail\AssignYourOnSiteContact;
use App\Mail\YourOnSiteContactIsSet;
use App\Domain\AttendanceChase;
use App\Models\{Booking, Category, Event, Finalization, StaffMember, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A crew member's face and number go to one client, for one event.
 *
 * Khadijah, 29 October: headshots and phone numbers are "scoped strictly to
 * the client holding the booking".
 *
 * A file under public/storage is readable by anybody who has the address, and
 * addresses leak: a copied link, a screenshot, a referrer header, a search
 * engine that crawls one page. So the file lives on the private disk, where
 * the web server serves nothing, and every read is decided per request.
 */
class AHeadshotIsOnlyForTheClientWhoWasGivenItTest extends TestCase
{
    use RefreshDatabase;

    private User $professional;
    private User $client;
    private StaffMember $deputy;
    private Booking $booking;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        Storage::fake('private');

        $this->client       = $this->person('client');
        $this->professional = $this->person('professional');

        $this->deputy = StaffMember::create([
            'supplier_id' => $this->professional->id,
            'name'        => 'Dana Reyes',
            'role'        => 'Lead engineer',
            'phone'       => '410-555-0134',
            'status'      => 'active',
            'photo'       => UploadedFile::fake()->image('dana.jpg')->store('staff', 'private'),
        ]);

        [$this->event, $this->booking] = $this->jobFor($this->client);
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

    /** @return array{0: Event, 1: Booking} */
    private function jobFor(User $client): array
    {
        $service = Category::firstOrCreate(
            ['slug' => 'wedding-dj'],
            ['name' => 'Wedding DJ', 'kind' => Category::SERVICE, 'is_active' => true],
        );

        $event = Event::create([
            'title'       => 'Rivera Wedding',
            'client_id'   => $client->id,
            'created_by'  => $client->id,
            'supplier_id' => $this->professional->id,
            'status'      => 'published',
            'starts_at'   => now()->addDays(20),
        ]);

        $booking = Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $client->id,
            'created_by'  => $client->id,
            'supplier_id' => $this->professional->id,
            'category_id' => $service->id,
            'status'      => 'confirmed',
            'price'       => 4000,
        ]);

        return [$event, $booking];
    }

    private function photo(): string
    {
        return route('staff.photo', $this->deputy);
    }

    // ── Who may look ─────────────────────────────────────────

    public function test_the_professional_can_see_their_own_crew_member(): void
    {
        $this->actingAs($this->professional)->get($this->photo())->assertSuccessful();
    }

    public function test_the_client_on_that_booking_can_see_them(): void
    {
        PointOfContact::set($this->booking, $this->deputy, $this->professional);

        $this->actingAs($this->client)->get($this->photo())->assertSuccessful();
    }

    // ── Who may not ──────────────────────────────────────────

    /** Nobody at all, which is the whole reason the file moved off public storage. */
    public function test_a_stranger_off_the_street_cannot(): void
    {
        PointOfContact::set($this->booking, $this->deputy, $this->professional);

        $this->get($this->photo())->assertRedirect(route('login'));
    }

    /** Not every client of this professional. One client, one event. */
    public function test_another_client_of_the_same_professional_cannot(): void
    {
        PointOfContact::set($this->booking, $this->deputy, $this->professional);

        $other = $this->person('client');
        $this->jobFor($other);

        $this->actingAs($other)->get($this->photo())->assertForbidden();
    }

    /** Not before they are named, even on their own booking. */
    public function test_the_client_cannot_look_before_this_person_is_named(): void
    {
        $this->actingAs($this->client)->get($this->photo())->assertForbidden();
    }

    /** And not after somebody else takes over. */
    public function test_the_client_cannot_look_once_somebody_else_is_named(): void
    {
        PointOfContact::set($this->booking, $this->deputy, $this->professional);

        $replacement = StaffMember::create([
            'supplier_id' => $this->professional->id,
            'name' => 'Sam Okafor', 'status' => 'active',
        ]);
        PointOfContact::set($this->booking->fresh(), $replacement, $this->professional);

        $this->actingAs($this->client)->get($this->photo())->assertForbidden();
    }

    public function test_another_professional_cannot(): void
    {
        $this->actingAs($this->person('professional'))->get($this->photo())->assertForbidden();
    }

    /** A crew member with no photo is a 404, not a blank image. */
    public function test_a_crew_member_with_no_photo_is_not_found(): void
    {
        $bare = StaffMember::create([
            'supplier_id' => $this->professional->id,
            'name' => 'Sam Okafor', 'status' => 'active',
        ]);

        $this->actingAs($this->professional)
            ->get(route('staff.photo', $bare))
            ->assertNotFound();
    }

    // ── Khadijah's three messages ────────────────────────────

    public function test_the_client_is_emailed_when_somebody_is_named(): void
    {
        Mail::fake();

        $this->signedAgreement(PointOfContact::LATER);
        PointOfContact::set($this->booking->fresh(), $this->deputy, $this->professional);

        Mail::assertSent(YourOnSiteContactIsSet::class,
            fn ($mail) => $mail->hasTo($this->client->email));
    }

    /**
     * Nothing is emailed for a professional saying they are coming
     * themselves. The client was never told otherwise, so there is nothing
     * to correct.
     */
    public function test_confirming_yourself_emails_nobody(): void
    {
        Mail::fake();

        $this->signedAgreement(PointOfContact::LATER);
        PointOfContact::set($this->booking->fresh(), null, $this->professional);

        Mail::assertNotSent(YourOnSiteContactIsSet::class);
    }

    /** The confirmation carries the responsibility line, which her draft omits. */
    public function test_the_confirmation_says_who_is_still_responsible(): void
    {
        $this->signedAgreement(PointOfContact::LATER);
        PointOfContact::set($this->booking, $this->deputy, $this->professional);

        $body = (new YourOnSiteContactIsSet($this->booking->fresh()))->render();

        $this->assertStringContainsString('Dana Reyes', $body);
        $this->assertStringContainsString('remains responsible for fulfilling the agreement', $body);
    }

    public function test_the_professional_is_emailed_at_the_deadline_but_not_a_week_out(): void
    {
        Mail::fake();

        $this->signedAgreement(PointOfContact::LATER);
        $this->event->forceFill(['starts_at' => now()->addDays(6)])->save();

        AttendanceChase::run();
        Mail::assertNotSent(AssignYourOnSiteContact::class);

        $this->event->forceFill(['starts_at' => now()->addDays(2)])->save();

        AttendanceChase::run();
        Mail::assertSent(AssignYourOnSiteContact::class,
            fn ($mail) => $mail->hasTo($this->professional->email));
    }

    /** It is Khadijah's wording, and it says the choice was Decide Later. */
    public function test_the_reminder_email_explains_why_it_arrived(): void
    {
        $body = (new AssignYourOnSiteContact($this->booking->load('event', 'supplier')))->render();

        $this->assertStringContainsString('Decide Later', $body);
        $this->assertStringContainsString('Assign On-Site Contact', $body);
        $this->assertStringContainsString('Rivera Wedding', $body);
    }

    /**
     * A mail outage does not swallow the reminder.
     *
     * The record is written before the notice, so a throw here would leave a
     * stage marked sent that nobody ever received and that can never retry.
     */
    public function test_a_mail_failure_does_not_lose_the_alert(): void
    {
        $this->signedAgreement(PointOfContact::LATER);
        $this->event->forceFill(['starts_at' => now()->addDays(2)])->save();

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('no mail server'));

        $this->assertSame(1, AttendanceChase::run());
        $this->assertSame(1, $this->professional->notifications()->count());
    }

    private function signedAgreement(string $attendance): Finalization
    {
        return Finalization::create([
            'event_id'      => $this->event->id,
            'category_id'   => $this->booking->category_id,
            'client_id'     => $this->client->id,
            'supplier_id'   => $this->professional->id,
            'booking_id'    => $this->booking->id,
            'terms_version' => 1,
            'agreed_price'  => 4000,
            'scope'         => 'Six hours of music.',
            'attendance'    => $attendance,
            'client_signature'      => $this->client->name,
            'client_signed_at'      => now(),
            'client_signed_version' => 1,
        ]);
    }
}
