<?php

namespace Tests\Feature;

use App\Domain\Badges\ClientBadges;
use App\Models\{Booking, Event, Finalization, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Client badges are awarded from the record, or not at all.
 *
 * The four badges and their rules are Sir Peter's PM-14 spec (Sep 5):
 * Verified Client, Frequent Planner, Prompt Payer, Community Voice. All
 * automatic, flat icons in brand blue, shown on the client's own profile and
 * not on the dashboard.
 *
 * The thing this must never do is what the verification badges once did: a
 * seeder stamped a timestamp and ten profiles wore a licence badge with no
 * document behind them. So there is no award method here and nothing to
 * stamp — a badge is counted from the record every time it is shown, which
 * also means it cannot outlive the thing that earned it.
 */
class ClientBadgesAreEarnedTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = $this->client->fresh();
    }

    private function pro(): User
    {
        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');

        return $pro;
    }

    private function event(): Event
    {
        return Event::create([
            'title' => 'Job', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'completed',
            'starts_at' => now()->subMonth(),
        ]);
    }

    private function booking(User $pro, string $status = 'completed', ?Event $event = null): Booking
    {
        return Booking::create([
            'event_id' => ($event ?? $this->event())->id, 'client_id' => $this->client->id,
            'supplier_id' => $pro->id, 'created_by' => $this->client->id,
            'status' => $status, 'price' => 500,
        ]);
    }

    private function keys(): array
    {
        return ClientBadges::earnedBy($this->client->fresh())->pluck('key')->all();
    }

    public function test_the_four_badges_are_the_pm14_spec(): void
    {
        $this->assertSame(
            ['Verified Client', 'Frequent Planner', 'Prompt Payer', 'Community Voice'],
            collect(config('badges.client'))->pluck('name')->all(),
        );

        foreach (config('badges.client') as $b) {
            $this->assertSame('#2563eb', $b['colour'], "{$b['name']} is not brand blue");
            $this->assertStringStartsWith('<svg', $b['icon'], "{$b['name']} is not a flat icon");
        }
    }

    public function test_a_new_client_has_earned_nothing(): void
    {
        $this->assertSame([], $this->keys());
    }

    /**
     * There is no client ID verification yet, so nobody is a Verified Client.
     * It is not approximated from email or address checks.
     */
    public function test_nobody_is_a_verified_client_without_id_verification(): void
    {
        $this->client->forceFill(['email_verified_at' => now()])->save();

        $this->assertNotContains('verified-client', $this->keys());
    }

    /* ── Frequent Planner: the 5th completed event ─────────── */

    public function test_five_completed_events_earn_frequent_planner(): void
    {
        foreach (range(1, 5) as $i) {
            $this->booking($this->pro());
        }

        $this->assertContains('frequent-planner', $this->keys());
    }

    public function test_four_is_not_enough(): void
    {
        foreach (range(1, 4) as $i) {
            $this->booking($this->pro());
        }

        $this->assertNotContains('frequent-planner', $this->keys());
    }

    /** Events, not bookings: five professionals at one event is one event. */
    public function test_several_bookings_on_one_event_count_once(): void
    {
        $event = $this->event();

        foreach (range(1, 5) as $i) {
            $this->booking($this->pro(), 'completed', $event);
        }

        $this->assertNotContains('frequent-planner', $this->keys());
    }

    /** Completed, not just booked. */
    public function test_unfinished_bookings_earn_nothing(): void
    {
        foreach (range(1, 5) as $i) {
            $this->booking($this->pro(), 'confirmed');
        }

        $this->assertSame([], $this->keys());
    }

    /* ── Prompt Payer: on time, and nothing late this month ── */

    private function agreement(?string $due, ?string $funded): void
    {
        Finalization::create([
            'event_id' => $this->event()->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro()->id, 'status' => 'complete',
            'agreed_price' => 500,
            'balance_due_on' => $due,
            'funded_at' => $funded,
        ]);
    }

    public function test_paying_on_time_earns_prompt_payer(): void
    {
        $this->agreement(now()->subDays(10)->toDateString(), now()->subDays(12)->toDateTimeString());

        $this->assertContains('prompt-payer', $this->keys());
    }

    /** "Revoked if a late payment occurs." */
    public function test_a_late_payment_this_month_takes_it_away(): void
    {
        $this->agreement(now()->subDays(40)->toDateString(), now()->subDays(41)->toDateTimeString());
        $this->agreement(now()->subDays(10)->toDateString(), now()->subDays(2)->toDateTimeString());

        $this->assertNotContains('prompt-payer', $this->keys());
    }

    /** An unpaid balance that has fallen due is late too. */
    public function test_an_unpaid_balance_past_due_takes_it_away(): void
    {
        $this->agreement(now()->subDays(40)->toDateString(), now()->subDays(41)->toDateTimeString());
        $this->agreement(now()->subDays(3)->toDateString(), null);

        $this->assertNotContains('prompt-payer', $this->keys());
    }

    /** "Re-evaluated monthly, not permanent": an old late payment no longer counts. */
    public function test_it_comes_back_after_a_clean_month(): void
    {
        $this->agreement(now()->subDays(60)->toDateString(), now()->subDays(50)->toDateTimeString());
        $this->agreement(now()->subDays(10)->toDateString(), now()->subDays(12)->toDateTimeString());

        $this->assertContains('prompt-payer', $this->keys());
    }

    /** No due date: cannot be late, and is not counted as on time either. */
    public function test_an_agreement_with_no_due_date_counts_neither_way(): void
    {
        $this->agreement(null, now()->subDays(2)->toDateTimeString());

        $this->assertNotContains('prompt-payer', $this->keys());
    }

    /* ── Community Voice: the 3rd submitted review ─────────── */

    private function review(): void
    {
        $pro = $this->pro();

        DB::table('reviews')->insert([
            'reviewer_id' => $this->client->id, 'reviewee_id' => $pro->id,
            'booking_id' => $this->booking($pro)->id, 'rating' => 5,
            'comment' => 'Great work.', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_three_reviews_earn_community_voice(): void
    {
        $this->review();
        $this->review();
        $this->assertNotContains('community-voice', $this->keys());

        $this->review();
        $this->assertContains('community-voice', $this->keys());
    }

    /* ── The rules are the Owner's, and they are not in the code ── */

    public function test_the_rules_live_in_config(): void
    {
        config(['badges.client' => [[
            'key' => 'tester', 'name' => 'Tester', 'blurb' => 'x', 'icon' => '★',
            'rule' => 'events_completed', 'need' => 2,
        ]]]);

        $this->booking($this->pro());
        $this->assertSame([], $this->keys());

        $this->booking($this->pro());
        $this->assertSame(['tester'], $this->keys());
    }

    /** Nothing can hand one out: there is no method that awards. */
    public function test_a_badge_cannot_be_granted_by_hand(): void
    {
        $this->assertFalse(method_exists(ClientBadges::class, 'award'));
        $this->assertFalse(method_exists(ClientBadges::class, 'grant'));

        $this->assertFalse(
            \Illuminate\Support\Facades\Schema::hasTable('user_badges'),
            'Badges in a table can be written by a seeder — which is exactly how the licence badges went wrong.',
        );
    }

    /* ── Where they appear ──────────────────────────────────── */

    /** PM-14: on the client's own profile. */
    public function test_the_profile_shows_the_badges(): void
    {
        foreach (range(1, 5) as $i) {
            $this->booking($this->pro());
        }

        $html = $this->actingAs($this->client->fresh())
            ->get(route('client.profile.index'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('Frequent Planner', $html);
        $this->assertStringContainsString('hexb-crest', $html);
        // Not yet won is shown, drained, rather than hidden.
        $this->assertStringContainsString('is-locked', $html);
        $this->assertStringContainsString('Community Voice', $html);
    }

    /** PM-14: "NOT on the main dashboard, to avoid clutter." (OA-134) */
    public function test_the_dashboard_has_no_badge_panel(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.dashboard'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringNotContainsString('Client Badges', $html);
        $this->assertStringNotContainsString('Badges Earned', $html);
        $this->assertStringNotContainsString('Frequent Planner', $html);
    }

    /** The colour and the icon are settings rather than markup. */
    public function test_the_colour_and_icon_come_from_config(): void
    {
        config(['badges.client' => [[
            'key' => 'frequent-planner', 'name' => 'Frequent Planner', 'blurb' => 'x',
            'icon' => '✿', 'colour' => '#123456',
            'rule' => 'events_completed', 'need' => 1,
        ]]]);

        $this->booking($this->pro());

        $html = $this->actingAs($this->client->fresh())
            ->get(route('client.profile.index'))
            ->getContent();

        $this->assertStringContainsString('#123456', $html);
        $this->assertStringContainsString('✿', $html);
    }
}
