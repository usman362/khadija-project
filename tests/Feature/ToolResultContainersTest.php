<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every element a tool's script writes its result into must exist on the page.
 *
 * This is the test that was missing. Emptying the fabricated dashboards out of
 * the AI tools was right; DELETING the containers with them was not. On Guest
 * Capacity the request succeeded, the server returned a real answer, and then
 * render() reached for #gcStats, found nothing, threw, and the catch told the
 * client "Network error. Please try again." — for a request that had worked.
 *
 * Page-render tests could not see this: the page looked fine, the form was
 * there, the button was there. Only pressing it failed. So this walks each
 * tool's script, collects every id it calls getElementById on, and checks the
 * page actually carries that id.
 *
 * Ids the script CREATES at runtime (passed into a builder that emits the
 * markup) are excluded — they are not expected in the served HTML.
 */
class ToolResultContainersTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string, 1: string}> */
    public static function toolViews(): array
    {
        return [
            'checklist'      => ['ai-tools.checklist-generator', 'resources/views/ai-tools/checklist-generator.blade.php'],
            'event planner'  => ['ai-tools.event-planner',       'resources/views/ai-tools/event-planner.blade.php'],
            'venue analyzer' => ['ai-tools.venue-analyzer',      'resources/views/ai-tools/venue-analyzer.blade.php'],
            'guest capacity' => ['ai-tools.guest-capacity',      'resources/views/ai-tools/guest-capacity.blade.php'],
            'timeline'       => ['ai-tools.timeline-builder',    'resources/views/ai-tools/timeline-builder.blade.php'],
            'style'          => ['ai-tools.theme-advisor',       'resources/views/ai-tools/theme-advisor.blade.php'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('toolViews')]
    public function test_every_element_the_script_writes_into_exists(string $route, string $view): void
    {
        $source = file_get_contents(base_path($view));

        preg_match_all("/getElementById\(\s*'([A-Za-z0-9_-]+)'\s*\)/", $source, $m);
        $wanted = array_unique($m[1]);

        $this->assertNotEmpty($wanted, "No script ids found in {$view} — has the tool lost its script?");

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Both branches, since each level renders a different half of the page.
        $html = '';
        foreach (['manual', 'semi', 'maximum'] as $level) {
            $html .= $this->actingAs($admin->fresh())
                ->get(route($route, ['preview' => $level]))
                ->assertSuccessful()
                ->getContent();
        }

        foreach ($wanted as $id) {
            // Skip ids the script BUILDS rather than expects to find: those are
            // passed as an argument to a markup builder, e.g.
            //   statCard('Guest Comfort Score', score + '%', tone, 'gcScoreCard')
            if (str_contains($source, ", '{$id}')")) {
                continue;
            }

            $this->assertStringContainsString(
                'id="' . $id . '"',
                $html,
                "The script writes into #{$id}, but no element with that id is on the page. "
                . 'Pressing the button will fail after a successful request.'
            );
        }
    }

    /**
     * And the button must actually produce an answer. Ali pressed "Suggest
     * Capacity Plan" with a real room and got "Network error"; the request had
     * succeeded, so nothing server-side would ever have shown it.
     */
    public function test_the_calculator_returns_a_result_for_a_real_room(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $response = $this->actingAs($client->fresh())
            ->postJson(route('ai-tools.guest-capacity.compute'), [
                'room_sqft'     => 2400,
                'seating_style' => 'banquet',
                'guest_count'   => 120,
            ]);

        $response->assertSuccessful();
        $response->assertJsonPath('success', true);

        // The exact shape render() reads. A result the page cannot draw is not
        // a result — that is the whole lesson of this file.
        $result = $response->json('result');
        $this->assertIsArray($result['capacity'] ?? null, 'render() reads result.capacity');
        foreach (['expected', 'comfort', 'legal'] as $key) {
            $this->assertArrayHasKey($key, $result['capacity']);
        }
    }

    /**
     * Style & Inspiration opened onto three finished theme concepts at 98/95/92%
     * Match and a Sage Green palette. Ali set the primary colour to red and the
     * formality to Casual, and still got sage green — the surest proof a result
     * was rendered before the question was asked.
     */
    public function test_style_and_inspiration_answers_the_colour_it_was_given(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $red = $this->actingAs($client->fresh())
            ->postJson(route('ai-tools.theme-advisor.compute'), [
                'event_type' => 'wedding', 'season' => 'spring',
                'primary_color' => '#ef3235', 'formality' => 'casual',
            ])->assertSuccessful()->json('result.palette');

        $blue = $this->actingAs($client->fresh())
            ->postJson(route('ai-tools.theme-advisor.compute'), [
                'event_type' => 'wedding', 'season' => 'spring',
                'primary_color' => '#1d4ed8', 'formality' => 'casual',
            ])->assertSuccessful()->json('result.palette');

        $this->assertNotEmpty($red);
        $this->assertNotEquals($blue, $red, 'The palette ignored the colour it was given.');

        // And the old canned palette must never come back.
        $hexes = strtolower(json_encode($red));
        foreach (['#5a7d57', '#f4d9d0', '#e8b4b8', '#c9a227'] as $ghost) {
            $this->assertStringNotContainsString($ghost, $hexes);
        }
    }

    /** The page must no longer ship a finished answer with it. */
    public function test_style_and_inspiration_ships_no_pre_rendered_answer(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $response = $this->actingAs($client->fresh())->get(route('ai-tools.theme-advisor'));

        $response->assertSuccessful();

        // The pre-rendered sections themselves. Not the colour NAMES: the
        // Starter branch seeds a few editable starter swatches into its
        // build-it-yourself palette, which is an affordance the client can
        // rename or remove — not an answer handed to them.
        // Assert MARKUP, never a bare class name — the stylesheet keeps
        // emitting .ta-theme and .ta-best whether or not anything uses them.
        foreach (['Your Theme Concepts', 'Recommended Color Palette', '<div class="ta-theme'] as $ghost) {
            $response->assertDontSee($ghost, false);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }
}