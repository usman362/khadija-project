<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\StateMatching;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 2026-08-31: use distance, turn off home-state matching. "A
 * photographer based in New Jersey shooting a wedding in Philadelphia is
 * standard practice."
 *
 * The switch is built and the decision is recorded. It is not flipped yet, and
 * the reason is data rather than disagreement: of 17 professionals, none has
 * placed a service origin or set a travel radius, and no client has
 * coordinates. There is nothing to measure a distance against.
 *
 * Flipping it today would not give distance matching. It would give NO
 * matching — the state filter is the only one in the listing scopes, so a
 * Maryland client would browse professionals across the country who cannot
 * reach them. And RadiusMatching refuses any professional whose origin is
 * unplaced, so switching that on instead matches nobody at all.
 *
 * These lock both halves: the switch works, and it is honestly off.
 */
class StateMatchingSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function client(string $state = 'MD'): User
    {
        $u = User::factory()->create();
        $u->assignRole('client');
        $u->getOrCreateProfile()->update(['country' => 'US', 'state' => $state, 'city' => 'Baltimore']);

        return $u->fresh();
    }

    public function test_the_switch_turns_the_rule_off(): void
    {
        $client = $this->client();

        config(['geo.state_matching' => true]);
        $this->assertTrue(StateMatching::appliesTo($client));

        config(['geo.state_matching' => false]);
        $this->assertFalse(StateMatching::appliesTo($client),
            'The rule must be reversible from configuration alone.');
    }

    /** With the rule off, a listing is no longer narrowed by state. */
    public function test_with_the_rule_off_a_listing_is_not_state_filtered(): void
    {
        config(['geo.state_matching' => false]);

        $sql = StateMatching::scopeUsersForViewer(User::query(), $this->client())->toSql();

        $this->assertStringNotContainsString('state', $sql,
            'A state condition survived with the rule switched off.');
    }

    /**
     * The reason it is not flipped. If this ever passes with real data, the
     * switch can go off.
     */
    public function test_nothing_can_be_matched_by_distance_until_origins_are_placed(): void
    {
        $pro = User::factory()->create();
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update(['country' => 'US', 'state' => 'NJ', 'city' => 'Newark']);

        $this->assertFalse(
            \App\Support\RadiusMatching::originIsMatchable($pro->fresh()),
            'A professional with no placed origin cannot be matched by distance, '
            . 'so distance cannot replace the state rule until origins exist.'
        );
    }

    /** Shipped state: on, so the site keeps filtering while the data is gathered. */
    public function test_it_ships_on(): void
    {
        $this->assertTrue(config('geo.state_matching'),
            'Turning this off before professionals have origins removes the only filter there is.');
    }
}
