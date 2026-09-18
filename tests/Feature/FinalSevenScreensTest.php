<?php

namespace Tests\Feature;

use App\Domain\Finance\PaymentTracker;
use App\Domain\Requests\Award;
use App\Domain\Requests\ProposalDate;
use App\Domain\Requests\VenueRule;
use App\Models\Bid;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\Finalization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Sir Peter's seven screens of 18 Sep: My Events, the venue question on
 * Event Details, backup dates with their own times, Review & Submit with its
 * checks, proposals that name the date they are for, and the Payment Tracker.
 */
class FinalSevenScreensTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Category $dj;

    private Category $hall;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();

        Category::create(['name' => 'Wedding', 'slug' => 'wedding-f7', 'kind' => Category::EVENT_TYPE, 'is_active' => true]);
        $music = Category::create(['name' => 'Music', 'slug' => 'music-f7', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true]);
        $venues = Category::create(['name' => VenueRule::CATEGORY, 'slug' => 'venues-f7', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true]);
        $this->dj = Category::create(['name' => 'Wedding DJs', 'slug' => 'djs-f7', 'parent_id' => $music->id, 'kind' => Category::SERVICE, 'is_active' => true]);
        $this->hall = Category::create(['name' => 'Banquet Halls', 'slug' => 'halls-f7', 'parent_id' => $venues->id, 'kind' => Category::SERVICE, 'is_active' => true]);
    }

    private function pro(string $name): User
    {
        $u = User::factory()->create(['name' => $name]);
        $u->assignRole('professional');

        return $u;
    }

    private function event(array $attrs = []): Event
    {
        return Event::create($attrs + [
            'title' => 'Spring Wedding', 'created_by' => $this->client->id, 'client_id' => $this->client->id,
            'status' => 'published', 'is_published' => true,
            'starts_at' => now()->addDays(40)->setTime(17, 0), 'ends_at' => now()->addDays(40)->setTime(23, 0),
            'backup_dates' => [['date' => now()->addDays(47)->toDateString(), 'start' => '12:00', 'end' => '18:00']],
        ]);
    }

    private function bid(Event $e, User $pro, Category $svc, ?string $day): Bid
    {
        return Bid::create([
            'event_id' => $e->id, 'category_id' => $svc->id, 'supplier_id' => $pro->id,
            'amount' => 1000, 'status' => 'submitted',
            'available_confirmed' => $day !== null, 'confirmed_date' => $day,
        ]);
    }

    private function wizard(string $step, array $data)
    {
        return $this->actingAs($this->client)->post(route('client.bsr.save', $step), $data);
    }

    /* ── Screen 6: the date each proposal is for ──────────────── */

    public function test_a_proposal_for_a_backup_date_is_flagged_as_different(): void
    {
        $e = $this->event();
        $backup = now()->addDays(47)->toDateString();

        $this->assertSame(ProposalDate::CONFIRMED, ProposalDate::check($this->bid($e, $this->pro('A'), $this->dj, $e->starts_at->toDateString())));
        $this->assertSame(ProposalDate::DIFFERENT, ProposalDate::check($this->bid($e, $this->pro('B'), $this->hall, $backup)));
        $this->assertSame(ProposalDate::UNCONFIRMED, ProposalDate::check($this->bid($e, $this->pro('C'), $this->hall, null)));
    }

    /** One event, one date: once a service is accepted, others must match it. */
    public function test_after_one_service_is_booked_a_different_date_is_refused(): void
    {
        $e = $this->event();
        $dj = $this->bid($e, $this->pro('Velvet Beats'), $this->dj, $e->starts_at->toDateString());
        $hall = $this->bid($e, $this->pro('Grand Oak'), $this->hall, now()->addDays(47)->toDateString());

        Award::book(Award::openFinalization($dj), 'test');

        $this->assertSame(ProposalDate::MISMATCH, ProposalDate::check($hall->fresh()));
        $this->assertTrue(ProposalDate::blocks(ProposalDate::check($hall->fresh())));

        $this->expectException(ValidationException::class);
        Award::openFinalization($hall->fresh());
    }

    /** Booking on a backup date moves the event there; the old date becomes a backup. */
    public function test_booking_a_backup_date_moves_the_event_to_it(): void
    {
        $e = $this->event();
        $oldDay = $e->starts_at->toDateString();
        $backup = now()->addDays(47)->toDateString();
        $hall = $this->bid($e, $this->pro('Grand Oak'), $this->hall, $backup);

        Award::book(Award::openFinalization($hall), 'test');

        $e->refresh();
        $this->assertSame($backup . ' 12:00', $e->starts_at->format('Y-m-d H:i'));
        $this->assertSame($backup . ' 18:00', $e->ends_at->format('Y-m-d H:i'));
        $this->assertSame([$oldDay], array_column($e->backup_dates, 'date'));
        $this->assertSame(ProposalDate::CONFIRMED, ProposalDate::check($hall->fresh()));
    }

    public function test_the_event_page_shows_the_request_id_and_date_flags(): void
    {
        $e = $this->event();
        $this->bid($e, $this->pro('Grand Oak'), $this->hall, now()->addDays(47)->toDateString());

        $this->actingAs($this->client)->get(route('client.events.show', [$e, 'tab' => 'proposals']))
            ->assertOk()
            ->assertSeeText('Request ID: BR-' . str_pad((string) $e->id, 5, '0', STR_PAD_LEFT))
            ->assertSee('Different date')
            ->assertSee('View all date options');
    }

    public function test_the_reference_says_what_kind_of_request_it_is(): void
    {
        $this->assertStringStartsWith('BR-', $this->event()->reference());
        $this->assertStringStartsWith('ER-', $this->event(['source' => 'esr'])->reference());
        $this->assertStringStartsWith('DR-', $this->event(['source' => 'direct_offer'])->reference());
    }

    /* ── Screens 2 and 5: the venue question ──────────────────── */

    private function startWizard(array $services): void
    {
        $this->wizard('service', [
            'services' => $services, 'event_type' => 'Wedding', 'organization_type' => 'individual',
        ])->assertSessionHasNoErrors();
    }

    public function test_step_one_offers_the_venue_added_notice(): void
    {
        $this->startWizard([$this->dj->id]);

        $this->actingAs($this->client)->get(route('client.bsr.step', 'service'))
            ->assertOk()->assertSee('data-bw-venue-added', false);
    }

    public function test_needing_a_venue_without_a_venue_service_is_refused(): void
    {
        $this->startWizard([$this->dj->id]);

        $this->wizard('event', [
            'location_need' => VenueRule::NEED, 'preferred_locations' => ['Towson', 'Bel Air'],
        ])->assertSessionHasErrors('location_need');
    }

    public function test_ticking_a_venue_type_adds_the_service_and_saves_the_towns(): void
    {
        $this->startWizard([$this->dj->id]);

        $this->wizard('event', [
            'location_need' => VenueRule::NEED, 'preferred_locations' => ['Towson', '', 'Bel Air', 'Towson'],
            'venue_types' => [$this->hall->id],
        ])->assertSessionHasNoErrors();

        $data = session('bsr_wizard');
        $this->assertContains($this->hall->id, array_map('intval', $data['services']));
        $this->assertSame(['Towson', 'Bel Air'], $data['preferred_locations']);
        $this->assertSame('Towson, MD', $data['location']);
    }

    public function test_more_than_five_towns_are_refused(): void
    {
        $this->startWizard([$this->hall->id]);

        $this->wizard('event', [
            'location_need' => VenueRule::NEED,
            'preferred_locations' => ['A', 'B', 'C', 'D', 'E', 'F'],
        ])->assertSessionHasErrors('preferred_locations');
    }

    /* ── Screen 3: Review & Submit ────────────────────────────── */

    /**
     * Sir Peter's screen: the client said they need a venue, then went back
     * and took the venue service off. Review says so and will not submit.
     */
    public function test_review_lists_what_is_missing_and_refuses_to_submit(): void
    {
        $this->startWizard([$this->dj->id, $this->hall->id]);
        $this->wizard('event', ['location_need' => VenueRule::NEED, 'preferred_locations' => ['Towson']])->assertSessionHasNoErrors();
        $this->wizard('requirements', ['description' => 'A DJ and a hall for a wedding of about one hundred and fifty guests.']);
        $this->wizard('budget', ['budget_min' => 2000, 'budget_max' => 4000]);
        $this->wizard('proposals', []);
        $this->wizard('files', []);
        $this->wizard('availability', ['event_date' => now()->addDays(40)->toDateString(), 'event_start_time' => '17:00'])->assertSessionHasNoErrors();

        // Clean so far: nothing flagged, Submit on.
        $this->actingAs($this->client)->get(route('client.bsr.step', 'review'))
            ->assertOk()->assertSee('Review &amp; Submit', false)->assertDontSee('Action required before submitting');

        $this->wizard('service', ['services' => [$this->dj->id], 'event_type' => 'Wedding', 'organization_type' => 'individual']);

        $this->actingAs($this->client)->get(route('client.bsr.step', 'review'))
            ->assertOk()
            ->assertSee('Action required before submitting')
            ->assertSee('Go to Service Selection');

        $this->wizard('review', ['confirm' => 1])->assertSessionHasErrors('review');
        $this->assertSame(0, Event::where('client_id', $this->client->id)->where('is_published', true)->count());
    }

    /* ── Screen 1: My Events ─────────────────────────────────── */

    public function test_list_status_moves_to_past_by_itself(): void
    {
        $open = $this->event(['title' => 'Open One']);
        $past = $this->event(['title' => 'Long Gone', 'starts_at' => now()->subDays(3)]);
        $booked = $this->event(['title' => 'All Set', 'status' => 'confirmed']);
        $draft = $this->event(['title' => 'Half Written', 'status' => 'pending', 'is_published' => false]);

        $this->assertSame('open', $open->listStage());
        $this->assertSame('past', $past->listStage());
        $this->assertSame('booked', $booked->listStage());
        $this->assertSame('draft', $draft->listStage());

        Booking::create([
            'event_id' => $open->id, 'client_id' => $this->client->id, 'supplier_id' => $this->pro('P')->id,
            'created_by' => $this->client->id, 'status' => 'confirmed', 'category_id' => $this->dj->id,
        ]);
        $this->assertSame('in_progress', $open->fresh()->listStage());
    }

    public function test_the_status_filter_matches_the_status_column(): void
    {
        $this->event(['title' => 'Open One']);
        $this->event(['title' => 'Long Gone', 'starts_at' => now()->subDays(3)]);

        $past = $this->actingAs($this->client)->get(route('client.events.index', ['status' => 'past']))->assertOk()->viewData('events');
        $this->assertSame(['Long Gone'], $past->pluck('title')->all());

        $open = $this->actingAs($this->client)->get(route('client.events.index', ['status' => 'open']))->assertOk()->viewData('events');
        $this->assertSame(['Open One'], $open->pluck('title')->all());
    }

    public function test_the_type_filter_and_tiles(): void
    {
        $this->event(['title' => 'Wed', 'event_type' => 'Wedding']);
        $this->event(['title' => 'Corp', 'event_type' => 'Corporate']);
        $this->event(['title' => 'Gone', 'starts_at' => now()->subDay()]);

        $r = $this->actingAs($this->client)->get(route('client.events.index', ['type' => 'Corporate']))->assertOk();
        $this->assertSame(['Corp'], $r->viewData('events')->pluck('title')->all());
        $this->assertSame(2, $r->viewData('stats')['list_open']);
        $this->assertSame(1, $r->viewData('stats')['list_past']);
        $r->assertSee('All Event Types')->assertSee('Past Event Status')->assertSee('Reset');
    }

    /* ── Screen 7: Payment Tracker ───────────────────────────── */

    private function finalization(Event $e, array $attrs): Finalization
    {
        return Finalization::create($attrs + [
            'event_id' => $e->id, 'client_id' => $this->client->id, 'supplier_id' => $this->pro('Pay Pro')->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_payment_statuses(): void
    {
        $e = $this->event();

        $noPrice = $this->finalization($e, []);
        $pending = $this->finalization($e, ['agreed_price' => 1000]);
        $partial = $this->finalization($e, ['agreed_price' => 1200, 'deposit_amount' => 250, 'funded_at' => now(), 'status' => 'booked']);
        $cancelled = $this->finalization($e, ['agreed_price' => 900, 'status' => 'cancelled']);
        $undated = $this->finalization($this->event(['starts_at' => null, 'ends_at' => null]), ['agreed_price' => 500]);

        $rows = PaymentTracker::rows($this->client);
        $this->assertSame(
            ['pending_amount', 'pending', 'partial', 'cancelled', 'not_scheduled'],
            collect([$noPrice, $pending, $partial, $cancelled, $undated])
                ->map(fn ($f) => $rows->first(fn ($r) => $r['event_id'] === $f->event_id && $r['amount'] === ($f->agreed_price !== null ? (float) $f->agreed_price : null))['status'])
                ->all(),
        );

        $sum = PaymentTracker::summary($rows);
        $this->assertEquals(250, $sum['paid']);
        $this->assertEquals(1000 + 1200 + 500, $sum['total']);
    }

    /** Overdue only when an amount is set and its due date has passed. */
    public function test_overdue_needs_an_amount_and_a_passed_due_date(): void
    {
        $e = $this->event();
        $this->finalization($e, ['agreed_price' => 800, 'balance_due_on' => now()->subDays(2)]);
        $this->finalization($e, ['balance_due_on' => now()->subDays(2)]);
        $this->finalization($e, ['agreed_price' => 700, 'balance_due_on' => now()->addDays(5)]);

        $rows = PaymentTracker::rows($this->client);

        $this->assertSame(1, $rows->where('overdue', true)->count());
        $this->assertEquals(800, PaymentTracker::summary($rows)['overdue']);

        $this->actingAs($this->client)->get(route('client.events.index'))
            ->assertOk()->assertSee('Pay Now')->assertSee('Overdue');
    }

    /** An agreement and a booking for the same professional and service are one row. */
    public function test_a_booking_and_its_agreement_are_not_listed_twice(): void
    {
        $e = $this->event();
        $pro = $this->pro('Grand Oak');
        Finalization::create([
            'event_id' => $e->id, 'client_id' => $this->client->id, 'supplier_id' => $pro->id,
            'category_id' => $this->hall->id, 'status' => 'in_progress', 'agreed_price' => 6200,
        ]);
        Booking::create([
            'event_id' => $e->id, 'client_id' => $this->client->id, 'supplier_id' => $pro->id,
            'created_by' => $this->client->id, 'category_id' => $this->hall->id, 'status' => 'confirmed', 'price' => 6200,
        ]);

        $rows = PaymentTracker::rows($this->client);

        $this->assertCount(1, $rows);
        $this->assertEquals(6200, PaymentTracker::summary($rows)['total']);
    }

    /* ── The professional's side of screen 6 ─────────────────── */

    /** A professional picks which of the client's dates they can do, or none. */
    public function test_a_professional_names_the_date_they_can_do(): void
    {
        $this->client->getOrCreateProfile()->update(['service_area_status' => 'supported']);
        $e = $this->event();
        $e->categories()->sync([$this->dj->id]);
        $pro = $this->pro('Velvet Beats');
        $pro->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore', 'service_area_status' => 'supported']);
        $pro->serviceCategories()->sync([$this->dj->id]);
        $pro = $pro->fresh();

        $this->actingAs($pro)->post(route('professional.bid.save', [$e, 'price']), ['amount' => 900, 'sealed_ack' => 1])
            ->assertSessionHasNoErrors();

        $this->actingAs($pro)->get(route('professional.bid.step', [$e, 'availability']))
            ->assertOk()
            ->assertSee('name="confirmed_date"', false)
            ->assertSee('None of these dates work for me');

        $this->actingAs($pro)->post(route('professional.bid.save', [$e, 'availability']), [])
            ->assertSessionHasErrors('confirmed_date');
        $this->actingAs($pro)->post(route('professional.bid.save', [$e, 'availability']), ['confirmed_date' => now()->addDays(3)->toDateString()])
            ->assertSessionHasErrors('confirmed_date');
        $this->actingAs($pro)->post(route('professional.bid.save', [$e, 'availability']), ['confirmed_date' => 'none'])
            ->assertSessionHasErrors('availability_note');
        $this->actingAs($pro)->post(route('professional.bid.save', [$e, 'availability']), ['confirmed_date' => now()->addDays(47)->toDateString()])
            ->assertSessionHasNoErrors();
    }
}
