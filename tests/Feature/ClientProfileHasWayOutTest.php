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
