<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * AI Timeline Builder (client). Builds an event-day run-of-show across vendor
 * tracks with buffers and conflict detection. The show() page renders
 * representative data; compute() builds a real schedule from user inputs with
 * clock times computed by adding cumulative minutes to the start time.
 */
class AiTimelineBuilderController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client';

        $level = \App\Domain\AiFeatures\AiAccess::level($request->user(), 'timeline-builder');
        if ($request->user()?->isAdmin() && in_array($request->query('preview'), ['manual', 'semi', 'maximum'], true)) {
            $level = $request->query('preview');
        }

        return view('ai-tools.timeline-builder', [
            'aiLayout' => $aiLayout,
            'level'    => $level,
            'stats' => [
                ['Timeline Health', '96%', 'Excellent', 'good'],
                ['Event Duration', '8h 00m', '5 PM – 1 AM', ''],
                ['Vendors Scheduled', '12', 'All confirmed', ''],
                ['Buffer Time Added', '1h 45m', 'of slack', 'good'],
                ['Conflicts Detected', '2', 'Review needed', 'warn'],
            ],
            'hours' => ['5 PM', '6 PM', '7 PM', '8 PM', '9 PM', '10 PM', '11 PM', '12 AM', '1 AM'],
            'tracks' => [
                ['Setup', '#64748b', [['Venue Access', 0, 12], ['Vendor Load-in', 9, 16]]],
                ['Ceremony', '#7c3aed', [['Guest Arrival', 18, 11], ['Ceremony', 28, 16]]],
                ['Reception', '#f97316', [['Cocktail Hour', 44, 12], ['Dinner Service', 55, 17], ['Dancing', 71, 25]]],
                ['Vendors', '#16a34a', [['Photographer', 14, 80], ['Catering Crew', 40, 38]]],
                ['Music / DJ', '#2563eb', [['Sound Check', 38, 7], ['Live Set', 45, 51]]],
            ],
            'conflicts' => [
                'Photographer overlaps DJ sound check at 8:00 PM — stagger by 15 min.',
                'Catering breakdown runs into dancing — add a 20 min buffer.',
            ],
        ]);
    }

    /**
     * Build a real run-of-show from user inputs. Segment durations are scaled so
     * the whole schedule fits the requested duration, and each segment's clock
     * time is computed by adding cumulative minutes to the start time.
     */
    public function compute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_date'     => ['required', 'date'],
            'start_time'     => ['required', 'string', 'regex:/^([01]?\d|2[0-3]):[0-5]\d$/'],
            'event_type'     => ['required', 'string', 'max:120'],
            'duration_hours' => ['required', 'numeric', 'min:2', 'max:12'],
        ]);

        try {
            $type      = strtolower(trim($validated['event_type']));
            $totalMin  = (int) round(((float) $validated['duration_hours']) * 60);

            // Base run-of-show templates: [segment, base minutes]. One entry may
            // be marked "flex" to absorb whatever time remains after scaling.
            $templates = [
                'wedding' => [
                    ['Guest Arrival', 30],
                    ['Ceremony', 30],
                    ['Cocktail Hour', 60],
                    ['Reception Entrance', 15],
                    ['Dinner', 60],
                    ['Toasts', 20],
                    ['First Dance', 10],
                    ['Open Dancing', 0, 'flex'],
                    ['Send-off', 15],
                ],
                'corporate' => [
                    ['Registration', 30],
                    ['Welcome', 15],
                    ['Keynote', 45],
                    ['Break', 15],
                    ['Breakout Sessions', 0, 'flex'],
                    ['Lunch', 60],
                    ['Networking', 45],
                    ['Closing', 15],
                ],
                'conference' => [
                    ['Registration', 30],
                    ['Opening Remarks', 15],
                    ['Keynote', 45],
                    ['Break', 15],
                    ['Sessions', 0, 'flex'],
                    ['Lunch', 60],
                    ['Networking', 45],
                    ['Closing', 15],
                ],
                'birthday' => [
                    ['Guest Arrival', 30],
                    ['Welcome & Mingling', 30],
                    ['Activities / Games', 0, 'flex'],
                    ['Meal', 45],
                    ['Cake & Toast', 20],
                    ['Dancing / Music', 60],
                    ['Wind-down', 15],
                ],
            ];

            $template = null;
            foreach ($templates as $key => $t) {
                if (str_contains($type, $key)) { $template = $t; break; }
            }
            if ($template === null) {
                $template = [
                    ['Guest Arrival', 30],
                    ['Opening', 20],
                    ['Main Program', 0, 'flex'],
                    ['Meal / Break', 45],
                    ['Entertainment', 45],
                    ['Closing', 15],
                ];
            }

            // Fixed total (non-flex) and how many flex segments.
            $fixedTotal = 0;
            $flexCount  = 0;
            foreach ($template as $seg) {
                if (($seg[2] ?? null) === 'flex') { $flexCount++; }
                else { $fixedTotal += $seg[1]; }
            }

            // Scale fixed segments so they never exceed the total, leaving room
            // for the flex segment(s). If there's no flex segment, scale all.
            $schedule = [];
            $start    = new \DateTimeImmutable($validated['event_date'] . ' ' . $validated['start_time']);
            $cursor   = $start;

            if ($flexCount > 0) {
                // Reserve at least 15 min per flex segment; scale fixed to fit.
                $reserved = 15 * $flexCount;
                $available = max($totalMin - $reserved, 1);
                $scale = $fixedTotal > 0 ? min(1.0, $available / $fixedTotal) : 1.0;

                // First pass: scaled fixed durations.
                $scaledFixedTotal = 0;
                $tmp = [];
                foreach ($template as $seg) {
                    if (($seg[2] ?? null) === 'flex') {
                        $tmp[] = [$seg[0], null];
                    } else {
                        $d = max(5, (int) round($seg[1] * $scale));
                        $scaledFixedTotal += $d;
                        $tmp[] = [$seg[0], $d];
                    }
                }
                $flexPool  = max($reserved, $totalMin - $scaledFixedTotal);
                $flexEach  = intdiv($flexPool, $flexCount);
                $flexFirst = $flexPool - ($flexEach * ($flexCount - 1));
                $flexSeen  = 0;
                foreach ($tmp as &$row) {
                    if ($row[1] === null) {
                        $flexSeen++;
                        $row[1] = $flexSeen === 1 ? $flexFirst : $flexEach;
                    }
                }
                unset($row);
                $template = $tmp;
            } else {
                $scale = $fixedTotal > 0 ? $totalMin / $fixedTotal : 1.0;
                $tmp = [];
                foreach ($template as $seg) {
                    $tmp[] = [$seg[0], max(5, (int) round($seg[1] * $scale))];
                }
                $template = $tmp;
            }

            // Correct any rounding drift so the schedule sums exactly to total.
            $built = array_sum(array_map(fn ($s) => $s[1], $template));
            $drift = $totalMin - $built;
            if ($drift !== 0 && count($template) > 0) {
                $lastIdx = count($template) - 1;
                $template[$lastIdx][1] = max(5, $template[$lastIdx][1] + $drift);
            }

            foreach ($template as [$segment, $mins]) {
                $schedule[] = [
                    'time'         => $cursor->format('g:i A'),
                    'segment'      => $segment,
                    'duration_min' => (int) $mins,
                ];
                $cursor = $cursor->modify('+' . (int) $mins . ' minutes');
            }

            $endTime = $cursor->format('g:i A');
            $summary = sprintf(
                'A suggested %s run-of-show for %s: %d segments starting at %s and wrapping around %s (about %s total).',
                $validated['event_type'],
                (new \DateTimeImmutable($validated['event_date']))->format('D, M j'),
                count($schedule),
                $start->format('g:i A'),
                $endTime,
                $this->humanDuration($totalMin)
            );

            return response()->json([
                'success' => true,
                'result'  => [
                    'summary'  => $summary,
                    'schedule' => $schedule,
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function humanDuration(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        if ($h && $m) return $h . 'h ' . $m . 'm';
        if ($h) return $h . 'h';
        return $m . 'm';
    }
}
