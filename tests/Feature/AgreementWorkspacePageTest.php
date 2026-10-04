<?php

namespace Tests\Feature;

use App\Domain\Agreements\Workspace;
use App\Models\Bid;
use App\Models\Event;
use App\Models\Finalization;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 27 September: the message and agreement "all within the same
 * area so they both can be directly one on one", with the client down one
 * side and the professional down the other.
 *
 * So Move Forward lands here rather than on step one of a wizard. The steps
 * still do the work; this is what you read, and what you approve or send back
 * from.
 */
class AgreementWorkspacePageTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $pro;

    private Finalization $fin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client', 'name' => 'Dana Whitfield']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = User::findOrFail($this->client->id);

        $this->pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'Priya Raghavan']);
        $this->pro->assignRole('professional');

        $event = Event::create([
            'title'      => 'Johnson Wedding',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(30),
            'city'       => 'Baltimore',
            'state'      => 'MD',
        ]);

        $bid = Bid::create([
            'event_id'    => $event->id,
            'supplier_id' => $this->pro->id,
            'amount'      => 1800,
            'status'      => 'submitted',
        ]);

        $this->fin = Finalization::create([
            'event_id'    => $event->id,
            'bid_id'      => $bid->id,
            'client_id'   => $this->client->id,
            'supplier_id' => $this->pro->id,
        ]);
    }

    private function page(): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->client)->get(route('client.finalize.overview', $this->fin));
    }

    /** Both people and the agreement are on one page. */
    public function test_the_agreement_shows_both_sides_and_the_terms(): void
    {
        $this->page()
            ->assertOk()
            ->assertSee('Event Agreement')
            ->assertSee('Dana Whitfield')
            ->assertSee('Priya Raghavan')
            ->assertSee('Johnson Wedding')
            ->assertSee('Messages');
    }

    /** It opens saying nobody is booked, which is the whole point of it. */
    public function test_it_says_nobody_is_booked_yet(): void
    {
        $this->page()
            ->assertOk()
            ->assertSee('Negotiating')
            ->assertSee('You are not booked yet', false);
    }

    /** A term nobody has settled says so, and offers the step that settles it. */
    public function test_an_unsettled_term_points_at_its_step(): void
    {
        $this->page()
            ->assertOk()
            ->assertSee('Not set yet')
            ->assertSee(route('client.finalize.step', [$this->fin, 'price']), false);
    }

    /** Signing is not offered until both sides approved the same version. */
    public function test_approval_state_is_reported_for_both_sides(): void
    {
        $this->page()->assertOk()->assertSee('Signing opens once you have both approved the same version', false);

        Workspace::approve($this->fin, $this->client);

        $this->page()->assertOk()->assertDontSee('Accept current terms');
    }

    /** Somebody else's agreement is nobody else's business. */
    public function test_another_client_cannot_open_it(): void
    {
        $other = User::factory()->create(['primary_role' => 'client']);
        $other->assignRole('client');

        $this->actingAs($other)
            ->get(route('client.finalize.overview', $this->fin))
            ->assertForbidden();
    }

    /** Move Forward lands here, not on step one. */
    public function test_move_forward_opens_the_agreement(): void
    {
        $this->assertStringContainsString(
            "route('client.finalize.overview', \$fin)",
            file_get_contents(base_path('app/Http/Controllers/Client/ClientFinalizeController.php')),
            'Move Forward no longer lands on the agreement.',
        );
    }
}
