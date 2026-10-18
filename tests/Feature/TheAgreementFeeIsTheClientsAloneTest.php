<?php

namespace Tests\Feature;

use App\Domain\Money\Pricing;
use App\Models\{Bid, Category, Event, Finalization, LedgerEntry, Payment, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * $4.99 on the agreement, and nothing from the provider.
 *
 * Sir Peter asked for the agreement fee from both sides on 17 October: "But
 * everyone gets charged $4.99 as the price for the agreement on both sides."
 * He withdrew the provider half the same day once the arithmetic was in front
 * of him: "ok let the commission rate override my 4.99".
 *
 * The arithmetic that changed his mind, and the reason this file guards it: a
 * professional already pays membership for exposure and commission on the
 * booking, which is 5% on Starter, 3% on Pro and 1.5% on Elite. A flat fee
 * would be a third charge on the same work, and the break-even is about $100,
 * so on a small gig it costs them more than the commission itself.
 */
class TheAgreementFeeIsTheClientsAloneTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;
    private Finalization $fin;

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

        $this->pro = User::factory()->create(['primary_role' => 'professional']);
        $this->pro->assignRole('professional');

        $event = Event::create([
            'title' => 'Rivera Wedding', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'starts_at' => now()->addDays(30), 'city' => 'Baltimore', 'state' => 'MD',
        ]);

        $category = Category::create(['name' => 'DJ', 'slug' => 'dj-' . uniqid()]);

        $bid = Bid::create([
            'event_id' => $event->id, 'supplier_id' => $this->pro->id,
            'category_id' => $category->id, 'amount' => 2000, 'status' => 'submitted',
        ]);

        $this->fin = Finalization::create([
            'event_id' => $event->id, 'bid_id' => $bid->id,
            'client_id' => $this->client->id, 'supplier_id' => $this->pro->id,
            'agreed_price' => 2000, 'deposit_percent' => 25, 'deposit_amount' => 500,
            'scope' => 'DJ for the reception',
        ]);

        // The wizard sends you back to the first incomplete step, so the ones
        // before Deposit & Terms are marked done.
        $this->fin->forceFill([
            'bid_reviewed_at'    => now(),
            'scope_agreed_at'    => now(),
            'price_agreed_at'    => now(),
            'schedule_agreed_at' => now(),
        ])->save();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function fund(): void
    {
        $this->actingAs($this->client)
            ->post(route('client.finalize.save', [$this->fin, 'payment']), ['confirm_payment' => 1]);
    }

    // ── The client pays it ───────────────────────────────────

    public function test_the_client_is_charged_the_agreement_fee(): void
    {
        $this->fund();

        $fee = Payment::all()->first(fn ($p) => ($p->metadata['kind'] ?? null) === 'agreement_fee');

        $this->assertNotNull($fee, 'The agreement fee was not charged.');
        $this->assertSame(4.99, (float) $fee->amount);
        $this->assertSame($this->client->id, $fee->user_id);
    }

    /** And it lands in the ledger, where every money event belongs. */
    public function test_the_fee_is_recorded_in_the_ledger(): void
    {
        $this->fund();

        $entry = LedgerEntry::where('entry_type', LedgerEntry::PLATFORM_FEE)
            ->whereJsonContains('metadata->reason', 'agreement')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('4.99', (string) $entry->amount);
        $this->assertSame($this->client->id, $entry->party_user_id);
        $this->assertSame(LedgerEntry::SECURED, $entry->state, 'It was taken with the deposit, so it is held.');
    }

    // ── The provider does not ────────────────────────────────

    /**
     * The half he withdrew.
     *
     * Nothing may charge a professional for the agreement. Their share is the
     * commission, which already scales with the size of the job.
     */
    public function test_the_professional_is_charged_nothing(): void
    {
        $this->fund();

        $charged = Payment::where('user_id', $this->pro->id)->get();

        $this->assertCount(0, $charged, 'A provider pays membership and commission, never an agreement fee.');

        $owed = LedgerEntry::where('party_user_id', $this->pro->id)
            ->where('entry_type', LedgerEntry::PLATFORM_FEE)
            ->get();

        $this->assertCount(0, $owed);
    }

    /** There is deliberately no provider-side fee to call for. */
    public function test_no_provider_agreement_fee_exists_to_be_charged(): void
    {
        $this->assertFalse(method_exists(Pricing::class, 'providerAgreementFee'));
        $this->assertFalse(method_exists(Pricing::class, 'supplierAgreementFee'));
    }

    // ── Once, not twice ──────────────────────────────────────

    /** A retried submit must not charge the fee again (A15). */
    public function test_funding_twice_charges_the_fee_once(): void
    {
        $this->fund();
        $this->fund();

        $fees = Payment::all()->filter(fn ($p) => ($p->metadata['kind'] ?? null) === 'agreement_fee');

        $this->assertCount(1, $fees);
    }

    // ── Separate from the posting fee ────────────────────────

    /**
     * Two fees, two charges, and the copy says so.
     *
     * The wizard used to read "Posting was free", which stopped being true the
     * moment B1 came into force.
     */
    public function test_the_wizard_does_not_still_say_posting_was_free(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.finalize.step', [$this->fin, 'price']))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringNotContainsString('Posting was free', $html);
        $this->assertStringContainsString('Agreement fee', $html);
    }

    /** And the screen names the right figure. */
    public function test_the_wizard_shows_the_agreement_fee(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.finalize.step', [$this->fin, 'price']))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('4.99', $html);
    }
}
