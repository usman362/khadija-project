<?php

namespace Tests\Feature;

use App\Models\{Category, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A dropdown long enough to need looking through can be typed into.
 *
 * Event type is 106 occasions and the category filter on My Events is 375 —
 * a native <select> gives you no way to search either: you scroll, or you know
 * the first letter and hope.
 *
 * The enhancement upgrades the <select> that is already on the page rather
 * than replacing it, so the element stays the thing that submits and every
 * existing `change` listener still hears it. With the script off, the page is
 * exactly what it was.
 */
class LongDropdownsAreSearchableTest extends TestCase
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
        $this->client = $this->client->fresh();
    }

    /** Every client page gets it, because it is on the layout. */
    public function test_client_pages_ship_the_enhancement(): void
    {
        foreach (['client.dashboard', 'client.events.index', 'client.esr.create'] as $route) {
            $html = $this->actingAs($this->client)->get(route($route))
                ->assertSuccessful()->getContent();

            $this->assertStringContainsString('ss-host', $html, "{$route} did not ship it.");
            $this->assertStringContainsString('MIN_OPTIONS', $html);
        }
    }

    /**
     * The select is upgraded, never replaced. If this ever became a rendered
     * widget instead, the form would stop submitting the field.
     */
    public function test_it_upgrades_the_select_rather_than_replacing_it(): void
    {
        $html = $this->actingAs($this->client)->get(route('client.dashboard'))->getContent();

        // The visible control is built from the select that is already there…
        $this->assertStringContainsString("querySelectorAll('select')", $html);
        // …and the page's own listeners are told, on the select itself.
        $this->assertStringContainsString("select.dispatchEvent(new Event('change'", $html);
    }

    /** Short lists stay native — a panel for four options is worse than a select. */
    public function test_short_lists_are_left_alone(): void
    {
        $html = $this->actingAs($this->client)->get(route('client.dashboard'))->getContent();

        $this->assertMatchesRegularExpression('/MIN_OPTIONS\s*=\s*[5-9]\b|MIN_OPTIONS\s*=\s*1[0-2]\b/', $html,
            'The threshold is missing or set somewhere unreasonable.');
    }

    /** The long ones on the request wizard really are long. */
    public function test_the_event_type_list_is_long_enough_to_need_this(): void
    {
        foreach (['Wedding', 'Baby Shower', 'Vow Renewal'] as $i => $name) {
            Category::create([
                'name' => $name, 'slug' => 'evt-'.$i,
                'kind' => Category::EVENT_TYPE, 'is_active' => true,
            ]);
        }

        $html = $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'service'))
            ->assertSuccessful()
            ->getContent();

        $this->assertStringContainsString('name="event_type"', $html);
        $this->assertStringContainsString('ss-host', $html);
    }

    /**
     * The panel escapes the card it lives in.
     *
     * The request forms and the wizard steps put these controls inside cards
     * that clip their overflow, so an absolutely positioned panel was cut off
     * at the card's edge — on the Direct Request page the search box appeared
     * and the list under it could not be seen at all. Fixed positioning takes
     * it out of every clipping context.
     */
    public function test_the_panel_is_not_clipped_by_the_card_it_sits_in(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.direct-offers.create'))
            ->assertSuccessful()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/\.ss-panel\s*\{[^}]*position:\s*fixed/',
            $html,
            'The panel is positioned inside its card, so a card that clips will cut it off.',
        );

        // Fixed means nothing moves it on its own; it is placed from the
        // control's own rectangle, and kept there while it is open.
        $this->assertStringContainsString('getBoundingClientRect()', $html);
        $this->assertStringContainsString('requestAnimationFrame', $html);
    }

    /** It opens upwards when there is no room below. */
    public function test_it_opens_upwards_when_it_has_to(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.direct-offers.create'))
            ->getContent();

        $this->assertStringContainsString('panel.style.bottom', $html);
        $this->assertStringContainsString('maxHeight', $html);
    }

    /**
     * The control sets the panel's floor, not its width.
     *
     * Sir Peter sent a screenshot of the Spending page where every event in
     * the list had been folded onto four lines. The control there is the
     * inline "All events" word, about 110px, and the panel was being pinned
     * to exactly that. Names are longer than the thing you click to see them,
     * so the width has to come from the content.
     */
    public function test_the_panel_is_not_squeezed_into_a_narrow_control(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.direct-offers.create'))
            ->getContent();

        $this->assertStringContainsString(
            "panel.style.minWidth = box.width + 'px'",
            $html,
            'The control should set the minimum width, not the width.',
        );
        $this->assertStringNotContainsString(
            "panel.style.width = box.width + 'px'",
            $html,
            'Pinning the panel to the control is what squashed the Spending list.',
        );

        // Free to grow, but not off the side of the screen, and not past a
        // width nobody wants to read across.
        $this->assertStringContainsString("panel.style.width = 'auto'", $html);
        $this->assertStringContainsString('window.innerWidth', $html);
        $this->assertStringContainsString('panel.style.maxWidth', $html);
    }

    /** A name too long for its row is cut, and still readable on hover. */
    public function test_a_long_name_is_cut_rather_than_stacked(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.direct-offers.create'))
            ->getContent();

        $opt = substr($html, strpos($html, '.ss-opt {'));
        $opt = substr($opt, 0, strpos($opt, '}'));

        foreach (['white-space: nowrap', 'text-overflow: ellipsis'] as $rule) {
            $this->assertStringContainsString(
                $rule,
                $opt,
                "Without {$rule} a long event name wraps over several lines.",
            );
        }

        $this->assertStringContainsString(
            'row.title = opt.text.trim()',
            $html,
            'A cut name has to stay reachable somewhere.',
        );
    }
}
