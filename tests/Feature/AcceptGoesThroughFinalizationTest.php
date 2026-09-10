<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Finalization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Accepting a proposal from the Proposals list opens the agreement; it no
 * longer books on the spot.
 *
 * Ali, 2026-09-10: "han finalization pe laga do". The list's Accept created a
 * confirmed booking straight away, skipping scope, price, schedule, contract
 * and the $2.99 fee, while Compare and the chat went through finalization.
 */
class AcceptGoesThroughFinalizationTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private Bid $bid;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create();
        $this->client->assignRole('client');
        $this->client = $this->client->fresh();
        $pro = User::factory()->create();
        $pro->assignRole('professional');

        $event = Event::create(['title' => 'Garden Party', 'status' => 'published', 'is_published' => true,
            'client_id' => $this->client->id, 'created_by' => $this->client->id, 'starts_at' => now()->addMonth()]);
        $this->bid = Bid::create(['event_id' => $event->id, 'supplier_id' => $pro->id, 'amount' => 800, 'status' => 'submitted']);
    }

    public function test_the_old_accept_route_opens_the_agreement_and_books_nothing(): void
    {
        $fin = null;
        $this->actingAs($this->client)->post(route('client.proposals.accept', $this->bid))
            ->assertRedirect()
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'Nothing is booked until you confirm it.'));

        $fin = Finalization::where('bid_id', $this->bid->id)->first();
        $this->assertNotNull($fin, 'Accept should open the agreement.');
        $this->assertSame(0, Booking::count(), 'Nothing is booked until the agreement is finished.');
        $this->assertSame('submitted', $this->bid->fresh()->status, 'The proposal is not won until then either.');
    }

    public function test_pressing_it_twice_opens_one_agreement(): void
    {
        $this->actingAs($this->client)->post(route('client.proposals.accept', $this->bid));
        $this->actingAs($this->client)->post(route('client.proposals.accept', $this->bid));

        $this->assertSame(1, Finalization::where('bid_id', $this->bid->id)->count());
    }

    public function test_someone_elses_proposal_cannot_be_accepted(): void
    {
        $stranger = User::factory()->create();
        $stranger->assignRole('client');

        $this->actingAs($stranger->fresh())->post(route('client.proposals.accept', $this->bid))->assertForbidden();
        $this->assertSame(0, Finalization::count());
    }

    public function test_the_list_button_goes_to_finalization(): void
    {
        $src = file_get_contents(resource_path('views/client/proposals/index.blade.php'));

        $this->assertStringNotContainsString("route('client.proposals.accept'", $src);
        $this->assertStringContainsString("route('client.finalize.start', \$p->id)", $src);
    }

    /** Only one place turns a proposal into a booking. */
    public function test_no_controller_books_a_proposal_itself(): void
    {
        // Making one, that is. Reading bookings (who a request is awarded to)
        // is fine and the Proposals page does it.
        foreach (['ClientProposalController', 'ClientFinalizeController'] as $c) {
            $this->assertDoesNotMatchRegularExpression(
                '/Booking::(create|firstOrCreate|updateOrCreate|insert)\(/',
                file_get_contents(app_path("Http/Controllers/Client/{$c}.php")),
                "{$c} creates a booking itself instead of through App\\Domain\\Requests\\Award.",
            );
        }
    }
}
