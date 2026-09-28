<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Support\ServiceAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The request wizard's availability step.
 *
 * The mockup offers four buckets — Available, Limited, Not Confirmed,
 * Unavailable — and a confidence gauge. Three of those buckets do not exist in
 * our data, and the one that does is not called "available": Availability
 * reads the GigResource calendar only, so a clear day means no commitment ON
 * GIGRESOURCE, not that the professional is free.
 *
 * These hold the step to what it can prove: who matches, who has the day
 * clear, who is already booked — and the caveat that says why that is not a
 * promise.
 */
class RequestAvailabilityStepTest extends TestCase
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
        $this->client->givePermissionTo('dashboard.view');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();

        $parent = Category::firstOrCreate(['slug' => 'avail-cat'],
            ['name' => 'Catering', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true]);
        $this->service = Category::create([
            'name' => 'Full-Service Catering', 'slug' => 'full-service-catering',
            'kind' => Category::SERVICE, 'parent_id' => $parent->id, 'is_active' => true,
        ]);
    }

    private function pro(string $state = 'MD'): User
    {
        $p = User::factory()->create();
        $p->assignRole('professional');
        $p->getOrCreateProfile()->update(['country' => 'US', 'state' => $state, 'city' => 'Baltimore']);
        $p->serviceCategories()->attach($this->service->id);

        return $p->fresh();
    }

    // ── What the counts mean ─────────────────────────────────────

    public function test_it_counts_matching_professionals_with_the_day_clear(): void
    {
        $this->pro();
        $this->pro();

        $counts = ServiceAvailability::on([$this->service->id], 'MD', now()->addMonth());

        $this->assertSame(2, $counts['matched']);
        $this->assertSame(2, $counts['nothing_booked']);
        $this->assertSame(0, $counts['already_booked']);
    }

    /** A professional with work that day is genuinely unavailable. */
    public function test_a_professional_booked_that_day_is_counted_as_booked(): void
    {
        $busy = $this->pro();
        $date = now()->addMonth();

        $event = Event::create([
            'title' => 'Other job', 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'confirmed', 'starts_at' => $date,
        ]);
        Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id, 'supplier_id' => $busy->id,
            'created_by' => $this->client->id, 'status' => 'confirmed', 'price' => 100, 'currency' => 'USD',
        ]);

        $counts = ServiceAvailability::on([$this->service->id], 'MD', $date);

        $this->assertSame(1, $counts['matched']);
        $this->assertSame(0, $counts['nothing_booked']);
        $this->assertSame(1, $counts['already_booked']);
    }

    /** R38 is a gate, not a ranking — an out-of-state pro cannot take the work. */
    public function test_out_of_state_professionals_are_not_counted(): void
    {
        $this->pro('MD');
        $this->pro('NY');

        $this->assertSame(1, ServiceAvailability::on([$this->service->id], 'MD', now()->addMonth())['matched']);
    }

    /** Nearby dates never offer a day nobody could book. */
    public function test_nearby_dates_exclude_the_past(): void
    {
        $this->pro();

        $days = ServiceAvailability::around([$this->service->id], 'MD', now());

        foreach ($days as $d) {
            $this->assertTrue($d['date']->isToday() || $d['date']->isFuture());
        }
    }

    // ── What the screen says ─────────────────────────────────────

    private function walkTo(string $step): \Illuminate\Testing\TestResponse
    {
        $save = fn (string $s, array $data) => $this->actingAs($this->client)
            ->post(route('client.bsr.save', $s), $data)->assertSessionHasNoErrors();

        Category::firstOrCreate(['slug' => 'wedding'],
            ['name' => 'Wedding', 'kind' => Category::EVENT_TYPE, 'is_active' => true]);

        $save('service', [
            'services' => [$this->service->id], 'event_type' => 'Wedding',
            'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
        ]);
        $save('event', [
            'title' => 'Availability check', 'starts_at' => now()->addMonth()->format('Y-m-d\TH:i'),
            'location' => 'Baltimore', 'guest_count' => 100, 'event_state' => 'MD',
        ]);
        $save('requirements', ['description' => 'Full-service catering for one hundred guests, plated service.']);
        $save('budget', ['budget_min' => 2000, 'budget_max' => 4000]);
        $save('proposals', []);
        $save('files', []);

        // The date is asked on the availability step itself.
        $save('availability', ['event_date' => now()->addMonth()->toDateString(), 'event_start_time' => '18:00']);

        return $this->actingAs($this->client)->get(route('client.bsr.step', $step))->assertOk();
    }

    public function test_the_step_states_the_caveat_and_avoids_the_word_available(): void
    {
        $this->pro();

        $html = $this->walkTo('availability')->getContent();

        $this->assertStringContainsString('have nothing booked', $html);
        $this->assertStringContainsString('on GigResource', $html);

        // The mockup's invented buckets must not appear.
        foreach (['Not Confirmed', 'Availability Strength', 'EXCELLENT'] as $invented) {
            $this->assertStringNotContainsString($invented, $html);
        }
    }

    /**
     * The hours each service runs reach the professional who reads the
     * request, and the one timing note does not, because it is not asked any
     * more.
     *
     * Sir Peter, 27 September: "with this newer set up, we can remove the
     * 'Anything they should know about timing?' question bc its asked after
     * each service."
     */
    public function test_each_service_carries_its_own_hours(): void
    {
        $this->walkTo('availability');

        $services = (array) (session('bsr_wizard')['services'] ?? []);
        $this->assertNotEmpty($services, 'The walk should have chosen a service.');
        $first = (int) $services[0];

        $this->actingAs($this->client)->post(route('client.bsr.save', 'availability'), [
            'event_date'       => now()->addDays(30)->toDateString(),
            'event_start_time' => '18:00',
            'event_end_time'   => '23:00',
            'service_times'    => [$first => ['start' => '19:00', 'end' => '21:00']],
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->client)->post(route('client.bsr.save', 'review'), ['confirm' => 1])
            ->assertSessionHasNoErrors();

        $event = Event::where('client_id', $this->client->id)->latest('id')->firstOrFail();
        $rows = \App\Domain\Requests\ServiceTimeline::of($event->load('categories'));

        $mine = collect($rows)->firstWhere('id', $first);
        $this->assertNotNull($mine);
        $this->assertTrue($mine['own'], 'The service kept its own hours.');
        $this->assertSame('19:00', $mine['starts_at']->format('H:i'));
        $this->assertSame('21:00', $mine['ends_at']->format('H:i'));
        $this->assertSame('2 hrs', \App\Domain\Requests\ServiceTimeline::duration($mine['minutes']));
    }

    /** A service left blank runs for the whole event, and says so. */
    public function test_a_service_with_no_hours_follows_the_event(): void
    {
        $this->walkTo('availability');

        $this->actingAs($this->client)->post(route('client.bsr.save', 'availability'), [
            'event_date'       => now()->addDays(30)->toDateString(),
            'event_start_time' => '18:00',
            'event_end_time'   => '23:00',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->client)->post(route('client.bsr.save', 'review'), ['confirm' => 1])
            ->assertSessionHasNoErrors();

        $event = Event::where('client_id', $this->client->id)->latest('id')->firstOrFail();
        $row = \App\Domain\Requests\ServiceTimeline::of($event->load('categories'))[0];

        $this->assertFalse($row['own']);
        $this->assertSame('18:00', $row['starts_at']->format('H:i'));
        $this->assertSame('23:00', $row['ends_at']->format('H:i'));
    }

    /** And the question it replaced is off the step. */
    public function test_the_one_timing_question_is_gone(): void
    {
        $this->walkTo('availability');

        $html = $this->actingAs($this->client)
            ->get(route('client.bsr.step', 'availability'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Anything they should know about timing?', $html);
        $this->assertStringNotContainsString('name="availability_note"', $html);
        $this->assertStringContainsString('When does each service run?', $html);
    }
}
