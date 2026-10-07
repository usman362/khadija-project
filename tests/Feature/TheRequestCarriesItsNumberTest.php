<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Sir Peter quotes requests by number — "DR-00065". Event::reference() has
 * built that number since the seven screens, and exactly one screen printed
 * it: the event's own page, which is the one place you already know which
 * request you are looking at.
 *
 * On a list it is not decoration. Proposals cuts the event title to sixteen
 * characters, so six proposals on one wedding are six rows reading "Wedding
 * Receptio..." with nothing to tell them apart.
 *
 * The prefix comes from events.source, and these pages narrow their eager
 * loads to named columns. A model loaded without that column answers null,
 * which reference() reads as the default — so an emergency request quietly
 * prints BR-. That is what the per-type assertions below are guarding.
 */
class TheRequestCarriesItsNumberTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private Category $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();

        $this->service = Category::create([
            'name' => 'Wedding DJs', 'slug' => Str::slug('Wedding DJs') . '-ref',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);
    }

    private function event(string $source = 'user', string $title = 'Wedding Reception'): Event
    {
        $e = Event::create([
            'title' => $title, 'created_by' => $this->client->id, 'client_id' => $this->client->id,
            'status' => 'published', 'is_published' => true,
            'starts_at' => now()->addDays(30)->setTime(17, 0),
            'source' => $source,
        ]);
        $e->categories()->sync([$this->service->id]);

        return $e;
    }

    private function pro(): User
    {
        $u = User::factory()->create(['name' => 'Velvet Beats', 'primary_role' => 'professional']);
        $u->assignRole('professional');

        return $u->fresh();
    }

    private function page(string $path): string
    {
        return $this->actingAs($this->client)->get($path)->assertSuccessful()->getContent();
    }

    /** The three prefixes, built from the request's own type. */
    public function test_each_kind_of_request_has_its_own_prefix(): void
    {
        $this->assertStringStartsWith('BR-', $this->event()->reference());
        $this->assertStringStartsWith('ER-', $this->event('esr')->reference());
        $this->assertStringStartsWith('DR-', $this->event('direct_offer')->reference());

        // Bought, not bid on: it used to read BR-, a bidding request number
        // for something nobody ever bid on.
        $this->assertStringStartsWith('PKG-', $this->event('package')->reference());
    }

    /** My Events: every row says which request it is. */
    public function test_my_events_shows_the_number(): void
    {
        $br = $this->event();
        $er = $this->event('esr', 'Rush Catering');

        $html = $this->page('/client/events');

        $this->assertStringContainsString($br->reference(), $html);
        $this->assertStringContainsString($er->reference(), $html);
        $this->assertStringContainsString('ER-', $html, 'An emergency request is listed as a bidding one.');
    }

    /**
     * Proposals: the page that needs it most, and the one whose eager load
     * names its columns.
     */
    public function test_proposals_shows_the_number_of_the_right_kind(): void
    {
        $er = $this->event('esr');

        Bid::create([
            'event_id' => $er->id, 'category_id' => $this->service->id,
            'supplier_id' => $this->pro()->id, 'amount' => 1400, 'status' => 'submitted',
            'available_confirmed' => true,
        ]);

        $html = $this->page('/client/proposals');

        $this->assertStringContainsString($er->reference(), $html);
        $this->assertStringNotContainsString(
            'BR-' . str_pad((string) $er->id, 5, '0', STR_PAD_LEFT),
            $html,
            'The proposals list loads the event without its source and calls an emergency request BR-.'
        );
    }

    /** Bookings: the same, and its eager load names its columns too. */
    public function test_bookings_shows_the_number_of_the_right_kind(): void
    {
        $dr = $this->event('direct_offer');
        $pro = $this->pro();

        Booking::create([
            'event_id' => $dr->id, 'client_id' => $this->client->id, 'supplier_id' => $pro->id,
            'created_by' => $this->client->id, 'status' => 'confirmed', 'price' => 1400,
        ]);

        $html = $this->page('/client/bookings');

        $this->assertStringContainsString($dr->reference(), $html);
        $this->assertStringNotContainsString(
            'BR-' . str_pad((string) $dr->id, 5, '0', STR_PAD_LEFT),
            $html,
            'The bookings list loads the event without its source and calls a direct request BR-.'
        );
    }

    /** The event's own page kept it. */
    public function test_the_event_page_still_shows_it(): void
    {
        $e = $this->event('esr');

        $this->assertStringContainsString($e->reference(), $this->page('/client/events/' . $e->id));
    }
}
