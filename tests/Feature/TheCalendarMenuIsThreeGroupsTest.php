<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Calendar, Check Availability and Hired Talent, each with its own pages.
 *
 * Sir Peter asked for "Calendar & Availability" to become subpages rather than
 * one page of tabs, and his own words and his own bullet list disagreed about
 * where My Availability belonged. His sidebar image settles it: it sits with
 * My Calendar, under Calendar, exactly as his sentence said. "A user looking
 * at their dates should be able to switch between their event schedule and the
 * availability they have personally set without feeling like they left the
 * calendar."
 *
 * Checking somebody else's availability and listing who you hired are the
 * other two, and each lists Professionals and Influencers under it.
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

    /** Three headings, and they are headings rather than links. */
    public function test_the_three_groups_are_there(): void
    {
        $html = $this->at(route('client.calendar.index'));

        foreach (['Calendar', 'Check Availability', 'Hired Talent'] as $group) {
            $this->assertMatchesRegularExpression(
                '/<li class="cl-nav-group">.*?' . preg_quote($group, '/') . '/s',
                $html,
                "\"{$group}\" should be a group heading in the menu.",
            );
        }
    }

    /**
     * My Availability lives with My Calendar.
     *
     * This is the half of Sir Peter's brief that contradicted itself. His
     * prose put it here; his bullet list put it under Availability; his
     * sidebar image agrees with the prose.
     */
    public function test_my_availability_sits_under_calendar(): void
    {
        $html = $this->at(route('client.calendar.index'));

        // The first sub-list on the page is Calendar's, because Calendar is
        // the first group. Sliced rather than searched for by name: both
        // entries we are looking for contain the word "Calendar".
        $block = substr($html, strpos($html, 'cl-nav-group'));
        $firstSub = substr($block, strpos($block, '<ul class="cl-nav-sub">'));
        $firstSub = substr($firstSub, 0, strpos($firstSub, '</ul>'));

        $this->assertStringContainsString('My Calendar', $firstSub);
        $this->assertStringContainsString('My Availability', $firstSub);
    }

    /** One page, one highlighted entry, never two and never none. */
    public function test_each_page_lights_exactly_one_entry(): void
    {
        $pages = [
            route('client.calendar.index')                                        => 'My Calendar',
            route('client.calendar.index', ['tab' => 'availability'])             => 'My Availability',
            route('client.check-availability.index')                              => 'Professionals',
            route('client.check-availability.index', ['who' => 'influencers'])    => 'Influencers',
            route('client.hired-talent.index', ['who' => 'professionals'])        => 'Professionals',
            route('client.hired-talent.index', ['who' => 'influencers'])          => 'Influencers',
        ];

        foreach ($pages as $url => $expected) {
            $html = $this->at($url);

            preg_match_all(
                '/<a [^>]*class="cl-nav-link active"[^>]*>\s*<span class="cl-nav-dot"><\/span>\s*([^<]+)/',
                $html,
                $m,
            );

            $lit = array_map('trim', $m[1] ?? []);

            $this->assertCount(1, $lit, "Exactly one menu entry should be lit on {$url}, not " . count($lit) . '.');
            $this->assertSame($expected, $lit[0], "Wrong entry lit on {$url}.");
        }
    }

    /**
     * The old single entry is gone, and so are the two loose ones.
     *
     * Check Availability and Hired Talent were added as flat items earlier the
     * same day. Leaving them there as well would list each page twice.
     */
    public function test_nothing_is_listed_twice(): void
    {
        $html = $this->at(route('client.calendar.index'));

        $this->assertStringNotContainsString('Calendar &amp; Availability</a>', $html);
        $this->assertSame(1, substr_count($html, '>Hired Talent'), 'Hired Talent appears more than once in the menu.');
        $this->assertSame(1, substr_count($html, '>Check Availability'), 'Check Availability appears more than once.');
    }
}
