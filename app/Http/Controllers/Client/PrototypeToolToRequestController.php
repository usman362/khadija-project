<?php

namespace App\Http\Controllers\Client;

use App\Domain\AiFeatures\AiToolCatalog;
use App\Http\Controllers\Client\ClientBsrController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PROTOTYPE — Tool → Request handoff.
 *
 * Built for Monday's design review (Khadijah, 2026-07-30): show Peter the whole
 * idea before we commit a week to building it, when only the "Post as BSR" leg
 * is planned for the first pass.
 *
 * Nothing here writes anything. Every screen is clearly marked as a prototype,
 * and the outcome cards say plainly which of the five already exist and which
 * are proposals — "Attach to Existing Event" is real today (nine tools carry an
 * "Add to my event" button), the rest are not.
 *
 * Tool names, routes and the BSR step list are read from the real catalog and
 * the real wizard, so the prototype cannot drift from the product while the
 * conversation is still going.
 */
class PrototypeToolToRequestController extends Controller
{
    /** The three tools Khadijah named for the first pass. */
    private const TOOLS = ['budget-allocator', 'event-planner', 'timeline-builder'];

    /**
     * The five outcomes, with an honest status against today's build.
     *   built    — exists now
     *   first    — the one leg planned for the first pass
     *   proposed — design only
     */
    private const OUTCOMES = [
        'bsr' => [
            'label'  => 'Post as BSR',
            'blurb'  => 'Opens the bidding request with everything the tool worked out already filled in. Professionals bid.',
            'status' => 'first',
            'icon'   => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        ],
        'esr' => [
            'label'  => 'Post as ESR',
            'blurb'  => 'Same, but flagged as a rush — within 72 hours, professionals notified with priority.',
            'status' => 'proposed',
            'icon'   => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        ],
        'dsr' => [
            'label'  => 'Send Direct Offer',
            'blurb'  => 'Skip the board — take the plan straight to a professional you already want.',
            'status' => 'proposed',
            'icon'   => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
        ],
        'draft' => [
            'label'  => 'Save as Draft',
            'blurb'  => "Keep the numbers without committing to anything. Peter's \"mess around with it first\" case.",
            'status' => 'proposed',
            'icon'   => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/>',
        ],
        'attach' => [
            'label'  => 'Attach to Existing Event',
            'blurb'  => 'Add the result to an event already being planned. This one already works — nine tools have it today.',
            'status' => 'built',
            'icon'   => '<path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>',
        ],
    ];

    public function show(Request $request): View
    {
        $toolKey = (string) $request->query('tool', '');
        $outcome = (string) $request->query('outcome', '');

        $catalog = collect(AiToolCatalog::forAudience('client'))->keyBy('key');
        $tools   = collect(self::TOOLS)
            ->map(fn ($k) => $catalog->get($k))
            ->filter()
            ->values();

        $tool = $toolKey !== '' ? $catalog->get($toolKey) : null;
        if ($tool && ! in_array($toolKey, self::TOOLS, true)) {
            $tool = null;
        }

        // Which of the three screens: pick a tool → see its result → see where
        // it can go → see what that would open.
        $step = match (true) {
            $tool === null                => 'pick',
            $outcome === ''               => 'result',
            isset(self::OUTCOMES[$outcome]) => 'outcome',
            default                       => 'result',
        };

        return view('client.prototype.tool-to-request', [
            'step'      => $step,
            'tools'     => $tools,
            'tool'      => $tool,
            'outcomes'  => self::OUTCOMES,
            'outcome'   => $outcome !== '' ? (self::OUTCOMES[$outcome] ?? null) : null,
            'outcomeKey' => $outcome,
            'sample'    => $tool ? $this->sampleFor($tool['key']) : [],
            'bsrSteps'  => ClientBsrController::STEPS,
        ]);
    }

    /**
     * Representative output for each tool — clearly labelled as such on screen.
     * The point of the prototype is the handoff, not the arithmetic.
     */
    private function sampleFor(string $key): array
    {
        return match ($key) {
            'budget-allocator' => [
                'headline' => 'Budget split for a 150-guest wedding',
                'rows' => [
                    ['Catering & bar', '$6,000', '40%'],
                    ['Photography & video', '$3,000', '20%'],
                    ['Floral & décor', '$2,250', '15%'],
                    ['Music & entertainment', '$1,875', '12.5%'],
                    ['Lighting & tech', '$1,875', '12.5%'],
                ],
                'carries' => ['Total budget $15,000', '5 services', 'Guest count 150'],
            ],
            'event-planner' => [
                'headline' => 'Plan for a corporate conference, 300 attendees',
                'rows' => [
                    ['Venue & layout', 'Ballroom + 2 breakout rooms', 'Required'],
                    ['Catering', 'Plated lunch, 2 coffee breaks', 'Required'],
                    ['AV & staging', 'Stage, screens, mics', 'Required'],
                    ['Photography', 'Half-day coverage', 'Optional'],
                ],
                'carries' => ['Event type Conference', '4 services', 'Guest count 300'],
            ],
            'timeline-builder' => [
                'headline' => 'Run-of-show — Saturday, 8 hours',
                'rows' => [
                    ['14:00', 'Vendor load-in & setup', '2 hrs'],
                    ['16:00', 'Guest arrival & cocktails', '1 hr'],
                    ['17:00', 'Ceremony', '45 min'],
                    ['18:00', 'Dinner service', '1.5 hrs'],
                    ['20:00', 'Music & dancing', '2 hrs'],
                ],
                'carries' => ['Event date Sat 15 Nov', 'Coverage 14:00–22:00', '3 services'],
            ],
            default => [],
        };
    }
}
