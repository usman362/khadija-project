<?php

namespace Tests\Feature;

use App\Domain\Money\FeeSchedule;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Every fee is described once, and every screen reads that description.
 *
 * This exists because the same figure went stale in three separate places
 * inside one month. "$0 to post. You only pay a single $2.99" was written by
 * hand into the Review & Submit partial, into config/request-help.php for all
 * four request rails, and into the landing page's database row. Each had to be
 * found and fixed on its own, and twice it was Sir Peter who noticed on screen
 * rather than anybody reading the code.
 *
 * His instruction on 20 October: "Maybe we should add a webpage named 'pricing
 * and rates'?" A page like that is only honest if it and the forms cannot
 * disagree, which is what these tests hold.
 */
class EveryFeeComesFromOneListTest extends TestCase
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

    // ── The four figures Peter confirmed ─────────────────────

    /**
     * "$2.99 to post a new request. $4.99 for the agreement fee. $1.99 to
     * extend a request for the specified number of additional days. $2.99 to
     * edit/repost a request." (20 October)
     */
    public function test_the_client_fees_are_the_ones_he_confirmed(): void
    {
        $fees = collect(FeeSchedule::forAudience(FeeSchedule::CLIENT))->keyBy('key');

        $this->assertSame(2.99, $fees['posting']['amount']);
        $this->assertSame(4.99, $fees['agreement']['amount']);
        $this->assertSame(1.99, $fees['request_extension']['amount']);
        $this->assertSame(2.99, $fees['repost']['amount']);
    }

    /**
     * "$2.99 to post a new package of services. $4.99 or the applicable
     * percentage for the agreement fee. $1.99 to extend ... $2.99 to
     * edit/repost a package of services." (20 October)
     */
    public function test_the_package_fees_are_the_ones_he_confirmed(): void
    {
        $fees = collect(FeeSchedule::forAudience(FeeSchedule::PROVIDER))->keyBy('key');

        $this->assertSame(2.99, $fees['package_posting']['amount']);
        $this->assertSame(4.99, $fees['package_agreement']['amount']);
        $this->assertSame(1.99, $fees['package_extension']['amount']);
        $this->assertSame(2.99, $fees['package_repost']['amount']);
    }

    /**
     * Provider and client fees stay told apart.
     *
     * Peter changed his mind once already about whether providers pay the
     * agreement fee, so a flat list with no audience on it is one careless
     * loop away from quoting somebody the other side's price.
     */
    public function test_each_fee_says_who_pays_it(): void
    {
        foreach (FeeSchedule::all() as $fee) {
            $this->assertContains(
                $fee['audience'],
                [FeeSchedule::CLIENT, FeeSchedule::PROVIDER],
                "{$fee['key']} does not say who pays it.",
            );
        }

        $this->assertCount(4, FeeSchedule::forAudience(FeeSchedule::CLIENT));
        $this->assertCount(4, FeeSchedule::forAudience(FeeSchedule::PROVIDER));
    }

    /** The extension lengths differ on purpose, and he gave both sets. */
    public function test_requests_and_packages_extend_by_different_lengths(): void
    {
        $request = collect(FeeSchedule::all())->firstWhere('key', 'request_extension');
        $package = collect(FeeSchedule::all())->firstWhere('key', 'package_extension');

        $this->assertStringContainsString('7, 14 or 21', $request['when']);
        $this->assertStringContainsString('30, 60 or 90', $package['when']);
    }

    // ── The no-refunds rule ──────────────────────────────────

    /**
     * "No refunds or credits for if the user cancels their monthly plans,
     * especially for their reposts/extension posts, or changes their minds ...
     * this needs to be added everywhere in the certain webpages too."
     */
    public function test_nothing_is_refundable(): void
    {
        foreach (FeeSchedule::all() as $fee) {
            $this->assertFalse($fee['refundable'], "{$fee['key']} claims to be refundable.");
        }

        $policy = FeeSchedule::refundPolicy();

        $this->assertStringContainsString('not refundable', $policy);
        $this->assertStringContainsString('Cancelling a plan', $policy);
        $this->assertStringContainsString('changing your mind', $policy);
    }

    /** And it is the same sentence wherever it appears. */
    public function test_the_refund_wording_is_the_same_on_the_form_and_the_page(): void
    {
        $sentence = FeeSchedule::refundPolicy();

        foreach ([route('pricing-and-rates'), route('client.esr.create')] as $url) {
            $html = $this->actingAs($this->client)->get($url)->assertSuccessful()->getContent();

            $this->assertStringContainsString($sentence, $html, "The wording differs on {$url}.");
        }
    }

    // ── The page itself ──────────────────────────────────────

    public function test_the_pricing_page_lists_every_fee(): void
    {
        $html = $this->get(route('pricing-and-rates'))->assertSuccessful()->getContent();

        foreach (FeeSchedule::all() as $fee) {
            $this->assertStringContainsString($fee['label'], $html, "{$fee['label']} is missing.");
        }
    }

    /** It works signed out, because somebody deciding has not signed up yet. */
    public function test_the_pricing_page_is_public(): void
    {
        $this->get(route('pricing-and-rates'))
            ->assertSuccessful()
            ->assertSee('Pricing and rates');
    }

    /**
     * The founders' section is deliberately absent.
     *
     * Peter, 20 October: "Before finalizing the founders' section, confirm
     * exactly what their benefits are ... This will prevent usman from
     * implementing unconfirmed pricing rules." Three different versions of that
     * rule were written down this month.
     */
    public function test_the_pricing_page_makes_no_founder_claim(): void
    {
        $html = $this->get(route('pricing-and-rates'))->assertSuccessful()->getContent();

        $this->assertStringNotContainsString('commission-free', $html);
        $this->assertStringNotContainsString('Founding', $html);
    }

    /** The form and the page quote the same number, which is the whole point. */
    public function test_the_form_and_the_page_quote_the_same_posting_fee(): void
    {
        $amount = '$' . number_format(FeeSchedule::find('posting', $this->client)['amount'], 2);

        $page = $this->actingAs($this->client)->get(route('pricing-and-rates'))->getContent();
        $form = $this->actingAs($this->client)->get(route('client.esr.create'))->getContent();

        $this->assertStringContainsString($amount, $page);
        $this->assertStringContainsString($amount, $form);
    }

    /** And the Pricing link in the menu goes to the page, not an anchor. */
    public function test_the_menu_points_at_the_page(): void
    {
        $html = $this->get(route('landing'))->assertSuccessful()->getContent();

        $this->assertStringContainsString(route('pricing-and-rates'), $html);
        $this->assertStringNotContainsString(route('landing') . '#pricing', $html);
    }
}
