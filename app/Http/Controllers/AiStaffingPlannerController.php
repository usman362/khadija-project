<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Staffing Planner — a client-portal AI Toolkit tool that builds a
 * recommended staff schedule (roles, head-counts and a timed shift for each)
 * for an event, and visualises it as a timeline, with live coverage stats.
 *
 * Like the other deterministic AI tools (Pricing Assistant / Proposal
 * Writer) this needs no LLM and no quota: a transparent planner derives the
 * roster from the event type + guest count + start time (head-counts scale
 * with guests; shift windows come from per-event-type templates). Coverage
 * stats are computed from the generated shifts. Not plan-gated.
 *
 * The event guest-count is not a DB column, so the on-screen event is a
 * sensible default; the PLAN itself is fully computed and regenerates live.
 *
 * Routes: GET  /ai-tools/staffing-planner           (show)
 *         POST /ai-tools/staffing-planner/generate  (regenerate → JSON)
 */
class AiStaffingPlannerController extends Controller
{
    public const EVENT_TYPES = [
        'wedding'   => 'Wedding Reception',
        'corporate' => 'Corporate Event',
        'birthday'  => 'Birthday Party',
        'gala'      => 'Gala Dinner',
    ];

    /**
     * Role templates per event type. Each role:
     *   [name, count(int|'server'|'crew'), startHour, endHour, color]
     * Hours are 24h with past-midnight expressed as +24 (e.g. 1 AM = 25).
     * baseStart = the template's natural start hour (used to shift on input).
     */
    private const TEMPLATES = [
        'wedding' => ['baseStart' => 10, 'roles' => [
            ['Event Manager', 1,        10, 25,   '#2563eb', true],
            ['DJ',            1,        18, 24.5, '#8b5cf6', false],
            ['Assistant',     1,        14, 24,   '#10b981', false],
            ['Setup Crew',    'crew',   8,  14,   '#f59e0b', false],
            ['Server Team',   'server', 16, 25,   '#ec4899', false],
            ['Cleanup Crew',  'crew',   24, 28,   '#14b8a6', false],
        ]],
        'corporate' => ['baseStart' => 8, 'roles' => [
            ['Event Manager', 1,        8,  18, '#2563eb', true],
            ['AV Technician', 1,        9,  17, '#8b5cf6', false],
            ['Assistant',     1,        8,  18, '#10b981', false],
            ['Setup Crew',    'crew',   6,  9,  '#f59e0b', false],
            ['Catering Team', 'server', 11, 16, '#ec4899', false],
            ['Cleanup Crew',  'crew',   17, 20, '#14b8a6', false],
        ]],
        'birthday' => ['baseStart' => 14, 'roles' => [
            ['Event Manager', 1,        14, 23, '#2563eb', true],
            ['DJ',            1,        16, 23, '#8b5cf6', false],
            ['Assistant',     1,        13, 22, '#10b981', false],
            ['Setup Crew',    'crew',   12, 15, '#f59e0b', false],
            ['Server Team',   'server', 15, 23, '#ec4899', false],
            ['Cleanup Crew',  'crew',   22, 25, '#14b8a6', false],
        ]],
        'gala' => ['baseStart' => 17, 'roles' => [
            ['Event Manager', 1,        17, 27,   '#2563eb', true],
            ['DJ / Band',     1,        19, 26,   '#8b5cf6', false],
            ['Assistant',     1,        16, 25,   '#10b981', false],
            ['Setup Crew',    'crew',   14, 18,   '#f59e0b', false],
            ['Server Team',   'server', 18, 26.5, '#ec4899', false],
            ['Cleanup Crew',  'crew',   26, 30,   '#14b8a6', false],
        ]],
    ];

    public function show(Request $request): View
    {
        $event = [
            'type'     => 'wedding',
            'name'     => self::EVENT_TYPES['wedding'],
            'date'     => 'May 24, 2025',
            'guests'   => 150,
            'location' => 'Los Angeles, CA',
        ];

        $plan = $this->plan('wedding', 150, 10);

        return view('client.ai-tools.staffing-planner', [
            'eventTypes' => self::EVENT_TYPES,
            'event'      => $event,
            'roles'      => $plan['roles'],
            'axis'       => $plan['axis'],
            'stats'      => $plan['stats'],
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_type' => ['nullable', 'string'],
            'guests'     => ['nullable', 'integer', 'min:10', 'max:2000'],
            'start_hour' => ['nullable', 'integer', 'min:6', 'max:20'],
            'event_name' => ['nullable', 'string', 'max:120'],
            'date'       => ['nullable', 'string', 'max:60'],
            'location'   => ['nullable', 'string', 'max:120'],
        ]);

        $type   = array_key_exists($data['event_type'] ?? '', self::TEMPLATES) ? $data['event_type'] : 'wedding';
        $guests = (int) ($data['guests'] ?? 150);
        $tpl    = self::TEMPLATES[$type];
        $start  = (int) ($data['start_hour'] ?? $tpl['baseStart']);

        $plan = $this->plan($type, $guests, $start);

        return response()->json([
            'success' => true,
            'event'   => [
                'type'     => $type,
                'name'     => $data['event_name'] ?: self::EVENT_TYPES[$type],
                'date'     => $data['date'] ?: 'May 24, 2025',
                'guests'   => $guests,
                'location' => $data['location'] ?: 'Los Angeles, CA',
            ],
            'roles' => $plan['roles'],
            'axis'  => $plan['axis'],
            'stats' => $plan['stats'],
        ]);
    }

    /**
     * Build the staffing plan: positioned role bars + axis + coverage stats.
     */
    private function plan(string $type, int $guests, int $startHour): array
    {
        $tpl   = self::TEMPLATES[$type] ?? self::TEMPLATES['wedding'];
        $shift = $startHour - $tpl['baseStart'];

        $server = max(2, min(12, (int) ceil($guests / 40)));
        $crew   = max(1, min(6, (int) ceil($guests / 80)));

        $roles = [];
        foreach ($tpl['roles'] as [$name, $count, $s, $e, $color, $isYou]) {
            $count = match ($count) {
                'server' => $server,
                'crew'   => $crew,
                default  => (int) $count,
            };
            $start = $s + $shift;
            $end   = $e + $shift;
            $roles[] = [
                'name'       => $name,
                'count'      => $count,
                'is_you'     => $isYou,
                'start'      => $start,
                'end'        => $end,
                'start_label' => $this->fmt($start),
                'end_label'  => $this->fmt($end),
                'color'      => $color,
            ];
        }

        // Timeline window + 6 evenly-spaced axis labels.
        $winStart = min(array_column($roles, 'start'));
        $winEnd   = max(array_column($roles, 'end'));
        $span     = max(1, $winEnd - $winStart);

        foreach ($roles as &$r) {
            $r['left']  = round(($r['start'] - $winStart) / $span * 100, 2);
            $r['width'] = round(($r['end'] - $r['start']) / $span * 100, 2);
        }
        unset($r);

        $axis = [];
        for ($i = 0; $i < 6; $i++) {
            $h = $winStart + $span * $i / 5;
            $axis[] = ['label' => $this->fmt($h), 'left' => round($i / 5 * 100, 2)];
        }

        return [
            'roles' => $roles,
            'axis'  => $axis,
            'stats' => $this->stats($roles, $winStart, $winEnd),
        ];
    }

    /**
     * Coverage stats from the generated shifts.
     */
    private function stats(array $roles, float $winStart, float $winEnd): array
    {
        $totalStaff = array_sum(array_column($roles, 'count'));
        $span       = max(1, $winEnd - $winStart);

        // Merge intervals to measure covered time (gap detection).
        $intervals = array_map(fn ($r) => [$r['start'], $r['end']], $roles);
        usort($intervals, fn ($a, $b) => $a[0] <=> $b[0]);
        $covered = 0.0;
        $curS = $curE = null;
        foreach ($intervals as [$s, $e]) {
            if ($curS === null) {
                $curS = $s; $curE = $e;
            } elseif ($s <= $curE) {
                $curE = max($curE, $e);
            } else {
                $covered += $curE - $curS;
                $curS = $s; $curE = $e;
            }
        }
        if ($curS !== null) {
            $covered += $curE - $curS;
        }
        $coveragePct = (int) round(min(100, $covered / $span * 100));
        $roleCount   = count($roles);

        return [
            'total_staff'  => $totalStaff,
            'coverage_hrs' => (int) round($span),
            'coverage_pct' => $coveragePct,
            'gaps'         => $coveragePct >= 100 ? 'No Gaps Detected' : 'Minor gaps to review',
            'on_time'      => "{$roleCount}/{$roleCount}",
            'efficiency'   => $coveragePct >= 95 ? 'High' : ($coveragePct >= 80 ? 'Good' : 'Fair'),
        ];
    }

    /** Format an hour value (past-midnight = +24) as e.g. "12:30 AM". */
    private function fmt(float $h): string
    {
        $h   = fmod($h, 24);
        $hh  = (int) floor($h);
        $mm  = (int) round(($h - $hh) * 60);
        if ($mm === 60) {
            $hh++; $mm = 0;
        }
        $period  = $hh < 12 ? 'AM' : 'PM';
        $display = $hh % 12 === 0 ? 12 : $hh % 12;

        return $display . ($mm ? ':' . str_pad((string) $mm, 2, '0', STR_PAD_LEFT) : '') . ' ' . $period;
    }
}
