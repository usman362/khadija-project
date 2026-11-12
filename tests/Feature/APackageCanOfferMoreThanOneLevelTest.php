<?php

namespace Tests\Feature;

use App\Domain\Money\Ledger;
use App\Models\{Booking, Category, LedgerEntry, Package, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tier pricing on a package, from the 5 November sprint checklist.
 *
 * The builder already had three price BASES, flat, starting-at and per hour,
 * which answer how a number is read rather than what else could be bought.
 * A level is the other question: Silver, Gold, Platinum.
 *
 * The rule these are really here to hold is that one package cannot carry two
 * prices. `price` is what every listing, filter and search card reads, so when
 * levels exist it is derived from the cheapest of them rather than typed, and
 * the booking is charged the level the client actually chose.
 */
class APackageCanOfferMoreThanOneLevelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function person(string $role): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function category(): Category
    {
        return Category::firstOrCreate(['slug' => 'dj-tiers'], ['name' => 'DJ']);
    }

    private function packageWithTiers(User $pro, array $tiers, int $price = 2400): Package
    {
        return Package::create([
            'user_id' => $pro->id, 'category_id' => $this->category()->id,
            'title' => 'Full day coverage', 'slug' => 'full-day-' . uniqid(),
            'type' => 'solo', 'price' => $price, 'price_unit' => 'from',
            'tiers' => $tiers,
            'services' => ['Photography', 'Videography'],
            'event_types' => ['Wedding'], 'state' => 'MD',
            'is_active' => true, 'status' => 'active',
        ]);
    }

    // ── the model's own reading of them ──────────────────────

    public function test_levels_come_back_cheapest_first_whatever_order_they_were_typed(): void
    {
        $package = $this->packageWithTiers($this->person('professional'), [
            ['name' => 'Platinum', 'price' => 3900, 'blurb' => 'Two shooters'],
            ['name' => 'Silver',   'price' => 1800, 'blurb' => null],
            ['name' => 'Gold',     'price' => 2600, 'blurb' => 'Album included'],
        ]);

        // A list of levels that does not climb reads as a mistake, and the
        // first one is the figure the card shows.
        $this->assertSame(['Silver', 'Gold', 'Platinum'], array_column($package->tierList(), 'name'));
        $this->assertTrue($package->hasTiers());
    }

    public function test_a_half_filled_level_is_not_a_level(): void
    {
        $package = $this->packageWithTiers($this->person('professional'), [
            ['name' => 'Silver', 'price' => 1800],
            ['name' => '',       'price' => 2600],   // no name
            ['name' => 'Gold',   'price' => 0],      // no price
        ]);

        $this->assertCount(1, $package->tierList());
        $this->assertFalse($package->hasTiers(), 'one level is not a choice');
    }

    public function test_one_level_is_found_by_name_and_not_by_position(): void
    {
        $package = $this->packageWithTiers($this->person('professional'), [
            ['name' => 'Silver', 'price' => 1800],
            ['name' => 'Gold',   'price' => 2600],
        ]);

        // Case and spacing are the client's, not ours.
        $this->assertSame(2600, $package->tierNamed(' gold ')['price']);
        $this->assertNull($package->tierNamed('Bronze'));
        $this->assertNull($package->tierNamed(null));
    }

    // ── what the builder saves ───────────────────────────────

    public function test_the_headline_price_becomes_the_cheapest_level(): void
    {
        $pro = $this->person('professional');

        $this->actingAs($pro)->post(route('professional.packages.store'), [
            'title' => 'Full day coverage',
            'category_id' => $this->category()->id,
            'services' => ['Photography', 'Videography'],
            // A figure the professional typed before adding levels, and which
            // the levels now contradict.
            'price' => 3250,
            'price_unit' => 'flat',
            'tiers' => [
                ['name' => 'Gold',   'price' => 2600, 'blurb' => 'Album included'],
                ['name' => 'Silver', 'price' => 1800, 'blurb' => ''],
                ['name' => '',       'price' => '',   'blurb' => ''],
            ],
            'publish' => 1,
        ]);

        $package = Package::where('user_id', $pro->id)->latest('id')->firstOrFail();

        $this->assertSame(1800, $package->price, 'the card would advertise a price nobody can buy at');
        $this->assertSame('from', $package->price_unit);
        $this->assertCount(2, $package->tierList(), 'the empty third row is dropped, not refused');
    }

    public function test_a_package_without_levels_keeps_the_price_that_was_typed(): void
    {
        $pro = $this->person('professional');

        $this->actingAs($pro)->post(route('professional.packages.store'), [
            'title' => 'Full day coverage',
            'category_id' => $this->category()->id,
            'services' => ['Photography', 'Videography'],
            'price' => 3250,
            'price_unit' => 'flat',
            'publish' => 1,
        ]);

        $package = Package::where('user_id', $pro->id)->latest('id')->firstOrFail();

        $this->assertSame(3250, $package->price);
        $this->assertSame('flat', $package->price_unit);
        $this->assertSame([], $package->tierList());
    }

    // ── what the client is charged ───────────────────────────

    public function test_the_booking_is_charged_the_level_the_client_chose(): void
    {
        $pro = $this->person('professional');
        $client = $this->person('client');
        $package = $this->packageWithTiers($pro, [
            ['name' => 'Silver', 'price' => 1800],
            ['name' => 'Gold',   'price' => 2600],
        ], price: 1800);

        $this->actingAs($client)->post(route('client.packages.book.store', $package), [
            'event_title' => 'Rooftop reception',
            'date' => now()->addDays(30)->toDateString(),
            'tier' => 'Gold',
            'agree' => 1,
        ]);

        $booking = Booking::where('client_id', $client->id)->latest('id')->firstOrFail();

        $this->assertEquals(2600, $booking->price);
        $this->assertStringContainsString('(Gold)', $booking->notes);
        // And the money record agrees with the booking, as everywhere else.
        $this->assertEquals(2600.00, Ledger::bookingTotal($booking));
    }

    public function test_an_unknown_level_falls_back_to_the_cheapest_rather_than_a_price_of_their_own(): void
    {
        $pro = $this->person('professional');
        $client = $this->person('client');
        $package = $this->packageWithTiers($pro, [
            ['name' => 'Silver', 'price' => 1800],
            ['name' => 'Gold',   'price' => 2600],
        ], price: 1800);

        // The hidden field is the client's to edit.
        $this->actingAs($client)->post(route('client.packages.book.store', $package), [
            'event_title' => 'Rooftop reception',
            'date' => now()->addDays(30)->toDateString(),
            'tier' => 'Free',
            'agree' => 1,
        ]);

        $booking = Booking::where('client_id', $client->id)->latest('id')->firstOrFail();

        $this->assertEquals(1800, $booking->price);
    }

    public function test_a_package_with_no_levels_books_at_its_own_price(): void
    {
        $pro = $this->person('professional');
        $client = $this->person('client');
        $package = $this->packageWithTiers($pro, [], price: 2400);

        $this->actingAs($client)->post(route('client.packages.book.store', $package), [
            'event_title' => 'Rooftop reception',
            'date' => now()->addDays(30)->toDateString(),
            'agree' => 1,
        ]);

        $booking = Booking::where('client_id', $client->id)->latest('id')->firstOrFail();

        $this->assertEquals(2400, $booking->price);
    }

    // ── what each side sees ──────────────────────────────────

    public function test_the_package_page_offers_each_level_as_its_own_button(): void
    {
        $pro = $this->person('professional');
        $client = $this->person('client');
        $package = $this->packageWithTiers($pro, [
            ['name' => 'Silver', 'price' => 1800],
            ['name' => 'Gold',   'price' => 2600, 'blurb' => 'Album included'],
        ], price: 1800);

        $page = $this->actingAs($client)->get(route('public.package', $package->slug))->assertOk();

        $page->assertSee('Choose Silver');
        $page->assertSee('Choose Gold');
        $page->assertSee('Album included');
        // One way to buy, not two: a Book button beside the levels would pick
        // a level for them without saying so.
        $page->assertDontSee('Book this package ·');
    }

    public function test_the_booking_form_opens_on_the_level_they_clicked(): void
    {
        $pro = $this->person('professional');
        $client = $this->person('client');
        $package = $this->packageWithTiers($pro, [
            ['name' => 'Silver', 'price' => 1800],
            ['name' => 'Gold',   'price' => 2600],
        ], price: 1800);

        $page = $this->actingAs($client)
            ->get(route('client.packages.book', ['package' => $package->id, 'tier' => 'Gold']))
            ->assertOk();

        $this->assertSame('Gold', $page->viewData('tier')['name']);
        $page->assertSee('Gold price');
        $page->assertSee('2,600.00');
    }
}
