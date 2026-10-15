<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Three webpages in the left column, with their tabs inside them.
 *
 * Sir Peter settled this over three messages in two days, and this file has
 * been rewritten each time, so it is worth recording where it landed and why.
 *
 *  - 10 Oct: "Calendar & Availability" should become subpages rather than one
 *    page of tabs. Built as three nested menu groups.
 *  - 12 Oct, 10:21pm, with a sidebar image: My Calendar standing alone, and
 *    Availability and Hired Talent as groups with subpages under them.
 *  - 12 Oct, 10:28pm, seven minutes later: "the three availabilities (My
 *    Availability, Professionals and Influencers) can be setup as tabs instead
 *    of subpages. The same thing for Hired ... But I want the clients
 *    calendar, Availability, and Hired as separate webpages."
 *
 * So: three plain links, no nesting at all, and the subpages became tabs on
 * the page they belong to. He then corrected the last label himself: "Use the
 * 'Hired Talent' instead of just 'Hired'."
 *
 * The move that matters underneath the menu: My Availability left the calendar
 * page, where it had been the second tab, and became the first tab on
 * Availability. The calendar is now only a calendar.
 */
class TheCalendarMenuIsThreeGroupsTest extends TestCase
{
    use RefreshDatabase;

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
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = User::findOrFail($this->client->id);
    }

    private function at(string $url): string
    {
        return $this->actingAs($this->client)->get($url)->assertSuccessful()->getContent();
    }

    /** The menu itself, never the page copy, which names these pages too. */
    private function menu(string $url): string
    {
        $html = $this->at($url);

        $from = strpos($html, '<nav class="cl-sidebar-nav">');
        $this->assertNotFalse($from, 'The sidebar nav has moved.');

        return substr($html, $from, strpos($html, '</nav>', $from) - $from);
    }

    /** Three links, in his order, and each one goes somewhere. */
    public function test_the_three_pages_are_plain_links(): void
    {
        $menu = $this->menu(route('client.calendar.index'));

        $expected = [
            'My Calendar'  => route('client.calendar.index'),
            'Availability' => route('client.check-availability.index'),
            'Hired Talent' => route('client.hired-talent.index'),
        ];

        $at = -1;
        foreach ($expected as $label => $url) {
            $pos = strpos($menu, '>' . "\n" . str_repeat(' ', 24) . $label);
            $pos = $pos === false ? strpos($menu, $label) : $pos;

            $this->assertNotFalse($pos, "\"{$label}\" is missing from the menu.");
            $this->assertGreaterThan($at, $pos, "\"{$label}\" is out of order.");
            $this->assertStringContainsString($url, $menu, "\"{$label}\" does not link to its page.");

            $at = $pos;
        }
    }

    /** Nothing nests any more. The subpages became tabs. */
    public function test_the_menu_has_no_nested_groups(): void
    {
        $menu = $this->menu(route('client.calendar.index'));

        $this->assertStringNotContainsString('cl-nav-sub', $menu, 'The sub-lists should be gone.');
        $this->assertStringNotContainsString('Check Availability', $menu, 'It is called Availability now.');
    }

    /** One page, one highlighted entry, never two and never none. */
    public function test_each_page_lights_exactly_one_entry(): void
    {
        $pages = [
            route('client.calendar.index')                                      => 'My Calendar',
            route('client.check-availability.index', ['tab' => 'mine'])         => 'Availability',
            route('client.check-availability.index', ['tab' => 'professionals']) => 'Availability',
            route('client.check-availability.index', ['tab' => 'influencers'])  => 'Availability',
            route('client.hired-talent.index', ['who' => 'professionals'])      => 'Hired Talent',
            route('client.hired-talent.index', ['who' => 'influencers'])        => 'Hired Talent',
        ];

        foreach ($pages as $url => $expected) {
            $menu = $this->menu($url);

            preg_match_all('/class="cl-nav-link active"[^>]*>\s*<svg.*?<\/svg>\s*([^<]+)/s', $menu, $m);

            $lit = array_values(array_filter(array_map('trim', $m[1] ?? [])));

            $this->assertCount(1, $lit, "Exactly one menu entry should be lit on {$url}, not " . count($lit) . '.');
            $this->assertSame($expected, $lit[0], "Wrong entry lit on {$url}.");
        }
    }

    /** Each name once. Listing a page twice is how this went wrong before. */
    public function test_nothing_is_listed_twice(): void
    {
        $menu = $this->menu(route('client.calendar.index'));

        $this->assertStringNotContainsString('Calendar &amp; Availability</a>', $menu);

        foreach (['My Calendar', 'Hired Talent'] as $name) {
            $this->assertSame(
                1,
                substr_count($menu, $name),
                "\"{$name}\" appears " . substr_count($menu, $name) . ' times in the menu.',
            );
        }
    }

    /** The three availabilities are tabs on one page, as he asked. */
    public function test_availability_carries_its_three_tabs(): void
    {
        $html = $this->at(route('client.check-availability.index'));

        foreach (['My Availability', 'Professionals', 'Influencers'] as $tab) {
            $this->assertStringContainsString($tab, $html, "The \"{$tab}\" tab is missing.");
        }

        // Landing on the page with nothing asked for opens the first tab.
        $this->assertStringContainsString('Say which days suit you', $html);
    }

    /** And Hired Talent carries its two. */
    public function test_hired_talent_carries_its_two_tabs(): void
    {
        $html = $this->at(route('client.hired-talent.index'));

        $this->assertStringContainsString('ht-tabs', $html);

        foreach (['Professionals', 'Influencers'] as $tab) {
            $this->assertStringContainsString($tab, $html, "The \"{$tab}\" tab is missing.");
        }
    }
}
