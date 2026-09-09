<?php

namespace Tests\Feature;

use App\Domain\Badges\ClientBadges;
use App\Models\{Booking, Event, Finalization, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Client badges are awarded from the record, or not at all.
 *
 * The dashboard card said badges "are awarded from what you actually do on
 * GigResource — events completed, paying on time, and coming back to the same
 * professionals" and then never awarded one. These are those three sentences,
 * measured against what the platform already records.
 *
 * The thing this must never do is what the verification badges once did: a
 * seeder stamped a timestamp and ten profiles wore a licence badge with no
 * document behind them. So there is no award method here and nothing to
 * stamp — a badge is counted from the bookings and agreements every time it is
 * shown, which also means it cannot outlive the thing that earned it.
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

    private function booking(User $pro, string $status = 'completed'): Booking
    {
        $event = Event::create([
            'title' => 'Job', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'completed',
            'starts_at' => now()->subMonth(),
        ]);

        return Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $pro->id, 'created_by' => $this->client->id,
            'status' => $status, 'price' => 500,
        ]);
    }

    private function keys(): array
    {
        return ClientBadges::earnedBy($this->client->fresh())->pluck('key')->all();
    }

    public function test_a_new_client_has_earned_nothing(): void
    {
        $this->assertSame([], $this->keys());
    }

    public function test_one_completed_event_earns_the_first_one(): void
    {
        $this->booking($this->pro());

        $this->assertContains('first-event', $this->keys());
        $this->assertNotContains('seasoned-host', $this->keys());
    }

    /** A booking that has not completed has not been done. */
    public function test_an_unfinished_booking_earns_nothing(): void
    {
        $this->booking($this->pro(), 'confirmed');

        $this->assertSame([], $this->keys());
    }

    public function test_five_completed_events_earn_the_second(): void
    {
        foreach (range(1, 5) as $i) {
            $this->booking($this->pro());
        }

        $this->assertContains('seasoned-host', $this->keys());
    }

    /** Coming back is about people, not bookings. */
    public function test_booking_the_same_professional_twice_counts_once(): void
    {
        $pro = $this->pro();
        $this->booking($pro);
        $this->booking($pro);

        $this->assertContains('they-came-back', $this->keys());
    }

    public function test_two_different_professionals_is_not_coming_back(): void
    {
        $this->booking($this->pro());
        $this->booking($this->pro());

        $this->assertNotContains('they-came-back', $this->keys());
    }

    /* ── Paid on time ───────────────────────────────────────── */

    private function agreement(?string $due, ?string $funded): void
    {
        $event = Event::create([
            'title' => 'Agreed job', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'completed',
            'starts_at' => now()->subMonth(),
        ]);

        Finalization::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro()->id, 'status' => 'complete',
            'agreed_price' => 500,
            'balance_due_on' => $due,
            'funded_at' => $funded,
        ]);
    }

    public function test_paying_before_the_due_date_counts(): void
    {
        foreach (range(1, 3) as $i) {
            $this->agreement(now()->subDays(10)->toDateString(), now()->subDays(12)->toDateTimeString());
        }

        $this->assertContains('pays-on-time', $this->keys());
    }

    public function test_paying_late_does_not(): void
    {
        foreach (range(1, 3) as $i) {
            $this->agreement(now()->subDays(10)->toDateString(), now()->subDays(2)->toDateTimeString());
        }

        $this->assertNotContains('pays-on-time', $this->keys());
    }

    /**
     * An agreement with no due date cannot be late — and must not be counted
     * as early either. That would be a badge for a deadline nobody set.
     */
    public function test_an_agreement_with_no_due_date_counts_neither_way(): void
    {
        foreach (range(1, 3) as $i) {
            $this->agreement(null, now()->subDays(2)->toDateTimeString());
        }

        $this->assertNotContains('pays-on-time', $this->keys());
    }

    /* ── The rules are the Owner's, and they are not in the code ── */

    public function test_the_rules_live_in_config(): void
    {
        $this->assertNotEmpty(config('badges.client'));

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

        // And none of it is stored, so nothing can be stamped onto an account.
        $this->assertFalse(
            \Illuminate\Support\Facades\Schema::hasTable('user_badges'),
            'Badges in a table can be written by a seeder — which is exactly how the licence badges went wrong.',
        );
    }

    /* ── On the page ────────────────────────────────────────── */

    public function test_the_dashboard_shows_what_was_earned(): void
    {
        $this->booking($this->pro());

        $this->actingAs($this->client->fresh())
            ->get(route('client.dashboard'))
            ->assertSuccessful()
            ->assertSee('First Event');
    }

    /** And what is still to do, rather than an empty panel. */
    public function test_the_dashboard_shows_the_next_one(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.dashboard'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('Completed your first event', $html);
        $this->assertStringContainsString('0 of 1', $html);
    }
}
