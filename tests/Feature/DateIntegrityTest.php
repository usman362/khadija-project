<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checklist rows 83, 84 and 89 — dates that contradicted each other.
 *
 * The reviewer flagged these three as one recurring pattern, and they are: a
 * date shown on screen was not the date of the thing it named.
 *
 *   Row 89 is the clearest. A request's Posted Date read 7:18 PM while a bid
 *   on that same request read 7:05 PM — a request taking a bid thirteen
 *   minutes before it existed. "Posted" was reading `created_at`, which is
 *   when the ROW was made, not when the request went out; and half the
 *   publish paths never stamped `published_at` at all.
 *
 *   Row 84 is the same fault in a feed. "Recent Professional Activity" listed
 *   events by `updated_at` under the words "Activity on X" — a timestamp that
 *   moves on any save whatsoever, over a sentence naming nothing that
 *   happened. So it contradicted the card beside it.
 *
 *   Row 83 asked what "Closed On" actually represents before its date
 *   sequence could be judged. The answer is the proposal deadline, and the
 *   guarantee worth holding is the one asserted at the bottom: a request's
 *   posted date precedes its deadline, and both precede nothing they should
 *   not.
 */
class DateIntegrityTest extends TestCase
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
            'title'      => 'Corporate Catering',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(30),
        ], $attributes));
    }

    /* ── Row 89: posted means posted ────────────────────────── */

    /** Publishing stamps the moment, wherever the publish came from. */
    public function test_publishing_stamps_the_moment_it_was_published(): void
    {
        $event = $this->event(['is_published' => false]);

        $this->assertNull($event->published_at);
        $this->assertNull($event->postedAt(), 'an unposted request has no posted date');

        $event->update(['is_published' => true]);

        $this->assertNotNull($event->fresh()->published_at);
    }

    /**
     * The heart of row 89. A request drafted a fortnight ago and posted today
     * was posted TODAY — `created_at` would have claimed a fortnight.
     */
    public function test_a_draft_published_later_is_posted_when_it_was_published(): void
    {
        $event = $this->event(['is_published' => false]);
        $event->forceFill(['created_at' => now()->subDays(14)])->save();

        $event->update(['is_published' => true]);

        $this->assertTrue(
            $event->fresh()->postedAt()->greaterThan(now()->subHour()),
            'posted today, not a fortnight ago',
        );
    }

    /** An already-stamped publish date is never overwritten by a later save. */
    public function test_republishing_does_not_move_the_posted_date(): void
    {
        $event = $this->event(['is_published' => true]);
        $event->forceFill(['published_at' => now()->subDays(3)])->save();

        $event->update(['title' => 'Corporate Catering (revised)']);

        $this->assertTrue(
            $event->fresh()->published_at->lessThan(now()->subDays(2)),
            'editing a live request does not repost it',
        );
    }

    /**
     * The invariant the row asked to be locked as a standing check: no bid can
     * predate the posting of the request it is a bid on.
     */
    public function test_no_bid_can_predate_the_request_it_bids_on(): void
    {
        $category = Category::firstOrCreate(
            ['slug' => 'date-integrity-service'],
            ['name' => 'Date Integrity Service', 'kind' => 'service', 'is_active' => true],
        );

        $event = $this->event(['is_published' => true, 'proposal_deadline' => now()->addDays(5)]);
        $event->categories()->syncWithoutDetaching([$category->id]);
        $this->pro->serviceCategories()->syncWithoutDetaching([$category->id]);

        $this->actingAs($this->pro)->post(route('professional.bidding-board.bid'), [
            'event_id' => $event->id,
            'amount'   => 1200,
        ])->assertSessionHasNoErrors();

        foreach (Bid::with('event')->get() as $bid) {
            $this->assertTrue(
                $bid->created_at->greaterThanOrEqualTo($bid->event->postedAt()),
                "bid {$bid->id} is timestamped before its request was posted",
            );
        }
    }

    /** The professional's gig page reports the publish date, not the row's birthday. */
    public function test_the_gig_page_shows_the_publish_date(): void
    {
        $event = $this->event(['is_published' => false]);
        $event->forceFill(['created_at' => now()->subMonths(2)])->save();
        $event->update(['is_published' => true]);

        $this->actingAs($this->pro)
            ->get(route('professional.gigs.show', $event))
            ->assertOk()
            ->assertDontSee('2 months ago', false);
    }

    /* ── Row 83: the closing date sits after the posting ───── */

    public function test_a_requests_deadline_never_precedes_its_posting(): void
    {
        $event = $this->event(['is_published' => true, 'proposal_deadline' => now()->addDays(6)]);

        $this->assertTrue($event->postedAt()->lessThan($event->proposal_deadline));
    }

    /* ── Row 84: the activity feed states what happened ─────── */

    private function activity()
    {
        return $this->actingAs($this->client)
            ->get(route('client.events.index'))
            ->assertOk()
            ->viewData('activity');
    }

    public function test_the_feed_names_the_thing_that_happened(): void
    {
        $event = $this->event(['is_published' => true]);

        Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'requested', 'price' => 900,
        ]);

        $texts = $this->activity()->pluck('text');

        $this->assertTrue($texts->contains(fn ($t) => str_contains($t, 'Proposal from')));
        $this->assertTrue($texts->contains('You posted'));
        $this->assertFalse($texts->contains(fn ($t) => str_contains($t, 'Activity on')));
    }

    /**
     * The fault itself: an unrelated save used to reorder the feed and restate
     * every timestamp, because the feed was reading `updated_at`.
     */
    public function test_editing_a_request_does_not_restate_when_it_was_posted(): void
    {
        $event = $this->event(['is_published' => true]);
        $event->forceFill(['published_at' => now()->subDays(9)])->save();

        // A save that changes nothing anyone was told about.
        $event->update(['description' => 'A clarifying sentence.']);

        $posted = $this->activity()->firstWhere('text', 'You posted');

        $this->assertNotNull($posted);
        $this->assertTrue(
            $posted['when']->lessThan(now()->subDays(8)),
            'the feed still says it was posted nine days ago',
        );
    }

    /** Nothing to report reads as nothing, not as a filled panel. */
    public function test_an_account_with_nothing_to_report_gets_an_empty_feed(): void
    {
        $this->assertCount(0, $this->activity());
        $this->actingAs($this->client)->get(route('client.events.index'))->assertSee('No recent activity', false);
    }

    /** Every entry is dated from the record it names, so none can sit in the future. */
    public function test_no_entry_is_dated_in_the_future(): void
    {
        $event = $this->event(['is_published' => true]);

        Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'supplier_id' => $this->pro->id, 'created_by' => $this->client->id,
            'status' => 'confirmed', 'price' => 900,
        ]);

        foreach ($this->activity() as $entry) {
            $this->assertTrue($entry['when']->lessThanOrEqualTo(now()), "{$entry['text']} is dated in the future");
        }
    }
}
