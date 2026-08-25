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
            'characteristic' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::CHARACTERISTICS),
        ]);
        $save('event', [
            'title' => 'Availability check', 'starts_at' => now()->addMonth()->format('Y-m-d\TH:i'),
            'location' => 'Baltimore', 'guest_count' => 100, 'event_state' => 'MD',
        ]);
        $save('requirements', ['description' => 'Full-service catering for one hundred guests, plated service.']);
        $save('budget', ['budget_min' => 2000, 'budget_max' => 4000]);
        $save('proposals', []);
        $save('files', []);

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

    /** The client's timing note reaches the professional who reads the request. */
    public function test_the_timing_note_is_stored_and_shown_to_the_professional(): void
    {
        $pro = $this->pro();
        $this->walkTo('availability');

        $this->actingAs($this->client)->post(route('client.bsr.save', 'availability'), [
            'availability_note' => 'Setup can start from 3pm.',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->client)->post(route('client.bsr.save', 'review'), ['confirm' => 1])
            ->assertSessionHasNoErrors();

        $event = Event::where('client_id', $this->client->id)->latest('id')->firstOrFail();
        $this->assertSame('Setup can start from 3pm.', $event->schedule_note);

        $this->actingAs($pro)->get(route('professional.gigs.show', $event))
            ->assertOk()->assertSee('Setup can start from 3pm.', false);
    }
}
