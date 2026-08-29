<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A tool must survive its own optional fields being left empty.
 *
 * Three tools crashed on the most ordinary use of them — filling in what was
 * required and leaving the rest alone:
 *
 *   Contract Assistant   leave the deposit box untouched
 *   Review Writer        leave provider / service / event / thoughts empty
 *   Vendor Matchmaking   no theme, no category
 *
 * All three were the same mistake. Laravel's `nullable` permits the value to
 * BE null; it does not put the key in the validated array when the field was
 * never submitted at all. Every other optional field in these controllers was
 * read through `??`; these four were read directly, so an untouched box threw
 * "Undefined array key".
 *
 * The rule this file holds: fill in only what is REQUIRED, and the tool must
 * still answer.
 */
class AiToolsOptionalFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function client(): User
    {
        // An admin bypasses the credit meter, which is exactly the path a real
        // client does NOT take — so these run as a client.
        $u = User::factory()->create();
        $u->assignRole('client');

        return $u->fresh();
    }

    public function test_the_contract_assistant_survives_an_untouched_deposit_box(): void
    {
        $response = $this->actingAs($this->client())
            ->postJson(route('ai-tools.contract-assistant.compute'), [
                'service'     => 'DJ',
                'total_price' => 1200,
                'event_date'  => now()->addMonths(3)->toDateString(),
                // deposit_pct deliberately absent — this is what threw
            ]);

        $response->assertSuccessful();
        $response->assertJsonPath('success', true);
    }

    /** And the default deposit is applied rather than the tool falling over. */
    public function test_an_omitted_deposit_falls_back_instead_of_failing(): void
    {
        $omitted = $this->actingAs($this->client())
            ->postJson(route('ai-tools.contract-assistant.compute'), [
                'service' => 'DJ', 'total_price' => 1000,
                'event_date' => now()->addMonths(3)->toDateString(),
            ])->json();

        $explicit = $this->actingAs($this->client())
            ->postJson(route('ai-tools.contract-assistant.compute'), [
                'service' => 'DJ', 'total_price' => 1000,
                'event_date' => now()->addMonths(3)->toDateString(),
                'deposit_pct' => 30,
            ])->json();

        // 30% is the documented default, so both should agree.
        $this->assertSame($explicit['result'] ?? null, $omitted['result'] ?? null);
    }

    public function test_the_review_writer_survives_an_empty_form(): void
    {
        $this->actingAs($this->client())
            ->postJson(route('ai-tools.review-writer.compose'), [])
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    public function test_vendor_matchmaking_survives_no_theme_and_no_category(): void
    {
        $this->actingAs($this->client())
            ->postJson(route('ai-tools.vendor-matchmaking.match'), [])
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    }

    /**
     * The tools still answer DIFFERENTLY for different input — the point of
     * the sweep was that none of them are static.
     */
    public function test_a_tool_answers_differently_for_different_input(): void
    {
        $client = $this->client();

        $small = $this->actingAs($client)->postJson(route('ai-tools.guest-capacity.compute'), [
            'room_sqft' => 600, 'seating_style' => 'theater', 'guest_count' => 40,
        ])->json('result');

        $large = $this->actingAs($client)->postJson(route('ai-tools.guest-capacity.compute'), [
            'room_sqft' => 4000, 'seating_style' => 'banquet', 'guest_count' => 250,
        ])->json('result');

        $this->assertNotNull($small);
        $this->assertNotEquals($small, $large, 'The tool returned the same answer for a different room.');
    }
}
