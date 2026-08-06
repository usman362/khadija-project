<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use App\Support\GigStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Issue 6 of the Professional IA Consolidation Plan: the same numbers were
 * worked out separately on each page and had drifted.
 *
 * The Gig Operations Hub called a gig "In Progress" when its event was running
 * at that moment. Contracts called a gig "Active" when it was confirmed *or*
 * still only requested — which counts proposals nobody has accepted. Those two
 * are now tabs of the same page, so a professional sees both words at once.
 */
class GigStatsAgreeAcrossPagesTest extends TestCase
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

    private function gig(string $status, ?string $when = null): Booking
    {
        $client = User::factory()->create();

        $starts = match ($when) {
            'now'    => now()->subHour(),
            'future' => now()->addMonth(),
            default  => now()->subMonth(),
        };

        $event = Event::create([
            'title'      => 'Gig',
            'created_by' => $client->id,
            'client_id'  => $client->id,
            'status'     => 'published',
            'starts_at'  => $starts,
            'ends_at'    => $when === 'now' ? now()->addHour() : $starts->copy()->addHours(4),
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

    public function test_the_words_add_up(): void
    {
        $this->gig('requested');
        $this->gig('confirmed', 'future');
        $this->gig('completed');
        $this->gig('cancelled');

        $s = GigStats::forProfessional($this->pro);

        $this->assertSame(4, $s['total']);
        $this->assertSame(1, $s['pending']);
        $this->assertSame(1, $s['booked']);
        $this->assertSame(1, $s['completed']);
        $this->assertSame(1, $s['cancelled']);

        // active is what is still live, and nothing else
        $this->assertSame(2, $s['active']);
        $this->assertSame(
            $s['total'],
            $s['pending'] + $s['booked'] + $s['completed'] + $s['cancelled'],
            'every gig lands in exactly one of the four statuses',
        );
    }

    public function test_in_progress_means_the_event_is_running_not_merely_accepted(): void
    {
        $this->gig('confirmed', 'future');   // accepted, starts next month
        $this->gig('confirmed', 'now');      // accepted, happening right now

        $s = GigStats::forProfessional($this->pro);

        $this->assertSame(2, $s['booked'], 'both are accepted');
        $this->assertSame(1, $s['inProgress'], 'only one is actually running');
    }

    public function test_an_unaccepted_proposal_is_active_but_not_booked(): void
    {
        $this->gig('requested');

        $s = GigStats::forProfessional($this->pro);

        $this->assertSame(1, $s['active']);
        $this->assertSame(0, $s['booked']);
        $this->assertSame(0, $s['inProgress']);
    }

    public function test_the_hub_and_its_contracts_tab_report_the_same_active_count(): void
    {
        $this->gig('requested');
        $this->gig('confirmed', 'now');
        $this->gig('completed');

        $hub = $this->actingAs($this->pro)
            ->get(route('professional.gig-hub.index'))->viewData('stats');

        $contracts = $this->actingAs($this->pro)
            ->get(route('professional.gig-hub.index', ['tab' => 'contracts']))->viewData('tabCounts');

        $this->assertSame($hub['active'], $contracts['active'], 'two tabs of one page must not disagree');
        $this->assertSame($hub['completed'], $contracts['completed']);
    }

    public function test_the_proposals_page_agrees_with_the_hub(): void
    {
        $this->gig('requested');
        $this->gig('confirmed', 'now');
        $this->gig('completed');

        $hub = $this->actingAs($this->pro)
            ->get(route('professional.gig-hub.index'))->viewData('stats');

        $proposals = $this->actingAs($this->pro)
            ->get(route('professional.proposals.index'))->viewData('stats');

        $this->assertSame($hub['in_progress'], $proposals['in_progress']);
        $this->assertSame($hub['completed'], $proposals['completed']);
        $this->assertSame(3, $proposals['all']);
    }

    public function test_another_professionals_gigs_are_never_counted(): void
    {
        $this->gig('confirmed', 'now');

        $other = User::factory()->create();
        Booking::create([
            'event_id'    => Event::first()->id,
            'client_id'   => $other->id,
            'supplier_id' => $other->id,
            'created_by'  => $other->id,
            'status'      => 'confirmed',
            'price'       => 999,
            'currency'    => 'USD',
        ]);

        $this->assertSame(1, GigStats::forProfessional($this->pro)['total']);
    }
}
