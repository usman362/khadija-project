<?php

namespace Tests\Feature;

use App\Domain\Money\{FeeSchedule, Ledger};
use App\Domain\Requests\RequestLifecycle;
use App\Models\{Event, LedgerEntry, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * A fee on the pricing page that nothing charges is a lie on a page.
 *
 * The Pricing and Rates page went up on 20 October listing a $1.99 extension
 * and a $2.99 repost. Checking afterwards: PostingFee::recordExtension existed
 * and was never called from anywhere, so a paid extension was real money that
 * the A1 ledger could not see. It sat in `event_extensions` alone, outside what
 * a client owed and outside what GigResource had earned, which is the one thing
 * rule A1 exists to prevent.
 *
 * The repost turned out to be charged already, through the posting fee on the
 * new copy, which is what Peter's lifecycle document describes. What it was
 * missing was the link back to the original.
 */
class ExtensionsAndRepostsAreRealMoneyTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Carbon::setTestNow(Carbon::now()->setTime(12, 0));

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = User::findOrFail($this->client->id);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function published(array $attrs = []): Event
    {
        return Event::create($attrs + [
            'title'        => 'Rivera Wedding',
            'client_id'    => $this->client->id,
            'created_by'   => $this->client->id,
            'status'       => 'published',
            'is_published' => true,
            'starts_at'    => now()->addDays(60),
        ]);
    }

    // ── Extensions ───────────────────────────────────────────

    /** One price, and it is the one the pricing page quotes. */
    public function test_every_extension_length_costs_what_the_page_says(): void
    {
        $quoted = FeeSchedule::find('request_extension')['amount'];

        $event = $this->published([
            'proposal_deadline' => now()->subHour(),
            'starts_at'         => now()->addDays(90),
        ]);

        $options = RequestLifecycle::extensionOptions($event);

        $this->assertNotEmpty($options, 'No extension is offered at all.');

        foreach ($options as $option) {
            $this->assertSame($quoted, $option['price']);
        }
    }

    /** The lengths Khadijah set, not the old ladder. */
    public function test_the_lengths_are_the_ones_agreed(): void
    {
        $event = $this->published([
            'proposal_deadline' => now()->subHour(),
            'starts_at'         => now()->addDays(90),
        ]);

        $this->assertSame(
            [7, 14, 21],
            array_column(RequestLifecycle::extensionOptions($event), 'days'),
        );
    }

    /**
     * And the money lands in the ledger.
     *
     * This is the gap that mattered: the amount lived only in
     * `event_extensions`, so a paid extension never appeared in what a client
     * owed or in what GigResource earned.
     */
    public function test_a_paid_extension_is_recorded_in_the_ledger(): void
    {
        $event = $this->published();

        \App\Domain\Money\PostingFee::recordExtension($event, 14, 1.99);

        $entry = LedgerEntry::where('event_id', $event->id)
            ->whereJsonContains('metadata->reason', 'extension')
            ->first();

        $this->assertNotNull($entry, 'A paid extension left no trace in the ledger.');
        $this->assertSame('1.99', (string) $entry->amount);
        $this->assertSame(14, $entry->metadata['days']);
        $this->assertSame($this->client->id, $entry->party_user_id);
    }

    /**
     * Secured, not pending.
     *
     * An extension is paid for before it is granted, unlike a posting fee,
     * which is owed the moment a request goes public and collected afterwards.
     */
    public function test_an_extension_is_recorded_as_money_already_taken(): void
    {
        $event = $this->published();

        $entry = \App\Domain\Money\PostingFee::recordExtension($event, 7, 1.99);

        $this->assertSame(LedgerEntry::SECURED, $entry->state);
    }

    /** It adds to the posting fee rather than replacing it. */
    public function test_an_extension_does_not_swallow_the_posting_fee(): void
    {
        $event = $this->published();

        \App\Domain\Money\PostingFee::recordExtension($event, 7, 1.99);

        $fees = Ledger::forEvent($event->fresh())
            ->where('entry_type', LedgerEntry::PLATFORM_FEE);

        $this->assertCount(2, $fees, 'The posting fee and the extension are two charges.');
        $this->assertEqualsWithDelta(4.98, $fees->sum(fn ($e) => (float) $e->amount), 0.01);
    }

    // ── Reposts ──────────────────────────────────────────────

    /**
     * A repost is charged, through being a new posting.
     *
     * Which is what the lifecycle document describes: "Repost Event: $2.99;
     * create a new posting ID and response round." There is no separate repost
     * charge to add, and adding one would bill it twice.
     */
    public function test_publishing_a_repost_is_charged_like_any_posting(): void
    {
        $original = $this->published();

        $this->actingAs($this->client)
            ->post(route('client.events.duplicate', $original))
            ->assertRedirect();

        $copy = Event::where('reposted_from_id', $original->id)->firstOrFail();

        $this->assertSame(0, LedgerEntry::where('event_id', $copy->id)->count(), 'A draft owes nothing.');

        $copy->update(['is_published' => true, 'status' => 'published']);

        $fee = LedgerEntry::where('event_id', $copy->id)
            ->where('entry_type', LedgerEntry::PLATFORM_FEE)
            ->first();

        $this->assertNotNull($fee);
        $this->assertSame(FeeSchedule::find('repost')['amount'], (float) $fee->amount);
    }

    /** And it remembers what it came from. */
    public function test_a_repost_is_linked_to_the_original(): void
    {
        $original = $this->published();

        $this->actingAs($this->client)->post(route('client.events.duplicate', $original));

        $copy = Event::where('reposted_from_id', $original->id)->firstOrFail();

        $this->assertTrue($copy->isRepost());
        $this->assertSame($original->id, $copy->repostedFrom->id);
        $this->assertTrue($original->fresh()->reposts->contains($copy));
    }

    /** The original is not a repost of anything. */
    public function test_a_first_posting_is_not_a_repost(): void
    {
        $this->assertFalse($this->published()->isRepost());
    }

    /**
     * Deleting the original empties the link rather than taking the repost.
     *
     * A repost is its own posting, with its own fee paid and possibly its own
     * bookings.
     */
    public function test_losing_the_original_does_not_lose_the_repost(): void
    {
        $original = $this->published();

        $this->actingAs($this->client)->post(route('client.events.duplicate', $original));

        $copy = Event::where('reposted_from_id', $original->id)->firstOrFail();

        $original->delete();

        $this->assertNotNull($copy->fresh(), 'The repost went with it.');
        $this->assertNull($copy->fresh()->reposted_from_id);
    }
}
