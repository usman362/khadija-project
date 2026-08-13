<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Influencer;
use App\Models\InfluencerReferral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checklist rows 113, 114, 115, 144 and 152 — the influencer portal.
 *
 * Row 113 was reported as three different datasets across four pages. The
 * pages read one list now; what was left was that none of them said HOW MUCH
 * of it they were showing, so five rows on one page beside ten on another
 * still read as two different datasets. Each panel states its own scope, and
 * the badge page no longer runs its own query — a view fetching its own data
 * is how four pages come to disagree in the first place.
 *
 * Row 144 is the one worth being careful about. A commission rate is
 * meaningless without saying what it is a percentage OF, and the answer had to
 * come from the code rather than from a guess: EloquentInfluencerService
 * applies the rate to the booking price, and pays a flat signup bonus
 * separately. Both figures on screen are those.
 */
class InfluencerTiersAndActivityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Influencer $influencer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->user = User::factory()->create(['primary_role' => 'influencer']);
        $this->user->assignRole('influencer');

        $this->influencer = Influencer::create([
            'user_id'         => $this->user->id,
            'full_name'       => $this->user->name,
            'email'           => $this->user->email,
            'referral_code'   => 'TESTCODE',
            'status'          => 'approved',
            'commission_tier' => 'starter',
            'total_referrals' => 0,
            'total_earnings'  => 0,
        ]);
    }

    private function tiersPage()
    {
        return $this->actingAs($this->user)->get(route('influencer.badges.tiers'))->assertOk();
    }

    private function refer(User $referred, float $commission = 5.0): InfluencerReferral
    {
        return InfluencerReferral::create([
            'influencer_id'     => $this->influencer->id,
            'referred_user_id'  => $referred->id,
            'type'              => 'signup_bonus',
            'base_amount'       => 0,
            'commission_rate'   => 0,
            'commission_amount' => $commission,
            'status'            => 'earned',
            'source'            => 'system',
        ]);
    }

    /* ── Row 114: an influencer tier is not a membership plan ── */

    public function test_the_tier_screen_says_which_kind_of_tier_it_means(): void
    {
        $page = $this->tiersPage();

        $page->assertSee('Influencer Tiers', false);
        $page->assertSee('Influencer Tier 1', false);
        $page->assertSee('not a membership plan', false);
    }

    /* ── Row 144: what the percentage is a percentage of ────── */

    /**
     * The figures on screen have to be the ones the code uses, or the
     * definition is just a different guess.
     */
    public function test_the_commission_base_is_stated_and_matches_the_code(): void
    {
        $page = $this->tiersPage();

        $page->assertSee('What your commission applies to', false);
        $page->assertSee('agreed price of each booking', false);

        // The signup bonus shown is the configured one, not a typed-in figure.
        $page->assertSee('$' . number_format((float) config('influencer.signup_bonus', 5), 2), false);
    }

    public function test_the_definition_rules_out_the_two_things_it_is_not(): void
    {
        $page = $this->tiersPage();

        // Static template copy, so the apostrophes are NOT entity-escaped —
        // and it wraps, so these match single lines rather than the sentence.
        $page->assertSee("Not the professional's", false);
        $page->assertSee('own earnings', false);
        $page->assertSee("not the platform's fee", false);
    }

    /* ── Row 115: the Elite benefit, with a figure behind it ── */

    public function test_a_lower_tier_is_told_what_elite_unlocks(): void
    {
        $page = $this->tiersPage();

        $page->assertSee('Reach Elite', false);
        $page->assertSee((string) config('influencer.tiers.elite.min_referrals'), false);
    }

    /** For an Elite influencer it is a count, not a promise. */
    public function test_elite_sees_how_many_referred_professionals_are_being_paid(): void
    {
        $this->influencer->update(['commission_tier' => 'elite']);

        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');
        $client = User::factory()->create(['primary_role' => 'client']);
        $client->assignRole('client');

        $this->refer($pro);

        $event = Event::create([
            'title' => 'A job', 'client_id' => $client->id, 'created_by' => $client->id,
            'status' => 'published', 'is_published' => true, 'starts_at' => now()->addDays(20),
        ]);

        Booking::create([
            'event_id' => $event->id, 'client_id' => $client->id, 'supplier_id' => $pro->id,
            'created_by' => $client->id, 'status' => 'confirmed', 'price' => 2000,
        ]);

        $page = $this->tiersPage();

        $this->assertSame(1, $page->viewData('paidProfessionals'));
        $page->assertSee('been booked and paid', false);
    }

    /** A referred professional nobody has booked is not counted as paid. */
    public function test_an_unbooked_referred_professional_is_not_counted(): void
    {
        $this->influencer->update(['commission_tier' => 'elite']);

        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');
        $this->refer($pro);

        $this->assertSame(0, $this->tiersPage()->viewData('paidProfessionals'));
    }

    /* ── Row 113: one list, and every panel saying its scope ── */

    public function test_the_badge_page_reads_the_referral_list_from_the_controller(): void
    {
        $referred = User::factory()->create();
        $this->refer($referred);

        $page = $this->actingAs($this->user)->get(route('influencer.badges.current'))->assertOk();

        $this->assertCount(1, $page->viewData('recentReferrals'));
        $page->assertSee('Recent Referrals', false);
        $page->assertSee('5 most recent', false);
    }

    public function test_the_dashboard_panel_states_how_much_of_the_list_it_shows(): void
    {
        $this->actingAs($this->user)
            ->get(route('influencer.dashboard'))
            ->assertOk()
            ->assertSee('10 most recent', false);
    }

    /** And the full list says it is the full one. */
    public function test_the_referrals_page_says_it_is_every_referral(): void
    {
        $this->refer(User::factory()->create());

        $this->actingAs($this->user)
            ->get(route('influencer.dashboard.referrals'))
            ->assertOk()
            ->assertSee('at every status', false);
    }

    /* ── Row 152: two click figures that measure different things ── */

    /**
     * Four tiles across the analytics pages all read "Total Clicks" and all
     * counted something else. None of them said which.
     */
    public function test_each_clicks_figure_names_what_it_counts(): void
    {
        $this->actingAs($this->user)->get(route('influencer.analytics.campaigns'))
            ->assertOk()->assertSee('Campaign Clicks', false)->assertSee('All campaigns, all time', false);

        $this->actingAs($this->user)->get(route('influencer.analytics.content'))
            ->assertOk()->assertSee('Content Clicks', false);

        $this->actingAs($this->user)->get(route('influencer.analytics.performance'))
            ->assertOk()->assertSee('Referral Link Clicks', false);
    }

    public function test_no_analytics_tile_still_reads_only_total_clicks(): void
    {
        foreach (['campaigns', 'content', 'performance'] as $tab) {
            $this->actingAs($this->user)
                ->get(route("influencer.analytics.{$tab}"))
                ->assertDontSee('>Total Clicks<', false);
        }
    }
}
