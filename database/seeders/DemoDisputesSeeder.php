<?php

namespace Database\Seeders;

use App\Domain\Disputes\DisputeClassification;
use App\Domain\Disputes\DisputeStates;
use App\Models\Booking;
use App\Models\DisputeCase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Database\Seeders\Concerns\OnlyOutsideProduction;

/**
 * A few cases on one demo professional's shelf.
 *
 * Disputes & Resolution sorts a party's cases into open / waiting on you /
 * under review / resolved, and with an empty shelf four of those tiles read
 * zero and the screen cannot be seen working. These are the states, one each.
 *
 * Deliberately no decisions attached: a decision is a finding about a real
 * professional, and seeding one would put "client prevails" against a demo
 * account's name on a page that reads as a record.
 */
class DemoDisputesSeeder extends Seeder
{
    use OnlyOutsideProduction;

    public function run(): void
    {
        if ($this->refusedOnProduction()) {
            return;
        }

        $pro = User::where('email', 'elena.demo@example.test')->first();

        if (! $pro) {
            $this->command?->warn('DemoDisputesSeeder: elena.demo@example.test not found — skipped.');

            return;
        }

        $bookings = Booking::where('supplier_id', $pro->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->with('event')
            ->latest('id')
            ->take(4)
            ->get();

        if ($bookings->isEmpty()) {
            $this->command?->warn('DemoDisputesSeeder: no bookings for the demo professional — skipped.');

            return;
        }

        // One per state, so every tile and every tab has something behind it.
        $cases = [
            [DisputeStates::AWAITING_RESPONSE, 'scope_disagreement', DisputeClassification::SEVERITY_QUALITY,
                'The client says two of the agreed coverage hours were not delivered; the booking notes say the ceremony over-ran.'],
            [DisputeStates::FORMAL_INVESTIGATION, 'incomplete_service', DisputeClassification::SEVERITY_QUALITY,
                'Part of the agreed shot list was not delivered and both sides have submitted their own account of the day.'],
            [DisputeStates::DECIDED, 'payment_dispute', DisputeClassification::SEVERITY_PAYMENT,
                'A late-booking surcharge was applied that the client says was never agreed in writing.'],
            [DisputeStates::CLOSED, 'communication_issue', DisputeClassification::SEVERITY_COMMUNICATION,
                'Messages went unanswered for several days in the week before the event; both sides have since settled it.'],
        ];

        $made = 0;

        foreach ($cases as $i => [$state, $taxonomy, $severity, $summary]) {
            $booking = $bookings[$i] ?? $bookings->first();

            // Keyed on the booking and what it is about, so re-running the
            // seeder does not stack four more cases onto the same shelf.
            $case = DisputeCase::firstOrNew([
                'booking_id' => $booking->id,
                'taxonomy'   => $taxonomy,
            ]);

            if ($case->exists) {
                continue;
            }

            $case->fill([
                // Filed by the client on three of them and by the professional
                // on one, so "Waiting on You" can be seen doing its job from
                // both sides rather than always pointing the same way.
                'filed_by'        => $i === 3 ? $pro->id : $booking->client_id,
                'client_id'       => $booking->client_id,
                'professional_id' => $pro->id,
                'severity'        => $severity,
                'state'           => $state,
                'summary'         => $summary,
            ])->save();

            $made++;
        }

        $this->command?->info("Seeded {$made} demo dispute case(s).");
    }
}
