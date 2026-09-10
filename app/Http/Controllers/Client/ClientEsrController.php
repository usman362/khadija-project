<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Domain\Budget\ServiceBudgetWriter;

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
            ->orderBy('name')->get(['id', 'name']);

        return view('client.esr.create', [
            'orgTypes' => \App\Models\Event::ORGANIZATION_TYPES,
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

    /**
     * The request's title, from what they picked rather than what they typed.
     *
     * "Urgent: DJ, Live Bands & Musicians" says more to a professional
     * scanning the board than a sentence would, and it cannot be left blank or
     * filled with something that contradicts the services attached to it.
     */
    private function titleFrom(array $serviceIds, string $reason): string
    {
        $names = \App\Models\Category::whereIn('id', $serviceIds)
            ->orderBy('name')->pluck('name');

        $what = match (true) {
            $names->isEmpty() => 'Urgent request',
            $names->count() === 1 => $names->first(),
            $names->count() === 2 => $names->implode(' and '),
            default => $names->take(2)->implode(', ') . ' +' . ($names->count() - 2),
        };

        return \Illuminate\Support\Str::limit('Urgent: ' . $what, 200, '');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            /*
             * No free-text "What do you need?" any more.
             *
             * It sat above the service picker and asked the same thing in
             * different words, so the form asked twice — the Owner's point on
             * 2026-08-20, with BR held up as the one that "is clean with only
             * asking once". The title is built from the service they pick.
             */
            'reason'       => ['required', 'in:' . implode(',', array_keys(self::REASONS))],
            // Asked on every request form now (Peter, 2026-08-20).
            'organization_type' => ['required', 'in:' . implode(',', array_keys(\App\Models\Event::ORGANIZATION_TYPES))],
            'location'     => ['nullable', 'string', 'max:200'],
            // R38 / R71 — the state the work happens in. See
            // StateMatching::requestState for why this is asked, not assumed.
            // Removed from the form on 2026-08-25 — see StateMatching::requestState().
            'guest_count'  => ['nullable', 'integer', 'min:1', 'max:1000000'],
            /*
             * Name, description and date are the three facts every request
             * carries into its agreement, so all three flows ask for them in
             * the same words. This one used to invent its own title from the
             * services picked and take no description at all.
             */
            'budget_min'   => ['nullable', 'integer', 'min:0'],
            'scope'        => ['nullable', 'in:single,multi'],

            /*
             * Sir Peter, 2026-09-08: the client must actively agree to the
             * $2.99 finalisation fee before the request is published. Checked
             * here and not only in the browser — a form that is required only
             * in the markup can be posted around.
             */
            'fee_agreed'   => ['accepted'],
            'services'     => ['required', 'array', 'min:1'],
            'services.*'   => ['integer', 'exists:categories,id', new \App\Rules\BookableService],
        ] + \App\Domain\Requests\CoreFacts::rules('event_name', 'description', 'needed_by') + [
            // Catering only — the rule is required or nullable depending on
            // what was ticked, which is why it is built rather than written.
        ] + \App\Domain\Requests\FoodDelivery::rules(
            array_map('intval', (array) $request->input('services', []))
        ), [
            // A rush request asks when it is NEEDED BY rather than when it
            // runs. Same fact, and this page's wording wins over the shared
            // one because "+" keeps the left-hand keys.
            'needed_by.required' => 'When do you need this by?',
            'services.required' => 'Select at least one service you need.',
            'fee_agreed.accepted' => 'Please confirm you understand the $2.99 fee applies when you finalize with a professional.',
            'reason.required'   => 'Tell us why this is urgent.',
        ] + \App\Domain\Requests\CoreFacts::messages('event_name', 'description', 'needed_by') + [
            'delivery_mode.required' => 'Say how the food should get there.',
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

        // Ten posted requests a day — Khadijah's sheet, 29 Aug. An emergency
        // is still a posting; the cap is high enough that a real emergency is
        // never the eleventh.
        \App\Support\UserLimit::hit('client-postings', $user, null, 'services');

        $event = Event::create([
            'title'        => $data['event_name'],
            'description'  => $data['description'] ?? null,
            'status'       => 'published',
            'is_published' => true,
            'starts_at'    => $data['needed_by'],
            'budget'       => $data['budget_min'] ?? null,
            'location'     => $data['location'] ?? null,
            'state'        => \App\Support\StateMatching::requestState($user),
            'guest_count'  => $data['guest_count'] ?? null,
            'delivery_mode' => \App\Domain\Requests\FoodDelivery::answerFor(
                $services->map(fn ($id) => (int) $id)->all(),
                $data['delivery_mode'] ?? null,
            ),
            'organization_type' => $data['organization_type'],
            'created_by'   => $user->id,
            'client_id'    => $user->id,
            'source'       => 'esr',   // marks it urgent on the Bidding Board
            'proposal_deadline' => $deadline,
            // Owner 2026-08-22: emergency bids show in real time as they land,
            // never held to the close. Sealed bids are a standard-request
            // feature; an emergency cannot afford to wait for the window.
            'sealed_proposals'  => false,
        ]);

        $event->categories()->sync($services->all());

        // Sir Peter, 2026-09-02: an ER naming several services says what each
        // one is worth, the same as a BR. Responders answer on one service, so
        // without this each of them prices against the whole request's total.
        ServiceBudgetWriter::save(
            $event,
            (array) $request->input('service_budgets', []),
            $services->all(),
        );

        // Land on the request itself; responses show up under Proposals.
        return redirect()
            ->route('client.events.show', $event)
            ->with('status', $scope === 'single'
                ? 'Rush request published. Verified professionals for that service are being notified now — responses will appear under Proposals.'
                : 'Rush request published. Verified professionals are being notified now — each service is bid on separately, and responses appear under Proposals.');
    }
}
