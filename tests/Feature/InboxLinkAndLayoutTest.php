<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Two faults reported from production on the same pass.
 *
 * 1. "Resolve Without Filing" on the Disputes page opened a screen of raw
 *    JSON. It linked to `route('messages.index')` — which is not the inbox
 *    page at all, but `MessageController@index`, a JSON API returning a
 *    paginated list of message rows. The name is the trap: it reads exactly
 *    like the page anyone would want. Two other views had the same link.
 *
 * 2. The Payments page left a dead 280px strip down its right side. Three
 *    cards were removed from a `<div class="pay-bottom">` on 2026-08-15 and
 *    the closing tag went with them, so the tag meant to close `.pay-main`
 *    closed `.pay-bottom` instead — and the right rail ended up nested inside
 *    the main column. `.pay-layout` was left with one child and its second
 *    grid column reserved but permanently empty.
 */
class InboxLinkAndLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user->fresh();
    }

    /** The JSON route must not be linked from any page. */
    public function test_no_view_links_to_the_json_messages_route(): void
    {
        $offenders = [];

        foreach ($this->blades() as $file) {
            if (str_contains(file_get_contents($file), "route('messages.index')")) {
                $offenders[] = str_replace(resource_path('views/'), '', $file);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "These views link to the JSON API instead of an inbox page: ".implode(', ', $offenders),
        );
    }

    public function test_a_client_is_sent_to_the_client_inbox(): void
    {
        $client = $this->user('client');

        $response = $this->actingAs($client)->get(route('disputes.index'));

        $response->assertSuccessful();
        $response->assertSee(route('client.chat.index'), false);
        $response->assertDontSee(route('messages.index').'"', false);
    }

    public function test_a_professional_is_sent_to_the_professional_inbox(): void
    {
        $pro = $this->user('professional');

        $response = $this->actingAs($pro)->get(route('disputes.index'));

        $response->assertSuccessful();
        $response->assertSee(route('professional.chat.index'), false);
    }

    /** And the destination is an HTML page, which is the whole complaint. */
    public function test_the_inbox_link_leads_to_a_page_not_json(): void
    {
        $client = $this->user('client');

        $response = $this->actingAs($client)->get(\App\Support\Inbox::urlFor($client));

        $response->assertSuccessful();
        $this->assertStringContainsString('text/html', $response->headers->get('content-type'));
    }

    /**
     * The rail has to be a sibling of the main column, or the grid's second
     * track is reserved and empty.
     */
    public function test_the_payments_rail_is_not_nested_inside_the_main_column(): void
    {
        $view = file_get_contents(resource_path('views/client/finance/payments.blade.php'));

        $start = strpos($view, '<div class="pay-main">');
        $end   = strpos($view, '</div>{{-- /.pay-main --}}');

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        // Strip Blade comments so their prose does not count as markup.
        $body = preg_replace('/\{\{--.*?--\}\}/s', '', substr($view, $start, $end - $start));

        $opens  = preg_match_all('/<div\b/', $body) - 1;   // minus .pay-main itself
        $closes = preg_match_all('#</div>#', $body);

        $this->assertSame(
            $opens,
            $closes,
            'An unclosed div inside .pay-main swallows its closing tag, which pulls the right rail in with it.',
        );

        // The rail must come after the close, at the layout's own level.
        $this->assertStringContainsString(
            "</div>{{-- /.pay-main --}}",
            $view,
        );
        $this->assertGreaterThan($end, strpos($view, '<aside class="pay-rail">'));
    }

    /**
     * Blade parses its own comments. A comment that quotes the close sequence
     * ends early and the rest renders as page copy — which is exactly what
     * happened when the note explaining the bug above was first written.
     */
    public function test_no_blade_comment_leaks_onto_the_payments_page(): void
    {
        $response = $this->actingAs($this->user('client'))->get(route('client.payments.index'));

        $response->assertSuccessful();
        $response->assertDontSee('pay-bottom');
        $response->assertDontSee('62/38');
        $response->assertDontSee('--}}', false);
    }

    /** @return list<string> */
    private function blades(): array
    {
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
                $files[] = $f->getPathname();
            }
        }

        return $files;
    }
}
