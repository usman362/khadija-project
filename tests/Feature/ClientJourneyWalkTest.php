<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The client's journey, walked end to end.
 *
 * The Owner's measure for this month is one user type genuinely finished from
 * beginning to end, demonstrated rather than described. This walks that path
 * as a real client and records what each step does, so the gaps are a list
 * rather than an impression.
 *
 * It reports every step rather than stopping at the first failure — the point
 * is the whole map, not the first pothole.
 */
class ClientJourneyWalkTest extends TestCase
{
    use RefreshDatabase;

    private array $log = [];

    private function step(string $name, callable $fn): void
    {
        try {
            $result = $fn();
            $this->log[] = sprintf('  OK    %-42s %s', $name, $result ?: '');
        } catch (\Throwable $e) {
            $this->log[] = sprintf('  FAIL  %-42s %s', $name, mb_substr($e->getMessage(), 0, 90));
        }
    }

    public function test_walk_the_client_journey(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $parent = Category::create(['name' => 'Photography Services', 'slug' => 'photo-svcs',
            'kind' => Category::SERVICE_CATEGORY, 'is_active' => true]);
        $service = Category::create(['name' => 'Event Photography', 'slug' => 'event-photography',
            'kind' => Category::SERVICE, 'is_active' => true, 'parent_id' => $parent->id]);
        Category::create(['name' => 'Wedding', 'slug' => 'wedding',
            'kind' => Category::EVENT_TYPE, 'is_active' => true, 'archetype' => 'Wedding & Related Ceremonies']);

        $client = User::factory()->create(['primary_role' => 'client']);
        $client->assignRole('client');
        $client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => 'supported']);
        $client = $client->fresh();

        $pro = User::factory()->create(['primary_role' => 'professional']);
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => 'supported']);
        $pro->serviceCategories()->attach($service->id);
        $pro = $pro->fresh();

        /* ── 1. Arrive and look around ─────────────────────── */
        $this->step('1. Landing page', fn () => $this->get('/')->assertOk() && '');
        $this->step('2. Browse professionals', fn () => $this->actingAs($client)->get(route('public.browse'))->assertOk() && '');
        $this->step('3. Event types page', fn () => $this->get(route('public.event-types'))->assertOk() && '');
        $this->step('4. One event type', fn () => $this->get(route('public.category', 'wedding'))->assertOk() && '');
        $this->step('5. Shop packages', fn () => $this->get(route('public.packages'))->assertOk() && '');

        /* ── 2. The client's own portal ────────────────────── */
        $this->step('6. Dashboard', fn () => $this->actingAs($client)->get(route('client.dashboard'))->assertOk() && '');
        $this->step('7. My events', fn () => $this->actingAs($client)->get(route('client.events.index'))->assertOk() && '');
        $this->step('8. Messages', fn () => $this->actingAs($client)->get(route('client.chat.index'))->assertOk() && '');
        $this->step('9. Bookings', fn () => $this->actingAs($client)->get(route('client.bookings.index'))->assertOk() && '');
        $this->step('10. Payments', fn () => $this->actingAs($client)->get(route('client.payments.index'))->assertOk() && '');

        /* ── 3. Post a request, step by step ───────────────── */
        $save = fn (string $step, array $data) => $this->actingAs($client)
            ->post(route('client.bsr.save', $step), $data)->assertSessionHasNoErrors();

        $this->step('11. Wizard: services', fn () => $save('service', [
            'services' => [$service->id], 'event_type' => 'Wedding',
            'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
            'characteristic' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::CHARACTERISTICS),
        ]) && '');
        $this->step('12. Wizard: event details', fn () => $save('event', [
            'title' => 'Spring Wedding', 'starts_at' => now()->addMonths(3)->format('Y-m-d\TH:i'),
            'location' => 'Baltimore', 'guest_count' => 120, 'event_state' => 'MD',
        ]) && '');
        $this->step('13. Wizard: requirements', fn () => $save('requirements', [
            'description' => 'We need a photographer for a spring wedding with 120 guests, all day.',
        ]) && '');
        $this->step('14. Wizard: budget', fn () => $save('budget', ['budget_min' => 2000, 'budget_max' => 3000]) && '');
        $this->step('15. Wizard: proposals', fn () => $save('proposals', [
            'proposal_deadline' => now()->addWeeks(2)->format('Y-m-d\TH:i'),
        ]) && '');
        $this->step('16. Wizard: files', fn () => $save('files', []) && '');
        $this->step('17. Publish the request', function () use ($client) {
            $r = $this->actingAs($client)->post(route('client.bsr.save', 'review'), ['confirm' => 1]);
            $errs = session('errors');
            if ($errs) { throw new \RuntimeException(json_encode($errs->getBag('default')->all())); }
            $e = \App\Models\Event::where('client_id', $client->id)->latest('id')->first();

            return $e && $e->is_published ? "event #{$e->id} live" : 'NOT PUBLISHED';
        });

        $event = \App\Models\Event::where('client_id', $client->id)->latest('id')->first();

        /* ── 4. A professional responds ────────────────────── */
        $this->step('18. Request reaches the board', function () use ($pro, $event) {
            $gigs = collect($this->actingAs($pro)->get(route('professional.bidding-board.index'))
                ->assertOk()->viewData('gigs'));

            return $gigs->where('event_id', $event?->id)->isNotEmpty() ? 'visible to the pro' : 'NOT ON THE BOARD';
        });

        $bid = null;
        $this->step('19. Professional bids', function () use ($pro, $event, $service, &$bid) {
            $bid = Bid::create(['event_id' => $event->id, 'supplier_id' => $pro->id,
                'category_id' => $service->id, 'amount' => 2400, 'status' => 'pending',
                'message' => 'Happy to cover the full day.']);

            return "bid #{$bid->id}";
        });

        /* ── 5. The client decides ─────────────────────────── */
        $this->step('20. Client sees proposals', function () use ($client, $event) {
            return $this->actingAs($client)->get(route('client.proposals.compare', $event))->assertOk() ? '' : '';
        });
        $this->step('21. Start finalising a bid', function () use ($client, $bid) {
            $r = $this->actingAs($client)->post(route('client.finalize.start', $bid));

            return 'status ' . $r->getStatusCode() . ', finalization #' . (\App\Models\Finalization::first()?->id ?? 'none');
        });

        /* ── 5b. The seven finalisation steps ──────────────── */
        $fin = \App\Models\Finalization::first();

        $fsave = function (string $step, array $data) use ($client, &$fin) {
            $r = $this->actingAs($client)->post(route('client.finalize.save', [$fin, $step]), $data);
            $errs = session('errors');
            if ($errs && $errs->getBag('default')->any()) {
                throw new \RuntimeException(implode(' | ', $errs->getBag('default')->all()));
            }
            $fin->refresh();

            return $fin->completed($step) ? 'agreed' : 'saved, not marked agreed';
        };

        $this->step('21a. Finalize: review bid', fn () => $fsave('bid', []));
        $this->step('21b. Finalize: scope', fn () => $fsave('scope', [
            'scope' => 'Full-day coverage, eight hours, edited gallery delivered within three weeks.',
        ]));
        $this->step('21c. Finalize: price', fn () => $fsave('price', ['agreed_price' => 2400]));
        $this->step('21d. Finalize: schedule', fn () => $fsave('schedule', [
            'service_start' => now()->addMonths(3)->format('Y-m-d\TH:i'),
            'service_end'   => now()->addMonths(3)->addHours(8)->format('Y-m-d\TH:i'),
        ]));
        $this->step('21e. Finalize: deposit & terms', fn () => $fsave('terms', ['deposit_percent' => 25]));
        $this->step('21f. Finalize: contract', fn () => $fsave('contract', [
            'client_signature' => 'Client User', 'agree' => 1,
        ]));
        $this->step('21g. Finalize: payment', fn () => $fsave('payment', ['confirm_payment' => 1]));

        $this->step('21h. Booking exists', function () use ($client) {
            $b = \App\Models\Booking::where('client_id', $client->id)->first();

            return $b ? "booking #{$b->id}, status {$b->status}" : 'NO BOOKING CREATED';
        });

        /* ── 6. The work happens, then closing it out ──────── */
        $booking = \App\Models\Booking::where('client_id', $client->id)->first();

        // Only the professional marks work delivered — TRANSITION_ACTORS says
        // 'confirmed->completed' => ['supplier'], and the client's own route
        // refuses it. That is the rule, not a gap.
        $this->step('22a. Professional marks it delivered', function () use ($pro, $booking) {
            if (! $booking) { return 'NO BOOKING TO COMPLETE'; }
            $r = $this->actingAs($pro)->patch(route('professional.proposals.update-status', $booking), ['status' => 'completed']);
            $errs = session('errors');
            if ($errs && $errs->getBag('default')->any()) {
                throw new \RuntimeException(implode(' | ', $errs->getBag('default')->all()));
            }

            return 'status now ' . $booking->fresh()->status;
        });

        $this->step('22b. Leave a review', function () use ($client, $booking) {
            if (! $booking) { return 'NO BOOKING TO REVIEW'; }
            $r = $this->actingAs($client)->post(route('client.reviews.store', $booking), [
                'rating' => 5, 'comment' => 'Turned up early, worked the whole day, gallery arrived on time.',
            ]);
            $errs = session('errors');
            if ($errs && $errs->getBag('default')->any()) {
                throw new \RuntimeException(implode(' | ', $errs->getBag('default')->all()));
            }

            return \App\Models\Review::count() . ' review(s) recorded';
        });

        /* ── 7. After the booking ──────────────────────────── */
        $this->step('22. Reviews page', fn () => $this->actingAs($client)->get(route('client.reviews.index'))->assertOk() && '');
        $this->step('23. Notifications', fn () => $this->actingAs($client)->get(route('client.notifications.index'))->assertOk() && '');

        fwrite(STDERR, "\n\nCLIENT JOURNEY\n" . implode("\n", $this->log) . "\n\n");

        // The walk is the demonstration; these are the claims it has to keep.
        $this->assertEmpty(
            array_filter($this->log, fn ($l) => str_starts_with($l, '  FAIL')),
            "the client journey broke:\n" . implode("\n", $this->log),
        );

        $event   = \App\Models\Event::where('client_id', $client->id)->latest('id')->first();
        $booking = \App\Models\Booking::where('client_id', $client->id)->first();

        $this->assertTrue((bool) $event?->is_published, 'the request was published');
        $this->assertNotNull($booking, 'a booking came out of it');
        $this->assertSame('completed', $booking->status, 'and it finished');
        $this->assertSame(1, \App\Models\Review::count(), 'and the client could review it');
    }
}
