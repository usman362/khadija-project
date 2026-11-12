<?php

namespace Tests\Feature;

use App\Models\Bid;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Event;
use App\Models\Finalization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The professional's side of a real job, walked from the client's first post
 * to the money.
 *
 * Ali, 13 November: "client se professional k liye kuch create ho, like
 * event, jab professional bid kare, qk real time client aur professional
 * dono test karne hain, kuch break na ho."
 *
 * ClientJourneyWalkTest already walks the client's path, but at the moment
 * the professional should bid it writes a Bid row by hand. So the bid wizard
 * itself, the six screens a professional actually fills in, was never walked
 * by anything. This walks it, through the real routes, and then checks that
 * the job turns up on every professional screen that should know about it.
 *
 * It reports every step rather than stopping at the first failure: the point
 * is the whole map.
 */
class AProfessionalWalksTheWholeJobTest extends TestCase
{
    use RefreshDatabase;

    private array $log = [];
    private int $failures = 0;

    private function step(string $name, callable $fn): void
    {
        try {
            $result = $fn();
            $this->log[] = sprintf('  OK    %-44s %s', $name, $result ?: '');
        } catch (\Throwable $e) {
            $this->failures++;
            $this->log[] = sprintf('  FAIL  %-44s %s', $name, mb_substr($e->getMessage(), 0, 100));
        }
    }

    public function test_walk_a_whole_job_from_the_professionals_side(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $parent = Category::create(['name' => 'Catering Services', 'slug' => 'catering-svcs',
            'kind' => Category::SERVICE_CATEGORY, 'is_active' => true]);
        $service = Category::create(['name' => 'Buffet Catering', 'slug' => 'buffet-catering',
            'kind' => Category::SERVICE, 'is_active' => true, 'parent_id' => $parent->id]);
        Category::create(['name' => 'Wedding', 'slug' => 'wedding',
            'kind' => Category::EVENT_TYPE, 'is_active' => true, 'archetype' => 'Wedding & Related Ceremonies']);

        $client = $this->account('client');
        $pro    = $this->account('professional');
        $pro->serviceCategories()->attach($service->id);
        $pro = $pro->fresh();

        /* ── The client posts a real request ──────────────────────── */
        $save = fn (string $step, array $data) => $this->actingAs($client)
            ->post(route('client.bsr.save', $step), $data)->assertSessionHasNoErrors();

        $this->step('1. Client picks the service', fn () => $save('service', [
            'services' => [$service->id], 'event_type' => 'Wedding',
            'organization_type' => array_key_first(\App\Http\Controllers\Client\ClientBsrController::ORG_TYPES),
        ]) && '');
        $this->step('2. Client fills the event', fn () => $save('event', [
            'title' => 'Harbour Wedding Dinner', 'starts_at' => now()->addMonths(3)->format('Y-m-d\TH:i'),
            'location' => 'Baltimore', 'guest_count' => 140, 'event_state' => 'MD',
        ]) && '');
        $this->step('3. Client states the brief', fn () => $save('requirements', [
            'description' => 'Buffet dinner for 140 guests at a harbour venue, service from six until late.',
        ]) && '');
        $this->step('4. Client sets a budget', fn () => $save('budget', ['budget_min' => 4000, 'budget_max' => 6000]) && '');
        $this->step('5. Client sets a deadline', fn () => $save('proposals', [
            'proposal_deadline' => now()->addWeeks(2)->format('Y-m-d\TH:i'),
        ]) && '');
        $this->step('6. Client adds no files', fn () => $save('files', []) && '');
        $this->step('7. Client names the date', fn () => $save('availability', [
            'event_date' => now()->addMonths(3)->toDateString(), 'event_start_time' => '18:00',
        ]) && '');
        $this->step('8. Client publishes it', function () use ($client) {
            $this->actingAs($client)->post(route('client.bsr.save', 'review'), ['confirm' => 1]);
            $e = Event::where('client_id', $client->id)->latest('id')->first();

            return $e && $e->is_published ? "event #{$e->id} live" : 'NOT PUBLISHED';
        });

        $event = Event::where('client_id', $client->id)->latest('id')->first();
        $this->assertNotNull($event, 'Nothing to walk: the client could not publish a request.');

        /* ── It reaches the professional ──────────────────────────── */
        $this->step('9. It is on the bidding board', function () use ($pro, $event) {
            $gigs = collect($this->actingAs($pro)->get(route('professional.bidding-board.index'))
                ->assertOk()->viewData('gigs'));

            return $gigs->where('event_id', $event->id)->isNotEmpty() ? 'visible' : 'NOT ON THE BOARD';
        });

        $this->step('10. It is a lead they may take', function () use ($pro, $event) {
            $lead = \App\Support\LeadPipeline::forProfessional($pro)->firstWhere('event_id', $event->id);

            if (! $lead) {
                return 'NOT IN THE LEADS LIST';
            }

            return $lead['can_bid'] ? "stage {$lead['stage']}, biddable" : "stage {$lead['stage']}, NOT BIDDABLE";
        });

        $this->step('11. Leads CRM renders it', function () use ($pro, $event) {
            $html = $this->actingAs($pro)->get(route('professional.leads.index'))->assertOk()->getContent();

            return str_contains($html, e($event->title))
                ? 'the title is on the page'
                : 'TITLE NOT ON THE PAGE (event title: ' . $event->title . ')';
        });

        /* ── The bid wizard, all six steps, through the real routes ── */
        $wizard = function (string $step, array $data) use ($pro, $event) {
            $r = $this->actingAs($pro)->post(route('professional.bid.save', [$event, $step]), $data);
            $errs = session('errors');
            if ($errs && $errs->getBag('default')->any()) {
                throw new \RuntimeException(implode(' | ', $errs->getBag('default')->all()));
            }

            return 'saved, ' . $r->getStatusCode();
        };

        $this->step('12. Wizard opens', fn () => $this->actingAs($pro)
            ->get(route('professional.bid.step', [$event]))->assertOk() && '');

        $this->step('13. Wizard: price', fn () => $wizard('price', [
            'amount' => 5200, 'sealed_ack' => '1',
            'item_label' => ['Food', 'Service staff'], 'item_cost' => [3800, 1400],
        ]));

        $dates = collect(\App\Domain\Requests\EventDates::options($event))->pluck('date');
        $this->step('14. Wizard: availability', fn () => $wizard('availability',
            $dates->isEmpty()
                ? ['available_confirmed' => '1']
                : ['confirmed_date' => $dates->first()]));

        $this->step('15. Wizard: service plan', fn () => $wizard('plan', [
            'plan' => 'Two chefs and four servers on site from four, buffet open at six, cleared by midnight.',
        ]));
        $this->step('16. Wizard: timeline and terms', fn () => $wizard('terms', [
            'terms' => 'Half on booking, the rest on the day.',
        ]));
        $this->step('17. Wizard: files', fn () => $wizard('files', []));
        $this->step('18. Wizard: review and submit', fn () => $wizard('review', [
            'note' => 'Happy to talk the menu through.', 'confirm' => '1',
        ]));

        $this->step('19. The bid exists and is submitted', function () use ($pro, $event) {
            $bid = Bid::where('supplier_id', $pro->id)->where('event_id', $event->id)->first();

            if (! $bid) {
                return 'NO BID WAS WRITTEN';
            }

            return "bid #{$bid->id}, status {$bid->status}, \${$bid->amount}";
        });

        /* ── The professional's own screens know about it ─────────── */
        $this->step('20. My Bids shows it', function () use ($pro, $event) {
            $html = $this->actingAs($pro)->get(route('professional.bidding-board.my-bids'))
                ->assertOk()->getContent();

            return str_contains($html, e($event->title))
                ? 'the bid is listed'
                : 'THE BID IS NOT LISTED';
        });

        $this->step('21. Proposals & Bids opens', fn () => $this->actingAs($pro)
            ->get(route('professional.proposals.index'))->assertOk() && '');

        $this->step('22. Export carries the filter', function () use ($pro) {
            $all = $this->actingAs($pro)->get(route('professional.proposals.export'));
            $one = $this->actingAs($pro)->get(route('professional.proposals.export', ['tab' => 'declined']));

            $count = fn ($r) => substr_count(trim($r->streamedContent()), "\n");

            return $count($one) <= $count($all)
                ? 'filtered export is not larger'
                : 'EXPORT IGNORES THE FILTER';
        });

        $this->step('23. The lead moved to Proposal sent', function () use ($pro, $event) {
            $lead = \App\Support\LeadPipeline::forProfessional($pro)->firstWhere('event_id', $event->id);

            return $lead ? "stage {$lead['stage']}" : 'LEAD DISAPPEARED';
        });

        /* ── The client takes it, and a booking exists ────────────── */
        $bid = Bid::where('supplier_id', $pro->id)->where('event_id', $event->id)->first();

        if ($bid) {
            $this->step('24. Client compares the proposals', fn () => $this->actingAs($client)
                ->get(route('client.proposals.compare', $event))->assertOk() && '');

            $this->step('25. Client starts the agreement', function () use ($client, $bid) {
                $this->actingAs($client)->post(route('client.finalize.start', $bid));

                return Finalization::first() ? 'finalization #' . Finalization::first()->id : 'NOT STARTED';
            });

            $fin = Finalization::first();

            if ($fin) {
                $fsave = function (string $step, array $data) use ($client, $fin) {
                    $this->actingAs($client)->post(route('client.finalize.save', [$fin, $step]), $data);
                    $errs = session('errors');
                    if ($errs && $errs->getBag('default')->any()) {
                        throw new \RuntimeException(implode(' | ', $errs->getBag('default')->all()));
                    }
                    $fin->refresh();

                    return $fin->completed($step) ? 'agreed' : 'saved';
                };

                $this->step('26. Agreement: the bid', fn () => $fsave('bid', []));
                $this->step('27. Agreement: scope', fn () => $fsave('scope', [
                    'scope' => 'Buffet dinner for 140, two chefs and four servers, cleared by midnight.',
                ]));
                $this->step('28. Agreement: price', fn () => $fsave('price', ['agreed_price' => 5200]));
                $this->step('29. Agreement: schedule', fn () => $fsave('schedule', [
                    'service_start' => now()->addMonths(3)->format('Y-m-d\TH:i'),
                    'service_end'   => now()->addMonths(3)->addHours(7)->format('Y-m-d\TH:i'),
                ]));
                $this->step('30. Agreement: deposit', fn () => $fsave('terms', ['deposit_percent' => 25]));

                $this->step('31. Professional accepts the terms', function () use ($pro, $fin) {
                    \App\Domain\Agreements\Workspace::approve($fin->fresh(), $pro);

                    return \App\Domain\Agreements\Workspace::supplierApproved($fin->fresh()) ? 'accepted' : 'DID NOT';
                });
                $this->step('32. Client accepts the terms', function () use ($client, $fin) {
                    $this->actingAs($client)->post(route('client.finalize.approve', $fin));

                    return \App\Domain\Agreements\Workspace::clientApproved($fin->fresh()) ? 'accepted' : 'DID NOT';
                });

                $this->step('33. Agreement: contract', fn () => $fsave('contract', [
                    'client_signature' => $client->name, 'agree' => 1,
                ]));
                $this->step('34. Professional signs', function () use ($pro, $fin) {
                    $this->actingAs($pro)->post(route('professional.agreements.sign', $fin), [
                        'supplier_signature' => $pro->name, 'agree' => '1',
                    ]);

                    return $fin->fresh()->supplier_signed_at ? 'signed' : 'DID NOT SIGN';
                });
                $this->step('35. Agreement: payment', fn () => $fsave('payment', ['confirm_payment' => 1]));

                $this->step('36. A booking exists', function () use ($pro) {
                    $b = Booking::where('supplier_id', $pro->id)->latest('id')->first();

                    return $b ? "booking #{$b->id}, {$b->status}, \${$b->price}" : 'NO BOOKING';
                });
            }
        }

        /* ── Every professional screen that should now know ───────── */
        $booking = Booking::where('supplier_id', $pro->id)->latest('id')->first();

        foreach ([
            '37. Dashboard'            => 'professional.dashboard',
            '38. Gig Operations Hub'   => 'professional.gig-hub.index',
            '39. Calendar'             => 'professional.calendar.index',
            '40. Agreements'           => 'professional.agreements.index',
            '41. My Clients'           => 'professional.clients.index',
            '42. Transactions'         => 'professional.transactions.index',
            '43. Payments & Payouts'   => 'professional.earnings.index',
            '44. Messages'             => 'professional.chat.index',
            '45. Priority Actions'     => 'professional.priority.index',
            '46. Reports'              => 'professional.reports.index',
        ] as $name => $route) {
            $this->step($name, fn () => $this->actingAs($pro)->get(route($route))->assertOk() && '');
        }

        if ($booking) {
            $this->step('47. The gig page opens', fn () => $this->actingAs($pro)
                ->get(route('professional.gigs.show', $booking->event_id))->assertOk() && '');

            $this->step('48. Professional marks it delivered', function () use ($pro, $booking) {
                $this->actingAs($pro)->patch(route('professional.proposals.update-status', $booking),
                    ['status' => 'completed']);

                return $booking->fresh()->status === 'completed' ? 'completed' : 'STILL ' . $booking->fresh()->status;
            });

            $this->step('49. The money is on the ledger', function () use ($booking) {
                $total = \App\Domain\Money\Ledger::bookingTotal($booking->fresh());

                return $total > 0 ? '$' . number_format($total, 2) : 'LEDGER SAYS NOTHING';
            });
        }

        fwrite(STDERR, "\n\nA PROFESSIONAL'S WHOLE JOB\n" . implode("\n", $this->log) . "\n\n");

        $this->assertSame(0, $this->failures,
            "Steps that failed:\n" . implode("\n", array_filter($this->log, fn ($l) => str_contains($l, 'FAIL'))));
    }

    private function account(string $role): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => 'supported']);

        return $user->fresh();
    }
}
