<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The search box on /event-types searches event types.
 *
 * It submitted to Find Professionals, so typing "wedding" on a page headed
 * "Search event types…" left the page and asked which PROFESSIONALS have
 * wedding in their name or headline — a different question, usually answered
 * with nothing. From the visitor's side the box simply did not work.
 */
class EventTypeSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            ['Wedding', 'Milestone & Personal Celebrations'],
            ['Vow Renewal', 'Milestone & Personal Celebrations'],
            ['Award Ceremony', 'Corporate & Business Events'],
        ] as [$name, $archetype]) {
            Category::create([
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name),
                'kind' => Category::EVENT_TYPE,
                'archetype' => $archetype,
                'is_active' => true,
            ]);
        }
    }

    public function test_it_searches_this_page_rather_than_leaving_it(): void
    {
        $html = $this->get(route('public.event-types'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString(
            'action="'.route('public.event-types').'"',
            $html,
            'The search box still submits somewhere else.',
        );
    }

    /**
     * The wall only. The "Most to plan for" rail is a separate list of the
     * busiest occasions and is not the search result, so asserting against the
     * whole page would pass whatever the search did.
     */
    private function wall(array $query): string
    {
        $html = $this->get(route('public.event-types', $query))
            ->assertSuccessful()
            ->getContent();

        $from = strpos($html, 'class="et-all"');
        $to   = strpos($html, 'class="et-all-foot"', $from ?: 0);

        return $from === false ? '' : substr($html, $from, ($to ?: strlen($html)) - $from);
    }

    public function test_a_search_narrows_the_wall(): void
    {
        $wall = $this->wall(['q' => 'wedding']);

        $this->assertStringContainsString('Wedding', $wall);
        $this->assertStringNotContainsString('Award Ceremony', $wall);

        // What was searched is on screen, so the shorter list is explained.
        $this->assertStringContainsString('matching', $this->get(route('public.event-types', ['q' => 'wedding']))->getContent());
    }

    /** The term survives in the box, so it can be corrected rather than retyped. */
    public function test_the_term_stays_in_the_box(): void
    {
        $this->get(route('public.event-types', ['q' => 'vow']))
            ->assertSuccessful()
            ->assertSee('value="vow"', false);
    }

    /** A group chip narrows the search instead of discarding it. */
    public function test_a_group_chip_keeps_the_search(): void
    {
        $html = $this->get(route('public.event-types', ['q' => 'renewal']))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString(
            e(route('public.event-types', [
                'group' => 'Milestone & Personal Celebrations', 'q' => 'renewal',
            ])),
            $html,
            'A group chip throws the search away.',
        );
    }

    public function test_nothing_found_says_what_was_searched(): void
    {
        $this->get(route('public.event-types', ['q' => 'zzzznothing']))
            ->assertSuccessful()
            ->assertSee('No event type matches')
            ->assertSee('zzzznothing');
    }

    /** A search and a group narrow together, not one instead of the other. */
    public function test_a_search_and_a_group_apply_together(): void
    {
        $wall = $this->wall(['q' => 'e', 'group' => 'Corporate & Business Events']);

        $this->assertStringContainsString('Award Ceremony', $wall);
        $this->assertStringNotContainsString('Vow Renewal', $wall);
    }
}
