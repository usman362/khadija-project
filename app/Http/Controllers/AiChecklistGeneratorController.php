<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * AI Checklist Generator (client). A generated planning command-center —
 * prioritised tasks, budget summary, vendor status and AI recommendations.
 * Representative data.
 */
class AiChecklistGeneratorController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.checklist-generator', [
            'aiLayout' => $aiLayout,
            'stats' => [
                ['Event Health', '93%', 'good'], ['Days to Event', '184', ''],
                ['Budget Remaining', '$12,450', 'good'], ['Pros Booked', '6', ''],
                ['Tasks', '82', ''],
            ],
            'priorities' => [
                ['Book wedding photographer', 'High', 'May 20', 'todo'],
                ['Finalize catering menu', 'High', 'May 28', 'todo'],
                ['Confirm final guest count', 'Medium', 'Jun 5', 'progress'],
                ['Choose wedding favors', 'Low', 'Jun 20', 'todo'],
                ['Send invitations', 'Medium', 'Jun 1', 'progress'],
            ],
            'budget' => [
                'total' => 25000, 'spent' => 12550,
                'lines' => [
                    ['Venue', 8000, '#7c3aed'], ['Catering', 6500, '#f97316'], ['Photography', 2500, '#16a34a'],
                    ['Floral & Décor', 3000, '#ec4899'], ['Music / DJ', 1800, '#2563eb'], ['Attire', 2200, '#0ea5e9'], ['Misc', 1000, '#64748b'],
                ],
            ],
            'vendors' => [
                ['The Garden Estate', 'Venue', 'Confirmed'], ['Gourmet Eats Co.', 'Catering', 'Confirmed'],
                ['Elite Events', 'Planning', 'Confirmed'], ['Blossom Floral', 'Floral', 'Pending'],
                ['DJ Soundwave', 'Music', 'Pending'], ['', 'Photography', 'Not booked'],
            ],
            'recommendations' => [
                ['Book photographer now', 'Top pros book out 6 months ahead — secure yours this week.', 'Find Pros'],
                ['Consider a weekday', 'Shift to a Friday and save ~$1,800 across vendors.', 'Explore'],
                ['Add live music', 'Couples who add a live set rate their reception 0.6★ higher.', 'Browse'],
            ],
        ]);
    }

    /**
     * Build a tailored, timeframe-based planning checklist from real input.
     * Pure deterministic logic — no external API.
     */
    public function compute(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'event_type'  => ['required', 'string', 'max:120'],
                'event_date'  => ['required', 'date'],
                'guest_count' => ['nullable', 'integer', 'min:1', 'max:100000'],
            ]);

            $eventDate = Carbon::parse($validated['event_date'])->startOfDay();
            $type      = strtolower(trim($validated['event_type']));
            $guests    = $validated['guest_count'] ?? null;

            // Map free-text/select type into a template bucket.
            $bucket = 'general';
            if (str_contains($type, 'wedding') || str_contains($type, 'anniversary')) {
                $bucket = 'wedding';
            } elseif (str_contains($type, 'corporate') || str_contains($type, 'conference')
                || str_contains($type, 'launch') || str_contains($type, 'meeting')) {
                $bucket = 'corporate';
            } elseif (str_contains($type, 'birthday') || str_contains($type, 'party')
                || str_contains($type, 'shower') || str_contains($type, 'graduation')) {
                $bucket = 'birthday';
            }

            // timeframe => weeks before event
            $timeframes = [
                '12+ weeks before' => 12,
                '8 weeks before'   => 8,
                '4 weeks before'   => 4,
                '2 weeks before'   => 2,
                '1 week before'    => 1,
                'Day of'           => 0,
            ];

            $templates = $this->itemTemplates($bucket, $guests);

            $groups = [];
            $total  = 0;
            foreach ($timeframes as $label => $weeks) {
                $items = $templates[$label] ?? [];
                if (empty($items)) {
                    continue;
                }
                $dueDate = $eventDate->copy()->subWeeks($weeks);
                $groups[] = [
                    'timeframe' => $label,
                    'due_date'  => $dueDate->format('D, M j, Y'),
                    'items'     => array_values($items),
                ];
                $total += count($items);
            }

            $eventLabel = $validated['event_type'];
            $summary = $guests
                ? "Suggested plan for your {$eventLabel} on {$eventDate->format('M j, Y')} — {$total} tasks across "
                    . count($groups) . " milestones, sized for about {$guests} guests. Dates are estimates you can adjust."
                : "Suggested plan for your {$eventLabel} on {$eventDate->format('M j, Y')} — {$total} tasks across "
                    . count($groups) . " milestones. Dates are estimates you can adjust.";

            return response()->json([
                'success' => true,
                'result'  => [
                    'summary'     => $summary,
                    'groups'      => $groups,
                    'total_items' => $total,
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function itemTemplates(string $bucket, ?int $guests): array
    {
        $guestLine = $guests
            ? "Confirm final headcount (planning for ~{$guests} guests)"
            : 'Confirm your final guest headcount';

        $catering = $guests
            ? "Finalize catering order for ~{$guests} guests"
            : 'Finalize catering menu and quantities';

        $templates = [
            'wedding' => [
                '12+ weeks before' => [
                    'Set your overall budget and guest list draft',
                    'Book the ceremony and reception venue',
                    'Reserve a photographer and videographer',
                    'Book caterer and start menu tasting',
                ],
                '8 weeks before' => [
                    'Order invitations and save-the-dates',
                    'Book florist and finalize décor direction',
                    'Arrange officiant and ceremony details',
                    'Reserve music / DJ or live band',
                ],
                '4 weeks before' => [
                    'Send invitations and start tracking RSVPs',
                    'Schedule hair and makeup trials',
                    'Confirm transportation and accommodation blocks',
                    'Finalize seating chart draft',
                ],
                '2 weeks before' => [
                    $guestLine,
                    $catering,
                    'Confirm timeline with all vendors',
                    'Pick up attire and complete final fittings',
                ],
                '1 week before' => [
                    'Prepare vendor payments and gratuities',
                    'Assemble emergency kit and day-of essentials',
                    'Share final schedule with the wedding party',
                ],
                'Day of' => [
                    'Delegate setup and vendor check-in to a point person',
                    'Keep hydrated and stick to the timeline',
                    'Assign someone to collect gifts and cards',
                ],
            ],
            'corporate' => [
                '12+ weeks before' => [
                    'Define event goals, agenda and budget',
                    'Secure venue and confirm A/V capabilities',
                    'Book keynote speakers or presenters',
                    'Set up event registration page',
                ],
                '8 weeks before' => [
                    'Open registration and promote the event',
                    'Arrange catering and dietary options',
                    'Order signage, badges and printed materials',
                    'Coordinate sponsors and partners',
                ],
                '4 weeks before' => [
                    'Finalize agenda and speaker run-of-show',
                    'Confirm A/V, streaming and tech rehearsal',
                    'Brief staff and volunteers on roles',
                    'Send attendee logistics and reminders',
                ],
                '2 weeks before' => [
                    $guestLine,
                    $catering,
                    'Confirm vendor load-in and setup times',
                    'Prepare presentation decks and backups',
                ],
                '1 week before' => [
                    'Run a full tech and A/V rehearsal',
                    'Print name badges and delegate check-in',
                    'Send final know-before-you-go email',
                ],
                'Day of' => [
                    'Arrive early for setup and registration',
                    'Keep the run-of-show on schedule',
                    'Capture feedback for post-event follow-up',
                ],
            ],
            'birthday' => [
                '12+ weeks before' => [
                    'Set the date, budget and theme',
                    'Book the venue or reserve your space',
                    'Draft the guest list',
                ],
                '8 weeks before' => [
                    'Send invitations or e-vites',
                    'Book entertainment or activities',
                    'Order the cake and plan the menu',
                ],
                '4 weeks before' => [
                    'Order decorations and party supplies',
                    'Plan games, music and activities',
                    'Arrange party favors',
                ],
                '2 weeks before' => [
                    $guestLine,
                    $catering,
                    'Confirm cake and any rentals',
                ],
                '1 week before' => [
                    'Buy remaining supplies and groceries',
                    'Confirm timings with helpers',
                    'Prepare a playlist',
                ],
                'Day of' => [
                    'Set up décor and food stations',
                    'Chill drinks and prep serving areas',
                    'Assign someone to photos and clean-up',
                ],
            ],
        ];

        return $templates[$bucket] ?? $templates['birthday'];
    }
}
