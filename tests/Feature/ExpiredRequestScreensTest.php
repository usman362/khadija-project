<?php

namespace Tests\Feature;

use App\Domain\Requests\RequestLifecycle;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventExtension;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Rule R33 on the screens — what the client is offered on an expired request,
 * and what the professional's board does with one.
 *
 * The rule that matters most here: the page must never offer an option the
 * backend will refuse. A tier that would land past the event date, an
 * extension past the cap, or a day-based extension on an ESR — offering any
 * of them takes the client to a checkout page for something they cannot have.
 */
class ExpiredRequestScreensTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = $this->account('client');
        $this->pro    = $this->account('professional');

        Notification::fake();
    }

    private function account(string $role): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function event(array $attributes = []): Event
    {
        $event = Event::create(array_merge([
            'title'             => 'Anniversary dinner',
            'client_id'         => $this->client->id,
            'created_by'        => $this->client->id,
            'status'            => 'published',
            'is_published'      => true,
            'published_at'      => now()->subDays(5),
            'starts_at'         => now()->addDays(40),
            'proposal_deadline' => now()->addDays(3),
            'location'          => 'Baltimore',
        ], $attributes));

        $category = Category::firstOrCreate(
            ['slug' => 'r33-screen-service'],
            ['name' => 'R33 Screen Service', 'kind' => 'service', 'is_active' => true],
        );
        $event->categories()->syncWithoutDetaching([$category->id]);
        $this->pro->serviceCategories()->syncWithoutDetaching([$category->id]);

        return $event->fresh();
    }

    private function show(Event $event)
    {
        return $this->actingAs($this->client)->get(route('client.events.show', $event));
    }

    /* ── The client's page ──────────────────────────────────── */

    public function test_a_live_request_shows_no_expiry_panel(): void
    {
        $page = $this->show($this->event());

        $page->assertOk();
        $page->assertSee('Open for Proposals', false);
        $page->assertDontSee('This request has expired', false);
    }

    public function test_an_expired_request_explains_itself_and_offers_the_tiers(): void
    {
        $page = $this->show($this->event(['proposal_deadline' => now()->subHours(2)]));

        $page->assertOk();
        $page->assertSee('This request has expired', false);
        $page->assertSee('+3 days — $1.99', false);
        $page->assertSee('+30 days — $7.99', false);

        // §1 — nothing has been deleted, and the page says so.
        $page->assertSee('Nothing has been deleted', false);
    }

    /** §2 — the free reopen appears inside 24 hours and not after. */
    public function test_the_free_reopen_appears_only_inside_the_grace_window(): void
    {
        $this->show($this->event(['proposal_deadline' => now()->subHours(2)]))
            ->assertSee('Reopen at no cost', false);

        $this->show($this->event(['proposal_deadline' => now()->subHours(30)]))
            ->assertDontSee('Reopen at no cost', false);
    }

    /**
     * §2's ceiling, on the page. A 30-day option on an event nine days away
     * would be a checkout page for something the backend refuses.
     */
    public function test_tiers_that_overrun_the_event_date_are_not_shown(): void
    {
        $page = $this->show($this->event([
            'starts_at'         => now()->addDays(9),
            'proposal_deadline' => now()->subHours(2),
        ]));

        $page->assertSee('+3 days', false);
        $page->assertDontSee('+30 days', false);
    }

    /** §5 — an ESR is offered close, copy or convert. Never a day extension. */
    public function test_an_expired_esr_is_not_offered_a_paid_extension(): void
    {
        $page = $this->show($this->event([
            'source'            => 'esr',
            'starts_at'         => now()->addHours(20),
            'proposal_deadline' => now()->subHour(),
        ]));

        $page->assertSee('This request has expired', false);
        $page->assertDontSee('+3 days', false);
        $page->assertSee("Emergency requests can't be extended by days", false);
    }

    /** §2 — after the third, Close or Duplicate only. */
    public function test_after_three_extensions_the_page_offers_close_or_copy(): void
    {
        $event = $this->event(['proposal_deadline' => now()->subHours(2)]);

        for ($i = 0; $i < 3; $i++) {
            EventExtension::create([
                'event_id' => $event->id, 'user_id' => $this->client->id, 'days' => 7,
                'is_grace' => false, 'amount' => 2.99, 'status' => EventExtension::STATUS_COMPLETED,
            ]);
        }

        $page = $this->show($event);

        $page->assertSee("used all three extensions", false);
        $page->assertDontSee('+7 days', false);
        $page->assertSee('Copy as a new request', false);
    }

    /* ── The client's actions ───────────────────────────────── */

    public function test_the_free_reopen_puts_the_request_back(): void
    {
        $event = $this->event(['proposal_deadline' => now()->subHour()]);

        $this->actingAs($this->client)
            ->post(route('client.events.reopen', $event), [
                'proposal_deadline' => now()->addDays(5)->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $this->assertSame(RequestLifecycle::OPEN, RequestLifecycle::statusFor($event->fresh()));
    }

    /** §2's ceiling, enforced on the free reopen the client dates themselves. */
    public function test_the_free_reopen_refuses_a_deadline_past_the_event(): void
    {
        $event = $this->event([
            'starts_at'         => now()->addDays(4),
            'proposal_deadline' => now()->subHour(),
        ]);

        $this->actingAs($this->client)
            ->post(route('client.events.reopen', $event), [
                'proposal_deadline' => now()->addDays(10)->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasErrors('proposal_deadline');

        $this->assertTrue(RequestLifecycle::isExpired($event->fresh()));
    }

    public function test_closing_a_request_records_the_decision(): void
    {
        $event = $this->event(['proposal_deadline' => now()->subHour()]);

        $this->actingAs($this->client)
            ->post(route('client.events.close', $event))
            ->assertRedirect(route('client.events.index'));

        $this->assertNotNull($event->fresh()->closed_at);
        $this->assertSame(RequestLifecycle::CLOSED, RequestLifecycle::statusFor($event->fresh()));
    }

    /** §2 — a duplicate starts a fresh count; nothing carries over. */
    public function test_duplicating_starts_a_clean_request(): void
    {
        $event = $this->event(['proposal_deadline' => now()->subHour()]);

        EventExtension::create([
            'event_id' => $event->id, 'user_id' => $this->client->id, 'days' => 7,
            'is_grace' => false, 'amount' => 2.99, 'status' => EventExtension::STATUS_COMPLETED,
        ]);

        $this->actingAs($this->client)->post(route('client.events.duplicate', $event))->assertRedirect();

        $copy = Event::where('title', 'Anniversary dinner (copy)')->firstOrFail();

        $this->assertSame(0, RequestLifecycle::paidExtensionsUsed($copy));
        $this->assertFalse((bool) $copy->is_published);
        $this->assertNull($copy->proposal_deadline);
        $this->assertSame(3, RequestLifecycle::extensionsRemaining($copy));
    }

    public function test_another_client_cannot_touch_this_request(): void
    {
        $event    = $this->event(['proposal_deadline' => now()->subHour()]);
        $outsider = $this->account('client');

        $this->actingAs($outsider)->post(route('client.events.close', $event))->assertForbidden();
        $this->actingAs($outsider)->post(route('client.events.duplicate', $event))->assertForbidden();
    }

    /* ── The professional's board ───────────────────────────── */

    /** §7 — the board is for work you can bid on. */
    public function test_an_expired_request_leaves_the_bidding_board(): void
    {
        $live    = $this->event(['title' => 'Live request']);
        $expired = $this->event(['title' => 'Expired request', 'proposal_deadline' => now()->subHours(2)]);

        $page = $this->actingAs($this->pro)->get(route('professional.bidding-board.index'));

        $page->assertOk();
        $page->assertSee('Live request', false);
        $page->assertDontSee('Expired request', false);
    }

    /** §1 — an awarded request is not expired, whatever its deadline says. */
    public function test_an_awarded_request_is_not_shown_as_expired_to_its_client(): void
    {
        $event = $this->event([
            'proposal_deadline' => now()->subDays(2),
            'supplier_id'       => $this->pro->id,
        ]);

        $this->show($event)
            ->assertSee('Booked / Awarded', false)
            ->assertDontSee('This request has expired', false);
    }

    /** §7 — and it refuses a proposal, not just hides the listing. */
    public function test_the_bid_wizard_refuses_an_expired_request(): void
    {
        $event = $this->event(['proposal_deadline' => now()->subHours(2)]);

        $this->actingAs($this->pro)
            ->get(route('professional.bid.step', $event))
            ->assertStatus(410);
    }
}
