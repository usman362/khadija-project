<?php

namespace Tests\Feature;

use App\Models\{Agreement, Booking, Category, Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A contract is the business of the two people it is between.
 *
 * Found while checking the professional side's agreement work against what
 * is already built, 27 October. The route middleware asks whether an account
 * may use agreements at all, and client, professional and admin all may. It
 * cannot ask whether THIS agreement is theirs, and only download() went on to
 * ask.
 *
 * So any signed-in account could put another number in the address and read a
 * contract between two strangers in full, reject it, or run the AI generator
 * over somebody else's booking. reject() logged their name against it as the
 * party who had refused.
 */
class AnAgreementIsOnlyTheTwoPartiesBusinessTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $professional;
    private User $stranger;
    private Booking $booking;
    private Agreement $agreement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client       = $this->person('client');
        $this->professional = $this->person('professional');

        // Not a stranger off the street: a full client account of its own,
        // holding every agreement permission the seeder grants. The point is
        // that the permission is not the question.
        $this->stranger = $this->person('client');

        $service = Category::firstOrCreate(
            ['slug' => 'wedding-dj'],
            ['name' => 'Wedding DJ', 'kind' => Category::SERVICE, 'is_active' => true],
        );

        $event = Event::create([
            'title'      => 'Rivera Wedding',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(30),
        ]);

        $this->booking = Booking::create([
            'client_id'   => $this->client->id,
            'supplier_id' => $this->professional->id,
            'event_id'    => $event->id,
            'category_id' => $service->id,
            'status'      => 'requested',
            'created_by'  => $this->client->id,
        ]);

        $this->agreement = Agreement::create([
            'booking_id'   => $this->booking->id,
            'generated_by' => $this->client->id,
            'title'        => 'Wedding DJ Agreement',
            'content'      => 'The fee is four thousand dollars and the deposit is one thousand.',
            'status'       => 'pending_review',
            'version'      => 1,
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

    // ── Reading ──────────────────────────────────────────────

    public function test_a_stranger_cannot_read_the_agreement(): void
    {
        $this->actingAs($this->stranger)
            ->get(route('app.agreements.show', $this->agreement))
            ->assertForbidden();
    }

    /** And in particular does not get the terms, which is the actual harm. */
    public function test_a_stranger_is_not_shown_the_money(): void
    {
        $response = $this->actingAs($this->stranger)
            ->get(route('app.agreements.show', $this->agreement));

        $this->assertStringNotContainsString('four thousand dollars', (string) $response->getContent());
    }

    public function test_the_client_can_read_their_own_agreement(): void
    {
        $this->actingAs($this->client)
            ->get(route('app.agreements.show', $this->agreement))
            ->assertSuccessful()
            ->assertSee('four thousand dollars', false);
    }

    /** The professional is a party too, which is the whole point of the week. */
    public function test_the_professional_can_read_the_agreement_they_are_named_in(): void
    {
        $this->actingAs($this->professional)
            ->get(route('app.agreements.show', $this->agreement))
            ->assertSuccessful()
            ->assertSee('four thousand dollars', false);
    }

    /**
     * The list has always been scoped. Keeping it that way.
     *
     * Asserted on the event title, because that is what the list prints. The
     * first version of this looked for the agreement's own title and failed
     * against a correct page: the column is the event, with the booking number
     * under it, and the agreement title is never shown here at all.
     */
    public function test_the_list_shows_a_stranger_nothing(): void
    {
        $this->actingAs($this->stranger)
            ->get(route('app.agreements.index'))
            ->assertSuccessful()
            ->assertDontSee('Rivera Wedding');

        $this->actingAs($this->professional)
            ->get(route('app.agreements.index'))
            ->assertSuccessful()
            ->assertSee('Rivera Wedding', false);

        $this->actingAs($this->client)
            ->get(route('app.agreements.index'))
            ->assertSuccessful()
            ->assertSee('Rivera Wedding', false);
    }

    // ── Tearing it up ────────────────────────────────────────

    /**
     * The one that writes, and the worst of them.
     *
     * A stranger could refuse two other people's contract and the log would
     * record them as the party who refused it.
     */
    public function test_a_stranger_cannot_reject_the_agreement(): void
    {
        $this->actingAs($this->stranger)
            ->post(route('app.agreements.reject', $this->agreement), ['rejection_reason' => 'No'])
            ->assertForbidden();

        $this->assertNotSame('rejected', $this->agreement->fresh()->status);
        $this->assertNull($this->agreement->fresh()->rejected_at);
    }

    public function test_a_party_can_still_reject_the_agreement(): void
    {
        $this->actingAs($this->professional)
            ->post(route('app.agreements.reject', $this->agreement), ['rejection_reason' => 'The fee is wrong'])
            ->assertRedirect();

        $this->assertSame('rejected', $this->agreement->fresh()->status);
    }

    // ── Spending somebody else's AI ──────────────────────────

    /**
     * Generating is a write and it costs money.
     *
     * It calls the AI, writes an agreement row against the booking and logs
     * it. A stranger could do all three to a booking they have nothing to do
     * with.
     */
    public function test_a_stranger_cannot_generate_an_agreement_on_a_booking(): void
    {
        $before = Agreement::where('booking_id', $this->booking->id)->count();

        $this->actingAs($this->stranger)
            ->post(route('app.agreements.generate', $this->booking))
            ->assertForbidden();

        $this->assertSame($before, Agreement::where('booking_id', $this->booking->id)->count());
    }

    public function test_a_stranger_cannot_regenerate_one_either(): void
    {
        $this->actingAs($this->stranger)
            ->post(route('app.agreements.regenerate', $this->booking))
            ->assertForbidden();
    }

    // ── Downloading ──────────────────────────────────────────

    /** This one was already guarded. Pinning it so it stays that way. */
    public function test_a_stranger_cannot_download_the_contract(): void
    {
        $this->agreement->update([
            'client_accepted_at'   => now(),
            'supplier_accepted_at' => now(),
            'status'               => 'fully_accepted',
        ]);

        $this->actingAs($this->stranger)
            ->get(route('app.agreements.download', $this->agreement))
            ->assertForbidden();
    }

    // ── An admin is still an admin ───────────────────────────

    public function test_an_admin_can_still_read_any_agreement(): void
    {
        $admin = User::factory()->create(['primary_role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs(User::findOrFail($admin->id))
            ->get(route('app.agreements.show', $this->agreement))
            ->assertSuccessful();
    }
}
