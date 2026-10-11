<?php

namespace Tests\Feature;

use App\Domain\Taxonomy\EventTypeAlias;
use App\Domain\Taxonomy\ServiceRelevance;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The occasion a client picks reaches the taxonomy that knows about it.
 *
 * There are two event-type lists. `config/event-types.php` has the 47 a client
 * chooses from; the `categories` table has 106 rows of `kind = event_type`,
 * and those carry the archetype, the service coverage and the relevance
 * matrix. Only 21 names are in both, so on 26 of the 47 picks every lookup by
 * name came back empty and the level 1 to level 3 ordering silently vanished.
 *
 * Sir Peter saw it from the outside: "ANY searches needing the L1-L3 have the
 * data even its a drop down window, i seen where the data is not full there."
 *
 * The event types here are loaded from the same sheet the importer reads,
 * rather than from a handful made up for the test, because the whole point is
 * whether the two real lists line up.
 */
class TheTwoEventListsAgreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $sheet = json_decode(
            file_get_contents(base_path('database/seeders/data/taxonomy_v2.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        foreach ($sheet['event_types'] as $i => $row) {
            Category::create([
                'name'      => $row['name'],
                'slug'      => Str::slug($row['name']) . '-' . $i,
                'kind'      => Category::EVENT_TYPE,
                'archetype' => $row['archetype'] ?? null,
                'is_active' => true,
            ]);
        }
    }

    /** An alias points at a row that is really there. */
    public function test_every_alias_lands_on_a_real_event_type(): void
    {
        $known = Category::query()
            ->where('kind', Category::EVENT_TYPE)
            ->pluck('name')
            ->map(fn ($n) => mb_strtolower($n))
            ->all();

        foreach (EventTypeAlias::map() as $picked => $target) {
            $this->assertContains(
                $target,
                $known,
                "The alias for \"{$picked}\" points at \"{$target}\", which is not an event type.",
            );
        }
    }

    /**
     * The bridge carries most of the occasions across.
     *
     * A floor rather than an exact number, because either list may grow. What
     * matters is that it is well above where it started: before the alias map
     * only 21 of the 47 reached their archetype.
     */
    public function test_most_occasions_now_reach_their_relevance(): void
    {
        $archetypes = ServiceRelevance::archetypeByEventType();
        $types = config('event-types.types', []);

        $reaching = collect($types)
            ->filter(fn ($t) => isset($archetypes[mb_strtolower(EventTypeAlias::resolve($t))]))
            ->count();

        $this->assertGreaterThanOrEqual(
            30,
            $reaching,
            "Only {$reaching} of " . count($types) . ' occasions reach their archetype; an alias has probably broken.',
        );
    }

    /**
     * The gap is a known list, not a growing one.
     *
     * These are occasions the table genuinely has no row for, or where it
     * offers several and none is the obvious answer. The picker sorts them A
     * to Z and claims nothing. If a new name turns up here, somebody added an
     * occasion with no event type behind it.
     */
    public function test_the_gap_does_not_quietly_grow(): void
    {
        $unresolved = collect(EventTypeAlias::unresolved())
            ->map(fn ($n) => mb_strtolower($n))
            ->sort()
            ->values()
            ->all();

        $expected = collect(EventTypeAlias::knownGaps())->sort()->values()->all();

        $this->assertSame(
            $expected,
            $unresolved,
            'The occasions with no event type behind them have changed. '
            . 'Add the row, add an alias, or record it as a known gap.',
        );
    }

    /** Resolving leaves alone anything it has no pair for. */
    public function test_an_unknown_name_passes_through_untouched(): void
    {
        $this->assertSame('Gala', EventTypeAlias::resolve('Gala'));
        $this->assertSame('Wedding', EventTypeAlias::resolve('Wedding'));
        $this->assertNull(EventTypeAlias::resolve(null));
    }

    /** Case and stray spaces are not a reason to miss. */
    public function test_it_is_not_fussy_about_how_the_name_arrives(): void
    {
        foreach (['Prom', 'prom', '  PROM  '] as $written) {
            $this->assertSame(
                'prom night',
                EventTypeAlias::resolve($written),
                "\"{$written}\" should still find Prom Night.",
            );
        }
    }

    /**
     * The pairs that would have been wrong if anyone had guessed.
     *
     * Fuzzy matching put "New Year Party" with "Chinese New Year Party" and
     * "Graduation" with "Bootcamp Graduation". Both are plausible by string
     * distance and both are the wrong event, which is worse than no answer:
     * the client is handed another occasion's services and told nothing.
     */
    public function test_the_tempting_wrong_answers_are_not_taken(): void
    {
        $this->assertSame("new year's eve party", EventTypeAlias::resolve('New Year Party'));
        $this->assertSame('graduation party', EventTypeAlias::resolve('Graduation'));
    }

    /** The picker's script gets the same bridge the server used. */
    public function test_the_live_picker_ships_the_aliases(): void
    {
        $services = collect([
            Category::create([
                'name' => 'Full-Service Catering', 'slug' => 'svc-full-catering',
                'kind' => Category::SERVICE, 'is_active' => true,
            ]),
        ]);

        $html = view('components.service-picker', [
            'categories'    => $services,
            'liveEventType' => true,
        ])->render();

        $this->assertStringContainsString('data-svc-event-alias', $html);
        $this->assertStringContainsString('prom night', $html);
    }

    /**
     * And the script uses it rather than merely carrying it.
     *
     * Read from the file: the picker pushes its script onto a stack that only
     * a full layout renders, so the component on its own returns the markup
     * without it. Shipping the alias table and then not consulting it would
     * leave the live picker exactly as broken as before, with a test passing.
     */
    public function test_the_script_puts_the_alias_in_front_of_the_lookup(): void
    {
        $source = file_get_contents(
            resource_path('views/components/service-picker.blade.php'),
        );

        $this->assertStringContainsString(
            'if (key && ALIAS[key]) key = ALIAS[key];',
            $source,
        );

        // Before the lookup, not after it.
        $this->assertLessThan(
            strpos($source, 'RELEVANCE.archetypeOf[key]'),
            strpos($source, 'ALIAS[key]'),
            'The alias has to be applied before the archetype is looked up.',
        );
    }
}
