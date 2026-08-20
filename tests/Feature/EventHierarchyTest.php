<?php

namespace Tests\Feature;

use App\Domain\Taxonomy\EventHierarchy;
use App\Models\Category;
use App\Models\CategoryRelevance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Peter's Event Hierarchy workflow: Main Event → Main Service → Sub-Main
 * Service → Specific Service Component.
 *
 * The diagram sets out three outcomes and these hold all three, plus the rule
 * it closes on — the system only shows data that exists in the source.
 *
 * Outcome B is the one worth being careful about. A disabled dropdown stops a
 * mouse; it does not stop a URL, and "the system prevents skipping Level 1"
 * has to be true of the system rather than of the cursor.
 */
class EventHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private Category $wedding;
    private Category $catering;
    private Category $cakes;

    protected function setUp(): void
    {
        parent::setUp();

        config(['taxonomy.version' => 'v2']);

        $this->wedding = $this->make('Wedding', Category::EVENT_TYPE, ['archetype' => 'Wedding & Related']);
        $this->make('Funeral Service', Category::EVENT_TYPE, ['archetype' => 'Memorial']);

        $this->catering = $this->make('Catering & Food Services', Category::SERVICE_CATEGORY);
        $lighting = $this->make('Lighting & AV', Category::SERVICE_CATEGORY);

        $this->cakes = $this->make('Celebration Cakes', Category::SERVICE, ['parent_id' => $this->catering->id]);
        $this->make('Candy Buffets', Category::SERVICE, ['parent_id' => $this->catering->id]);

        CategoryRelevance::create(['archetype' => 'Wedding & Related', 'category_id' => $this->catering->id, 'tier' => 'Essential']);
        CategoryRelevance::create(['archetype' => 'Wedding & Related', 'category_id' => $lighting->id, 'tier' => 'Occasional']);

        \Illuminate\Support\Facades\Cache::flush();
    }

    private function make(string $name, string $kind, array $over = []): Category
    {
        return Category::create(array_merge([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name) . '-eh',
            'kind' => $kind,
            'taxonomy_version' => 'v2',
            'is_active' => true,
        ], $over));
    }

    // ── The page's starting state: nothing chosen ────────────────

    public function test_the_page_opens_with_only_level_one_available(): void
    {
        $html = $this->get('/event-hierarchy')->assertOk()->getContent();

        $this->assertStringContainsString('Please start by selecting a Main Event (Level 1)', $html);
        $this->assertStringContainsString('Wedding', $html);

        // Levels 2, 3 and 4 render disabled — counted on the select elements
        // themselves, not on the prompt text, which the script also contains.
        preg_match_all('/<select[^>]*data-level="(\d)"[^>]*>/', $html, $m, PREG_SET_ORDER);

        $this->assertCount(4, $m, 'the four levels should all be drawn');

        foreach ($m as $tag) {
            $isFirst = $tag[1] === '1';
            $this->assertSame($isFirst, ! str_contains($tag[0], 'disabled'),
                "level {$tag[1]} should " . ($isFirst ? 'not ' : '') . 'be disabled at the start');
        }
    }

    // ── Outcome A: a Main Event is chosen ────────────────────────

    public function test_choosing_a_main_event_opens_level_two_with_its_own_services(): void
    {
        $response = $this->getJson('/event-hierarchy/options?level=2&parent=' . $this->wedding->id)
            ->assertOk();

        $names = collect($response->json('options'))->pluck('name')->all();

        $this->assertContains('Catering & Food Services', $names);
        $this->assertFalse($response->json('blocked'));
    }

    public function test_level_two_is_ordered_by_the_masterlists_ranking(): void
    {
        /*
         * A tier is a ranking, not a permission. "Occasional" for weddings
         * still belongs at a wedding — it sorts last, it is not hidden. Hiding
         * it would throw away the distinction the matrix was written to make.
         */
        $options = $this->getJson('/event-hierarchy/options?level=2&parent=' . $this->wedding->id)
            ->assertOk()->json('options');

        $this->assertSame('Catering & Food Services', $options[0]['name']);
        $this->assertSame('Essential', $options[0]['tier']);

        $names = collect($options)->pluck('name')->all();
        $this->assertContains('Lighting & AV', $names, 'an Occasional service was hidden rather than ranked');
    }

    public function test_level_three_lists_what_sits_under_the_chosen_service(): void
    {
        $names = collect($this->getJson('/event-hierarchy/options?level=3&parent=' . $this->catering->id)
            ->assertOk()->json('options'))->pluck('name')->all();

        $this->assertEqualsCanonicalizing(['Candy Buffets', 'Celebration Cakes'], $names);
    }

    // ── Outcome B: the skip ──────────────────────────────────────

    public function test_asking_for_level_two_without_a_main_event_is_refused(): void
    {
        $this->getJson('/event-hierarchy/options?level=2')
            ->assertStatus(422)
            ->assertJson([
                'blocked' => true,
                'message' => 'Please select a Main Event (Level 1) first.',
                'options' => [],
            ]);
    }

    public function test_the_skip_is_refused_at_every_level_not_just_the_second(): void
    {
        foreach ([2, 3, 4] as $level) {
            $this->getJson('/event-hierarchy/options?level=' . $level)
                ->assertStatus(422)
                ->assertJsonPath('blocked', true);
        }
    }

    public function test_a_level_that_does_not_exist_is_refused(): void
    {
        $this->getJson('/event-hierarchy/options?level=9&parent=1')->assertStatus(422);
        $this->getJson('/event-hierarchy/options?level=0&parent=1')->assertStatus(422);
    }

    // ── "Only data that exists in the source" ────────────────────

    public function test_a_level_with_nothing_behind_it_says_so_rather_than_inventing_one(): void
    {
        /*
         * The live tree stops at three levels: no service has children. The
         * diagram's own closing rule is that only data in the source is shown,
         * so Level 4 comes back empty with a reason instead of being filled.
         */
        $response = $this->getJson('/event-hierarchy/options?level=4&parent=' . $this->cakes->id)
            ->assertOk();

        $this->assertSame([], $response->json('options'));
        $this->assertStringContainsString('No specific components', $response->json('empty_reason'));
    }

    public function test_the_page_states_how_deep_the_live_tree_actually_goes(): void
    {
        // Said out loud rather than left for somebody to discover: the
        // four-level tree the workflow was drawn from is the old v1 import.
        $this->assertSame(3, EventHierarchy::depth());

        $this->get('/event-hierarchy')
            ->assertOk()
            ->assertSee('3 levels')
            ->assertSee('Level 4 stays');
    }

    public function test_an_event_type_with_no_ranking_still_gets_every_service(): void
    {
        // Ranked or not, the categories exist and the client can want them.
        $funeral = Category::where('name', 'Funeral Service')->firstOrFail();

        $options = $this->getJson('/event-hierarchy/options?level=2&parent=' . $funeral->id)
            ->assertOk()->json('options');

        $this->assertCount(2, $options);
        $this->assertNull($options[0]['tier']);
    }
}
