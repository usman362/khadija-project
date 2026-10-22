<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\{ServiceArea, SupplierProfile, Vocabulary};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The word for the other side of a request comes from one place.
 *
 * Sir Peter, 21 October: "I need to help figure out how we will word the
 * influencers in the rules, tasks, the bidding since we have almost everywhere
 * listed it only as professionals." Then: "I will leave that
 * professionals/influencers wording with you to do."
 *
 * Measured first: 289 user-visible occurrences of "professional" across 45
 * client views. Written out by hand, changing the wording again is 289 edits,
 * which is exactly how the posting fee went stale in three places inside one
 * month.
 *
 * And a finding that changed the shape of the job. The approved wording is
 * "professionals and influencers", and it is NOT TRUE YET: no influencer
 * account exists, no influencer package exists, the Direct Request picker
 * lists professionals only, and the influencer role holds 5 of the 18
 * permissions a professional holds. Writing it across the site today would be
 * the site claiming something it does not do, which is the verified badge and
 * the landing page's trust bar over again, both of which Peter found himself.
 *
 * So the deliverable is the switch, not the sentence.
 */
class OneWordForBothKindsOfAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        Carbon::setTestNow(Carbon::now()->setTime(12, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function client(): User
    {
        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    // ── The switch ───────────────────────────────────────────

    /** Khadijah's term, and it is true the day it ships. */
    public function test_the_general_word_is_the_one_khadijah_settled_on(): void
    {
        $this->assertSame('service providers', Vocabulary::providers());
        $this->assertSame('service provider', Vocabulary::provider());
    }

    /**
     * And it does not promise influencers where there are none.
     *
     * This is why her term is better than the one proposed first. Nothing can
     * send a request to an influencer yet: no influencer account exists and
     * the role holds 5 of the 18 permissions a professional holds. "Service
     * provider" is accurate either way, so it needed no flag and no second
     * pass.
     */
    public function test_the_word_does_not_promise_an_influencer(): void
    {
        $this->assertStringNotContainsString('influencer', Vocabulary::providers());
        $this->assertSame(0, User::role('influencer')->count(), 'An influencer account now exists.');
    }

    // ── Rule 1: two searches stay two searches ───────────────

    /**
     * Where somebody is CHOOSING between them, both words stay.
     *
     * Searching for a professional and searching for an influencer are
     * different searches, and folding them into one neutral word would hide
     * one behind the other. So this search is NOT reworded by the switch.
     *
     * Peter's own mockup shows "Find Influencers" beside it in the top nav.
     * Checked on 21 October: that link does not exist anywhere in the
     * application. One more thing waiting on the influencer side, and the
     * reason the switch is off.
     */
    public function test_finding_a_professional_stays_its_own_search(): void
    {
        $html = $this->get(route('landing'))->assertSuccessful()->getContent();

        $this->assertStringContainsString('Find Professionals', $html);

        $this->assertStringNotContainsString(
            'Find ' . Vocabulary::providersCapitalised(),
            $html,
            'A search that must name one role has been made generic.',
        );
    }

    /** And the influencer search Peter's mockup shows is not built. */
    public function test_there_is_no_influencer_search_yet(): void
    {
        $html = $this->get(route('landing'))->assertSuccessful()->getContent();

        $this->assertStringNotContainsString(
            'Find Influencers',
            $html,
            'An influencer search now exists, so the wording switch can be reconsidered.',
        );
    }

    // ── Rule 2: one person, named correctly ──────────────────

    /** A named person is described by their own account, not by a guess. */
    public function test_a_named_person_is_described_from_their_account(): void
    {
        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');

        $inf = User::factory()->create(['primary_role' => 'influencer']);
        $inf->assignRole('influencer');

        $this->assertSame('professional', SupplierProfile::noun($pro));
        $this->assertSame('influencer', SupplierProfile::noun($inf));
    }

    // ── Rule 3: one defined term in the rules ────────────────

    /** Policies name one party, not a list that has to be amended. */
    public function test_the_rules_use_one_defined_term(): void
    {
        $this->assertSame('Service Provider', Vocabulary::legalTerm());
        $this->assertSame('Service Providers', Vocabulary::legalTerm(true));
    }

    // ── The screens read it rather than spelling it out ──────

    /** The bidding request's own copy comes through the helper. */
    public function test_the_bidding_request_reads_the_word_from_one_place(): void
    {
        $html = $this->actingAs($this->client())
            ->get(route('client.bsr.step', 'service'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString(Vocabulary::providers(), $html);
    }

    /**
     * No screen spells the approved phrase out by hand.
     *
     * One hand-written copy is all it takes for a screen to keep saying
     * "professionals and influencers" after somebody turns the switch back off.
     */
    public function test_no_view_writes_the_phrase_out_by_hand(): void
    {
        $offenders = [];

        foreach (glob(resource_path('views/**/*.blade.php'), GLOB_BRACE) + glob(resource_path('views/**/**/*.blade.php')) as $file) {
            $body = file_get_contents($file);
            $body = preg_replace('/\{\{--.*?--\}\}/s', '', $body);

            if (str_contains($body, 'professionals and influencers')
                || preg_match('/\\bservice providers?\\b/i', $body)) {
                $offenders[] = basename($file);
            }
        }

        $this->assertSame([], $offenders, 'These write the phrase out instead of reading it.');
    }
}
