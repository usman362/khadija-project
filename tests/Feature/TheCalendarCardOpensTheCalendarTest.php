<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 7 October: "lets add a weblink so the dashboard's mini calendar
 * allows the user to click a weblink to be sent to the
 * https://gigresource.com/client/calendar".
 *
 * The card had existed since before the full page did, and nothing was ever
 * added to join them: the only way to the calendar was the left menu. The link
 * carries the month and the view the card is showing, so arriving on the full
 * page does not throw the reader back to today — the commonest way a "go to
 * the big version" link turns out to be useless.
 *
 * It is deliberately not a data-live link. Those are swapped into the
 * dashboard in place; this one is meant to leave it.
 */
class TheCalendarCardOpensTheCalendarTest extends TestCase
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
        $user = User::factory()->create();
        $user->assignRole('client');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    /** The card's head holds a link out of it. */
    public function test_the_calendar_card_links_to_the_calendar_page(): void
    {
        $html = $this->actingAs($this->client())->get('/client/dashboard')
            ->assertSuccessful()->getContent();

        $card = $this->card($html);

        $this->assertMatchesRegularExpression(
            '/<a[^>]+href="[^"]*\/client\/calendar[^"]*"[^>]*>\s*Open Calendar\s*<\/a>/',
            $card,
            'The calendar card has no link to the calendar page.'
        );
    }

    /** A link to a page that is not there is worse than no link. */
    public function test_the_link_goes_somewhere(): void
    {
        $user = $this->client();

        $html = $this->actingAs($user)->get('/client/dashboard')->assertSuccessful()->getContent();

        $href = $this->href($this->card($html));

        $this->actingAs($user)->get($href)->assertSuccessful();
    }

    /**
     * Looking at December and clicking through should not land on October.
     */
    public function test_the_link_carries_the_month_being_looked_at(): void
    {
        $user = $this->client();

        $html = $this->actingAs($user)->get('/client/dashboard?calview=month&cal=2027-02-10')
            ->assertSuccessful()->getContent();

        $href = $this->href($this->card($html));

        $this->assertStringContainsString('cal=2027-02', $href, 'The link drops the month.');
        $this->assertStringContainsString('calview=month', $href, 'The link drops the view.');

        // And the page it reaches is actually showing that month.
        $this->actingAs($user)->get($href)->assertSuccessful()->assertSee('February 2027');
    }

    /**
     * data-live links are fetched and swapped into the dashboard. This one
     * must not be: its whole purpose is to leave.
     */
    public function test_the_link_leaves_the_dashboard(): void
    {
        $html = $this->actingAs($this->client())->get('/client/dashboard')
            ->assertSuccessful()->getContent();

        preg_match('/<a\b[^>]*\/client\/calendar[^>]*>/', $this->card($html), $tag);

        $this->assertNotEmpty($tag, 'No link to the calendar page to check.');
        $this->assertStringNotContainsString(
            'data-live',
            $tag[0],
            'The calendar link is data-live, so it would be swapped into the dashboard instead of opening the page.'
        );
    }

    /** The head of the calendar card, and nothing else on the dashboard. */
    private function card(string $html): string
    {
        $start = strpos($html, 'id="odCalCard"');
        $this->assertNotFalse($start, 'The dashboard has no calendar card.');

        $head = strpos($html, 'od-card-head', $start);
        $this->assertNotFalse($head, 'The calendar card has no head.');

        $end = strpos($html, '</div>', $head);

        return substr($html, $head, $end - $head);
    }

    private function href(string $card): string
    {
        preg_match('/href="([^"]*\/client\/calendar[^"]*)"/', $card, $m);
        $this->assertNotEmpty($m, 'No link to the calendar page in the card head.');

        return html_entity_decode($m[1]);
    }
}
