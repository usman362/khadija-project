<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BUG-9, Khadijah: "the client profile page is not connected to anything…
 * It is like a room with no doors."
 *
 * She was describing it exactly. Every link on the page pointed back at the
 * page — its own tabs and its own forms. A client who opened Profile from the
 * navigation could reach their events, bookings or payments only by going back
 * the way they came.
 *
 * The counts beside each link are read from that client's own rows. A number
 * that was not counted would be worse than no number, and this is the sort of
 * panel that invites one.
 */
class ClientProfileHasWayOutTest extends TestCase
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

        return User::findOrFail($u->id);
    }

    public function test_the_profile_links_out_to_the_rest_of_the_account(): void
    {
        $html = $this->actingAs($this->client())->get('/client/profile')->assertOk()->getContent();

        $missing = [];

        foreach ([
            'events' => route('client.events.index'),
            'bookings' => route('client.bookings.index'),
            'proposals' => route('client.proposals.index'),
            'payments' => route('client.payments.index'),
        ] as $what => $url) {
            if (! str_contains($html, 'href="'.$url.'"')) {
                $missing[] = $what;
            }
        }

        $this->assertSame([], $missing, 'the profile still has no door to: '.implode(', ', $missing));
    }

    /** The numbers are this client's own, counted, not decoration. */
    public function test_the_counts_are_real(): void
    {
        $client = $this->client();

        foreach (range(1, 3) as $i) {
            Event::create([
                'title' => "Event {$i}", 'client_id' => $client->id,
                'created_by' => $client->id, 'status' => 'open',
            ]);
        }

        $activity = $this->actingAs($client)->get('/client/profile')->assertOk()->viewData('activity');

        $byLabel = collect($activity)->keyBy('label');

        $this->assertSame(3, $byLabel['My Events']['count']);
        $this->assertSame(0, $byLabel['Bookings']['count']);
    }

    /**
     * A number beside a link is a promise about what is behind it.
     *
     * Bookings counted `created_by` while the page it opens lists `client_id`
     * and leaves out a booking where the client is their own professional, and
     * Payments counted completed Payment rows while that page lists one row
     * per booking. Funding an agreement writes two payments, the deposit and
     * the agreement fee, so the panel said two and the page showed one.
     */
    public function test_each_count_matches_the_page_it_opens(): void
    {
        $client = $this->client();
        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');

        $event = Event::create([
            'title' => 'Rivera Wedding', 'client_id' => $client->id,
            'created_by' => $client->id, 'status' => 'published',
            'starts_at' => now()->addDays(30),
        ]);

        $category = \App\Models\Category::create(['name' => 'DJ', 'slug' => 'dj-' . uniqid()]);

        Booking::create([
            'event_id' => $event->id, 'client_id' => $client->id,
            'supplier_id' => $pro->id, 'created_by' => $client->id,
            'category_id' => $category->id,
            'status' => 'confirmed', 'price' => 2000, 'currency' => 'USD',
        ]);

        /*
         * A legacy row where the client is their own professional. Inserted
         * past the model, because Booking now refuses to create one — the
         * guard came after the rows did, which is exactly why notSelfSupplied
         * still has to exist and still has to be matched by every count.
         */
        \Illuminate\Support\Facades\DB::table('bookings')->insert([
            'event_id' => $event->id, 'client_id' => $client->id,
            'supplier_id' => $client->id, 'created_by' => $client->id,
            'category_id' => $category->id,
            'status' => 'confirmed', 'price' => 400, 'currency' => 'USD',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Two payments against the one real booking, which is what funding an
        // agreement leaves behind.
        foreach ([['booking_deposit', 500], ['agreement_fee', 4.99]] as [$kind, $amount]) {
            \App\Models\Payment::create([
                'user_id' => $client->id, 'gateway' => 'test', 'status' => 'completed',
                'amount' => $amount, 'currency' => 'USD', 'payment_method' => 'test_card',
                'completed_at' => now(),
                'metadata' => ['kind' => $kind, 'event_id' => $event->id, 'supplier_id' => $pro->id],
            ]);
        }

        $byLabel = collect(
            $this->actingAs($client)->get('/client/profile')->assertOk()->viewData('activity')
        )->keyBy('label');

        $onBookingsPage = $this->actingAs($client)->get(route('client.bookings.index'))
            ->assertOk()->viewData('bookings')->total();
        $onPaymentsPage = $this->actingAs($client)->get(route('client.payments.index'))
            ->assertOk()->viewData('transactions')->total();

        $this->assertSame($onBookingsPage, $byLabel['Bookings']['count']);
        $this->assertSame($onPaymentsPage, $byLabel['Payments']['count']);
        $this->assertSame(1, $byLabel['Bookings']['count'], 'the self-supplied row is not a booking');
    }

    /**
     * And they do not count anybody else's. A shared panel that quietly
     * included another client's bookings would be a privacy failure wearing
     * the clothes of a convenience.
     */
    public function test_the_counts_do_not_include_another_client(): void
    {
        $mine = $this->client();
        $theirs = $this->client();

        $event = Event::create([
            'title' => 'Theirs', 'client_id' => $theirs->id,
            'created_by' => $theirs->id, 'status' => 'open',
        ]);

        Booking::create([
            'event_id' => $event->id, 'client_id' => $theirs->id,
            'created_by' => $theirs->id, 'status' => 'confirmed',
            'price' => 100, 'currency' => 'USD',
        ]);

        $activity = $this->actingAs($mine)->get('/client/profile')->assertOk()->viewData('activity');

        foreach ($activity as $row) {
            $this->assertSame(0, $row['count'], "{$row['label']} counted somebody else's rows");
        }
    }

    /** A brand-new client sees zeros, not an empty box or a crash. */
    public function test_a_new_client_sees_zeros(): void
    {
        $activity = $this->actingAs($this->client())
            ->get('/client/profile')->assertOk()->viewData('activity');

        $this->assertCount(4, $activity);

        foreach ($activity as $row) {
            $this->assertSame(0, $row['count']);
        }
    }
}
