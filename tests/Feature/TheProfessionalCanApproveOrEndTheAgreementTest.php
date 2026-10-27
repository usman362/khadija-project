<?php

namespace Tests\Feature;

use App\Domain\Agreements\{EditAgreement, Workspace};
use App\Models\{Category, Event, Finalization, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tuesday of the agreement week: see what changed, approve or decline.
 *
 * Neither action needed a professional's copy of anything. Workspace::approve
 * works out which side the person is on, Workspace::decline is symmetric, and
 * AgreementNotices tells whoever did not do it. What was missing was the two
 * routes and the two buttons.
 *
 * The reading half is the narrower question, and the one worth getting right:
 * somebody looking at an agreement that came back to them wants to know what
 * is different from the version they said yes to, not the whole history.
 */
class TheProfessionalCanApproveOrEndTheAgreementTest extends TestCase
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

        $this->agreement = Finalization::create([
            'event_id'      => $event->id,
            'category_id'   => $service->id,
            'client_id'     => $this->client->id,
            'supplier_id'   => $this->professional->id,
            'terms_version' => 1,
            'agreed_price'  => 4000,
            'scope'         => 'Six hours of music.',
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

    // ── Approving ────────────────────────────────────────────

    public function test_the_professional_can_approve_the_version_in_front_of_them(): void
    {
        $this->actingAs($this->professional)
            ->post(route('professional.agreements.approve', $this->agreement))
            ->assertRedirect();

        $f = $this->agreement->fresh();

        $this->assertTrue(Workspace::supplierApproved($f));
        $this->assertSame(1, (int) $f->supplier_approved_version);
    }

    /** Approval is approval of a version, and the button says which. */
    public function test_the_button_names_the_version_it_approves(): void
    {
        $this->actingAs($this->professional)
            ->get(route('professional.agreements.show', $this->agreement))
            ->assertSuccessful()
            ->assertSee('Approve version 1', false);
    }

    /** Once approved there is nothing left to approve, so the button goes. */
    public function test_the_approve_button_goes_once_they_have_approved(): void
    {
        Workspace::approve($this->agreement, $this->professional);

        $this->actingAs($this->professional)
            ->get(route('professional.agreements.show', $this->agreement->fresh()))
            ->assertSuccessful()
            ->assertDontSee('Approve version 1')
            ->assertSee('It waits on', false);
    }

    public function test_a_stranger_cannot_approve(): void
    {
        $this->actingAs($this->person('professional'))
            ->post(route('professional.agreements.approve', $this->agreement))
            ->assertForbidden();

        $this->assertFalse(Workspace::supplierApproved($this->agreement->fresh()));
    }

    /** The client approves on their own screen, not through this one. */
    public function test_the_client_cannot_approve_through_the_professionals_door(): void
    {
        $this->actingAs($this->client)
            ->post(route('professional.agreements.approve', $this->agreement))
            ->assertForbidden();
    }

    // ── Ending it ────────────────────────────────────────────

    public function test_the_professional_can_end_the_agreement(): void
    {
        $this->actingAs($this->professional)
            ->post(route('professional.agreements.decline', $this->agreement))
            ->assertRedirect(route('professional.agreements.show', $this->agreement));

        $f = $this->agreement->fresh();

        $this->assertNotNull($f->declined_at);
        $this->assertSame($this->professional->id, (int) $f->declined_by);
    }

    /** Ending it is not something to do by accident. */
    public function test_ending_it_asks_first(): void
    {
        $html = $this->actingAs($this->professional)
            ->get(route('professional.agreements.show', $this->agreement))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('cannot be undone', $html);
        $this->assertStringContainsString('onsubmit="return confirm(', $html);
    }

    public function test_a_stranger_cannot_end_it(): void
    {
        $this->actingAs($this->person('professional'))
            ->post(route('professional.agreements.decline', $this->agreement))
            ->assertForbidden();

        $this->assertNull($this->agreement->fresh()->declined_at);
    }

    // ── An agreement that is already over ────────────────────

    /**
     * Workspace does nothing on a settled agreement, which is right for the
     * domain and wrong for a person: a button that reports success and
     * changes nothing is worse than one that says why not.
     */
    public function test_neither_action_works_on_an_agreement_already_ended(): void
    {
        $this->agreement->forceFill(['declined_at' => now(), 'declined_by' => $this->client->id])->save();

        $this->actingAs($this->professional)
            ->post(route('professional.agreements.approve', $this->agreement))
            ->assertStatus(409);

        $this->actingAs($this->professional)
            ->post(route('professional.agreements.decline', $this->agreement))
            ->assertStatus(409);
    }

    public function test_neither_action_works_once_the_money_has_landed(): void
    {
        $this->agreement->forceFill(['funded_at' => now()])->save();

        $this->actingAs($this->professional)
            ->post(route('professional.agreements.approve', $this->agreement))
            ->assertStatus(409);
    }

    /** And the screen does not offer buttons it would refuse. */
    public function test_a_finished_agreement_offers_no_buttons(): void
    {
        $this->agreement->forceFill(['declined_at' => now(), 'declined_by' => $this->client->id])->save();

        $html = $this->actingAs($this->professional)
            ->get(route('professional.agreements.show', $this->agreement))
            ->assertSuccessful()
            ->assertSee('This agreement was ended', false)
            ->getContent();

        $this->assertSame(
            0,
            preg_match_all('/action="[^"]*professional\/agreements\/\d+\/(approve|decline)"/i', $html),
            'A finished agreement is still offering approve or decline.',
        );
    }

    // ── Seeing what changed ──────────────────────────────────

    /**
     * The whole point of Tuesday's reading half.
     *
     * The professional approves, the client edits a term, and what the
     * professional must see on returning is that one term, from the value
     * they approved to the value now.
     */
    public function test_what_changed_since_they_approved_is_what_they_are_shown(): void
    {
        Workspace::approve($this->agreement, $this->professional);

        EditAgreement::submit(
            $this->agreement->fresh(),
            $this->client,
            ['agreed_price' => 3200] + EditAgreement::current($this->agreement->fresh()),
            'The budget came down.',
        );

        $f = $this->agreement->fresh();

        $since = EditAgreement::changesSince($f, $this->professional);

        $this->assertArrayHasKey('agreed_price', $since, 'The price change is not reported back to them.');
        $this->assertSame('4000.00', (string) $since['agreed_price']['from']);
        $this->assertSame(3200, $since['agreed_price']['to']);

        $this->actingAs($this->professional)
            ->get(route('professional.agreements.show', $f))
            ->assertSuccessful()
            ->assertSee('Changed since you approved', false)
            ->assertSee('The budget came down.', false);
    }

    /** Their approval stopped counting, so the button is back. */
    public function test_an_edit_puts_the_approval_back_in_their_court(): void
    {
        Workspace::approve($this->agreement, $this->professional);

        EditAgreement::submit(
            $this->agreement->fresh(),
            $this->client,
            ['agreed_price' => 3200] + EditAgreement::current($this->agreement->fresh()),
            'Lower budget.',
        );

        $f = $this->agreement->fresh();

        $this->assertFalse(Workspace::supplierApproved($f));
        $this->assertTrue(Workspace::waitingOn($f, $this->professional));

        $this->actingAs($this->professional)
            ->get(route('professional.agreements.show', $f))
            ->assertSee('Approve version 2', false);
    }

    /** Having approved the version in front of them, nothing is pending. */
    public function test_nothing_is_pending_when_they_approved_what_is_in_front_of_them(): void
    {
        Workspace::approve($this->agreement, $this->professional);

        $this->assertSame([], EditAgreement::changesSince($this->agreement->fresh(), $this->professional));
    }

    /**
     * Never approved anything is not the same as something changed.
     *
     * The whole agreement is new to them, and the screen shows it in full
     * further down. Reporting it as "changed since you approved" would be a
     * sentence about an approval that was never given.
     */
    public function test_a_professional_who_never_approved_is_shown_no_changes(): void
    {
        EditAgreement::submit(
            $this->agreement->fresh(),
            $this->client,
            ['agreed_price' => 3200] + EditAgreement::current($this->agreement->fresh()),
            'Lower budget.',
        );

        $this->assertSame([], EditAgreement::changesSince($this->agreement->fresh(), $this->professional));

        $this->actingAs($this->professional)
            ->get(route('professional.agreements.show', $this->agreement->fresh()))
            ->assertSuccessful()
            ->assertDontSee('Changed since you approved');
    }

    /** Two edits to one term read as one move: what they approved, to now. */
    public function test_a_term_changed_twice_reads_as_one_move(): void
    {
        Workspace::approve($this->agreement, $this->professional);

        foreach ([3600, 3200] as $price) {
            EditAgreement::submit(
                $this->agreement->fresh(),
                $this->client,
                ['agreed_price' => $price] + EditAgreement::current($this->agreement->fresh()),
                'Adjusting.',
            );
        }

        $since = EditAgreement::changesSince($this->agreement->fresh(), $this->professional);

        $this->assertSame('4000.00', (string) $since['agreed_price']['from'], 'Not the value they approved.');
        $this->assertSame(3200, $since['agreed_price']['to'], 'Not the value in front of them now.');
    }

    /** The full history is there too, under the narrow answer. */
    public function test_the_version_history_is_on_the_page(): void
    {
        EditAgreement::submit(
            $this->agreement->fresh(),
            $this->client,
            ['agreed_price' => 3200] + EditAgreement::current($this->agreement->fresh()),
            'The budget came down.',
        );

        $this->actingAs($this->professional)
            ->get(route('professional.agreements.show', $this->agreement->fresh()))
            ->assertSuccessful()
            ->assertSee('Version history', false)
            ->assertSee('Version 1', false);
    }

    /** A stranger's view of what changed is nothing at all. */
    public function test_somebody_on_neither_side_is_shown_no_changes(): void
    {
        $this->assertSame([], EditAgreement::changesSince($this->agreement, $this->person('professional')));
    }
}
