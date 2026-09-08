<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DIR-1, Khadijah — a repeat directive: every service and request-type list
 * reads A to Z.
 *
 * The cause was one line copied everywhere: `orderBy('sort_order')` before
 * `orderBy('name')`. sort_order is arbitrary and mostly unset, so the same set
 * of services came back in a different order on each screen and none of them
 * was scannable — the browse filter, the navbar menu, the category page, the
 * ER and DR service pickers.
 *
 * Ordering is the sort of thing that is fixed on one page, reported as done,
 * and quietly wrong on the other six. This checks the surfaces rather than the
 * line, so a seventh screen ordering itself some other way fails here.
 */
class SidebarListsAreAlphabeticalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Names chosen so alphabetical and sort_order disagree. Seeded in reverse,
     * with sort_order ascending — a list that still reads Zebra-first is
     * reading sort_order, and would pass a test using tidy fixtures.
     */
    private function services(): void
    {
        foreach ([['Zebra Balloons', 1], ['Apple Catering', 2], ['Mango Lighting', 3]] as $i => [$name, $order]) {
            Category::create([
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name),
                'kind' => Category::SERVICE,
                'is_active' => true,
                'sort_order' => $order,
            ]);
        }
    }

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

        return User::findOrFail($u->id);
    }

    /** Read the order the three names appear in the rendered page. */
    private function orderOnPage(string $html): array
    {
        $found = [];

        foreach (['Apple Catering', 'Mango Lighting', 'Zebra Balloons'] as $name) {
            $at = strpos($html, $name);

            if ($at !== false) {
                $found[$name] = $at;
            }
        }

        asort($found);

        return array_keys($found);
    }

    public function test_the_service_pickers_read_a_to_z(): void
    {
        $this->services();
        $client = $this->client();

        $pages = [
            'ER' => '/client/esr/create',
            'DR' => '/client/direct-offers/create',
        ];

        $wrong = [];

        foreach ($pages as $label => $path) {
            $order = $this->orderOnPage(
                $this->actingAs($client)->get($path)->assertOk()->getContent()
            );

            if (count($order) === 3 && $order !== ['Apple Catering', 'Mango Lighting', 'Zebra Balloons']) {
                $wrong[] = $label.': '.implode(' → ', $order);
            }
        }

        $this->assertSame([], $wrong, "not A to Z:\n".implode("\n", $wrong));
    }

    /** The relation every subcategory list reads from. */
    public function test_subcategories_come_back_a_to_z(): void
    {
        $parent = Category::create([
            'name' => 'Parent', 'slug' => 'parent',
            'kind' => Category::SERVICE_CATEGORY, 'is_active' => true,
        ]);

        foreach ([['Zebra Balloons', 1], ['Apple Catering', 2], ['Mango Lighting', 3]] as [$name, $order]) {
            Category::create([
                'name' => $name, 'slug' => \Illuminate\Support\Str::slug($name),
                'kind' => Category::SERVICE, 'is_active' => true,
                'sort_order' => $order, 'parent_id' => $parent->id,
            ]);
        }

        $this->assertSame(
            ['Apple Catering', 'Mango Lighting', 'Zebra Balloons'],
            $parent->children()->pluck('name')->all(),
        );
    }

    /**
     * And no category listing anywhere still puts sort_order first. Checked in
     * the source because this is about the query, and the query is the thing
     * that keeps being copied into the next screen.
     */
    public function test_no_category_list_orders_by_sort_order_first(): void
    {
        $offenders = [];

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path()));

        foreach ($it as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $body = file_get_contents($file->getPathname());

            // Packages and plans have a sort_order their owner controls. This
            // is only about category and service listings.
            if (! str_contains($body, 'Category')) {
                continue;
            }

            // The Category model specifically. BlogCategory and page sections
            // have a sort_order an admin sets on purpose, and reordering those
            // alphabetically would undo somebody's arrangement.
            if (preg_match("/(?<!Blog)Category::[^;]{0,400}orderBy\('sort_order'\)/s", $body)) {
                $offenders[] = str_replace(base_path().'/', '', $file->getPathname());
            }
        }

        $this->assertSame([], $offenders, "still ordering categories by sort_order:\n".implode("\n", $offenders));
    }

    /* ── Request types, not just services ───────────────────── */

    /**
     * The five paths on "How do you want to request?" read A to Z.
     *
     * They were in the order they were built — Shop Packages first, because it
     * was added first — which is an order only somebody who worked on the page
     * can scan.
     *
     * The measurement is the delicate part, and it caught me twice. Searching
     * the whole document finds the sidebar, which carries the same names and
     * comes first. Searching from "rc-grid" finds the CSS rule of that name,
     * which is also before the sidebar. Both made a correctly sorted page read
     * as unsorted, and one of them would have had me "fix" something that was
     * already right.
     */
    public function test_the_request_paths_read_a_to_z(): void
    {
        $html = $this->actingAs($this->client())
            ->get('/client/post-event/choose')->assertOk()->getContent();

        $from = strpos($html, '<div class="rc-grid">');
        $this->assertNotFalse($from, 'the cards are not on the page');

        $to = strpos($html, 'Not sure which to pick', $from);
        $cards = substr($html, $from, $to - $from);

        $expected = [
            'Bidding Request (BR)',
            'Direct Request (DR)',
            'Emergency Request (ER)',
            'Plan with Toolkit',
            'Shop Packages',
        ];

        $found = [];

        foreach ($expected as $label) {
            $at = strpos($cards, $label);

            if ($at !== false) {
                $found[$label] = $at;
            }
        }

        $this->assertCount(count($expected), $found, 'a card is missing, so the order proves nothing');

        asort($found);

        $this->assertSame($expected, array_keys($found), 'the cards are not A to Z');
    }

    /**
     * And they are sorted rather than written in order, so a sixth path cannot
     * land in the wrong place.
     */
    public function test_the_chooser_sorts_its_cards(): void
    {
        $this->assertStringContainsString(
            'usort($routes',
            file_get_contents(resource_path('views/client/post-event/choose.blade.php')),
        );
    }
}
