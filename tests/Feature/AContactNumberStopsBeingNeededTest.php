<?php

namespace Tests\Feature;

use App\Domain\PointOfContact;
use App\Models\{AgreementLog, Booking, Category, DisputeCase, Event, StaffMember, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A client's reason to reach a crew member runs out when the job does.
 *
 * Sir Peter, 29 October: "restricting Client access to staff contact
 * information after the booking has been completed, with a proposed 30-day
 * access period unless an active dispute or another legitimate requirement
 * calls for longer."
 *
 * The NAME and ROLE are not withheld when the window closes. They are the
 * record of who actually did the work, and a client who cannot say who
 * attended their own wedding has lost something the restriction was never
 * meant to take. It is the phone number and the photo that stop being
 * needed, and those are what go.
 */
class AContactNumberStopsBeingNeededTest extends TestCase
{
    use RefreshDatabase;

    private User $professional;
    private User $client;
    private StaffMember $deputy;
    private Event $event;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        Storage::fake('private');

        Carbon::setTestNow(Carbon::parse('2026-11-01 12:00:00'));

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

        $this->event = Event::create([
            'title'       => 'Rivera Wedding',
            'client_id'   => $this->client->id,
            'created_by'  => $this->client->id,
            'supplier_id' => $this->professional->id,
            'status'      => 'published',
            'starts_at'   => now()->subDays(40),
            'ends_at'     => now()->subDays(40)->addHours(6),
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

        PointOfContact::set($this->booking, $this->deputy, $this->professional);
        $this->booking->refresh();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
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

    /** Mark the job over, that many days ago. */
    private function finished(int $daysAgo, string $status = 'completed'): void
    {
        $this->booking->forceFill(['status' => $status])->save();

        // created_at is not fillable on an append-only log, so it is written
        // after the row rather than passed to create() and quietly dropped.
        AgreementLog::create([
            'subject_type' => 'booking',
            'subject_id'   => $this->booking->id,
            'to_status'    => $status,
        ])->forceFill(['created_at' => now()->subDays($daysAgo)])->save();

        $this->booking->refresh();
    }

    private function shown(): ?array
    {
        return PointOfContact::forClient($this->booking->fresh()->load('event'));
    }

    // ── While the job is live ────────────────────────────────

    public function test_a_running_booking_is_never_closed_off(): void
    {
        $this->assertFalse(PointOfContact::contactWindowClosed($this->booking));
        $this->assertSame('410-555-0134', $this->shown()['phone']);
    }

    public function test_the_window_has_not_run_out_the_day_after(): void
    {
        $this->finished(daysAgo: 1);

        $this->assertNotNull($this->shown()['phone']);
    }

    public function test_it_is_still_open_on_the_last_day(): void
    {
        $this->finished(daysAgo: 29);

        $this->assertNotNull($this->shown()['phone']);
    }

    // ── Once it has run out ──────────────────────────────────

    public function test_the_number_and_the_photo_go(): void
    {
        $this->finished(daysAgo: 31);

        $shown = $this->shown();

        $this->assertNull($shown['phone']);
        $this->assertNull($shown['photo']);
        $this->assertTrue($shown['closed']);
    }

    /**
     * But who attended stays.
     *
     * A client who cannot say who did the work at their own event has lost
     * something the restriction was never meant to take.
     */
    public function test_who_attended_is_still_on_the_record(): void
    {
        $this->finished(daysAgo: 31);

        $shown = $this->shown();

        $this->assertSame('Dana Reyes', $shown['name']);
        $this->assertSame('Lead engineer', $shown['role']);
    }

    /** And the page says why, rather than looking as though nothing was given. */
    public function test_the_booking_card_says_why_the_number_is_gone(): void
    {
        $this->finished(daysAgo: 31);

        $this->actingAs($this->client)
            ->get(route('client.bookings.index'))
            ->assertSuccessful()
            ->assertSee('Dana Reyes', false)
            ->assertSee('no longer shown now the booking is over', false)
            ->assertDontSee('410-555-0134');
    }

    /** A cancelled job has no day to turn up to either. */
    public function test_a_cancelled_booking_closes_the_same_way(): void
    {
        $this->finished(daysAgo: 31, status: 'cancelled');

        $this->assertTrue(PointOfContact::contactWindowClosed($this->booking->fresh()));
    }

    // ── Sir Peter's exception ────────────────────────────────

    /**
     * An open dispute keeps it open, which is his own carve-out and the one
     * that matters: an argument about what happened at the event is exactly
     * when a client needs to say who was there.
     */
    public function test_an_open_dispute_keeps_the_details_reachable(): void
    {
        $this->finished(daysAgo: 60);

        $this->assertTrue(PointOfContact::contactWindowClosed($this->booking->fresh()));

        DisputeCase::create([
            'booking_id'      => $this->booking->id,
            'filed_by'        => $this->client->id,
            'client_id'       => $this->client->id,
            'professional_id' => $this->professional->id,
            'severity'        => 2,
            'taxonomy'        => 'poor_workmanship',
            'summary'         => 'The sound cut out for an hour.',
        ]);

        $this->assertFalse(PointOfContact::contactWindowClosed($this->booking->fresh()));
        $this->assertNotNull($this->shown()['phone']);
    }

    // ── Enforced on the server, not on the page ──────────────

    /**
     * Sir Peter asked for this in as many words: "including image URLs and
     * API responses, rather than relying only on hiding information in the
     * interface".
     */
    public function test_the_image_address_is_refused_once_the_window_closes(): void
    {
        $url = route('staff.photo', $this->deputy);

        $this->actingAs($this->client)->get($url)->assertSuccessful();

        $this->finished(daysAgo: 31);

        $this->actingAs($this->client)->get($url)->assertForbidden();
    }

    /** The professional keeps their own records either way. */
    public function test_the_professional_still_sees_their_own_crew(): void
    {
        $this->finished(daysAgo: 400);

        $this->actingAs($this->professional)
            ->get(route('staff.photo', $this->deputy))
            ->assertSuccessful();
    }

    // ── The window is Sir Peter's to set ─────────────────────

    public function test_the_window_comes_from_configuration(): void
    {
        $this->finished(daysAgo: 31);

        config(['staff.client_access_days' => 60]);
        $this->assertFalse(PointOfContact::contactWindowClosed($this->booking->fresh()));

        config(['staff.client_access_days' => 7]);
        $this->assertTrue(PointOfContact::contactWindowClosed($this->booking->fresh()));
    }

    /** Switched off leaves access open rather than cutting it off. */
    public function test_a_negative_window_means_no_cut_off(): void
    {
        $this->finished(daysAgo: 400);
        config(['staff.client_access_days' => -1]);

        $this->assertFalse(PointOfContact::contactWindowClosed($this->booking->fresh()));
    }

    /**
     * With no date to count from, access stays.
     *
     * Guessing a date and cutting somebody off on the guess is worse than
     * leaving it open: this is a courtesy limit, not a security boundary.
     */
    public function test_with_no_date_to_count_from_nothing_is_cut_off(): void
    {
        $this->booking->forceFill(['status' => 'completed'])->save();
        $this->event->forceFill(['starts_at' => null, 'ends_at' => null])->save();

        $this->assertFalse(PointOfContact::contactWindowClosed($this->booking->fresh()->load('event')));
    }

    /** With no log, the event's own date does the counting. */
    public function test_the_event_date_is_used_when_nothing_logged_it(): void
    {
        $this->booking->forceFill(['status' => 'completed'])->save();

        // The event ended 40 days ago, so a 30-day window has run out.
        $this->assertTrue(PointOfContact::contactWindowClosed($this->booking->fresh()->load('event')));
    }
}
