<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Walk the whole client side as a signed-in client and check nothing breaks.
 *
 * Written before handing the client portal over for review. Individual
 * features are tested elsewhere; what was missing was the flat question — can
 * a client open every page we are about to present? A 500 on one screen in a
 * demo undoes the impression of forty that worked.
 *
 * The assertion is deliberately weak: NOT a server error. Some of these
 * addresses are professional-only and answer 403 to a client, some redirect,
 * and demanding 200 everywhere would either fail honestly-correct pages or
 * push me to narrow the list until it passed. 5xx is the line — it means the
 * page threw, whoever asked for it.
 *
 * Routes taking parameters are excluded: fabricating an id per route tests my
 * fixtures rather than the pages.
 */
class ClientSideSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
        ]);

        // Returned fresh, the way a request resolves the signed-in user. Handing
        // back the instance the profile was created on carries that relation's
        // cache into every page, and the pages then fail on a state no real
        // request is ever in — two hours spent on a 500 that only existed here.
        return User::findOrFail($u->id);
    }

    /** @return array<int, string> */
    private function parameterlessGetRoutes(string $prefix): array
    {
        $paths = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();

            if (str_contains($uri, '{') || ! str_starts_with($uri, $prefix)) {
                continue;
            }

            $paths[] = '/'.$uri;
        }

        sort($paths);

        return array_values(array_unique($paths));
    }

    /**
     * Every /client/* page. Reported all at once rather than failing on the
     * first, so one run says what needs fixing instead of one page per run.
     */
    public function test_no_client_page_throws(): void
    {
        $client = $this->client();
        $paths = $this->parameterlessGetRoutes('client/');

        $this->assertGreaterThan(20, count($paths), 'the route list looks wrong');

        $broken = [];

        foreach ($paths as $path) {
            $status = $this->actingAs($client)->get($path)->getStatusCode();

            if ($status >= 500) {
                $broken[] = "{$path} → {$status}";
            }
        }

        $this->assertSame([], $broken, "client pages that threw:\n".implode("\n", $broken));
    }

    /** The AI tools a client is offered, same rule. */
    public function test_no_tool_page_throws(): void
    {
        $client = $this->client();
        $paths = $this->parameterlessGetRoutes('tools');

        $this->assertGreaterThan(10, count($paths), 'the tool list looks wrong');

        $broken = [];

        foreach ($paths as $path) {
            $status = $this->actingAs($client)->get($path)->getStatusCode();

            if ($status >= 500) {
                $broken[] = "{$path} → {$status}";
            }
        }

        $this->assertSame([], $broken, "tool pages that threw:\n".implode("\n", $broken));
    }

    /**
     * A client with nothing yet — no events, no bookings, no messages — is the
     * state every real new signup is in, and the one least often clicked
     * through while building. A page that only works once its fixtures exist
     * is a page that greets every new client with a crash.
     */
    public function test_no_client_page_throws_for_a_brand_new_account(): void
    {
        $fresh = $this->client();

        $this->assertSame(0, \App\Models\Event::where('client_id', $fresh->id)->count());

        $broken = [];

        foreach ($this->parameterlessGetRoutes('client/') as $path) {
            $status = $this->actingAs($fresh)->get($path)->getStatusCode();

            if ($status >= 500) {
                $broken[] = "{$path} → {$status}";
            }
        }

        $this->assertSame([], $broken, "pages that threw for a new client:\n".implode("\n", $broken));
    }

    /**
     * The pages a demo actually walks through: an event, its proposals, a
     * conversation, a payment. These take an id, so they were left out of the
     * sweep above — which means the busiest screens in the portal were the
     * ones nothing checked. Real rows, opened by the client who owns them.
     */
    public function test_the_pages_that_take_an_id_open_for_their_owner(): void
    {
        $client = $this->client();

        $event = \App\Models\Event::create([
            'title' => 'A real event', 'client_id' => $client->id,
            'created_by' => $client->id, 'status' => 'open',
            'location' => 'Baltimore, MD', 'starts_at' => now()->addDays(30),
        ]);

        $paths = [
            "/client/events/{$event->id}",
            "/client/events/{$event->id}/compare-proposals",
            "/client/bsr-resume/{$event->id}",
        ];

        $broken = [];

        foreach ($paths as $path) {
            $status = $this->actingAs($client)->get($path)->getStatusCode();

            if ($status >= 500) {
                $broken[] = "{$path} → {$status}";
            }
        }

        $this->assertSame([], $broken, "owner pages that threw:\n".implode("\n", $broken));
    }

    /**
     * And the same pages must not open for somebody else. A smoke test that
     * only proves pages render is worth little if any signed-in client can
     * read another client's event by changing the number in the address.
     */
    public function test_another_client_cannot_open_them(): void
    {
        $owner = $this->client();
        $stranger = $this->client();

        $event = \App\Models\Event::create([
            'title' => 'Private event', 'client_id' => $owner->id,
            'created_by' => $owner->id, 'status' => 'open',
            'location' => 'Baltimore, MD', 'starts_at' => now()->addDays(30),
        ]);

        $status = $this->actingAs($stranger)->get("/client/events/{$event->id}")->getStatusCode();

        $this->assertContains(
            $status,
            [403, 404, 302],
            "a stranger got {$status} on another client's event",
        );
    }

    /**
     * A page can answer 200 and still be visibly broken. This one was: a CSS
     * block added just after its own </style> rendered as a paragraph of
     * stylesheet across the top of Requests & Submissions, and every check
     * here passed it, because "did not throw" is a low bar and I had treated
     * it as the whole test.
     *
     * So: strip the real style and script blocks, then look at what a reader
     * would see. Declarations and comment terminators have no business there.
     */
    public function test_no_page_shows_its_stylesheet_as_text(): void
    {
        $client = $this->client();
        $leaking = [];

        // Not just /client/*. The page this was written for lives at /forms,
        // and sweeping only the client prefix is why the first version of this
        // test passed on the broken page it was written to catch.
        $paths = array_merge(
            $this->parameterlessGetRoutes('client/'),
            $this->parameterlessGetRoutes('forms'),
            $this->parameterlessGetRoutes('disputes'),
            $this->parameterlessGetRoutes('cancellations'),
        );

        foreach ($paths as $path) {
            $html = $this->actingAs($client)->get($path)->getContent();

            $visible = preg_replace('#<(style|script)\b[^>]*>.*?</\1>#is', '', $html);
            $visible = strip_tags((string) $visible);

            // The end of a CSS comment, or a declaration block, in plain text.
            if (preg_match('#\*/#', $visible) || preg_match('#\{[^{}]*:[^{}]*;[^{}]*\}#', $visible)) {
                $leaking[] = $path;
            }
        }

        $this->assertSame([], $leaking, "pages showing stylesheet as text:\n".implode("\n", $leaking));
    }
}
