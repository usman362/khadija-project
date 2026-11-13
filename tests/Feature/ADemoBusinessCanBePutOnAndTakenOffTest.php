<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\Payout;
use App\Models\Review;
use App\Models\User;
use App\Support\DemoBusiness;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Putting a demonstration business on a live account, and taking it off.
 *
 * The seeders refuse on production and that rule stays. This is the other
 * shape of the same need: one named account, nothing written until asked,
 * and every row removable because every row is marked.
 *
 * The two things that matter here are that it cannot reach anybody else's
 * screen, and that removing it removes exactly what it added. The second is
 * the one that is easy to get wrong: a first pass swept the open requests a
 * different seeder writes, which meant tidying up a demonstration could
 * delete a bid somebody had really placed.
 */
class ADemoBusinessCanBePutOnAndTakenOffTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro    = $this->person('professional', 'Lorcan Whitby');
        $this->client = $this->person('client', 'Marisol Quayle');

        $service = Category::create([
            'name' => 'Wedding DJs', 'slug' => 'wedding-djs-'.uniqid(),
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        $this->pro->serviceCategories()->syncWithoutDetaching([$service->id]);
        $this->pro = User::findOrFail($this->pro->id);
    }

    private function person(string $role, string $name): User
    {
        $u = User::factory()->create(['primary_role' => $role, 'name' => $name]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    /** Nothing is written until somebody says so. */
    public function test_it_writes_nothing_without_force(): void
    {
        $this->artisan('demo:business', [
            'professional' => $this->pro->email,
            '--client'     => $this->client->email,
        ])
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        $this->assertSame(0, Booking::where('supplier_id', $this->pro->id)->count());
    }

    /** A client that is not there stops it, rather than guessing one. */
    public function test_it_stops_when_the_booking_client_does_not_exist(): void
    {
        $this->artisan('demo:business', [
            'professional' => $this->pro->email,
            '--client'     => 'nobody@example.test',
        ])->assertFailed();
    }

    /** An account that is not a professional is refused by name. */
    public function test_it_refuses_an_account_that_is_not_a_professional(): void
    {
        $this->artisan('demo:business', ['professional' => $this->client->email, '--force' => true])
            ->assertFailed();

        $this->assertSame(0, Booking::where('supplier_id', $this->client->id)->count());
    }

    /** The whole business arrives, and the dashboard reads it. */
    public function test_it_builds_a_business_the_dashboard_can_read(): void
    {
        $this->artisan('demo:business', [
            'professional' => $this->pro->email,
            '--client'     => $this->client->email,
            '--force'      => true,
        ])->assertSuccessful();

        $this->assertGreaterThan(20, Booking::where('supplier_id', $this->pro->id)->count());
        $this->assertSame(2, Payout::where('user_id', $this->pro->id)->count());
        $this->assertSame(2, Review::where('reviewee_id', $this->pro->id)->count());

        $this->actingAs($this->pro->fresh())->get(route('professional.dashboard'))
            ->assertOk()
            ->assertSee('This month against your goal')
            ->assertSee('When your work falls')
            ->assertDontSee('Nothing booked for today.');
    }

    /**
     * None of it can reach another professional.
     *
     * Every job is awarded, so the Opportunity Feed — which asks for work
     * with no professional on it — cannot return any of them. This is what
     * makes the command safe to run on a live site at all.
     */
    public function test_none_of_it_appears_on_anybody_elses_feed(): void
    {
        $this->artisan('demo:business', [
            'professional' => $this->pro->email,
            '--client'     => $this->client->email,
            '--force'      => true,
        ])->assertSuccessful();

        $this->assertSame(
            0,
            Event::where('description', 'like', '%'.DemoBusiness::MARK.'%')
                ->whereNull('supplier_id')->count(),
            'A demonstration job with no professional on it would be offered to real ones.',
        );

        $other = $this->person('professional', 'Imelda Rooke');
        $other->serviceCategories()->syncWithoutDetaching(
            $this->pro->serviceCategories()->pluck('categories.id')->all()
        );

        $feed = \App\Support\OpportunityFeed::for(User::findOrFail($other->id));

        $this->assertCount(0, $feed['listed']);
        $this->assertCount(0, $feed['related']);
    }

    /** Taking it off takes exactly what was put on, and nothing else. */
    public function test_removing_it_leaves_real_rows_alone(): void
    {
        // Something of this professional's own, which must survive.
        $real = Event::create([
            'title' => 'A real request', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'status' => 'published',
            'is_published' => true, 'starts_at' => now()->addDays(20),
            'location' => 'Baltimore, MD', 'state' => 'MD',
        ]);

        $realBid = Bid::create([
            'event_id' => $real->id, 'supplier_id' => $this->pro->id,
            'amount' => 900, 'status' => 'submitted', 'note' => 'My own bid',
        ]);

        $this->artisan('demo:business', [
            'professional' => $this->pro->email,
            '--client'     => $this->client->email,
            '--force'      => true,
        ])->assertSuccessful();

        $this->artisan('demo:business', [
            'professional' => $this->pro->email,
            '--remove'     => true,
            '--force'      => true,
        ])->assertSuccessful();

        $this->assertSame(0, Booking::where('supplier_id', $this->pro->id)->count());
        $this->assertSame(0, Payout::where('user_id', $this->pro->id)->count());
        $this->assertSame(0, Review::where('reviewee_id', $this->pro->id)->count());
        $this->assertSame(
            0,
            Event::where('description', 'like', '%'.DemoBusiness::MARK.'%')->count(),
        );

        $this->assertDatabaseHas('bids', ['id' => $realBid->id]);
        $this->assertDatabaseHas('events', ['id' => $real->id]);
    }

    /** Running it twice does not double anything. */
    public function test_building_it_twice_changes_nothing_the_second_time(): void
    {
        $run = fn () => $this->artisan('demo:business', [
            'professional' => $this->pro->email,
            '--client'     => $this->client->email,
            '--force'      => true,
        ])->assertSuccessful();

        $run();
        $first = Booking::where('supplier_id', $this->pro->id)->count();

        $run();

        $this->assertSame($first, Booking::where('supplier_id', $this->pro->id)->count());
    }
}
