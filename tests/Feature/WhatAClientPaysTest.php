<?php

namespace Tests\Feature;

use App\Domain\Money\Pricing;
use App\Models\{Event, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The prices Sir Peter confirmed on 16 and 17 October 2026.
 *
 * Settled over two days of chat, so they are pinned here. The figures live in
 * config so he can change them; what these tests hold are the RULES, which are
 * the part a changed number must not quietly break.
 */
class WhatAClientPaysTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Away from the UTC and New York boundary, which has caught three other
        // test files in this suite. See the note in timezone handling.
        Carbon::setTestNow(Carbon::now()->setTime(12, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function client(): User
    {
        $user = User::factory()->create(['primary_role' => 'client']);

        return $user;
    }

    // ── Posting ──────────────────────────────────────────────

    /** A founder pays the introductory price. */
    public function test_a_founder_pays_the_founder_rate(): void
    {
        $this->assertSame(2.99, Pricing::postingFee($this->client()));
    }

    /**
     * Their clock starts at the first published posting, not at signup.
     *
     * Khadijah's point and Peter took it: counting from signup burns the
     * window of somebody who registered early and was not ready to post, which
     * is the opposite of what the offer exists for.
     */
    public function test_the_six_months_run_from_the_first_published_posting(): void
    {
        $client = $this->client();

        // Registered a year ago, which on a signup clock would be long expired.
        $client->forceFill(['created_at' => now()->subYear()])->save();

        $this->assertSame(2.99, Pricing::postingFee($client->fresh()), 'Nothing published, so the clock has not started.');

        Event::create([
            'title' => 'First one', 'client_id' => $client->id, 'created_by' => $client->id,
            'status' => 'published', 'published_at' => now()->subMonths(2), 'starts_at' => now()->addDays(10),
        ]);

        $this->assertSame(2.99, Pricing::postingFee($client->fresh()), 'Two months in, still inside the six.');
    }

    /** And it ends when those six months are up. */
    public function test_the_founder_rate_ends_after_six_months(): void
    {
        $client = $this->client();

        Event::create([
            'title' => 'Old one', 'client_id' => $client->id, 'created_by' => $client->id,
            'status' => 'published', 'published_at' => now()->subMonths(7), 'starts_at' => now()->subMonths(6),
        ]);

        $this->assertFalse(Pricing::isFounder($client->fresh()));
        $this->assertSame(4.99, Pricing::postingFee($client->fresh()));
    }

    /** Past the cohort size, the regular price applies from the start. */
    public function test_a_client_outside_the_first_thousand_pays_the_regular_rate(): void
    {
        config(['payments.founder_cohort_size' => 2]);

        $first  = $this->client();
        $second = $this->client();
        $third  = $this->client();

        $this->assertSame(2.99, Pricing::postingFee($first));
        $this->assertSame(2.99, Pricing::postingFee($second));
        $this->assertSame(4.99, Pricing::postingFee($third), 'The third is outside a cohort of two.');
    }

    /** Position is counted in signup order, and is total. */
    public function test_position_is_a_queue_with_no_ties(): void
    {
        $one   = $this->client();
        $two   = $this->client();
        $three = $this->client();

        $this->assertSame([1, 2, 3], [
            Pricing::position($one),
            Pricing::position($two),
            Pricing::position($three),
        ]);
    }

    // ── Agreement ────────────────────────────────────────────

    /**
     * The one Peter changed his mind about.
     *
     * He asked for $4.99 from both sides, then withdrew it for providers: "let
     * the commission rate override my 4.99". A professional already pays
     * membership and commission, so a flat fee would be a third charge on the
     * same work, and the break-even of about $100 means it costs them MORE than
     * the 5% they pay today on exactly the small jobs we want.
     */
    public function test_the_agreement_fee_is_charged_to_the_client_only(): void
    {
        $this->assertSame(4.99, Pricing::agreementFee());

        // There is deliberately no provider-side equivalent to call.
        $this->assertFalse(
            method_exists(Pricing::class, 'providerAgreementFee'),
            'Peter withdrew the provider-side flat fee. Commission covers their share.',
        );
    }

    /*
     * Extensions are NOT tested here, and are not in Pricing at all.
     *
     * Rule R33 already owns them: its own day and price ladder, a limit of
     * three paid extensions, a free 24-hour grace reopen, and expiry derived
     * from the client's own proposal_deadline. Peter's October figures, a flat
     * $1.99 for 7, 14 or 21 days, conflict with that ladder, so they wait for
     * him rather than becoming a second price list.
     */

    // ── The processor's cut ──────────────────────────────────

    /**
     * Stripe's published US rate, for estimating only.
     *
     * Peter's figures checked out: $2.99 nets $2.60, $4.99 nets $4.55.
     */
    public function test_the_estimated_processor_fee_matches_the_published_rate(): void
    {
        $this->assertSame(0.39, Pricing::estimatedProviderFee(2.99));
        $this->assertSame(2.60, Pricing::estimatedNet(2.99));

        $this->assertSame(0.44, Pricing::estimatedProviderFee(4.99));
        $this->assertSame(4.55, Pricing::estimatedNet(4.99));
    }

    /**
     * Each payment carries its own fixed component.
     *
     * Two separate $4.99 charges cost more in fees than one $9.98, which is why
     * combining them was discussed. They cannot be combined here, because the
     * posting and the agreement are owed at different moments.
     */
    public function test_two_separate_charges_cost_more_than_one(): void
    {
        $separate = Pricing::estimatedProviderFee(4.99) * 2;
        $combined = Pricing::estimatedProviderFee(9.98);

        $this->assertGreaterThan($combined, $separate);

        /*
         * Peter's own document put the saving on a pair of $2.99 charges at
         * "approximately $0.31", and that is exactly what this produces. It is
         * not quite the $0.30 fixed component, because each charge is rounded
         * to the cent on its own, as Stripe rounds it.
         */
        $this->assertSame(
            0.31,
            round(Pricing::estimatedProviderFee(2.99) * 2 - Pricing::estimatedProviderFee(5.98), 2),
        );

        $this->assertSame(0.29, round($separate - $combined, 2));
    }

    /*
     * Where the fee is actually taken is NOT tested here.
     *
     * The first version of this file asserted
     * config('payments.client_request_fee_collect_at') === 'publish' and
     * passed, while the fee was still being charged at finalisation, because
     * nothing in the application reads that key. A config value is not a
     * behaviour. B1 is held by ThePostingFeeIsOwedAtPublishTest, which watches
     * the ledger.
     */
}
