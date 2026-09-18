<?php

namespace Tests\Feature;

use App\Domain\Requests\Award;
use App\Domain\Requests\ProposalDate;
use App\Domain\Requests\ServiceCoverage;
use App\Models\Bid;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Sir Peter, 2026-09-17, closing out the multi-service, multi-professional
 * bidding question.
 *
 * A Wedding Reception asking for a banquet hall, a DJ and a photo booth, with
 * proposals in every arrangement: one professional on one service, one on all
 * three, two competing on the same service, and three companies splitting the
 * request. What the client has to be able to do with that: see proposals by
 * service, compare the competing ones, see which services nobody has bid on,
 * accept service by service, and be warned about a proposal that does not hold
 * for their date.
 */
class MultiServiceProposalsReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Event $event;

    /** @var array<string, Category> */
    private array $svc = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client = $this->client->fresh();

        foreach (['hall' => 'Banquet Halls', 'dj' => 'Wedding DJs', 'booth' => '360 Photo Booths', 'cake' => 'Wedding Cakes'] as $k => $name) {
            $this->svc[$k] = Category::create([
                'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name) . '-msr',
                'kind' => Category::SERVICE, 'is_active' => true,
            ]);
        }

        $this->event = Event::create([
            'title' => 'Wedding Reception', 'created_by' => $this->client->id, 'client_id' => $this->client->id,
            'status' => 'published', 'is_published' => true, 'starts_at' => now()->addDays(40)->setTime(17, 0),
        ]);

        // Four services asked for; the cake has no proposals at all.
        $this->event->categories()->sync(collect($this->svc)->pluck('id')->all());
    }

    private function pro(string $name): User
    {
        $u = User::factory()->create(['name' => $name]);
        $u->assignRole('professional');

        return $u;
    }

    private function bid(User $pro, string $svc, int $amount, bool $dateOk = true): Bid
    {
        return Bid::create([
            'event_id' => $this->event->id, 'category_id' => $this->svc[$svc]->id,
            'supplier_id' => $pro->id, 'amount' => $amount, 'status' => 'submitted',
            'available_confirmed' => $dateOk,
        ]);
    }

    private function award(Bid $bid): void
    {
        Award::book(Award::openFinalization($bid), 'test');
    }

    /** The four arrangements Sir Peter listed, on one request. */
    private function scenario(): array
    {
        $hall   = $this->pro('Grand Oak Hall');
        $dj     = $this->pro('Velvet Beats');
        $all    = $this->pro('Evermore Events');
        $boothA = $this->pro('SnapSpin 360');
        $boothB = $this->pro('Glow Booth');

        return [
            'hallBid'   => $this->bid($hall, 'hall', 6200),
            'djBid'     => $this->bid($dj, 'dj', 1400),
            'allHall'   => $this->bid($all, 'hall', 5800, false),
            'allDj'     => $this->bid($all, 'dj', 1650, false),
            'allBooth'  => $this->bid($all, 'booth', 900, false),
            'boothA'    => $this->bid($boothA, 'booth', 750),
            'boothB'    => $this->bid($boothB, 'booth', 680, false),
        ];
    }

    private function proposalsTab(): string
    {
        return $this->actingAs($this->client)
            ->get(route('client.events.show', [$this->event, 'tab' => 'proposals']))
            ->assertOk()
            ->getContent();
    }

    /* ── Grouped by service ─────────────────────────────────── */

    public function test_proposals_are_grouped_under_the_service_they_are_for(): void
    {
        $this->scenario();

        $html = $this->proposalsTab();

        foreach ($this->svc as $service) {
            $this->assertStringContainsString('id="service-' . $service->id . '"', $html);
        }
        // Three bids on the booth, two on the hall, two on the DJ.
        $this->assertStringContainsString('Compare 3 side by side', $html);
        $this->assertStringContainsString('Compare 2 side by side', $html);
    }

    /** "If only 2 of 3 services have any bids, does the client clearly see which is uncovered?" */
    public function test_a_service_nobody_has_bid_on_is_named(): void
    {
        $this->scenario();

        $html = $this->proposalsTab();

        $this->assertStringContainsString('Still uncovered:</b> Wedding Cakes', $html);
        $this->assertStringContainsString('3 of 4', $html);
    }

    /* ── Accepting across split coverage ────────────────────── */

    public function test_three_companies_can_each_be_booked_for_their_own_service(): void
    {
        $b = $this->scenario();

        $this->award($b['hallBid']);
        $this->award($b['djBid']);
        $this->award($b['boothA']);

        $rows = ServiceCoverage::for($this->event->fresh(), Bid::where('event_id', $this->event->id)->get())
            ->keyBy(fn ($r) => $r['service']->name);

        $this->assertSame('Grand Oak Hall', $rows['Banquet Halls']['booking']->supplier->name);
        $this->assertSame('Velvet Beats', $rows['Wedding DJs']['booking']->supplier->name);
        $this->assertSame('SnapSpin 360', $rows['360 Photo Booths']['booking']->supplier->name);
        $this->assertSame(ServiceCoverage::OPEN, $rows['Wedding Cakes']['state']);
    }

    /** One service, one professional: a second award for it is refused. */
    public function test_a_service_cannot_be_booked_with_two_professionals(): void
    {
        $b = $this->scenario();
        $this->award($b['hallBid']);

        $this->expectException(ValidationException::class);

        Award::openFinalization($b['allHall']);
    }

    /** Booking one service leaves the others free to be chosen. */
    public function test_booking_one_service_does_not_close_the_others(): void
    {
        $b = $this->scenario();
        $this->award($b['hallBid']);

        $fin = Award::openFinalization($b['allBooth']);

        $this->assertNotNull($fin->id);
        $this->assertFalse($this->event->fresh()->isFullyAwarded());
    }

    /**
     * The Compare page's old bug: one booking anywhere on the request marked
     * every other proposal "not selected" and took its buttons away, including
     * proposals on services nobody had been chosen for.
     */
    public function test_compare_still_offers_the_services_that_are_open(): void
    {
        $b = $this->scenario();
        $this->award($b['hallBid']);

        $html = $this->actingAs($this->client)
            ->get(route('client.proposals.compare', [$this->event, 'service' => $this->svc['booth']->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('client.finalize.start', $b['boothA']), $html);
        $this->assertStringContainsString(route('client.finalize.start', $b['boothB']), $html);
        $this->assertStringNotContainsString(route('client.finalize.start', $b['hallBid']), $html,
            'The service filter should keep other services off this page.');
    }

    public function test_compare_hides_accept_on_a_service_already_booked(): void
    {
        $b = $this->scenario();
        $this->award($b['hallBid']);

        $html = $this->actingAs($this->client)
            ->get(route('client.proposals.compare', [$this->event, 'service' => $this->svc['hall']->id]))
            ->getContent();

        $this->assertStringNotContainsString(route('client.finalize.start', $b['allHall']), $html);
    }

    /* ── The date ───────────────────────────────────────────── */

    public function test_each_proposal_says_whether_it_holds_for_the_date(): void
    {
        $b = $this->scenario();

        $this->assertSame(ProposalDate::CONFIRMED, ProposalDate::check($b['boothA']));
        $this->assertSame(ProposalDate::UNCONFIRMED, ProposalDate::check($b['boothB']));

        $html = $this->proposalsTab();
        $this->assertStringContainsString('Confirmed for ' . $this->event->starts_at->format('M j, Y'), $html);
        $this->assertStringContainsString('Has not confirmed a date', $html);
    }

    /** Already booked elsewhere that day outranks the tick they gave. */
    public function test_a_professional_booked_elsewhere_that_day_is_a_clash(): void
    {
        $b = $this->scenario();
        $pro = $b['boothA']->supplier;

        $other = Event::create([
            'title' => 'Another party', 'created_by' => $this->client->id, 'client_id' => $this->client->id,
            'status' => 'published', 'starts_at' => $this->event->starts_at->copy()->setTime(11, 0),
        ]);
        Booking::create([
            'event_id' => $other->id, 'client_id' => $this->client->id, 'supplier_id' => $pro->id,
            'created_by' => $this->client->id, 'status' => 'confirmed',
        ]);

        $this->assertSame(ProposalDate::CLASH, ProposalDate::check($b['boothA']->fresh()));
    }

    /** The warning is on screen before the agreement goes any further. */
    public function test_the_agreement_warns_about_an_unconfirmed_date(): void
    {
        $b = $this->scenario();
        $fin = Award::openFinalization($b['boothB']);

        $this->actingAs($this->client)
            ->get(route('client.finalize.step', [$fin, 'bid']))
            ->assertOk()
            ->assertSee('Glow Booth has not confirmed they can do your date', false);
    }

    public function test_an_event_with_no_date_has_nothing_to_check(): void
    {
        $b = $this->scenario();
        $this->event->update(['starts_at' => null]);

        $this->assertSame(ProposalDate::NO_DATE, ProposalDate::check($b['boothA']->fresh()));
    }

    /* ── The Proposals page ─────────────────────────────────── */

    /** "Its stats look totaled across the whole account, not scoped to one event." */
    public function test_the_proposals_page_can_be_narrowed_to_one_request(): void
    {
        $this->scenario();

        $elsewhere = Event::create([
            'title' => 'Office party', 'created_by' => $this->client->id, 'client_id' => $this->client->id, 'status' => 'published',
        ]);
        Bid::create(['event_id' => $elsewhere->id, 'supplier_id' => $this->pro('Other Co')->id, 'amount' => 300, 'status' => 'submitted']);

        $this->actingAs($this->client)->get(route('client.proposals.index'))
            ->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['submitted'] === 8);

        $this->actingAs($this->client)->get(route('client.proposals.index', ['event' => $this->event->id]))
            ->assertOk()
            ->assertViewHas('stats', fn ($s) => $s['submitted'] === 7)
            ->assertSee('Services on this request')
            ->assertSee('<th>Service</th>', false);
    }

    /** Another client's request cannot be used to scope the page. */
    public function test_someone_elses_request_does_not_scope_the_page(): void
    {
        $this->scenario();
        $stranger = User::factory()->create();
        $stranger->assignRole('client');

        $this->actingAs($stranger->fresh())
            ->get(route('client.proposals.index', ['event' => $this->event->id]))
            ->assertOk()
            ->assertViewHas('scoped', null)
            ->assertViewHas('stats', fn ($s) => $s['submitted'] === 0);
    }

    /* ── The test data command ──────────────────────────────── */

    public function test_the_scenario_command_writes_nothing_without_force(): void
    {
        $this->artisan('demo:msr-scenario', ['client' => $this->client->email])->assertSuccessful();

        $this->assertSame(0, Event::where('client_id', $this->client->id)
            ->where('description', 'like', '%' . \App\Console\Commands\DemoMultiServiceScenario::MARKER . '%')->count());
    }
}
