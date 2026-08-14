<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Emergency Request (ER) — a standalone "Post a Rush Request" flow
 * for time-sensitive needs within 72 hours (Peter's request-types spec: ER
 * is its OWN flow, not an MSR link). It reuses the whole downstream engine:
 * an ER publishes a real Event (source 'esr') that surfaces on the pro
 * Bidding Board with priority, then flows through bids → proposals → award →
 * booking → review exactly like SSR/MSR.
 *
 * Fees: success-only. $0 to post; the client pays a single $2.99 when an
 * agreement finalizes, and nothing at all if the request goes unfilled. (This
 * used to claim "$2.99 at post + $8.99 ER service fee" — an earlier model.
 * $8.99 appears in no current workflow doc, and the Integration Diagnosis is
 * explicit: "a single $2.99 only when something finalizes.")
 */
class ClientEsrController extends Controller
{
    /** Urgency reasons an ER can cite. */
    public const REASONS = [
        'professional_cancelled' => 'A professional cancelled or is unavailable',
        'no_show'                => 'A professional did not arrive as scheduled',
        'last_minute'            => 'A last-minute service became necessary',
        'equipment_failure'      => 'Equipment or service failure / breakdown',
        'other'                  => 'Other urgent circumstance',
    ];

    public function create(Request $request): View
    {
        // Row 91 — this list had no filter at all, so Baby Shower and
        // Birthday Party sat among the services a client could request.
        $categories = Category::active()->bookableServices()
            ->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        return view('client.esr.create', [
            'categories' => $categories,
            'reasons'    => self::REASONS,
            'scope'      => $this->scopeOf($request->query('scope')),
        ]);
    }

    /** Normalise the single/multi choice; single is the default — a rush
     *  request is usually one urgent gap to fill. */
    private function scopeOf(?string $raw): string
    {
        return $raw === 'multi' ? 'multi' : 'single';
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_name'   => ['required', 'string', 'max:200'],
            'reason'       => ['required', 'in:' . implode(',', array_keys(self::REASONS))],
            'needed_by'    => ['required', 'date'],
            'location'     => ['nullable', 'string', 'max:200'],
            // R38 / R71 — the state the work happens in. See
            // StateMatching::requestState for why this is asked, not assumed.
            'event_state'  => ['nullable', 'string', 'in:' . implode(',', array_keys(config('geo.allowed_states', [])))],
            'guest_count'  => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'budget_min'   => ['nullable', 'integer', 'min:0'],
            'scope'        => ['nullable', 'in:single,multi'],
            'services'     => ['required', 'array', 'min:1'],
            'services.*'   => ['integer', 'exists:categories,id', new \App\Rules\BookableService],
        ], [
            'services.required' => 'Select at least one service you need.',
            'reason.required'   => 'Tell us why this is urgent.',
            'needed_by.required' => 'When do you need this by?',
        ]);

        $user     = $request->user();
        $scope    = $this->scopeOf($data['scope'] ?? null);
        $services = collect($data['services'])->unique()->values();

        // A single-service rush request means exactly that — one service. The
        // picker enforces it client-side; this is the server-side guard.
        if ($scope === 'single' && $services->count() > 1) {
            return back()->withInput()->withErrors([
                'services' => 'A single-service rush request takes one service. Pick just one, or switch to a multi-service request.',
            ]);
        }

        // A rush request had no closing time at all, so it stayed open forever
        // — the one request type where that is least defensible. The approved
        // window is 24 hours (Khadijah, approved 2026-07-31), but it can never
        // outlast the event itself: a request needed in six hours closes in
        // six, not tomorrow. The client can still accept a bid at any point.
        /*
         * Checklist row 108 (R7) — bidding closes at least five hours before
         * the event starts.
         *
         * The cap used to be the event itself, so bidding could stay open
         * until the moment the professional was supposed to be on site. Win a
         * rush job at 6:58 for a 7:00 start and nobody gets there — and the
         * client, who raised an EMERGENCY request, has no one to fall back
         * on.
         */
        $neededBy = \Illuminate\Support\Carbon::parse($data['needed_by']);
        $buffer   = (int) config('bsr.esr.closes_hours_before_start');
        $latest   = $neededBy->copy()->subHours($buffer);

        $deadline = now()->addHours((int) config('bsr.esr.default_window_hours'));

        if ($deadline->gt($latest)) {
            $deadline = $latest;
        }

        // Nothing left to bid in. Refused rather than published with a
        // deadline already behind it, which would be a request nobody could
        // answer dressed up as an open one.
        if ($deadline->isPast()) {
            return back()->withInput()->withErrors([
                'needed_by' => "An emergency request needs at least {$buffer} hours before it starts, so professionals have time to reach you. For anything sooner, message a professional directly.",
            ]);
        }

        $event = Event::create([
            'title'        => $data['event_name'],
            'description'  => $data['description'] ?? null,
            'status'       => 'published',
            'is_published' => true,
            'starts_at'    => $data['needed_by'],
            'budget'       => $data['budget_min'] ?? null,
            'location'     => $data['location'] ?? null,
            'state'        => \App\Support\StateMatching::requestState($user, $data['event_state'] ?? null),
            'guest_count'  => $data['guest_count'] ?? null,
            'created_by'   => $user->id,
            'client_id'    => $user->id,
            'source'       => 'esr',   // marks it urgent on the Bidding Board
            'proposal_deadline' => $deadline,
        ]);

        $event->categories()->sync($services->all());

        // Land on the request itself; responses show up under Proposals.
        return redirect()
            ->route('client.events.show', $event)
            ->with('status', $scope === 'single'
                ? 'Rush request published. Verified professionals for that service are being notified now — responses will appear under Proposals.'
                : 'Rush request published. Verified professionals are being notified now — each service is bid on separately, and responses appear under Proposals.');
    }
}
