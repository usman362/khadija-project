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
}
