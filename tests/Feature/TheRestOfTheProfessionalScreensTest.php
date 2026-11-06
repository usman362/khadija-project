<?php

namespace Tests\Feature;

use App\Models\Addon;
use App\Models\Bid;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\PricingRule;
use App\Models\User;
use App\Support\BidPerformance;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The rest of Khadijah's 28 October screens.
 *
 * Three of her panels are deliberately absent and each has a test here saying
 * so: a visibility score, an AI win rate predictor and a market benchmark.
 * The platform has nothing behind any of them, and a figure with nothing
 * behind it is worst on exactly these screens, which are the ones somebody
 * uses to decide what to charge and who to chase.
 */
class TheRestOfTheProfessionalScreensTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro    = $this->person('professional', 'Hollis Tremaine');
        $this->client = $this->person('client', 'Imogen Hartwell');
    }

    private function person(string $role, string $name): User
    {
        $u = User::factory()->create(['primary_role' => $role, 'name' => $name]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function event(array $over = []): Event
    {
        return Event::create(array_merge([
            'title'        => 'Hartwell Anniversary',
            'client_id'    => $this->client->id,
            'created_by'   => $this->client->id,
            'status'       => 'published',
            'is_published' => true,
            'starts_at'    => now()->addDays(20),
            'location'     => 'Baltimore, MD',
            'state'        => 'MD',
            'event_type'   => 'Wedding',
        ], $over));
    }

    // ── My Services & Pricing: the four tabs that had nothing behind them ──

    public function test_the_page_offers_khadijahs_five_tabs(): void
    {
        $this->actingAs($this->pro)->get(route('professional.services.index'))
            ->assertOk()
            ->assertSee('Add-ons &amp; extras', false)
            ->assertSee('Pricing rules')
            ->assertSee('Travel')
            ->assertSee('Quick Bid');
    }

    public function test_pricing_rules_are_saved(): void
    {
        $this->actingAs($this->pro)->post(route('professional.services.rules'), [
            'tab'                   => 'rules',
            'minimum_booking'       => 500,
            'weekend_surcharge_pct' => 15,
            'rush_surcharge_pct'    => 25,
        ])->assertRedirect();

        $rules = PricingRule::firstOrFail();

        $this->assertSame('500.00', $rules->minimum_booking);
        $this->assertSame(15, $rules->weekend_surcharge_pct);
    }

    /** Blank means "not set", which is not the same claim as zero. */
    public function test_a_blank_rule_is_not_a_rule_of_zero(): void
    {
        PricingRule::create(['user_id' => $this->pro->id, 'minimum_booking' => 500]);

        $this->actingAs($this->pro)->post(route('professional.services.rules'), [
            'tab'             => 'rules',
            'minimum_booking' => '',
        ]);

        $this->assertNull(PricingRule::firstOrFail()->minimum_booking);
    }

    /**
     * Each tab posts only its own fields, so saving travel must not wipe a
     * minimum booking the professional set on another tab.
     */
    public function test_saving_one_tab_leaves_the_others_alone(): void
    {
        PricingRule::create(['user_id' => $this->pro->id, 'minimum_booking' => 500]);

        $this->actingAs($this->pro)->post(route('professional.services.rules'), [
            'tab'               => 'travel',
            'travel_free_miles' => 30,
        ]);

        $rules = PricingRule::firstOrFail();

        $this->assertSame(30, $rules->travel_free_miles);
        $this->assertSame('500.00', $rules->minimum_booking);
    }

    public function test_an_extra_can_be_added_and_removed(): void
    {
        $this->actingAs($this->pro)->post(route('professional.services.addons.add'), [
            'name' => 'Extra hour of lighting', 'price' => 150, 'price_unit' => 'hourly',
        ])->assertRedirect();

        $addon = Addon::firstOrFail();
        $this->assertSame('Extra hour of lighting', $addon->name);

        $this->actingAs($this->pro)->delete(route('professional.services.addons.remove', $addon))
            ->assertRedirect();

        $this->assertDatabaseCount('professional_addons', 0);
    }

    public function test_one_professional_cannot_remove_anothers_extra(): void
    {
        $other = $this->person('professional', 'Marguerite Okafor');
        $addon = Addon::create(['user_id' => $other->id, 'name' => 'Theirs', 'price' => 10]);

        $this->actingAs($this->pro)->delete(route('professional.services.addons.remove', $addon))
            ->assertNotFound();

        $this->assertDatabaseCount('professional_addons', 1);
    }

    // ── Bid Intelligence ──────────────────────────────────────────────

    /** An open bid is in neither total. Nobody has lost a bid still being decided. */
    public function test_an_open_bid_counts_as_neither_won_nor_lost(): void
    {
        $service = Category::firstOrCreate(['slug' => 'wedding-dj-perf'],
            ['name' => 'Wedding DJ', 'kind' => Category::SERVICE]);

        foreach (['won', 'lost', 'submitted'] as $status) {
            Bid::create([
                'event_id' => $this->event()->id, 'category_id' => $service->id,
                'supplier_id' => $this->pro->id, 'amount' => 1200, 'status' => $status,
            ]);
        }

        $record = BidPerformance::record(BidPerformance::bids($this->pro->fresh()));

        $this->assertSame(3, $record['placed']);
        $this->assertSame(1, $record['won']);
        $this->assertSame(1, $record['lost']);
        $this->assertSame(1, $record['open']);
        $this->assertSame(50, $record['rate']);
    }

    /** Never having had one decided is not a win rate of nought. */
    public function test_a_professional_with_nothing_decided_has_no_rate(): void
    {
        $this->assertNull(BidPerformance::record(collect())['rate']);
    }

    public function test_the_page_shows_their_own_record_and_no_prediction(): void
    {
        $page = $this->actingAs($this->pro)->get(route('professional.bid-intelligence.index'))
            ->assertOk()
            ->assertSee('Your record');

        $html = $page->getContent();

        foreach (['Predicted Win Rate', 'AI Win-Rate', 'Market Context', 'Visibility Score'] as $invented) {
            $this->assertStringNotContainsString($invented, $html);
        }
    }

    // ── Dashboard ─────────────────────────────────────────────────────

    public function test_the_setup_bar_names_what_is_missing(): void
    {
        $this->actingAs($this->pro)->get(route('professional.dashboard'))
            ->assertOk()
            ->assertSee('Finish setting up')
            ->assertSee('List the services you offer');
    }

    /** No goal set means no goal shown, not a target nobody chose. */
    public function test_no_revenue_goal_is_shown_until_one_is_set(): void
    {
        $this->actingAs($this->pro)->get(route('professional.dashboard'))
            ->assertOk()
            ->assertDontSee('This month against your goal');

        PricingRule::create(['user_id' => $this->pro->id, 'monthly_revenue_goal' => 10000]);

        $this->actingAs($this->pro->fresh())->get(route('professional.dashboard'))
            ->assertOk()
            ->assertSee('This month against your goal');
    }

    /** Each summary card says what it is made of, not just a total. */
    public function test_the_summary_cards_name_their_parts(): void
    {
        Booking::create([
            'event_id' => $this->event()->id, 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'supplier_id' => $this->pro->id,
            'status' => 'requested', 'price' => 900,
        ]);

        $this->actingAs($this->pro)->get(route('professional.dashboard'))
            ->assertOk()
            ->assertSee('Needs your response')
            // The count, and underneath it what the count is made of.
            ->assertSee('1 request');
    }

    /**
     * "Since you were last here" needs a previous visit to measure from.
     * On a first visit it is not shown, rather than reporting every record
     * the account has ever had as new.
     */
    public function test_nothing_is_new_on_a_first_visit(): void
    {
        $this->assertNull($this->pro->dashboard_seen_at);

        $this->actingAs($this->pro)->get(route('professional.dashboard'))
            ->assertOk()
            ->assertDontSee('Since you were last here');

        // The visit is recorded, so the next one has something to compare to.
        $this->assertNotNull($this->pro->fresh()->dashboard_seen_at);
    }

    /** The old Priority Actions card said the same thing twice. */
    public function test_the_attention_list_is_not_shown_twice(): void
    {
        $html = $this->actingAs($this->pro)->get(route('professional.dashboard'))
            ->assertOk()->getContent();

        // The card's own title, not any mention of the phrase: a CSS comment
        // in the page carries it too and is not what this is about.
        $this->assertSame(1, substr_count($html, '>Needs your attention<'));
        $this->assertStringNotContainsString('>Priority Actions<', $html);
    }

    /** Two bookings do not make a season. */
    public function test_the_months_chart_waits_for_enough_history(): void
    {
        Booking::create([
            'event_id' => $this->event()->id, 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'supplier_id' => $this->pro->id,
            'status' => 'completed', 'price' => 900,
        ]);

        $this->actingAs($this->pro)->get(route('professional.dashboard'))
            ->assertOk()
            ->assertDontSee('When your work falls');
    }
}
