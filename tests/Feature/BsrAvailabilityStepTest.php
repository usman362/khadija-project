<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BR wizard, step 7 — date, time and availability.
 *
 * Two defects and one gap, all visible in the same screenshot:
 *
 *  1. The wizard said "Step 7 of 8" and had no eighth step. `furthestAllowed`
 *     listed six completable steps for a seven-completable-step wizard, so
 *     `furthest` capped at step 7's own index: Continue redirected to Review,
 *     and Review's guard bounced straight back. Publishing still worked —
 *     save() has no such guard — so what was actually missing was the client's
 *     chance to read what they were about to publish.
 *
 *  2. The step's two controls carried a class, `.bw-input`, that the wizard's
 *     stylesheet never defines. They rendered as raw browser widgets.
 *
 *  3. The client's mockup asks for start and end times on this step. There was
 *     one "Move your date" datetime box and no end time at all.
 *
 * What is deliberately NOT here: the mockup's Available / Limited / Not
 * Confirmed / Unavailable buckets and its EXCELLENT strength gauge. Three of
 * those four states do not exist in our data and the rating is not measured.
 */
class BsrAvailabilityStepTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function client(): User
    {
        $user = User::factory()->create();
        $user->assignRole('client');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    /**
     * The eighth step exists.
     *
     * The date itself comes from step 2, so Review was always legitimately
     * *earned* — it simply could not be opened, because `furthest` could not
     * count past step 7.
     */
    public function test_the_review_step_is_reachable_once_the_date_is_set(): void
    {
        $client = $this->client();
        $this->throughFiles($client);

        $this->actingAs($client)->post(route('client.bsr.save', 'availability'), [
            'event_date'       => now()->addDays(45)->toDateString(),
            'event_start_time' => '18:00',
        ])->assertRedirect(route('client.bsr.step', 'review'));

        // And now it opens, rather than bouncing back the way it always did.
        $this->actingAs($client)->get(route('client.bsr.step', 'review'))
            ->assertSuccessful()
            ->assertSee('Review &amp; publish', false);
    }

    public function test_the_step_asks_for_a_date_and_both_times(): void
    {
        $client = $this->client();
        $this->throughFiles($client);

        $response = $this->actingAs($client)->get(route('client.bsr.step', 'availability'));

        $response->assertSuccessful();
        $response->assertSee('Event date');
        $response->assertSee('Start time');
        $response->assertSee('End time');

        // The controls are inside .bw-field now, which is what the wizard's
        // stylesheet actually styles — they used to carry `.bw-input`, a class
        // it never defines, and rendered as raw browser widgets.
        $response->assertSee('id="av_date"', false);
        $response->assertSee('id="av_start"', false);
        $response->assertDontSee('class="bw-input"', false);
    }

    public function test_the_date_and_times_become_one_start_and_one_end(): void
    {
        $client = $this->client();
        $this->throughFiles($client);
        $date = now()->addDays(45)->toDateString();

        $this->actingAs($client)->post(route('client.bsr.save', 'availability'), [
            'event_date'       => $date,
            'event_start_time' => '18:00',
            'event_end_time'   => '22:00',
        ]);
        $this->actingAs($client)->post(route('client.bsr.save', 'review'), ['confirm' => 1]);

        $event = Event::where('client_id', $client->id)->latest('id')->first();

        $this->assertSame($date.' 18:00:00', $event->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame($date.' 22:00:00', $event->ends_at->format('Y-m-d H:i:s'));
    }

    /** A reception that runs to 1am ends the NEXT day — not before it began. */
    public function test_an_end_time_before_the_start_rolls_to_the_next_day(): void
    {
        $client = $this->client();
        $this->throughFiles($client);
        $date = now()->addDays(45)->toDateString();

        $this->actingAs($client)->post(route('client.bsr.save', 'availability'), [
            'event_date'       => $date,
            'event_start_time' => '21:00',
            'event_end_time'   => '01:00',
        ]);
        $this->actingAs($client)->post(route('client.bsr.save', 'review'), ['confirm' => 1]);

        $event = Event::where('client_id', $client->id)->latest('id')->first();

        $this->assertTrue($event->ends_at->greaterThan($event->starts_at));
        $this->assertSame(
            now()->addDays(46)->toDateString().' 01:00:00',
            $event->ends_at->format('Y-m-d H:i:s')
        );
    }

    /** No end time is a real answer, not a missing one. */
    public function test_the_end_time_is_optional(): void
    {
        $client = $this->client();
        $this->throughFiles($client);

        $this->actingAs($client)->post(route('client.bsr.save', 'availability'), [
            'event_date'       => now()->addDays(45)->toDateString(),
            'event_start_time' => '18:00',
        ])->assertSessionHasNoErrors();

        $this->actingAs($client)->post(route('client.bsr.save', 'review'), ['confirm' => 1]);

        $this->assertNull(Event::where('client_id', $client->id)->latest('id')->first()->ends_at);
    }

    public function test_the_date_and_start_time_are_required(): void
    {
        $client = $this->client();
        $this->throughFiles($client);

        $this->actingAs($client)
            ->post(route('client.bsr.save', 'availability'), ['event_date' => '', 'event_start_time' => ''])
            ->assertSessionHasErrors(['event_date', 'event_start_time']);
    }

    public function test_a_past_date_is_rejected(): void
    {
        $client = $this->client();
        $this->throughFiles($client);

        $this->actingAs($client)
            ->post(route('client.bsr.save', 'availability'), [
                'event_date'       => now()->subDay()->toDateString(),
                'event_start_time' => '18:00',
            ])
            ->assertSessionHasErrors('event_date');
    }

    /**
     * Moving the event earlier here can strand a deadline that was fine on
     * step 5. A proposal deadline after the event is not a deadline.
     */
    public function test_moving_the_event_earlier_pulls_the_proposal_deadline_back(): void
    {
        $client = $this->client();
        $this->throughFiles($client, deadlineDays: 40);

        $this->actingAs($client)->post(route('client.bsr.save', 'availability'), [
            'event_date'       => now()->addDays(10)->toDateString(),
            'event_start_time' => '18:00',
        ]);
        $this->actingAs($client)->post(route('client.bsr.save', 'review'), ['confirm' => 1]);

        $event = Event::where('client_id', $client->id)->latest('id')->first();

        $this->assertTrue($event->proposal_deadline->lessThan($event->starts_at));
    }

    /**
     * Nobody matching is a different answer from everybody being busy, and it
     * gets a different screen — one that says which door is closed and which
     * are open, rather than three zeros.
     */
    public function test_no_matching_professional_says_so_instead_of_showing_zeros(): void
    {
        $client = $this->client();
        $this->throughFiles($client);

        $response = $this->actingAs($client)->get(route('client.bsr.step', 'availability'));

        $response->assertSuccessful();
        $response->assertSee('No professional on GigResource offers');
        $response->assertSee('Send a Direct Request instead');
        $response->assertDontSee('match your request');
    }

    // ── helpers ──────────────────────────────────────────────

    private function throughFiles(User $client, int $deadlineDays = 20): void
    {
        [$type, $service] = $this->taxonomy();

        $this->actingAs($client)->post(route('client.bsr.save', 'service'), [
            'services'          => [$service->id],
            'event_type'        => $type->name,
            'organization_type' => 'individual',
        ]);
        $this->actingAs($client)->post(route('client.bsr.save', 'event'), [
            'title'       => 'Charity gala',
            'starts_at'   => now()->addDays(45)->format('Y-m-d H:i'),
            'guest_count' => 100,
        ]);
        $this->actingAs($client)->post(route('client.bsr.save', 'requirements'), [
            'description' => 'Catering for one hundred guests with vegetarian options and staff for four hours.',
        ]);
        $this->actingAs($client)->post(route('client.bsr.save', 'budget'), ['budget_min' => 3000, 'budget_max' => 6000]);
        $this->actingAs($client)->post(route('client.bsr.save', 'proposals'), [
            'proposal_deadline' => now()->addDays($deadlineDays)->format('Y-m-d H:i'),
        ]);
        $this->actingAs($client)->post(route('client.bsr.save', 'files'));
    }

    private function taxonomy(): array
    {
        $v2 = config('taxonomy.version', 'v1') === 'v2';

        $type = Category::create([
            'name' => 'Charity Event', 'slug' => 'charity-event', 'is_active' => true,
        ] + ($v2 ? ['kind' => Category::EVENT_TYPE] : []));

        $group = Category::create([
            'name' => 'Catering & Food Services', 'slug' => 'catering-food-services', 'is_active' => true,
        ] + ($v2 ? ['kind' => Category::SERVICE_CATEGORY] : []));

        $service = Category::create([
            'name' => 'Full-Service Catering', 'slug' => 'full-service-catering',
            'parent_id' => $group->id, 'is_active' => true,
        ] + ($v2 ? ['kind' => Category::SERVICE] : []));

        return [$type, $service];
    }
}
