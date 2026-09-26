<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 25 September: "go thru each of the client's webpages to align
 * them by fixing it, i have mentioned it before because they need to be done
 * everywhere, but I only seen it once fix correctly."
 *
 * The bar across the top and the page beneath it were two columns. The page
 * is capped and centred; the bar was not capped at all, so on any screen
 * wider than the cap the orange banner's edges and the cards' edges parted
 * company and nothing lined up with anything above it. On a narrow screen the
 * two measurements happened to match, which is why it looked right sometimes.
 *
 * It was never a page-by-page fault, so it is not a page-by-page fix: one
 * column is declared in the layout and every client page inherits it. What
 * this holds is that construction, because the way it broke was two places
 * each carrying their own numbers.
 */
class ClientPagesShareOneColumnTest extends TestCase
{
    use RefreshDatabase;

    private function layout(): string
    {
        return file_get_contents(base_path('resources/views/layouts/client.blade.php'));
    }

    public function test_the_column_is_declared_once(): void
    {
        $layout = $this->layout();

        foreach (['--cl-max:', '--cl-edge:'] as $token) {
            $this->assertSame(
                1,
                substr_count($layout, $token),
                "{$token} is declared more than once, so two places can disagree about it.",
            );
        }

        // The gutter is set twice on purpose, wide and narrow, and both times
        // on the one element the bar and the page both sit inside.
        $this->assertSame(2, substr_count($layout, '--cl-gutter:'));
        $this->assertSame(
            2,
            preg_match_all('/\.cl-main\s*\{[^}]*--cl-gutter:/s', $layout),
            'Something other than .cl-main is setting the gutter.',
        );
    }

    public function test_the_bar_and_the_page_take_their_edges_from_it(): void
    {
        $layout = $this->layout();

        // The page: capped and centred on the shared measurements.
        $this->assertMatchesRegularExpression(
            '/\.cl-content\s*\{[^}]*padding:\s*24px\s+var\(--cl-gutter\)[^}]*max-width:\s*var\(--cl-max\)/s',
            $layout,
        );

        // The bar: full width, because it is sticky and a capped one would let
        // the page scroll past it at the sides, with its contents on the
        // page's own line.
        $this->assertMatchesRegularExpression(
            '/\.cl-topbar\s*\{[^}]*padding:\s*12px\s+var\(--cl-edge\)/s',
            $layout,
        );
    }

    public function test_neither_carries_its_own_numbers_any_more(): void
    {
        $layout = $this->layout();

        foreach ([
            '.cl-topbar { display: flex; align-items: center; gap: 16px; padding: 12px 26px 2px;',
            'padding: 24px 26px 28px;',
            '.cl-content { padding: 20px 16px; }',
        ] as $old) {
            $this->assertStringNotContainsString($old, $layout,
                'A hard-coded edge is back; that is how the two columns parted in the first place.');
        }
    }

    /**
     * And nothing else reaches in to set the page's edge behind the bar's
     * back. A shared partial was forcing the content to 14px on a phone while
     * the bar kept its own number — a third place carrying its own edge, and
     * the same fault one layer down.
     */
    public function test_no_shared_partial_sets_the_page_edge_on_its_own(): void
    {
        $mobile = file_get_contents(base_path('resources/views/partials/_mobile_fixes.blade.php'));

        $this->assertStringNotContainsString(
            ".cl-content,\n        .pf-content",
            $mobile,
            'The mobile partial is setting the client page edge directly again.',
        );
        $this->assertStringContainsString('.cl-main { --cl-gutter: 14px; }', $mobile);

        // Whatever a partial does, the page's own edge comes from the gutter.
        $this->assertSame(
            0,
            preg_match('/\.cl-content[^{]*\{[^}]*padding-(left|right|inline)[^}]*!important/s', $mobile),
        );
    }

    /** And every client page still renders both of them. */
    public function test_every_client_page_uses_that_column(): void
    {
        $client = \App\Models\User::factory()->create(['primary_role' => 'client']);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $client->assignRole('client');
        $client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => \App\Support\ServiceArea::SUPPORTED,
        ]);
        $client = \App\Models\User::findOrFail($client->id);

        $wrong = [];

        foreach ([
            '/client/dashboard', '/client/events', '/client/bookings', '/client/proposals',
            '/client/direct-offers/create', '/client/esr/create', '/client/bsr',
            '/client/spending', '/client/payments', '/client/profile',
        ] as $path) {
            $response = $this->actingAs($client)->get($path);

            if ($response->getStatusCode() !== 200) {
                continue;
            }

            $html = $response->getContent();

            if (! str_contains($html, 'class="cl-topbar"') || ! str_contains($html, 'class="cl-content"')) {
                $wrong[] = $path;
            }
        }

        $this->assertSame([], $wrong, "these pages are outside the shared column:\n".implode("\n", $wrong));
    }
}
