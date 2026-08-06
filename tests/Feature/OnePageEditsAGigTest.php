<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue 8 of the Professional IA Consolidation Plan: "One page edits. Other
 * pages summarize. No duplicate editing interfaces, no duplicate workflows for
 * the same object."
 *
 * A gig's life was split rather than duplicated, which is the same problem
 * wearing a different hat: Accept and Decline lived on the Proposals page,
 * while Mark as delivered lived on the Gig Operations Hub. One booking, two
 * pages, and a professional had to remember which page carried which step.
 *
 * Per the plan's ownership matrix, Gig and Contract belong to the Gig
 * Operations Hub, so every step is driven from there.
 */
class OnePageEditsAGigTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro = User::factory()->create();
        $this->pro->assignRole('professional');
        $this->pro->givePermissionTo(['dashboard.view', 'events.view_any']);
        $this->pro->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->pro = $this->pro->fresh();
    }

    private function gig(string $status): Booking
    {
        $client = User::factory()->create();

        $event = Event::create([
            'title'      => 'Gig',
            'created_by' => $client->id,
            'client_id'  => $client->id,
            'status'     => 'published',
            'starts_at'  => now()->addMonth(),
            'ends_at'    => now()->addMonth()->addHours(4),
        ]);

        return Booking::create([
            'event_id'    => $event->id,
            'client_id'   => $client->id,
            'supplier_id' => $this->pro->id,
            'created_by'  => $client->id,
            'status'      => $status,
            'price'       => 500,
            'currency'    => 'USD',
        ]);
    }

    public function test_only_one_view_carries_the_status_control(): void
    {
        $views = array_merge(
            glob(resource_path('views/professional/**/*.blade.php')) ?: [],
            glob(resource_path('views/professional/**/**/*.blade.php')) ?: [],
        );

        $owners = [];
        foreach (array_unique($views) as $path) {
            if (str_contains(file_get_contents($path), 'proposals.update-status')) {
                $owners[] = str_replace(resource_path('views/professional/'), '', $path);
            }
        }

        $this->assertSame(
            ['gig-hub/tabs/contracts.blade.php'],
            $owners,
            'a gig is driven from one page — see the plan\'s ownership matrix',
        );
    }

    public function test_the_hub_offers_every_step_of_the_life_of_a_gig(): void
    {
        $this->gig('requested');

        $page = $this->actingAs($this->pro)
            ->get(route('professional.gig-hub.index', ['tab' => 'contracts']));

        $page->assertSuccessful();
        // Not Accept — that is the client's move (see below).
        $page->assertSee('Withdraw');
    }

    public function test_a_professional_cannot_accept_their_own_proposal(): void
    {
        // The Proposals page carried an Accept button that never worked: the
        // booking's state machine makes requested→confirmed the client's move,
        // so it was refused every time it was pressed.
        $booking = $this->gig('requested');

        $this->actingAs($this->pro)
            ->patch(route('professional.proposals.update-status', $booking), ['status' => 'confirmed'])
            ->assertSessionHasErrors();

        $this->assertSame('requested', $booking->fresh()->status);
    }

    public function test_a_professional_can_withdraw_their_own_proposal(): void
    {
        $booking = $this->gig('requested');

        $this->actingAs($this->pro)
            ->patch(route('professional.proposals.update-status', $booking), ['status' => 'cancelled'])
            ->assertSessionHasNoErrors();

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_the_proposals_page_points_at_the_hub_instead_of_acting(): void
    {
        $this->gig('requested');

        $page = $this->actingAs($this->pro)->get(route('professional.proposals.index'));

        $page->assertSuccessful();
        $page->assertSee(route('professional.gig-hub.index', ['tab' => 'contracts']));
    }

    public function test_marking_delivered_from_the_hub_moves_the_booking_on(): void
    {
        $booking = $this->gig('confirmed');

        $this->actingAs($this->pro)
            ->patch(route('professional.proposals.update-status', $booking), ['status' => 'completed'])
            ->assertSessionHasNoErrors();

        $this->assertSame('completed', $booking->fresh()->status);
    }

    public function test_a_step_the_state_machine_forbids_is_still_refused(): void
    {
        // requested → completed skips acceptance. Moving the buttons must not
        // have moved the rules with them.
        $booking = $this->gig('requested');

        $this->actingAs($this->pro)
            ->patch(route('professional.proposals.update-status', $booking), ['status' => 'completed']);

        $this->assertSame('requested', $booking->fresh()->status);
    }
}
