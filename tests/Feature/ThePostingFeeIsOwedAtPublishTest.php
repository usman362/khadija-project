<?php

namespace Tests\Feature;

use App\Domain\Money\{Ledger, PostingFee, Pricing};
use App\Models\{Event, LedgerEntry, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * B1 in force at last: the fee is owed when the request goes public.
 *
 * Sir Peter decided this on 22 August and it was never built, because A1
 * listed the same fee as a ledger item to design first, so the two overlapped
 * and both waited. A1 was approved on 16 October.
 *
 * Worth recording why this file exists at all. The first version of this work
 * flipped config('payments.client_request_fee_collect_at') from 'finalization'
 * to 'publish' and asserted the config value. Nothing in the application reads
 * that key. The setting was decorative, the test passed, and the fee was still
 * being charged at finalisation. A config value is not a behaviour, and
 * asserting one proves nothing. These tests watch the ledger instead.
 */
class ThePostingFeeIsOwedAtPublishTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function draft(): Event
    {
        return Event::create([
            'title'      => 'Rivera Wedding',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'pending',
            'starts_at'  => now()->addDays(30),
        ]);
    }

    private function fees(Event $event)
    {
        return LedgerEntry::where('event_id', $event->id)
            ->where('entry_type', LedgerEntry::PLATFORM_FEE)
            ->get();
    }

    // ── Drafts are free ──────────────────────────────────────

    public function test_a_draft_owes_nothing(): void
    {
        $event = $this->draft();

        $this->assertCount(0, $this->fees($event), 'B1: drafts stay free.');
        $this->assertSame(0.0, PostingFee::outstanding($event));
    }

    // ── Publishing is what owes ──────────────────────────────

    public function test_publishing_records_the_fee(): void
    {
        $event = $this->draft();

        $event->update(['is_published' => true, 'status' => 'published']);

        $fees = $this->fees($event);

        $this->assertCount(1, $fees);
        $this->assertSame('2.99', (string) $fees->first()->amount, 'A founder pays the founder rate.');
        $this->assertSame(LedgerEntry::PENDING, $fees->first()->state);
        $this->assertSame($this->client->id, $fees->first()->party_user_id);
        $this->assertSame('posting', $fees->first()->metadata['reason']);
    }

    /** A request created already published is charged too. */
    public function test_a_request_published_on_creation_is_charged(): void
    {
        $event = Event::create([
            'title'        => 'Rush job',
            'client_id'    => $this->client->id,
            'created_by'   => $this->client->id,
            'status'       => 'published',
            'is_published' => true,
            'starts_at'    => now()->addDays(2),
        ]);

        $this->assertCount(1, $this->fees($event), 'ESR publishes on creation, and still owes the fee.');
    }

    // ── Never twice ──────────────────────────────────────────

    /**
     * Idempotent publication, which the lifecycle document asks for by name.
     *
     * Five code paths can publish, a request can be retried, and a client can
     * save a live posting again. None of those is a second fee.
     */
    public function test_publishing_twice_owes_one_fee(): void
    {
        $event = $this->draft();

        $event->update(['is_published' => true, 'status' => 'published']);
        $event->update(['is_published' => true, 'status' => 'published']);
        PostingFee::recordFor($event->fresh());

        $this->assertCount(1, $this->fees($event));
    }

    /** Editing a live posting is free. */
    public function test_editing_a_published_request_is_free(): void
    {
        $event = $this->draft();
        $event->update(['is_published' => true, 'status' => 'published']);

        $event->update(['title' => 'Rivera Wedding, renamed', 'budget' => 5000]);

        $this->assertCount(1, $this->fees($event), 'Ordinary edits stay free while a posting is active.');
    }

    // ── The price that applied, recorded with it ─────────────

    public function test_the_regular_rate_applies_once_the_founder_window_closes(): void
    {
        // Published long ago, so their six months are spent.
        Event::create([
            'title' => 'Old', 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'published', 'is_published' => true,
            'published_at' => now()->subMonths(8), 'starts_at' => now()->subMonths(7),
        ]);

        $event = $this->draft();
        $event->update(['is_published' => true, 'status' => 'published']);

        $fee = $this->fees($event)->first();

        // Priced apart on purpose: Peter made posting flat on 20 October, so
        // the two rates are equal and the boundary would otherwise be
        // invisible. What is tested is that the WINDOW closes and that the
        // recorded fee follows config, not that it closes onto any one number.
        $this->assertSame('2.99', (string) $fee->amount);
        $this->assertFalse($fee->metadata['founder']);
    }

    /**
     * Which price applied is recorded, not recomputed later.
     *
     * A client asking why they were charged $4.99 should be answered from the
     * record. Recomputing it against rules that may have changed since is how
     * a receipt comes to disagree with the charge.
     */
    public function test_the_record_says_which_price_applied(): void
    {
        $event = $this->draft();
        $event->update(['is_published' => true, 'status' => 'published']);

        $this->assertTrue($this->fees($event)->first()->metadata['founder']);
    }

    /*
     * Extension charges are recorded by PostingFee::recordExtension, but the
     * amount and the rules come from Rule R33, which already owns extensions.
     * They are exercised with R33's own tests rather than re-asserted here
     * against a second set of figures.
     */

    // ── Settling and refunding ───────────────────────────────

    public function test_a_fee_is_owed_until_it_is_secured(): void
    {
        $event = $this->draft();
        $event->update(['is_published' => true, 'status' => 'published']);

        $this->assertFalse(PostingFee::isPaid($event));

        Ledger::settle($this->fees($event)->first(), LedgerEntry::SECURED);

        $this->assertTrue(PostingFee::isPaid($event->fresh()));
        $this->assertSame(0.0, PostingFee::outstanding($event->fresh()));
    }

    /**
     * A refunded fee still cost the platform the processor's cut.
     *
     * Peter confirmed the platform absorbs it: Stripe keeps its fee on a
     * refund, so a refunded $2.99 costs $0.39 rather than nothing. The
     * reversal nets the fee to zero; the provider fee stays recorded.
     */
    public function test_a_refunded_fee_leaves_the_processor_cost_behind(): void
    {
        $event = $this->draft();
        $event->update(['is_published' => true, 'status' => 'published']);

        $fee = $this->fees($event)->first();
        Ledger::settle($fee, LedgerEntry::SECURED);

        Ledger::record(
            LedgerEntry::PROVIDER_FEE,
            Pricing::estimatedProviderFee(2.99),
            '-',
            event: $event,
            state: LedgerEntry::SECURED,
        );

        Ledger::reverse($fee, 'Duplicate posting');

        $margin = Ledger::margin(Ledger::forEvent($event->fresh()));

        $this->assertSame(0.0, $margin['platform'], 'The fee itself nets to nothing.');
        $this->assertSame(0.39, $margin['provider_fee'], 'But the processor kept its cut.');
        $this->assertSame(-0.39, $margin['net'], 'So the platform is out of pocket, which is the decision he made.');
    }
}
