<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Best Match — R40's fifth leg, and the bug found while building it.
 *
 * This tool is the one place that does not print a statistic: it names people
 * and says hire them. That makes both findings here sharper than the same
 * faults elsewhere.
 *
 * The shortlist is topped up from a hard-coded representative catalogue when
 * the live pool is thin, so "a match" and "a person you can send an offer to"
 * are not the same thing. The id is what separates them.
 */
class BestMatchDirectOfferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function user(string $role, string $state, string $city = 'Baltimore'): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->givePermissionTo(['dashboard.view', 'events.create']);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => $state, 'city' => $city,
            // The fallback event is "Tropical Beach Party" and the ranker needs
            // a keyword overlap to clear the 80% floor, so this is what makes
            // the professional appear at all — not decoration.
            'service_area_status' => 'supported', 'skills' => ['Beach', 'Photography'],
        ]);

        return $u->fresh();
    }

    /** Not named matches(): PHPUnit\Framework\Assert::matches() is final. */
    private function shortlist(User $client): array
    {
        return array_values(array_filter(
            $this->actingAs($client)->get(route('ai-tools.vendor-matchmaking'))
                ->assertOk()->viewData('matches'),
            fn ($m) => ! empty($m['id']),
        ));
    }

    /**
     * Rule R38. A recommendation the client is forbidden to act on is worse
     * than no recommendation: they choose someone, and the Direct Offer at the
     * end of it is refused with a 422 after the choosing.
     */
    public function test_it_only_recommends_professionals_the_client_may_hire(): void
    {
        $near = $this->user('professional', 'MD');
        $this->user('professional', 'DE', 'Dover');

        $names = array_column($this->shortlist($this->user('client', 'MD')), 'name');

        $this->assertContains($near->name, $names);
        $this->assertCount(1, $names, 'the out-of-state professional was recommended anyway');
    }

    /** A real professional can be sent an offer, and the card says so. */
    public function test_a_real_match_offers_a_direct_offer(): void
    {
        $pro    = $this->user('professional', 'MD');
        $client = $this->user('client', 'MD');

        $this->assertSame([$pro->id], array_column($this->shortlist($client), 'id'));

        $this->actingAs($client)->get(route('ai-tools.vendor-matchmaking'))
            ->assertSee(route('client.direct-offers.create', ['pro' => $pro->id]), false);
    }

    /**
     * And the catalogue filler does not, because an offer addressed to
     * "Breeze Event Planning" would reach nobody.
     */
    public function test_the_representative_catalogue_carries_no_offer_link(): void
    {
        $client = $this->user('client', 'MD');

        $filler = array_filter(
            $this->actingAs($client)->get(route('ai-tools.vendor-matchmaking'))
                ->assertOk()->viewData('matches'),
            fn ($m) => empty($m['id']),
        );

        $this->assertNotEmpty($filler, 'with no live pros the shortlist should be filler');

        // Rendered in isolation on purpose: the page also carries the JS that
        // builds this same anchor at runtime, so a whole-page assertion cannot
        // tell a rendered link from the template for one.
        $html = view('client.ai-tools._vendor_matches', ['matches' => array_values($filler)])->render();

        $this->assertStringNotContainsString('direct-offers/create', $html);
        $this->assertStringNotContainsString('vm-offer', $html);
    }

    /**
     * The link lands on a form that opens, with that professional chosen.
     *
     * The name is set rather than left to the factory: this assertion ran
     * unescaped, so a faker name carrying an apostrophe rendered as
     * O&#039;Brien and failed perhaps one run in twenty. A test that fails
     * occasionally is worse than one that fails always — it teaches people to
     * re-run rather than to look.
     */
    public function test_the_offer_link_opens_the_form_on_that_professional(): void
    {
        $pro    = $this->user('professional', 'MD');
        $client = $this->user('client', 'MD');

        $pro->update(['name' => 'Bayside Sound']);

        $this->actingAs($client)
            ->get(route('client.direct-offers.create', ['pro' => $pro->id]))
            ->assertOk()
            ->assertSee('Bayside Sound');
    }
}
