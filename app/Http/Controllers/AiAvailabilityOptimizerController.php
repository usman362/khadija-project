<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * AI Availability Optimizer (professional). Reads the pro's calendar and
 * suggests how to fill gaps, tighten turnarounds and lift revenue — with an
 * availability score, hours saved, revenue and smart booking suggestions.
 *
 * Representative data pending the live calendar/booking pipeline.
 */
class AiAvailabilityOptimizerController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client';

        $level = \App\Domain\AiFeatures\AiAccess::level($request->user(), 'availability-optimizer');
        if ($request->user()?->isAdmin() && in_array($request->query('preview'), ['manual', 'semi', 'maximum'], true)) {
            $level = $request->query('preview');
        }

        return view('ai-tools.availability-optimizer', [
            'aiLayout' => $aiLayout,
            'level'    => $level,
            'stats' => [
                ['Availability Score', '96%', 'Excellent', 'good'],
                ['Hours Saved', '18', 'This month', ''],
                ['Revenue', '$18,400', 'This month', ''],
                ['Trend Efficiency', '92%', 'Excellent', 'good'],
                ['Open Opportunities', '7', 'Act now', 'warn'],
            ],
            'days' => ['Mon 18', 'Tue 19', 'Wed 20', 'Thu 21', 'Fri 22', 'Sat 23', 'Sun 24'],
            // [day index, type, time, title]
            'events' => [
                [0, 'confirmed', '9:00 AM', 'Corporate AV Setup'],
                [0, 'open', '2:00 PM', 'Open — 2 slots'],
                [1, 'tight', '11:00 AM', 'Tight turnaround'],
                [2, 'confirmed', '5:00 PM', 'Wedding — Chicago'],
                [3, 'open', '10:00 AM', 'Open opportunity'],
                [4, 'confirmed', '6:00 PM', 'Birthday DJ set'],
                [5, 'confirmed', '4:00 PM', 'Wedding reception'],
                [5, 'personal', '12:00 PM', 'Personal time'],
                [6, 'open', '1:00 PM', 'Open — high demand'],
            ],
            'legend' => [
                ['confirmed', 'Confirmed Booking'], ['tight', 'Tight Turnaround'],
                ['open', 'Open Opportunity'], ['personal', 'Personal Time'],
            ],
            'opportunities' => [
                ['Wedding Reception DJ', 'Sat, May 23 · 6 PM', '$1,800', 96],
                ['Corporate Gala AV', 'Thu, May 21 · 10 AM', '$2,400', 91],
                ['Birthday Party', 'Sun, May 24 · 1 PM', '$650', 84],
            ],
            'forecast' => ['total' => '$69,800', 'bars' => [40, 62, 55, 78, 70, 92, 85]],
            'suggestions' => [
                'Fill your Thu 10 AM gap — 3 matching gigs nearby (+$2,400 potential).',
                'Your Sat is back-to-back — add 30 min buffer to avoid a tight turnaround.',
                'Sunday afternoon is high-demand — open it up for +$650 average.',
                'Raise weekend rates 8% — demand is above your booked capacity.',
            ],
        ]);
    }

    /**
     * Compute a deterministic availability/utilization estimate from the
     * professional's working pattern. Pure PHP math — no external services.
     */
    public function compute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'working_days'              => ['required', 'integer', 'min:1', 'max:7'],
            'hours_per_day'             => ['required', 'numeric', 'min:1', 'max:16'],
            'avg_gig_hours'            => ['required', 'numeric', 'min:0.5', 'max:24'],
            'current_bookings_per_week' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        try {
            $workingDays = (int) $validated['working_days'];
            $hoursPerDay = (float) $validated['hours_per_day'];
            $avgGigHours = (float) $validated['avg_gig_hours'];
            $bookings    = (int) $validated['current_bookings_per_week'];

            $capacity   = round($workingDays * $hoursPerDay, 1);
            $bookedHours = round($bookings * $avgGigHours, 1);

            $utilization = $capacity > 0
                ? round(min(100, max(0, ($bookedHours / $capacity) * 100)), 1)
                : 0.0;

            $remaining = max(0, $capacity - $bookedHours);
            $openSlots = $avgGigHours > 0 ? (int) floor($remaining / $avgGigHours) : 0;

            if ($utilization < 60) {
                $status = 'Under-booked — room to grow';
            } elseif ($utilization < 85) {
                $status = 'Healthy';
            } else {
                $status = 'Near capacity';
            }

            $suggestions = [];

            if ($openSlots > 0) {
                $suggestions[] = "You have room for about {$openSlots} more gig(s) per week ({$remaining}h open) — consider promoting these open slots.";
            }

            if ($utilization < 60) {
                $suggestions[] = 'Utilization is under 60% — batch similar gigs on your peak days to reduce travel/setup time and free up marketing capacity.';
                $suggestions[] = 'Offer a limited-time package or off-peak rate to fill the open ' . $remaining . ' hours this week.';
            } elseif ($utilization < 85) {
                $suggestions[] = 'Your schedule is healthy — protect a small buffer between gigs to avoid tight turnarounds as you fill the remaining ' . $remaining . ' hours.';
                $suggestions[] = 'Group bookings on fewer days where possible so you can keep whole days open for larger, higher-value events.';
            } else {
                $suggestions[] = 'You are near capacity — this is a strong signal to review and raise your rates, since demand is meeting your available ' . $capacity . ' hours.';
                $suggestions[] = 'Add turnaround buffers between back-to-back gigs to keep quality consistent when you are this booked.';
            }

            $suggestions[] = "At {$avgGigHours}h per gig across {$workingDays} working day(s), each extra booking uses about " . round(($avgGigHours / max($capacity, 0.1)) * 100, 1) . '% of your weekly capacity — price accordingly.';

            $suggestions = array_slice($suggestions, 0, 4);

            $summary = "With {$workingDays} working day(s) at {$hoursPerDay}h each, your estimated weekly capacity is {$capacity}h. "
                . "You are currently using about {$bookedHours}h ({$utilization}% utilization) across {$bookings} booking(s), leaving roughly {$openSlots} open slot(s). "
                . 'These are planning estimates based on the figures you entered.';

            $result = [
                'weekly_capacity_hours' => $capacity,
                'booked_hours'          => $bookedHours,
                'utilization_pct'       => $utilization,
                'open_slots'            => $openSlots,
                'status'                => $status,
                'suggestions'           => array_values($suggestions),
                'summary'               => $summary,
            ];
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'result'  => $result,
        ]);
    }
}
