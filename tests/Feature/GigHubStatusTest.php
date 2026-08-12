<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use App\Support\GigStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checklist rows 130 to 133 and 150 — the Gig Operations Hub.
 *
 * Row 133 is the substantive one. The buckets at the top of the page and the
 * badges on the rows beneath were separate pieces of code that happened to
 * share vocabulary, and they disagreed: a `requested` booking — a proposal the
 * client has not accepted — was badged "In Progress", while the "In Progress"
 * count above it meant an accepted gig whose event is running right now. Same
 * two words, two meanings, one screen. The two tabs of the page each kept
 * their own copy of that map, so they could drift from each other too.
 *
 * The approved wording is Payment Secured / Work in Progress / Paid. Two of
 * those are claims about money, so they are only made when the money is there
 * — a badge promising payment that has not happened would be a worse fault
 * than the one reported.
 */
class GigHubStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro    = $this->account('professional');
        $this->client = $this->account('client');
    }

    private function account(string $role): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->givePermissionTo('dashboard.view');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function gig(string $status, ?\Carbon\Carbon $starts, ?\Carbon\Carbon $ends = null): Booking
    {
        $event = Event::create([
            'title' => 'Outdoor Wedding', 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'published', 'is_published' => true, 'starts_at' => $starts, 'ends_at' => $ends,
        ]);

        return Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id, 'supplier_id' => $this->pro->id,
            'created_by' => $this->client->id, 'status' => $status, 'price' => 4000,
        ])->fresh(['event']);
    }

    private function payDeposit(Booking $booking): void
    {
        Payment::create([
            'user_id' => $this->client->id,
            'amount'  => 1200,
            'status'  => 'completed',
            'gateway' => 'stripe',
            'metadata' => [
                'kind'        => 'booking_deposit',
                'event_id'    => $booking->event_id,
                'supplier_id' => $booking->supplier_id,
            ],
        ]);
    }

    private function label(Booking $booking): string
    {
        return GigStatus::for($booking)[0];
    }

    /* ── Row 133: the badge means what the bucket counts ────── */

    /**
     * The reported fault. A proposal nobody has accepted is not work in
     * progress, and calling it that is what made the badge contradict the
     * count above it.
     */
    public function test_an_unaccepted_proposal_is_not_called_work_in_progress(): void
    {
        $this->assertSame('Awaiting Client', $this->label($this->gig('requested', now()->addDays(20))));
    }

    public function test_work_in_progress_means_the_event_is_running_now(): void
    {
        $running = $this->gig('confirmed', now()->subHour(), now()->addHour());

        $this->assertSame('Work in Progress', $this->label($running));
    }

    /** Payment Secured is said only where payment was actually taken. */
    public function test_payment_secured_requires_a_payment(): void
    {
        $unpaid = $this->gig('confirmed', now()->addDays(20));
        $this->assertSame('Booked', $this->label($unpaid));

        $paid = $this->gig('confirmed', now()->addDays(25));
        $this->payDeposit($paid);
        $this->assertSame('Payment Secured', $this->label($paid->fresh(['event'])));
    }

    /** And so is Paid. A delivered gig nobody has paid for says so. */
    public function test_a_delivered_gig_with_no_payment_is_not_called_paid(): void
    {
        $this->assertSame('Awaiting Payment', $this->label($this->gig('completed', now()->subDays(10))));

        $paid = $this->gig('completed', now()->subDays(12));
        $this->payDeposit($paid);
        $this->assertSame('Paid', $this->label($paid->fresh(['event'])));
    }

    public function test_a_cancelled_gig_reads_cancelled(): void
    {
        $this->assertSame('Cancelled', $this->label($this->gig('cancelled', now()->addDays(5))));
    }

    /**
     * The two tabs kept separate copies of the map, so they could label one
     * gig two ways. They read from one source now.
     */
    public function test_both_tabs_label_the_same_gig_the_same_way(): void
    {
        $gig = $this->gig('requested', now()->addDays(20));

        $overview  = $this->actingAs($this->pro)->get(route('professional.gig-hub.index'))->assertOk();
        $contracts = $this->actingAs($this->pro)->get(route('professional.gig-hub.index', ['tab' => 'contracts']))->assertOk();

        $overview->assertSee('Awaiting Client', false);
        $contracts->assertSee('Awaiting Client', false);

        // The bucket and the badge for a running gig now use the SAME words,
        // rather than "In Progress" above and "Work in Progress" below.
        $overview->assertSee('Work in Progress', false);
        $overview->assertDontSee('>In Progress<', false);
    }

    /* ── Row 130: one today for the whole page ──────────────── */

    public function test_the_page_shares_one_today(): void
    {
        $today = $this->actingAs($this->pro)
            ->get(route('professional.gig-hub.index'))
            ->assertOk()
            ->viewData('today');

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $today);
        $this->assertTrue($today->isToday());
    }

    /* ── Rows 131, 132 and 150: the sample cards ────────────── */

    public function test_an_awarded_gig_shows_a_contract_value_not_a_pre_award_range(): void
    {
        $page = $this->actingAs($this->pro)->get(route('professional.gig-hub.index'))->assertOk();

        $page->assertSee('Contract: $4,000', false);
        $page->assertDontSee('$2k–$4k', false);
    }

    /** Row 132 — the number a professional acts on, said out loud. */
    public function test_the_crew_counter_states_what_is_left_to_fill(): void
    {
        $this->actingAs($this->pro)
            ->get(route('professional.gig-hub.index'))
            ->assertSee('2 to fill', false);
    }

    /** Row 150 — equipment is counted, food is weighed. */
    public function test_inventory_is_counted_rather_than_weighed(): void
    {
        $page = $this->actingAs($this->pro)->get(route('professional.gig-hub.index'))->assertOk();

        $page->assertSee('Chafing dishes &times; 6', false);
        $page->assertDontSee('Chafing Dishes (6)', false);
    }
}
