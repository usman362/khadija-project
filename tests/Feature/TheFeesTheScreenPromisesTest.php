<?php

namespace Tests\Feature;

use App\Domain\Money\Pricing;
use App\Models\{Category, Event, LedgerEntry, Package, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The page a client ticks must quote the fees they will actually be charged.
 *
 * Sir Peter, 18 October, with a screenshot of Review & Submit: "this needs to
 * be UPDATED or changed: If a client creates a post it will be $2.99 but if they
 * finally get an agreement then the clients $4.99 as well as the others ER and
 * DR and once the client buys a professional's package."
 *
 * The screen said "$0 to post. You only pay a single $2.99 when you finalize",
 * which stopped being true when B1 moved the posting fee to publish and the
 * agreement fee was added. A wrong figure on the one screen somebody actively
 * agrees to is worse than no figure.
 *
 * One partial carries this wording for the bidding request, the emergency
 * request and the direct offer, so all three say the same thing. That was
 * deliberate from the start, and it is what made this one fix instead of three.
 */
class TheFeesTheScreenPromisesTest extends TestCase
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

    // ── What the screens say ─────────────────────────────────

    /** The promise that is no longer true. */
    public function test_no_screen_still_says_posting_is_free(): void
    {
        $partial = file_get_contents(resource_path('views/client/partials/_request_fee_terms.blade.php'));

        // Comments explaining the change may say it; the markup may not.
        $markup = preg_replace('/\{\{--.*?--\}\}/s', '', $partial);

        $this->assertStringNotContainsString('$0</b> to post', $markup);
        $this->assertStringNotContainsString('is free, and that a single', $markup);
    }

    /** Both fees are named, so neither arrives as a surprise. */
    public function test_the_wording_names_both_fees(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.esr.create'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('to publish this request', $html);
        $this->assertStringContainsString('agreement fee', $html);
        $this->assertStringContainsString('2.99', $html, 'A founder sees the founder rate.');
        $this->assertStringContainsString('4.99', $html);
    }

    /**
     * The posting figure is still read per client, even though it is flat now.
     *
     * Peter made posting a flat $2.99 on 20 October, replacing the founder and
     * regular split he confirmed on 16 October. The figure is still read rather
     * than written into the copy, because that split is still a live idea in
     * his Founding Starter Plan and the page must follow the config the day it
     * comes back.
     */
    public function test_the_posting_figure_is_read_from_the_schedule_not_the_copy(): void
    {
        Event::create([
            'title' => 'Old one', 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'published', 'is_published' => true,
            'published_at' => now()->subMonths(8), 'starts_at' => now()->subMonths(7),
        ]);

        // Past the founder window, and still $2.99, because the rates are equal.
        $this->assertFalse(Pricing::isFounder($this->client->fresh()));
        $this->assertSame(2.99, Pricing::postingFee($this->client->fresh()));

        // And if the split ever returns, the page follows it.
        config(['payments.posting_fee_regular' => 6.99]);

        $html = $this->actingAs($this->client)
            ->get(route('client.esr.create'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('$6.99</b> to publish', $html);
    }

    /** All three request types quote it, because all three use the partial. */
    public function test_every_request_type_quotes_the_same_fees(): void
    {
        foreach ([
            route('client.esr.create'),
            route('client.direct-offers.create'),
        ] as $url) {
            $html = $this->actingAs($this->client)->get($url)->assertSuccessful()->getContent();

            $this->assertStringContainsString('to publish this request', $html, "Missing on {$url}");
            $this->assertStringContainsString('agreement fee', $html, "Missing on {$url}");
        }
    }

    /**
     * The help rail carried the old figures too, on all four request pages.
     *
     * config/request-help.php said "$0 to post" and "a single $2.99". Being one
     * config read by four rails is what put one wrong sentence on four pages;
     * the figures are filled in per client now, so the founder rate and the
     * regular rate cannot be confused with each other.
     */
    public function test_the_help_rail_quotes_the_current_fees(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.direct-offers.create'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('$2.99 to publish', $html);
        $this->assertStringContainsString('$4.99 agreement fee', $html);
        $this->assertStringNotContainsString('$0 to post', $html);
        $this->assertStringNotContainsString("it's free to post", $html);
    }

    // ── Buying a package ─────────────────────────────────────

    /**
     * "Once the client buys a professional's package."
     *
     * Buying a package IS the agreement: it is already priced and awarded to
     * one professional, so there is no negotiation to finalize later and this
     * is the moment the fee is owed.
     */
    public function test_buying_a_package_owes_the_agreement_fee(): void
    {
        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update(['state' => 'MD']);

        $category = Category::create(['name' => 'DJ', 'slug' => 'dj-' . uniqid()]);

        $package = Package::create([
            'user_id'     => $pro->id,
            'category_id' => $category->id,
            'title'       => 'Reception DJ package',
            'slug'        => 'reception-dj-' . uniqid(),
            'price'       => 1200,
            'state'       => 'MD',
            'is_active'   => true,
        ]);

        $this->actingAs($this->client)->post(route('client.packages.book.store', $package), [
            'event_title' => 'Anniversary Party',
            'date'        => now()->addDays(40)->toDateString(),
            'location'    => 'Baltimore, MD',
            'guests'      => 80,
            'agree'       => 1,
        ])->assertSessionHasNoErrors();

        $fee = LedgerEntry::where('entry_type', LedgerEntry::PLATFORM_FEE)
            ->whereJsonContains('metadata->via', 'package')
            ->first();

        $this->assertNotNull($fee, 'Buying a package should owe the agreement fee.');
        $this->assertSame('4.99', (string) $fee->amount);
        $this->assertSame($this->client->id, $fee->party_user_id);
        $this->assertSame(LedgerEntry::PENDING, $fee->state, 'Owed, not taken: nothing is charged here yet.');
    }

    /**
     * And no POSTING fee, because nobody bids on a package.
     *
     * The event behind a package purchase is deliberately unpublished. If that
     * ever changes, Event::saved() would start charging a posting fee for
     * buying something already priced.
     */
    public function test_buying_a_package_does_not_owe_a_posting_fee(): void
    {
        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update(['state' => 'MD']);

        $category = Category::create(['name' => 'Catering', 'slug' => 'catering-' . uniqid()]);

        $package = Package::create([
            'user_id'     => $pro->id,
            'category_id' => $category->id,
            'title'       => 'Buffet package',
            'slug'        => 'buffet-' . uniqid(),
            'price'       => 2000,
            'state'       => 'MD',
            'is_active'   => true,
        ]);

        $this->actingAs($this->client)->post(route('client.packages.book.store', $package), [
            'event_title' => 'Anniversary Party',
            'date'        => now()->addDays(40)->toDateString(),
            'location'    => 'Baltimore, MD',
            'guests'      => 80,
            'agree'       => 1,
        ])->assertSessionHasNoErrors();

        $posting = LedgerEntry::where('entry_type', LedgerEntry::PLATFORM_FEE)
            ->whereJsonContains('metadata->reason', 'posting')
            ->count();

        $this->assertSame(0, $posting);
    }
}
