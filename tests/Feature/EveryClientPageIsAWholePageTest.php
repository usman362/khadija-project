<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Every page a client can open is a whole, styled page.
 *
 * Ali, 27 October: "client side yeh check kro koi html layout etc break to
 * nhi horahaha hai aur basic html to kahin nhi hai."
 *
 * Two different faults, and they fail differently, so they are checked
 * separately. A page can render with its layout and still be broken markup
 * (a <div> left open swallows everything after it). A page can be perfectly
 * valid HTML and still be a bare white page, which is what "basic html"
 * means: a view that forgot its layout, or an error page dressed as content.
 *
 * Walks the routes rather than a written list, so a client page added next
 * month is covered without anybody remembering to add it here.
 */
class EveryClientPageIsAWholePageTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        $this->client = User::findOrFail($u->id);
    }

    /**
     * Client pages that take no parameters, straight from the router.
     *
     * A route with a required parameter needs a record that belongs to this
     * client to be meaningful, and inventing one per route would test the
     * factory rather than the page. Those are covered by their own tests.
     *
     * @return list<array{string,string}>
     */
    private function pages(): array
    {
        $pages = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $name = $route->getName();

            if (! $name || ! $this->isAClientPage($name)) {
                continue;
            }

            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            // Optional parameters are fine; required ones are not.
            if (preg_match('/\{[A-Za-z_]+\}/', $route->uri())) {
                continue;
            }

            $pages[$name] = [$name, $route->uri()];
        }

        ksort($pages);

        $out = array_values($pages);

        /*
         * Pages whose content is chosen by a query parameter.
         *
         * Asking for /client/profile renders one of seven branches and leaves
         * the other six unvisited, which is how a stray </div> sat in the
         * General tab. The same blind spot as the AI tools' IQ levels: one
         * request renders one branch, so the branches have to be named.
         *
         * Written out rather than derived, because the values live in a
         * controller constant in one case and in @if lines in the view in the
         * other, and guessing them is how a tab quietly stops being checked.
         */
        foreach ([
            'client.profile.index' => ['tab=general', 'tab=company', 'tab=social',
                                       'tab=notifications', 'tab=password', 'tab=modes', 'tab=danger'],
            'client.check-availability.index' => ['tab=mine', 'tab=professionals', 'tab=influencers'],
            'client.hired-talent.index'       => ['who=all', 'who=professionals', 'who=influencers', 'who=saved'],
            'client.events.index'             => ['sub=schedule', 'sub=influencers', 'sub=payments'],
            'client.calendar.index'           => ['tab=availability'],
        ] as $name => $queries) {
            if (! isset($pages[$name])) {
                continue;
            }

            foreach ($queries as $query) {
                $out[] = [$name . '?' . $query, $pages[$name][1] . '?' . $query];
            }
        }

        return $out;
    }

    /**
     * Whether a route is one the client side shows.
     *
     * Not every page a client opens is named client.*: the sidebar in
     * layouts/client.blade.php also sends them to Disputes, Cancellations,
     * Packages, Event Types, the forms library, Find Professionals and the AI
     * tools, which are shared with the professional side and named for
     * themselves. Those are read off that layout rather than listed from
     * memory, so a link added to the sidebar is covered by this.
     */
    private function isAClientPage(string $name): bool
    {
        if (str_starts_with($name, 'client.')) {
            return true;
        }

        static $fromTheSidebar = null;

        if ($fromTheSidebar === null) {
            preg_match_all(
                "/route\('([a-z0-9_.-]+)'/",
                (string) file_get_contents(resource_path('views/layouts/client.blade.php')),
                $found,
            );

            $fromTheSidebar = array_values(array_unique($found[1]));
        }

        return in_array($name, $fromTheSidebar, true);
    }

    /** The body of a response, with the layout's own <head> left out. */
    private function body(string $html): string
    {
        $at = stripos($html, '<body');

        return $at === false ? $html : substr($html, $at);
    }

    /**
     * The HTML of one client page, or null when that route is not a page.
     *
     * Decided by what the route answers with rather than by a list of names
     * to skip. client.budget-guide is named like a page and is a JSON endpoint
     * the request forms call as services are ticked; client.reports.csv is a
     * file. Both were reported as "bare HTML" by the first version of this,
     * which was true and meant nothing.
     */
    private function pageHtml(string $uri): ?string
    {
        $response = $this->actingAs($this->client)->get('/' . $uri);

        // getStatusCode, not status(): a streamed response (the CSV export)
        // has no status() to forward to and fatals on the way past.
        if ($response->baseResponse->getStatusCode() !== 200) {
            return null;
        }

        if (! str_contains((string) $response->headers->get('content-type'), 'text/html')) {
            return null;
        }

        return (string) $response->getContent();
    }

    public function test_there_are_client_pages_to_check(): void
    {
        $pages = $this->pages();

        $this->assertGreaterThan(
            50,
            count($pages),
            'The router gave almost no client pages, so the rest of this test is measuring nothing.',
        );

        /*
         * And they are really being read, not quietly skipped.
         *
         * Every check below steps over a page that is not 200 text/html, which
         * is right for the CSV and the JSON endpoint, and would also be right
         * for a page that started redirecting everybody. Without this the
         * whole test could pass while reading nothing at all.
         */
        $rendered = 0;

        foreach ($pages as [, $uri]) {
            if ($this->pageHtml($uri) !== null) {
                $rendered++;
            }
        }

        $this->assertGreaterThan(40, $rendered, "Only {$rendered} of " . count($pages) . ' client pages rendered HTML.');
    }

    /** No client page answers with a server error. */
    public function test_no_client_page_falls_over(): void
    {
        $broken = [];

        foreach ($this->pages() as [$name, $uri]) {
            $status = $this->actingAs($this->client)->get('/' . $uri)->baseResponse->getStatusCode();

            if ($status >= 500) {
                $broken[] = "{$name} ({$uri}) answered {$status}";
            }
        }

        $this->assertSame([], $broken, "Client pages returning a server error:\n" . implode("\n", $broken));
    }

    /**
     * Nothing a client opens is bare HTML.
     *
     * A page that lost its layout still answers 200 and still contains the
     * words it was asked for. What it does not have is the document around
     * them, which is why this checks the document and not the content.
     */
    public function test_no_client_page_is_bare_html(): void
    {
        $bare = [];

        foreach ($this->pages() as [$name, $uri]) {
            $html = $this->pageHtml($uri);

            if ($html === null) {
                continue;   // Not a page: a redirect, a file, a JSON answer.
            }

            foreach ([
                'no doctype'    => stripos($html, '<!doctype html') === false,
                'no <html>'     => stripos($html, '<html') === false,
                'never closed'  => stripos($html, '</html>') === false,
                'no stylesheet' => stripos($html, '<style') === false && stripos($html, '<link') === false,
            ] as $what => $missing) {
                if ($missing) {
                    $bare[] = "{$name} ({$uri}): {$what}";
                }
            }
        }

        $this->assertSame([], $bare, "Client pages that are not whole documents:\n" . implode("\n", $bare));
    }

    /**
     * The boxes a client page opens, it closes.
     *
     * One unclosed <div> is the fault that looks like "the layout broke":
     * everything after it is swallowed into a box that never ends, and the
     * footer climbs into the sidebar.
     *
     * This took two wrong tools before the right one. Counting <table> with a
     * regex reported every page in the application as broken, and both of the
     * "unclosed tables" were the word "<table>" inside a CSS comment in the
     * layout. Handing the page to DOMDocument instead produced 2,389
     * complaints, because libxml does not know HTML5 and gives up inside the
     * first inline <svg>, after which every close tag on the page is a
     * surprise to it.
     *
     * So: only <div>, which is never void, never self-closing and never part
     * of an SVG, counted after the places that talk ABOUT markup are removed.
     * It is the one tag whose imbalance actually breaks a layout, which is
     * what was asked.
     */
    public function test_client_pages_close_the_boxes_they_open(): void
    {
        $unbalanced = [];

        foreach ($this->pages() as [$name, $uri]) {
            $html = $this->pageHtml($uri);

            if ($html === null) {
                continue;
            }

            $markup = $this->markupOnly($html);

            $open  = preg_match_all('/<div(?=[\s>])/i', $markup);
            $close = preg_match_all('/<\/div\s*>/i', $markup);

            if ($open !== $close) {
                $unbalanced[] = sprintf(
                    '%s (%s): %d <div> opened, %d closed',
                    $name, $uri, $open, $close,
                );
            }
        }

        $this->assertSame([], $unbalanced, "Client pages with unbalanced tags:\n" . implode("\n", $unbalanced));
    }

    /**
     * The page with everything that merely TALKS about markup removed.
     *
     * Scripts, styles and comments all contain tag-shaped text that is not a
     * tag. Leaving them in is what made the first version of the test above
     * report the whole application as broken.
     */
    private function markupOnly(string $html): string
    {
        return (string) preg_replace(
            [
                '/<script\b[^>]*>.*?<\/script>/is',
                '/<style\b[^>]*>.*?<\/style>/is',
                '/<!--.*?-->/s',
            ],
            '',
            $html,
        );
    }

    /**
     * No client page prints Blade at the reader.
     *
     * An unrendered directive means a typo in the template that Blade handed
     * through as text: @if with a space before the bracket, a stray @endforeach.
     */
    public function test_no_client_page_leaks_its_own_template(): void
    {
        $leaks = [];

        foreach ($this->pages() as [$name, $uri]) {
            $html = $this->pageHtml($uri);

            if ($html === null) {
                continue;
            }

            $body = $this->body($html);

            foreach (['@if', '@foreach', '@endif', '@endforeach', '@extends', '@yield', '{{ $'] as $directive) {
                if (str_contains($body, $directive)) {
                    $leaks[] = "{$name} ({$uri}) printed {$directive}";
                }
            }
        }

        $this->assertSame([], $leaks, "Client pages showing their own template:\n" . implode("\n", $leaks));
    }

    /** No client page shows the words of a PHP error. */
    public function test_no_client_page_shows_an_error_to_the_client(): void
    {
        $shouting = [];

        foreach ($this->pages() as [$name, $uri]) {
            $html = $this->pageHtml($uri);

            if ($html === null) {
                continue;
            }

            $body = $this->body($html);

            foreach ([
                'Undefined variable',
                'Attempt to read property',
                'Call to a member function',
                'Trying to access array offset',
                'SQLSTATE',
                'Whoops, looks like something went wrong',
            ] as $shout) {
                if (str_contains($body, $shout)) {
                    $shouting[] = "{$name} ({$uri}) says \"{$shout}\"";
                }
            }
        }

        $this->assertSame([], $shouting, "Client pages showing an error:\n" . implode("\n", $shouting));
    }
}
