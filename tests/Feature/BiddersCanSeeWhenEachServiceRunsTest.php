<?php

namespace Tests\Feature;

use App\Models\{Booking, Category, Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Per-service times, shown to the people they were built for.
 *
 * Sir Peter asked for start and finish under each service for one reason, in
 * his own words: so bidders see when each runs and whether they overlap. The
 * client has been able to set them since 28 September, ServiceTimeline reads
 * them and can even work out the clashes, and all of it appeared on the
 * client's own event page. The bidders could not see any of it.
 */
class BiddersCanSeeWhenEachServiceRunsTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;
    private Event $event;
    private Category $dj;
    private Category $photo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro    = $this->person('professional', 'Hollis Tremaine');
        $this->client = $this->person('client', 'Imogen Hartwell');

        $this->dj    = $this->service('Wedding DJ', 'wedding-dj');
        $this->photo = $this->service('Event Photography', 'event-photography');

        $this->event = Event::create([
            'title' => 'Hartwell Anniversary', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'supplier_id' => $this->pro->id,
            'status' => 'published', 'is_published' => true,
            'starts_at' => now()->addDays(20)->setTime(17, 0),
            'ends_at'   => now()->addDays(20)->setTime(23, 0),
            'location' => 'Baltimore, MD',
        ]);

        Booking::create([
            'event_id' => $this->event->id, 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'supplier_id' => $this->pro->id,
            'category_id' => $this->dj->id, 'status' => 'confirmed', 'price' => 2600,
        ]);
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

    private function service(string $name, string $slug): Category
    {
        return Category::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'kind' => Category::SERVICE, 'is_active' => true],
        );
    }

    /** @param array<int, array{0:string,1:string}> $times id => [start, end] */
    private function withTimes(array $times): void
    {
        foreach ($times as $id => [$start, $end]) {
            $this->event->categories()->syncWithoutDetaching([
                $id => [
                    'starts_at' => now()->addDays(20)->setTimeFromTimeString($start),
                    'ends_at'   => now()->addDays(20)->setTimeFromTimeString($end),
                ],
            ]);
        }

        $this->event->refresh();
    }

    private function gigPage(): string
    {
        return $this->actingAs($this->pro)
            ->get(route('professional.gigs.show', $this->event))
            ->assertSuccessful()
            ->getContent();
    }

    public function test_the_times_the_client_set_are_on_the_gig_page(): void
    {
        $this->withTimes([
            $this->dj->id    => ['19:00', '23:00'],
            $this->photo->id => ['17:00', '20:00'],
        ]);

        $html = $this->gigPage();

        $this->assertStringContainsString('When each service runs', $html);
        $this->assertStringContainsString('7:00 PM', $html);
        $this->assertStringContainsString('5:00 PM', $html);
    }

    /**
     * The other half of what he asked for. Two services at once is the
     * client's plan, not a mistake, but a bidder has to know before they
     * promise to be in both places.
     */
    public function test_services_running_at_the_same_time_are_called_out(): void
    {
        $this->withTimes([
            $this->dj->id    => ['19:00', '23:00'],
            $this->photo->id => ['17:00', '20:00'],
        ]);

        $this->assertStringContainsString('Running at the same time', $this->gigPage());
    }

    public function test_services_that_do_not_overlap_raise_nothing(): void
    {
        $this->withTimes([
            $this->dj->id    => ['21:00', '23:00'],
            $this->photo->id => ['17:00', '20:00'],
        ]);

        $this->assertStringNotContainsString('Running at the same time', $this->gigPage());
    }

    /**
     * Nothing is said where the client did not break the day up. A service
     * that simply follows the event is not an answer to repeat back.
     */
    public function test_nothing_is_shown_when_the_client_set_no_times(): void
    {
        $this->event->categories()->syncWithoutDetaching([$this->dj->id, $this->photo->id]);
        $this->event->refresh();

        $this->assertStringNotContainsString('When each service runs', $this->gigPage());
    }
}
