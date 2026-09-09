<?php

namespace Tests\Feature;

use App\Models\{Category, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The controls on Find Professionals do something.
 *
 * Three did not:
 *
 *  · Grid / List were <button type="button"> with no handler and "Grid" hard
 *    coded as selected — a picture of a control.
 *  · The heart on every card had no handler at all, while the routes to save
 *    and unsave a professional sat there uncalled. "Saved Professionals" on
 *    the dashboard could only ever read zero.
 *  · The live-swap click handler matched hrefs containing "/browse". This page
 *    moved to /find-professionals, so the pager, the city rail and the
 *    trending row all quietly went back to full page loads.
 */
class BrowseControlsAreLiveTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $pro;

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

        $this->pro = User::factory()->create(['name' => 'Rossi Studio', 'primary_role' => 'professional']);
        $this->pro->assignRole('professional');
        $this->pro->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $service = Category::create([
            'name' => 'Event Photography', 'slug' => 'event-photography-bc',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);
        $this->pro->serviceCategories()->sync([$service->id]);
    }

    private function page(array $query = []): string
    {
        return $this->actingAs($this->client)
            ->get(route('public.browse', $query))
            ->assertSuccessful()
            ->getContent();
    }

    public function test_grid_and_list_are_links_that_change_the_page(): void
    {
        $grid = $this->page();

        $this->assertStringContainsString('view=list', $grid, 'List does not go anywhere.');
        // The class attribute, not the stylesheet rule of the same name.
        $this->assertStringNotContainsString('br-pro is-list', $grid);

        $list = $this->page(['view' => 'list']);

        $this->assertStringContainsString('br-pro is-list', $list, 'List looks exactly like grid.');
    }

    /**
     * And it is a different shape, not the same card with a smaller picture.
     *
     * The first attempt only shrank the image: the row stayed as tall, so the
     * two views were hard to tell apart. In list the body reads across — who
     * they are, then the price and the buttons — instead of stacking down.
     */
    public function test_list_is_a_row_not_a_smaller_card(): void
    {
        $html = $this->page(['view' => 'list']);

        $this->assertMatchesRegularExpression(
            '/\.br-pro\.is-list \.br-pro-body \{[^}]*flex-direction:\s*row/',
            $html,
            'The list body still stacks like the card, so the two views look the same.',
        );

        // The rule under the footer belongs to a card, not to a row.
        $this->assertMatchesRegularExpression(
            '/\.br-pro\.is-list \.br-pro-foot \{[^}]*border-top:\s*0/',
            $html,
        );

        // The picture fills the row instead of leaving white space beneath it.
        $this->assertMatchesRegularExpression(
            '/\.br-pro\.is-list \.br-pro-media \{[^}]*min-height:\s*100%/',
            $html,
        );
    }

    /** The choice travels with every link built on the page. */
    public function test_the_view_survives_a_filter(): void
    {
        $list = $this->page(['view' => 'list', 'city' => 'Baltimore']);

        $this->assertStringContainsString('br-pro is-list', $list);

        // The city rail's links carry it, so clicking one does not undo it.
        $this->assertStringContainsString('view=list', $list);
    }

    /**
     * Grid is the default, so the links the page builds do not write it into
     * the address. The toggle itself must carry it — that is how you switch
     * back — so this looks at a city link rather than the whole document.
     */
    public function test_grid_is_not_written_into_the_pages_own_links(): void
    {
        $html = $this->page(['city' => 'Baltimore']);

        preg_match('/<a class="br-loc-row[^"]*"\s+href="([^"]+)"/', $html, $m);

        $this->assertNotEmpty($m, 'No city link to check.');
        $this->assertStringNotContainsString('view=', $m[1]);
    }

    public function test_the_heart_saves_and_unsaves(): void
    {
        $html = $this->page();

        $this->assertStringContainsString(route('client.saved-professionals.store'), $html,
            'The heart still posts nowhere.');

        $this->actingAs($this->client)
            ->post(route('client.saved-professionals.store'), ['professional_id' => $this->pro->id])
            ->assertSessionHasNoErrors();

        $this->assertTrue($this->client->savedProfessionals()->where('users.id', $this->pro->id)->exists());

        // Saved, the card shows it and offers the way back out.
        $saved = $this->page();
        $this->assertStringContainsString('br-fav is-on', $saved);
        $this->assertStringContainsString(route('client.saved-professionals.destroy', $this->pro), $saved);
    }

    /** A visitor who is not signed in is not offered a control that needs an account. */
    public function test_a_guest_gets_no_heart(): void
    {
        $html = $this->get(route('public.browse'));

        // The page is login-gated, so a guest is sent to sign in rather than
        // shown a control that cannot work.
        $html->assertRedirect(route('login'));
    }

    /** The live swap matches this page's real path. */
    public function test_the_live_swap_knows_where_it_is(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('PAGE_PATH', $html);
        $this->assertStringContainsString('/find-professionals', $html);
    }

    /**
     * A filter can be changed twice.
     *
     * The sidebar carries hidden copies of q, city and category so its own
     * submit does not drop them, and it comes after the hero in the document.
     * Collected blind, the stale hidden copy overwrote the value just chosen —
     * so picking a second category left the first one in the address and the
     * filter looked stuck on whatever was set first.
     *
     * A hidden stand-in never speaks over the control itself now.
     */
    public function test_a_hidden_copy_cannot_overwrite_the_control_itself(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('function visibleNames', $html,
            'Nothing works out which control owns a name, so the stale hidden copy wins again.');

        $this->assertStringContainsString("el.type === 'hidden' && owned", $html);
    }

    /** The server takes the second value, whatever the page sent first. */
    public function test_the_second_choice_is_the_one_that_applies(): void
    {
        $first = Category::create([
            'name' => 'First Pick', 'slug' => 'first-pick',
            'kind' => Category::SERVICE_CATEGORY, 'is_active' => true,
        ]);
        $second = Category::create([
            'name' => 'Second Pick', 'slug' => 'second-pick',
            'kind' => Category::SERVICE_CATEGORY, 'is_active' => true,
        ]);

        $one = $this->page(['category' => $first->slug]);
        $two = $this->page(['category' => $second->slug]);

        $this->assertStringContainsString('category='.$first->slug, $one);
        $this->assertStringContainsString('category='.$second->slug, $two);
        $this->assertStringNotContainsString('category='.$first->slug, $two);
    }
}
