<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A debugging pass over every client-side AI tool: each page opens, and no
 * compute endpoint answers a question it was not asked.
 *
 * Two of them did.
 *
 *  · Review Builder fell back to the worked example on the page, so submitting
 *    a blank form returned a finished review naming "Sarah Bennett
 *    Photography" — a business that does not exist — written in the first
 *    person and ready to paste.
 *
 *  · Best Match defaulted a missing budget to $1,000. rankReal() reads 0 as
 *    "no ceiling", so the default was a real filter nobody set, quietly hiding
 *    every professional who publishes a rate above it.
 */
class AiToolsDoNotInventTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENT_TOOLS = [
        'ai-tools.budget-allocator',
        'ai-tools.vendor-matchmaking',
        'ai-tools.event-planner',
        'ai-tools.timeline-builder',
        'ai-tools.venue-analyzer',
        'ai-tools.checklist-generator',
        'ai-tools.guest-capacity',
        'ai-tools.theme-advisor',
        'ai-tools.review-writer',
        'ai-tools.contract-assistant',
        'ai-tools.message-assistant',
        'ai-tools.translator',
    ];

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
        ]);
        $this->client = $this->client->fresh();
    }

    public function test_every_client_tool_opens(): void
    {
        foreach (self::CLIENT_TOOLS as $route) {
            $this->assertTrue(\Route::has($route), "{$route} has no route.");

            $this->actingAs($this->client)
                ->get(route($route))
                ->assertSuccessful();
        }
    }

    /** A review is a statement about somebody. That part cannot be filled in for them. */
    public function test_the_review_builder_refuses_to_name_a_professional_for_you(): void
    {
        $this->actingAs($this->client)
            ->postJson(route('ai-tools.review-writer.compose'), [
                'rating' => 5, 'thoughts' => 'great',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('provider');

        $this->actingAs($this->client)
            ->postJson(route('ai-tools.review-writer.compose'), [
                'provider' => '', 'rating' => 5,
            ])
            ->assertStatus(422);
    }

    /** And writes about the professional it was actually given. */
    public function test_the_review_builder_writes_about_who_it_was_given(): void
    {
        $json = $this->actingAs($this->client)
            ->postJson(route('ai-tools.review-writer.compose'), [
                'provider' => 'Rossi Studio', 'rating' => 5, 'tone' => 'balanced',
                'thoughts' => 'on time, great photos',
            ])
            ->assertSuccessful()
            ->json();

        $short = $json['review']['formats']['short'];

        $this->assertStringContainsString('Rossi Studio', $short);
        $this->assertStringNotContainsString('Sarah Bennett', $short);
    }

    /** A budget nobody set is not a filter. */
    public function test_best_match_applies_no_ceiling_when_none_was_given(): void
    {
        $json = $this->actingAs($this->client)
            ->postJson(route('ai-tools.vendor-matchmaking.match'), ['theme' => 'garden wedding'])
            ->assertSuccessful()
            ->json();

        $this->assertSame(0, $json['budget'],
            'A missing budget became a $1,000 ceiling, hiding every professional above it.');
    }

    public function test_best_match_keeps_the_ceiling_it_was_given(): void
    {
        $json = $this->actingAs($this->client)
            ->postJson(route('ai-tools.vendor-matchmaking.match'), [
                'theme' => 'garden wedding', 'max_budget' => 20000,
            ])
            ->assertSuccessful()
            ->json();

        $this->assertSame(20000, $json['budget']);
    }

    /**
     * Nothing 500s on a blank submission: every compute endpoint either
     * refuses with a reason or answers, and none of them fall over.
     */
    public function test_no_compute_endpoint_breaks_on_an_empty_submission(): void
    {
        $checked = 0;

        foreach (\Route::getRoutes() as $route) {
            $name = $route->getName();

            if (! $name || ! str_starts_with($name, 'ai-tools.') || ! in_array('POST', $route->methods(), true)) {
                continue;
            }

            $status = $this->actingAs($this->client)->post(route($name), [])->getStatusCode();

            $this->assertLessThan(500, $status, "{$name} fell over on an empty submission.");
            $checked++;
        }

        $this->assertGreaterThan(15, $checked, 'The sweep found almost no endpoints — it is not testing anything.');
    }
}
