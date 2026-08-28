<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\StateMatching;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter's two named rules, 25–26 Aug 2026 — and which one is in force.
 *
 *   Event Location Rule   the event's location when the client gives one,
 *                         their home address as the fallback.
 *   State Boundary Rule   matching is ALWAYS by the client's home state.
 *                         No cross-state, even if the event is out of state.
 *
 * The boundary rule overrides the other, in his words: "each state has its own
 * rules/laws so until we can figure it all out then we will at least get this
 * problem resolved."
 *
 * It mattered, and this is the bug it fixed. Posting a request used to route
 * on whichever state the client picked for the event, so a Maryland client
 * with an event in Pennsylvania reached Pennsylvania professionals — while a
 * Direct Request to any of those same professionals was refused, because that
 * path compares home states. Same client, same event, two opposite answers.
 */
class StateBoundaryRuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function person(string $role, string $state): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => $state, 'city' => 'Somewhere']);

        return $user->fresh();
    }

    /** The rule itself. */
    public function test_an_out_of_state_event_still_matches_the_clients_home_state(): void
    {
        $client = $this->person('client', 'MD');

        $this->assertSame('MD', StateMatching::requestState($client, 'PA'));
    }

    public function test_with_no_event_location_it_is_the_home_state_too(): void
    {
        $client = $this->person('client', 'MD');

        $this->assertSame('MD', StateMatching::requestState($client, null));
        $this->assertSame('MD', StateMatching::requestState($client));
    }

    /**
     * The conflict this closed: posting and direct hiring now agree.
     *
     * Before, `requestState` returned PA while `allows()` returned false for
     * the very same pair.
     */
    public function test_posting_and_direct_hiring_give_the_same_answer(): void
    {
        $client = $this->person('client', 'MD');
        $paPro  = $this->person('professional', 'PA');
        $mdPro  = $this->person('professional', 'MD');

        // Posting a request for an event in PA.
        $matchState = StateMatching::requestState($client, 'PA');

        $this->assertSame('MD', $matchState);
        $this->assertNotSame(StateMatching::stateOf($paPro), $matchState);
        $this->assertSame(StateMatching::stateOf($mdPro), $matchState);

        // Hiring one professional directly — the same verdict, both ways.
        $this->assertFalse(StateMatching::allows($client, $paPro));
        $this->assertTrue(StateMatching::allows($client, $mdPro));
    }

    /** The forms no longer offer a choice that changes nothing. */
    public function test_the_request_forms_do_not_ask_for_an_event_state(): void
    {
        foreach ([
            'client/bsr/wizard.blade.php',
            'client/esr/create.blade.php',
        ] as $view) {
            $html = file_get_contents(resource_path('views/'.$view));
            $code = preg_replace('/\{\{--.*?--\}\}/s', '', $html);

            $this->assertStringNotContainsString(
                'name="event_state"',
                $code,
                "{$view} still offers a state selector that cannot change the outcome.",
            );
        }
    }

    /** And they say plainly who will actually see the request. */
    public function test_the_forms_state_which_professionals_will_see_it(): void
    {
        $client = $this->person('client', 'MD');

        $this->actingAs($client)->get(route('client.esr.create'))
            ->assertSuccessful()
            ->assertSee('Maryland', false)
            ->assertSee('within one state', false);
    }
}
