<?php

namespace Tests\Feature;

use App\Domain\Requests\EventExtensionService;
use App\Domain\Requests\RequestLifecycle;
use App\Models\Bid;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventExtension;
use App\Models\User;
use App\Notifications\EventChanged;
use App\Notifications\EventReopened;
use App\Notifications\NewEventAvailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Rule R33 — event expiration, the free grace reopen and the paid extension.
 * Checklist rows 177, 178, 216, 238.
 *
 * The spec is mostly a list of things that must NOT happen: an awarded
 * request must not expire, a failed payment must not extend anything, a new
 * deadline must not land past the event, a reactivation must not repeat the
 * first-publication blast, and a changed event must not touch anybody's
 * existing proposal. Those are the tests.
 */
class RequestLifecycleTest extends TestCase
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
        return Event::create(array_merge([
            'title'             => 'Anniversary dinner',
            'client_id'         => $this->client->id,
            'created_by'        => $this->client->id,
            'status'            => 'published',
            'is_published'      => true,
            'published_at'      => now()->subDays(5),
            'starts_at'         => now()->addDays(40),
            'proposal_deadline' => now()->addDays(3),
        ], $attributes));
    }

    private function expired(array $attributes = []): Event
    {
        return $this->event(array_merge(['proposal_deadline' => now()->subHours(2)], $attributes));
    }

    private function service(): EventExtensionService
    {
        return app(EventExtensionService::class);
    }

    /* ── §1 Status flow ─────────────────────────────────────── */

    public function test_a_live_request_is_open_for_proposals(): void
    {
        $this->assertSame(RequestLifecycle::OPEN, RequestLifecycle::statusFor($this->event()));

        // §8 — the status TEXT changes; the bidding mechanism keeps its name.
        $this->assertSame('Open for Proposals', RequestLifecycle::label($this->event()));
    }

    public function test_a_request_past_its_deadline_is_expired_not_closed(): void
    {
        $event = $this->expired();

        $this->assertSame(RequestLifecycle::EXPIRED, RequestLifecycle::statusFor($event));
        $this->assertNull($event->closed_at);
        $this->assertDatabaseHas('events', ['id' => $event->id]);   // not deleted
    }

    /**
     * §1 — the moment a professional is awarded, the deadline stops
     * mattering. Row 178: "an awarded request can still be treated as
     * expired" was the reported bug.
     */
    public function test_an_awarded_request_bypasses_expired_entirely(): void
    {
        $event = $this->expired(['supplier_id' => $this->pro->id]);

        $this->assertSame(RequestLifecycle::AWARDED, RequestLifecycle::statusFor($event));
        $this->assertFalse(RequestLifecycle::isExpired($event));
    }

    /** §1 — Expired never survives past the actual event date. */
    public function test_expired_does_not_outlive_the_event_date(): void
    {
        $event = $this->event([
            'starts_at'         => now()->subDay(),
            'proposal_deadline' => now()->subDays(3),
        ]);

        $this->assertSame(RequestLifecycle::DATE_PASSED, RequestLifecycle::statusFor($event));
    }

    /** §7 — no new proposal can come in while a listing is expired. */
    public function test_an_expired_request_accepts_no_proposals(): void
    {
        $this->assertFalse(RequestLifecycle::acceptsProposals($this->expired()));
        $this->assertTrue(RequestLifecycle::acceptsProposals($this->event()));
    }

    /* ── §2 Grace period ────────────────────────────────────── */

    public function test_the_free_reopen_is_available_for_24_hours(): void
    {
        $this->assertTrue(RequestLifecycle::inGracePeriod($this->expired(['proposal_deadline' => now()->subHours(23)])));
        $this->assertFalse(RequestLifecycle::inGracePeriod($this->expired(['proposal_deadline' => now()->subHours(25)])));
    }

    public function test_the_free_reopen_reopens_the_listing_and_costs_nothing(): void
    {
        Notification::fake();

        $event = $this->expired(['proposal_deadline' => now()->subHour()]);

        $extension = $this->service()->graceReopen($event, $this->client, now()->addDays(5));

        $this->assertTrue($extension->is_grace);
        $this->assertSame('0.00', (string) $extension->amount);
        $this->assertSame(RequestLifecycle::OPEN, RequestLifecycle::statusFor($event->fresh()));
        $this->assertNotNull($event->fresh()->reopened_at);
    }

    /** §2 — it does not count toward the three-extension cap. */
    public function test_the_free_reopen_does_not_consume_a_paid_extension(): void
    {
        Notification::fake();

        $event = $this->expired(['proposal_deadline' => now()->subHour()]);
        $this->service()->graceReopen($event, $this->client, now()->addDays(5));

        $this->assertSame(0, RequestLifecycle::paidExtensionsUsed($event));
        $this->assertSame(3, RequestLifecycle::extensionsRemaining($event));
    }

    /** Once. A client who expires again inside 24 hours does not get another. */
    public function test_the_free_reopen_is_only_available_once(): void
    {
        Notification::fake();

        $event = $this->expired(['proposal_deadline' => now()->subHour()]);
        $this->service()->graceReopen($event, $this->client, now()->addDays(5));

        $event->forceFill(['proposal_deadline' => now()->subHour()])->save();

        $this->assertFalse(RequestLifecycle::inGracePeriod($event->fresh()));
    }

    /* ── §2 Paid extension ──────────────────────────────────── */

    public function test_the_four_tiers_are_offered_with_their_prices(): void
    {
        $options = RequestLifecycle::extensionOptions($this->expired());

        $this->assertSame([3, 7, 14, 30], array_column($options, 'days'));
        $this->assertSame([1.99, 2.99, 4.99, 7.99], array_column($options, 'price'));
    }

    /**
     * §2's hard ceiling. A tier that would land past the event date is not
     * offered — refusing it after payment is the worst place to refuse it.
     */
    public function test_tiers_that_would_overrun_the_event_date_are_not_offered(): void
    {
        $event = $this->expired([
            'starts_at'         => now()->addDays(9),
            'proposal_deadline' => now()->subHour(),
        ]);

        $days = array_column(RequestLifecycle::extensionOptions($event), 'days');

        // Measured from the end of the 24h grace window, so 7 fits and 14 does not.
        $this->assertContains(3, $days);
        $this->assertContains(7, $days);
        $this->assertNotContains(14, $days);
        $this->assertNotContains(30, $days);
    }

    /** §2 — after the third, it is Close or Duplicate only. */
    public function test_the_cap_is_three_paid_extensions(): void
    {
        $event = $this->expired();

        for ($i = 0; $i < 3; $i++) {
            EventExtension::create([
                'event_id' => $event->id, 'user_id' => $this->client->id, 'days' => 7,
                'is_grace' => false, 'amount' => 2.99, 'status' => EventExtension::STATUS_COMPLETED,
            ]);
        }

        $this->assertSame(3, RequestLifecycle::paidExtensionsUsed($event));
        $this->assertSame(0, RequestLifecycle::extensionsRemaining($event));
        $this->assertFalse(RequestLifecycle::mayBuyExtension($event));
        $this->assertSame([], RequestLifecycle::extensionOptions($event));
    }

    /** Only completed extensions count — a failed payment bought nothing. */
    public function test_a_failed_payment_does_not_count_toward_the_cap(): void
    {
        $event = $this->expired();

        EventExtension::create([
            'event_id' => $event->id, 'user_id' => $this->client->id, 'days' => 7,
            'is_grace' => false, 'amount' => 2.99, 'status' => EventExtension::STATUS_FAILED,
        ]);

        $this->assertSame(0, RequestLifecycle::paidExtensionsUsed($event));
    }

    /** §2 — a payment failure leaves the event expired and grants nothing. */
    public function test_a_failed_payment_leaves_the_deadline_alone(): void
    {
        $event    = $this->expired();
        $deadline = $event->proposal_deadline;

        $extension = EventExtension::create([
            'event_id' => $event->id, 'user_id' => $this->client->id, 'days' => 7,
            'is_grace' => false, 'amount' => 2.99, 'status' => EventExtension::STATUS_PROCESSING,
        ]);

        $this->service()->fail($extension, 'Card declined.');

        $this->assertSame(EventExtension::STATUS_FAILED, $extension->fresh()->status);
        $this->assertEquals($deadline, $event->fresh()->proposal_deadline);
        $this->assertTrue(RequestLifecycle::isExpired($event->fresh()));
    }

    public function test_completing_an_extension_moves_the_deadline_and_reopens(): void
    {
        Notification::fake();

        $event = $this->expired(['proposal_deadline' => now()->subHours(2)]);

        $extension = EventExtension::create([
            'event_id' => $event->id, 'user_id' => $this->client->id, 'days' => 7,
            'is_grace' => false, 'amount' => 2.99, 'status' => EventExtension::STATUS_PROCESSING,
        ]);

        $this->service()->complete($extension, 'pi_test_123');

        $this->assertTrue($extension->fresh()->isCompleted());
        $this->assertSame(RequestLifecycle::OPEN, RequestLifecycle::statusFor($event->fresh()));
        $this->assertTrue($event->fresh()->proposal_deadline->isFuture());
    }

    /** Idempotent — a webhook that arrives twice must not extend twice. */
    public function test_completing_twice_extends_once(): void
    {
        Notification::fake();

        $event = $this->expired(['proposal_deadline' => now()->subHours(2)]);

        $extension = EventExtension::create([
            'event_id' => $event->id, 'user_id' => $this->client->id, 'days' => 7,
            'is_grace' => false, 'amount' => 2.99, 'status' => EventExtension::STATUS_PROCESSING,
        ]);

        $this->service()->complete($extension);
        $first = $event->fresh()->proposal_deadline;

        $this->service()->complete($extension);

        $this->assertEquals($first, $event->fresh()->proposal_deadline);
    }

    /**
     * The ceiling still holds at the moment of payment. A client who moved
     * the event closer while the checkout page was open gets the extension,
     * capped at the event date — not a deadline after their own event.
     */
    public function test_completion_never_pushes_the_deadline_past_the_event(): void
    {
        Notification::fake();

        $event = $this->expired([
            'starts_at'         => now()->addDays(2),
            'proposal_deadline' => now()->subHour(),
        ]);

        $extension = EventExtension::create([
            'event_id' => $event->id, 'user_id' => $this->client->id, 'days' => 30,
            'is_grace' => false, 'amount' => 7.99, 'status' => EventExtension::STATUS_PROCESSING,
        ]);

        $this->service()->complete($extension);

        $this->assertTrue($event->fresh()->proposal_deadline->lessThanOrEqualTo($event->starts_at));
    }

    /* ── §5 ESR ─────────────────────────────────────────────── */

    /**
     * §5 — an ESR's window is hours, not days, so every tier would land past
     * the event. The option is removed rather than offered and then refused.
     */
    public function test_an_esr_gets_no_paid_extension(): void
    {
        $esr = $this->expired(['source' => 'esr', 'starts_at' => now()->addHours(20)]);

        $this->assertFalse(RequestLifecycle::mayBuyExtension($esr));
        $this->assertSame([], RequestLifecycle::extensionOptions($esr));
        $this->assertSame(['close', 'duplicate', 'convert_to_ssr'], RequestLifecycle::esrOptions());
    }

    /* ── §6 Notifications ───────────────────────────────────── */

    /** First publication gets the loud notice. */
    public function test_publishing_sends_new_event_available(): void
    {
        Notification::fake();

        $event = $this->event(['is_published' => false, 'published_at' => null]);
        $this->attachService($event);

        $event->update(['is_published' => true, 'published_at' => now()]);

        Notification::assertSentTo($this->pro, NewEventAvailable::class);
    }

    /**
     * §6 — a reactivation NEVER repeats the first-publication blast. Without
     * this, extending three times buys three more rounds of notifications to
     * the same professionals, which makes the fee an advertising fee.
     */
    public function test_reopening_sends_the_lighter_notice_and_never_the_first_one(): void
    {
        Notification::fake();

        $event = $this->expired(['proposal_deadline' => now()->subHour()]);
        $this->attachService($event);

        $this->service()->graceReopen($event, $this->client, now()->addDays(5));

        Notification::assertSentTo($this->pro, EventReopened::class);
        Notification::assertNotSentTo($this->pro, NewEventAvailable::class);
    }

    /** R38 — a professional in another state hears nothing. */
    public function test_the_notice_stays_inside_the_state(): void
    {
        Notification::fake();

        $outOfState = $this->account('professional');
        $outOfState->profile->update(['state' => 'PA']);

        $event = $this->expired(['proposal_deadline' => now()->subHour()]);
        $this->attachService($event, [$this->pro, $outOfState]);

        $this->service()->graceReopen($event, $this->client, now()->addDays(5));

        Notification::assertSentTo($this->pro, EventReopened::class);
        Notification::assertNotSentTo($outOfState, EventReopened::class);
    }

    /* ── §3 Editing an expired event ────────────────────────── */

    /** A typo fix must not fire at everyone who priced the job. */
    public function test_a_minor_edit_notifies_nobody(): void
    {
        Notification::fake();

        $event = $this->expired();
        $this->bidOn($event);

        $event->update(['title' => 'Anniversary dinner (evening)', 'description' => 'Updated wording.']);

        Notification::assertNothingSent();
    }

    public function test_a_major_edit_asks_proposal_holders_to_review(): void
    {
        Notification::fake();

        $event = $this->expired();
        $this->bidOn($event);

        $event->update(['budget_max' => 500]);

        Notification::assertSentTo($this->pro, EventChanged::class);
    }

    /**
     * §3 — the proposal stays valid until the professional acts. Never
     * auto-withdrawn, auto-rejected or auto-repriced: only the person who
     * quoted the work can say whether the quote still stands.
     */
    public function test_a_major_edit_leaves_the_existing_proposal_untouched(): void
    {
        Notification::fake();

        $event = $this->expired();
        $bid   = $this->bidOn($event);

        $before = $bid->only(['amount', 'status']);

        $event->update(['starts_at' => now()->addDays(60)]);

        $this->assertSame($before, $bid->fresh()->only(['amount', 'status']));
    }

    /* ── §2 Search ranking ──────────────────────────────────── */

    /**
     * §2 — "Published Today → Extended Event → Older Active Events". A
     * reopened listing is never boosted to the top; otherwise paying
     * repeatedly buys permanent first place.
     */
    public function test_a_reopened_listing_ranks_below_todays_new_ones(): void
    {
        $newToday = $this->event(['published_at' => now()]);
        $reopened = $this->event(['published_at' => now()->subDays(10), 'reopened_at' => now()]);
        $older    = $this->event(['published_at' => now()->subDays(10)]);

        $this->assertLessThan(RequestLifecycle::rankBucket($reopened), RequestLifecycle::rankBucket($newToday));
        $this->assertLessThan(RequestLifecycle::rankBucket($older), RequestLifecycle::rankBucket($reopened));
    }

    /* ── §1/§4 MSR per-service (R12) ────────────────────────── */

    /**
     * An MSR with three services and one professional hired is one third
     * awarded — the other two lines keep expiring on their own.
     */
    public function test_awarding_one_service_on_an_msr_leaves_the_others_open(): void
    {
        $event = $this->expired();

        $a = Category::create(['name' => 'Photography R33', 'slug' => 'photography-r33', 'kind' => 'service', 'is_active' => true]);
        $b = Category::create(['name' => 'Catering R33', 'slug' => 'catering-r33', 'kind' => 'service', 'is_active' => true]);
        $event->categories()->sync([$a->id, $b->id]);

        Bid::create([
            'event_id' => $event->id, 'supplier_id' => $this->pro->id, 'category_id' => $a->id,
            'amount' => 900, 'status' => 'accepted',
        ]);

        $this->assertSame([$a->id], RequestLifecycle::awardedServiceIds($event));
        $this->assertSame([$b->id], RequestLifecycle::openServiceIds($event));
        $this->assertFalse(RequestLifecycle::fullyAwarded($event));
    }

    /* ── Helpers ────────────────────────────────────────────── */

    private function attachService(Event $event, ?array $pros = null): void
    {
        $category = Category::firstOrCreate(
            ['slug' => 'notify-service-r33'],
            ['name' => 'Notify Service R33', 'kind' => 'service', 'is_active' => true],
        );

        $event->categories()->syncWithoutDetaching([$category->id]);

        foreach ($pros ?? [$this->pro] as $pro) {
            $pro->serviceCategories()->syncWithoutDetaching([$category->id]);
        }
    }

    private function bidOn(Event $event): Bid
    {
        return Bid::create([
            'event_id' => $event->id, 'supplier_id' => $this->pro->id,
            'amount' => 1200, 'status' => 'submitted',
        ]);
    }
}
