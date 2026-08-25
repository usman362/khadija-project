<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The package purchase path — audit row 25.
 *
 * A package had a price, a page, and a button that opened the Direct Request
 * form: a different product, in which the package's price appeared nowhere.
 * The audit called it "purchase/detail state needed" and it was the one client
 * row with no path at all behind it.
 *
 * What these tests pin down is as much what the flow must NOT do as what it
 * must. It must not confirm the booking on the client's click — the
 * professional has not seen the date yet — and it must not imply money moved,
 * because no payment provider is connected to this app.
 */
class ClientPackagePurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function client(string $state = 'MD'): User
    {
        $user = User::factory()->create();
        $user->assignRole('client');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => $state, 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function pro(string $state = 'MD'): User
    {
        $user = User::factory()->create();
        $user->assignRole('professional');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => $state, 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function package(User $pro, string $state = 'MD', int $price = 2400): Package
    {
        return Package::create([
            'user_id'     => $pro->id,
            'category_id' => Category::first()?->id,
            'title'       => 'Full-day coverage',
            'slug'        => 'full-day-coverage-'.uniqid(),
            'description' => 'Everything for one day.',
            'price'       => $price,
            'status'      => 'active',
            'is_active'   => true,
            'state'       => $state,
            'includes'    => ['Eight hours on site', 'Edited gallery'],
        ]);
    }

    public function test_the_package_page_offers_a_price_not_a_different_form(): void
    {
        $pro     = $this->pro();
        $package = $this->package($pro);

        $response = $this->actingAs($this->client())->get(route('public.package', $package));

        $response->assertSuccessful();
        // The CTA leads to the purchase path, carrying the package's own price.
        $response->assertSee(route('client.packages.book', $package));
        $response->assertDontSee(route('client.direct-offers.create'));
    }

    public function test_a_client_can_open_the_booking_form(): void
    {
        $pro     = $this->pro();
        $package = $this->package($pro);

        $response = $this->actingAs($this->client())->get(route('client.packages.book', $package));

        $response->assertSuccessful();
        $response->assertSee('Send booking request');
        $response->assertSee('2,400.00');
    }

    public function test_booking_a_package_creates_a_request_the_professional_still_has_to_accept(): void
    {
        $pro     = $this->pro();
        $package = $this->package($pro);
        $client  = $this->client();
        $date    = now()->addDays(30)->toDateString();

        $response = $this->actingAs($client)->post(route('client.packages.book.store', $package), [
            'event_title' => 'Rooftop reception',
            'date'        => $date,
            'location'    => 'Baltimore, MD',
            'guests'      => 80,
            'agree'       => 1,
        ]);

        $booking = Booking::where('client_id', $client->id)->latest('id')->first();

        $this->assertNotNull($booking);
        $response->assertRedirect(route('client.packages.booked', $booking));

        // The price on the booking is the package's own price — the whole point.
        $this->assertEquals(2400, (int) $booking->price);
        $this->assertSame($pro->id, $booking->supplier_id);

        /*
         * NOT confirmed. An instant-book that commits the professional before
         * they have seen the date is a screen promising something the other
         * side never agreed to.
         */
        $this->assertSame('requested', $booking->status);

        // The event it created is not published — nobody bids on a package
        // that is already priced and already assigned to one professional.
        $event = Event::find($booking->event_id);
        $this->assertNotNull($event);
        $this->assertFalse((bool) $event->is_published);
        $this->assertSame('Rooftop reception', $event->title);
    }

    public function test_the_confirmation_never_says_the_client_has_paid(): void
    {
        $pro     = $this->pro();
        $package = $this->package($pro);
        $client  = $this->client();

        $this->actingAs($client)->post(route('client.packages.book.store', $package), [
            'event_title' => 'Rooftop reception',
            'date'        => now()->addDays(30)->toDateString(),
            'agree'       => 1,
        ]);

        $booking  = Booking::where('client_id', $client->id)->latest('id')->first();
        $response = $this->actingAs($client)->get(route('client.packages.booked', $booking));

        $response->assertSuccessful();
        $response->assertSee('Nothing has been charged');
        $response->assertSee('Waiting on the professional');
    }

    public function test_a_package_from_another_state_cannot_be_booked(): void
    {
        // R38 — GigResource matches within a state, so a purchase path that
        // crosses one is a dead end dressed as a checkout.
        $pro     = $this->pro('PA');
        $package = $this->package($pro, 'PA');

        $this->actingAs($this->client('MD'))
            ->get(route('client.packages.book', $package))
            ->assertForbidden();
    }

    public function test_a_draft_package_cannot_be_booked(): void
    {
        $pro     = $this->pro();
        $package = $this->package($pro);
        $package->update(['status' => 'draft', 'is_active' => false]);

        $this->actingAs($this->client())
            ->get(route('client.packages.book', $package))
            ->assertNotFound();
    }

    public function test_a_professional_is_not_offered_their_own_package(): void
    {
        $pro     = $this->pro();
        $package = $this->package($pro);

        $this->actingAs($pro)
            ->get(route('client.packages.book', $package))
            ->assertForbidden();
    }

    public function test_a_past_date_is_rejected(): void
    {
        $pro     = $this->pro();
        $package = $this->package($pro);

        $this->actingAs($this->client())
            ->post(route('client.packages.book.store', $package), [
                'event_title' => 'Rooftop reception',
                'date'        => now()->subDay()->toDateString(),
                'agree'       => 1,
            ])
            ->assertSessionHasErrors('date');

        $this->assertSame(0, Booking::count());
    }

    public function test_the_same_professional_cannot_be_double_booked_on_one_date(): void
    {
        $pro     = $this->pro();
        $package = $this->package($pro);
        $client  = $this->client();
        $date    = now()->addDays(30)->toDateString();

        $payload = ['event_title' => 'Rooftop reception', 'date' => $date, 'agree' => 1];

        $this->actingAs($client)->post(route('client.packages.book.store', $package), $payload);
        $this->actingAs($client)->post(route('client.packages.book.store', $package), $payload)
            ->assertSessionHasErrors('date');

        $this->assertSame(1, Booking::count());
    }

    public function test_another_client_cannot_read_the_confirmation(): void
    {
        $pro     = $this->pro();
        $package = $this->package($pro);
        $client  = $this->client();

        $this->actingAs($client)->post(route('client.packages.book.store', $package), [
            'event_title' => 'Rooftop reception',
            'date'        => now()->addDays(30)->toDateString(),
            'agree'       => 1,
        ]);

        $booking = Booking::latest('id')->first();

        $this->actingAs($this->client())
            ->get(route('client.packages.booked', $booking))
            ->assertForbidden();
    }
}
