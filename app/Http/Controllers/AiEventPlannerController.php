<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * AI Event Planner (client). Organises an event end-to-end — a smart checklist
 * by milestone, progress, AI recommendations, marketplace vendor suggestions
 * and upcoming deadlines. The show() page renders representative sample data;
 * compute() runs a real, deterministic planning engine on the user's inputs.
 */
class AiEventPlannerController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->activeRole() === 'professional' ? 'layouts.professional' : 'layouts.client';

        $level = \App\Domain\AiFeatures\AiAccess::level($request->user(), 'event-planner');
        if ($request->user()?->isAdmin() && in_array($request->query('preview'), ['manual', 'semi', 'maximum'], true)) {
            $level = $request->query('preview');
        }

        $snap = \App\Support\ClientPlanningSnapshot::for($request->user());

        // Real professionals the client could actually reach — same state only,
        // per the State Boundary Rule. Never an invented vendor list.
        $suggested = collect();
        if ($snap->hasEvent()) {
            $suggested = \App\Support\StateMatching::scopeUsersForViewer(
                \App\Models\User::query()->whereHas('roles', fn ($q) => $q->where('name', 'professional')),
                $request->user()
            )->with('serviceCategories:id,name')->limit(3)->get();
        }

        return view('ai-tools.event-planner', [
            'aiLayout'  => $aiLayout,
            'level'     => $level,
            'snap'      => $snap,
            'suggested' => $suggested,
            // Generic planning guidance — advice, not a claim about this client.
            'tips' => [
                'Aim to spend around half the budget on venue and catering.',
                'Lock vendors in priority order: venue → photography → catering.',
                'Keep about 10% back for last-minute changes.',
            ],
        ]);
    }

    /**
     * Build a real, deterministic event plan from the user's inputs — milestone
     * checklist with due dates counted back from the event date, vendor
     * categories tailored to the event type, a budget split that sums exactly to
     * the total, and tips built from the real numbers. No external calls.
     */
    public function compute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_type'  => ['required', 'string', 'max:120'],
            'event_date'  => ['required', 'date'],
            'guest_count' => ['required', 'integer', 'min:1', 'max:100000'],
            'budget'      => ['required', 'numeric', 'min:1', 'max:99999999'],
            'location'    => ['nullable', 'string', 'max:200'],
        ]);

        try {
            $type    = strtolower(trim($validated['event_type']));
            $guests  = (int) $validated['guest_count'];
            $budget  = (float) $validated['budget'];
            $date    = new \DateTimeImmutable($validated['event_date']);
            $today   = new \DateTimeImmutable('today');
            $daysOut = (int) $today->diff($date)->format('%r%a');

            // --- Milestones: each is (label, weeks-before-event). We keep the
            // ones that still fall on/after today; anything whose date has
            // already passed is marked done so the plan stays realistic. ---
            $milestoneDefs = [
                ['Set budget & guest list', 20],
                ['Book venue', 12],
                ['Book key vendors (catering, photo)', 10],
                ['Send save-the-dates / invites', 6],
                ['Confirm menu & rentals', 4],
                ['Finalize headcount', 2],
                ['Confirm vendors & timeline', 1],
                ['Event day', 0],
            ];

            $milestones = [];
            foreach ($milestoneDefs as [$label, $weeksBefore]) {
                $due = $date->modify('-' . ($weeksBefore * 7) . ' days');
                if ($weeksBefore === 0) {
                    $status = $daysOut < 0 ? 'done' : 'upcoming';
                } elseif ($due < $today) {
                    $status = 'done';
                } elseif ($due <= $today->modify('+21 days')) {
                    $status = 'due-soon';
                } else {
                    $status = 'upcoming';
                }
                $milestones[] = [
                    'label'    => $label,
                    'due_date' => $due->format('M j, Y'),
                    'status'   => $status,
                ];
            }

            // --- Vendor categories + budget weights per event type. ---
            $profiles = [
                'wedding'   => ['Venue' => 24, 'Catering' => 22, 'Photography' => 13, 'Florals & Décor' => 12, 'Entertainment / DJ' => 8, 'Planner / Coordinator' => 6, 'Attire & Beauty' => 7, 'Stationery & Favors' => 4, 'Contingency' => 4],
                'corporate' => ['Venue' => 22, 'Catering' => 20, 'AV & Production' => 18, 'Speakers / Program' => 12, 'Branding & Print' => 8, 'Staffing' => 9, 'Transportation' => 4, 'Contingency' => 7],
                'conference'=> ['Venue' => 24, 'Catering' => 18, 'AV & Production' => 18, 'Speakers / Program' => 12, 'Marketing & Signage' => 9, 'Staffing' => 9, 'Transportation' => 4, 'Contingency' => 6],
                'birthday'  => ['Venue' => 20, 'Catering' => 24, 'Entertainment / DJ' => 16, 'Décor & Balloons' => 14, 'Cake & Desserts' => 9, 'Photography' => 8, 'Favors' => 4, 'Contingency' => 5],
                'gala'      => ['Venue' => 22, 'Catering' => 24, 'Entertainment / Program' => 14, 'Florals & Décor' => 14, 'AV & Lighting' => 10, 'Staffing' => 7, 'Photography' => 4, 'Contingency' => 5],
                'baby shower'=> ['Venue' => 18, 'Catering' => 26, 'Décor & Balloons' => 18, 'Cake & Desserts' => 12, 'Games & Activities' => 9, 'Favors' => 8, 'Photography' => 4, 'Contingency' => 5],
            ];

            $weights = null;
            foreach ($profiles as $key => $w) {
                if (str_contains($type, $key)) { $weights = $w; break; }
            }
            if ($weights === null) {
                $weights = ['Venue' => 25, 'Catering' => 25, 'Entertainment' => 12, 'Décor & Florals' => 12, 'Photography' => 10, 'Staffing' => 6, 'Transportation' => 5, 'Contingency' => 5];
            }
            // Big guest counts lean more on catering (per-head cost).
            if ($guests >= 200 && isset($weights['Catering'])) {
                $weights['Catering'] += 4;
            }

            $vendorCategories = array_values(array_filter(
                array_keys($weights),
                fn ($c) => $c !== 'Contingency'
            ));

            // --- Budget split that sums EXACTLY to the total. ---
            $sum        = array_sum($weights);
            $budgetSplit = [];
            $running    = 0.0;
            foreach ($weights as $cat => $w) {
                $amount = round($budget * $w / $sum);
                $running += $amount;
                $budgetSplit[] = ['category' => $cat, 'amount' => (float) $amount];
            }
            $diff = round($budget - $running, 2);
            if (abs($diff) >= 0.01) {
                // Absorb rounding remainder into Contingency, else the last row.
                $idx = count($budgetSplit) - 1;
                foreach ($budgetSplit as $i => $row) {
                    if ($row['category'] === 'Contingency') { $idx = $i; break; }
                }
                $budgetSplit[$idx]['amount'] += $diff;
            }

            $perGuest      = $guests > 0 ? $budget / $guests : 0;
            $cateringAmt   = 0;
            foreach ($budgetSplit as $row) {
                if ($row['category'] === 'Catering') { $cateringAmt = $row['amount']; break; }
            }

            $locationText = !empty($validated['location']) ? ' in ' . $validated['location'] : '';
            $summary = sprintf(
                'A suggested plan for your %s%s on %s — %s guests, %s total budget (about %s per guest). %s days out, with %d milestones mapped back from the event date.',
                $validated['event_type'],
                $locationText,
                $date->format('M j, Y'),
                number_format($guests),
                '$' . number_format($budget),
                '$' . number_format($perGuest),
                $daysOut >= 0 ? (string) $daysOut : 'Past',
                count($milestones)
            );

            $tips = [];
            $tips[] = 'Estimated per-guest spend is about $' . number_format($perGuest) . '. Confirm caterer and venue minimums early.';
            if ($cateringAmt > 0 && $guests > 0) {
                $tips[] = 'Catering is your largest food line at $' . number_format($cateringAmt) . ' (roughly $' . number_format($cateringAmt / $guests) . ' per guest) — request quotes before locking the venue.';
            }
            $tips[] = 'Book ' . ($vendorCategories[0] ?? 'your venue') . ' and ' . ($vendorCategories[1] ?? 'catering') . ' first; they drive availability and price.';
            if ($daysOut >= 0 && $daysOut < 60) {
                $tips[] = 'With ' . $daysOut . ' days left, treat every "due soon" milestone as this-week work and confirm headcount promptly.';
            } else {
                $tips[] = 'Keep your Contingency line untouched until the final weeks — it is your buffer for the surprises every event has.';
            }
            $tips[] = 'Aim to keep Venue + Catering near half of the total budget; the current split lands them at about '
                . round((($this->catAmount($budgetSplit, 'Venue') + $cateringAmt) / max(1, $budget)) * 100)
                . '% combined.';

            return response()->json([
                'success' => true,
                'result'  => [
                    'summary'           => $summary,
                    'milestones'        => $milestones,
                    'vendor_categories' => $vendorCategories,
                    'budget_split'      => $budgetSplit,
                    'tips'              => $tips,
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function catAmount(array $split, string $category): float
    {
        foreach ($split as $row) {
            if ($row['category'] === $category) {
                return (float) $row['amount'];
            }
        }
        return 0.0;
    }
}
