<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Two event types renamed — Sir Peter, 29 Aug.
 *
 *   "Silent Auction"                -> "Live Auction"
 *   "Retirement & Going-Away Party" -> "Going-Away Party"
 *
 * His reasons: an auction is more likely to be live than silent, and the
 * combined title duplicated the "Retirement Party" that sits two cards along.
 *
 * The Silent Auction PICTURE is cleared with the rename. It showed a
 * silent-auction bid sheet, which under "Live Auction" is a photograph of the
 * wrong thing — the card falls back to its lettered tile until the new one is
 * uploaded. The Going-Away Party picture is kept: he said its image was
 * already right and only the wording was wrong.
 */
class EventTypeRenameTest extends TestCase
{
    use RefreshDatabase;

    private function eventType(string $name, string $slug, ?string $image = null): Category
    {
        return Category::create([
            'name' => $name, 'slug' => $slug, 'is_active' => true,
            'kind' => Category::EVENT_TYPE, 'thumbnail' => $image,
        ]);
    }

    public function test_the_renamed_types_are_the_ones_in_the_database(): void
    {
        // RefreshDatabase runs the migrations, so the rename has already run
        // against whatever the seeder produced.
        $this->assertNull(Category::where('slug', 'silent-auction')->first());
        $this->assertNull(Category::where('slug', 'retirement-going-away-party')->first());
    }

    /** A link already shared must not die. */
    public function test_the_old_addresses_redirect_to_the_new_ones(): void
    {
        $this->eventType('Live Auction', 'live-auction');
        $this->eventType('Going-Away Party', 'going-away-party');

        $this->get(route('public.category', 'silent-auction'))
            ->assertRedirect(route('public.category', 'live-auction'));

        $this->get(route('public.category', 'retirement-going-away-party'))
            ->assertRedirect(route('public.category', 'going-away-party'));
    }

    /** Permanently, so search engines follow it rather than keeping the old one. */
    public function test_the_redirect_is_permanent(): void
    {
        $this->eventType('Live Auction', 'live-auction');

        $this->get(route('public.category', 'silent-auction'))->assertStatus(301);
    }

    public function test_the_new_pages_open(): void
    {
        $this->eventType('Live Auction', 'live-auction');
        $this->eventType('Going-Away Party', 'going-away-party');

        $this->get(route('public.category', 'live-auction'))
            ->assertSuccessful()->assertSee('Live Auction');

        $this->get(route('public.category', 'going-away-party'))
            ->assertSuccessful()->assertSee('Going-Away Party');
    }

    /** "Retirement Party" is a separate type and is untouched. */
    public function test_the_separate_retirement_party_still_exists(): void
    {
        $this->eventType('Retirement Party', 'retirement-party');

        $this->get(route('public.category', 'retirement-party'))
            ->assertSuccessful()->assertSee('Retirement Party');
    }

    /** An unknown slug is still a 404 — the alias list is not a catch-all. */
    public function test_an_unknown_slug_is_still_missing(): void
    {
        $this->get(route('public.category', 'no-such-event-type'))->assertNotFound();
    }
}
