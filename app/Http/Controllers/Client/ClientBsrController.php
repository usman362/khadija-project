<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

/**
 * Client — create a BSR (Bidding Service Request).
 *
 * A BSR is the broadcast bidding route: the client posts, every eligible
 * professional is notified, and they bid. Scope is SSR (one service) or MSR
 * (several) — the same request type either way, which is why this is one wizard
 * with a scope choice rather than two separate forms.
 *
 * State lives in the session between steps, so an abandoned wizard leaves no
 * half-built Event rows behind. "Save draft" is the moment a real row appears:
 * an unpublished Event, which is what a draft already means everywhere else in
 * the app. Publishing is the last step and is what opens it to bidding.
 */
class ClientBsrController extends Controller
{
    /** The wizard, in order. Keys are used in URLs and in the session. */
    public const STEPS = [
        'service'      => 'Service',
        'event'        => 'Event Details',
        'requirements' => 'Requirements',
        'budget'       => 'Budget & Request',
        'proposals'    => 'Proposal Settings',
        'files'        => 'Files',
        'review'       => 'Review & Submit',
    ];

    /** Request characteristics. "Emergency" is absent on purpose — that is the
     *  ESR request type, not a characteristic of a BSR. */
    public const CHARACTERISTICS = [
        'standard'   => ['Standard', 'Typical timeline and scope.'],
        'urgent'     => ['Urgent', 'Shorter timeline than standard.'],
        'recurring'  => ['Recurring', 'Occurs on a regular schedule.'],
        'high_value' => ['High-Value', 'Large budget or complex request.'],
    ];

    public const ORG_TYPES = ['individual' => 'Individual', 'business' => 'Business',
                              'government' => 'Government', 'nonprofit' => 'Nonprofit'];

    private const KEY = 'bsr_wizard';

    public function show(Request $request, string $step = 'service'): View|RedirectResponse
    {
        if (! array_key_exists($step, self::STEPS)) {
            return redirect()->route('client.bsr.step', 'service');
        }

        $data  = $this->state($request);
        $index = array_search($step, array_keys(self::STEPS), true);

        // Can't jump ahead of what's been filled in — the review step in
        // particular would otherwise render a mostly empty summary.
        $furthest = $this->furthestAllowed($data);
        if ($index > $furthest) {
            return redirect()->route('client.bsr.step', array_keys(self::STEPS)[$furthest])
                ->withErrors(['step' => 'Finish this step first.']);
        }

        return view('client.bsr.wizard', [
            'step'      => $step,
            'stepIndex' => $index,
            'steps'     => self::STEPS,
            'data'      => $data,
            'scope'     => ($data['scope'] ?? 'single'),
            // Row 91 — one shared definition of a bookable service, so this
            // form's catalogue cannot drift from the emergency and direct
            // offer forms the way it had.
            'categories' => Category::active()->bookableServices()
                ->orderBy('name')->get(['id', 'name'])->unique('name')->values(),
            'eventTypes' => Category::active()->eventTypes()
                ->orderBy('name')->get(['id', 'name']),
            'characteristics' => self::CHARACTERISTICS,
            'orgTypes'        => self::ORG_TYPES,
            'draftId'         => $data['draft_id'] ?? null,
            'defaultWindowDays' => config('bsr.default_proposal_window_days'),
        ]);
    }

    /** Save one step and move on (or back, or straight to a draft). */
    public function save(Request $request, string $step): RedirectResponse
    {
        abort_unless(array_key_exists($step, self::STEPS), 404);

        $data = $this->state($request);
        $rules = $this->rulesFor($step, $request);

        $validated = $request->validate($rules, $this->messagesFor($step));

        // Scope is derived from how many services were picked — that is literally
        // what single vs multi service means, so it isn't a separate question.
        if ($step === 'service') {
            $validated['scope'] = count($validated['services'] ?? []) >= 2 ? 'multi' : 'single';
        }

        $data = array_merge($data, $validated);
        Session::put(self::KEY, $data);

        if ($request->input('action') === 'draft') {
            $event = $this->persist($request, $data, publish: false);
            $data['draft_id'] = $event->id;
            Session::put(self::KEY, $data);

            return redirect()->route('client.bsr.step', $step)
                ->with('status', 'Draft saved. It is in My Events until you publish it.');
        }

        if ($step === 'review') {
            $event = $this->persist($request, $data, publish: true);
            Session::forget(self::KEY);

            return redirect()->route('client.events.show', $event)->with('status',
                'Your request is live — free to post. Professionals are being notified now, and proposals will appear under Proposals as they arrive.');
        }

        $keys = array_keys(self::STEPS);
        $next = $keys[min(array_search($step, $keys, true) + 1, count($keys) - 1)];

        return redirect()->route('client.bsr.step', $next);
    }

    /** Resume an existing unpublished request in the wizard. */
    public function resume(Request $request, Event $event): RedirectResponse
    {
        abort_unless((int) $event->client_id === (int) $request->user()->id, 403);
        abort_if($event->is_published, 400, 'That request is already published.');

        Session::put(self::KEY, [
            'draft_id'          => $event->id,
            'services'          => $event->categories->pluck('id')->all(),
            'scope'             => $event->categories->count() >= 2 ? 'multi' : 'single',
            'event_type'        => $event->event_type,
            'organization_type' => $event->organization_type,
            'characteristic'    => $event->characteristic,
            'title'             => $event->title,
            'starts_at'         => $event->starts_at?->format('Y-m-d\TH:i'),
            'location'          => $event->location,
            'venue'             => $event->venue,
            'guest_count'       => $event->guest_count,
            'description'       => $event->description,
            'budget_min'        => $event->budget_min,
            'budget_max'        => $event->budget_max,
            'proposal_deadline' => $event->proposal_deadline?->format('Y-m-d\TH:i'),
            'sealed_proposals'  => (bool) $event->sealed_proposals,
            'questions_enabled' => (bool) $event->questions_enabled,
        ]);

        return redirect()->route('client.bsr.step', 'review');
    }

    /**
     * Checklist row 226, Phase 1 — start a bidding request from a tool result.
     *
     * The approved scope is this ONE leg from three tools (Budget Planner,
     * Guided Event Planner, Timeline Builder), to prove the handoff before
     * committing to five outcomes across twelve tools. The other four legs of
     * R40's vision are deliberately absent, and the clickable prototype of the
     * full five stays where it is for Sir Peter's review.
     *
     * What carries across is what the client TYPED — the event type, date,
     * guest count, budget and location they gave the tool. Not the tool's
     * output: a suggested timeline is a suggestion, and a request that
     * silently asks professionals to bid against a machine's guess is not what
     * the client wrote. Nothing is published; this seeds the wizard and stops.
     */
    public const FROM_TOOL = ['budget-allocator', 'event-planner', 'timeline-builder'];

    public function fromTool(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tool_key'    => ['required', 'string', 'in:' . implode(',', self::FROM_TOOL)],
            'tool_name'   => ['required', 'string', 'max:80'],
            'event_type'  => ['nullable', 'string', 'max:120'],
            'event_date'  => ['nullable', 'date'],
            'guest_count' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'budget'      => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'location'    => ['nullable', 'string', 'max:200'],
        ]);

        // An event type the client typed into a tool is free text; it only
        // becomes the request's event type if it names one we actually have.
        $eventType = null;
        if (filled($data['event_type'] ?? null)) {
            $eventType = Category::active()->eventTypes()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['event_type']))])
                ->value('name');
        }

        $budget = isset($data['budget']) ? (float) $data['budget'] : null;

        Session::put(self::KEY, array_filter([
            'from_tool'      => $data['tool_key'],
            'from_tool_name' => $data['tool_name'],
            'event_type'     => $eventType,
            // $eventType, not the raw text: a working title is only built
            // from an event type we recognise. "Sarah and Alex's big day"
            // typed into a budget calculator is a private note, and promoting
            // it to the title professionals see on the board is a step the
            // client never took. Left blank, the wizard asks for one.
            'title'          => $this->titleFrom($eventType, $data['event_date'] ?? null),
            'starts_at'      => isset($data['event_date'])
                ? \Illuminate\Support\Carbon::parse($data['event_date'])->format('Y-m-d\TH:i')
                : null,
            'guest_count'    => $data['guest_count'] ?? null,
            'location'       => $data['location'] ?? null,
            // One figure, carried as the range's own middle rather than split
            // into a band nobody stated. The client can widen it in the wizard.
            'budget_min'     => $budget,
            'budget_max'     => $budget,
        ], fn ($v) => $v !== null && $v !== ''));

        // Straight to the first step regardless: services, organisation type
        // and characteristic are things no tool asked for, and they are what
        // the wizard needs before anything else.
        return redirect()
            ->route('client.bsr.step', 'service')
            ->with('status', $data['tool_name'] . ' details carried over. Choose the services you need — you can change everything else as you go.');
    }

    /** A working title, so the client edits one rather than writes one. */
    private function titleFrom(?string $eventType, ?string $date): ?string
    {
        if (! filled($eventType)) {
            return null;
        }

        $when = $date ? \Illuminate\Support\Carbon::parse($date)->format('F Y') : null;

        return $when ? "{$eventType} — {$when}" : $eventType;
    }

    public function discard(Request $request): RedirectResponse
    {
        Session::forget(self::KEY);

        return redirect()->route('client.events.index')->with('status', 'Draft discarded.');
    }

    // ── internals ────────────────────────────────────────────────────

    private function state(Request $request): array
    {
        return (array) Session::get(self::KEY, []);
    }

    /** Per-step rules. Only the step being submitted is validated. */
    private function rulesFor(string $step, Request $request): array
    {
        return match ($step) {
            'service' => [
                'services'          => ['required', 'array', 'min:1', 'max:12'],
                'services.*'        => ['integer', 'exists:categories,id', new \App\Rules\BookableService],
                'event_type'        => ['nullable', 'string', 'max:80'],
                'organization_type' => ['required', 'in:' . implode(',', array_keys(self::ORG_TYPES))],
                'characteristic'    => ['required', 'in:' . implode(',', array_keys(self::CHARACTERISTICS))],
            ],
            'event' => [
                'title'       => ['required', 'string', 'max:200'],
                'starts_at'   => ['nullable', 'date'],
                'location'    => ['nullable', 'string', 'max:200'],
                'venue'       => ['nullable', 'string', 'max:200'],
                'guest_count' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            ],
            'requirements' => [
                'description' => ['required', 'string', 'min:20', 'max:4000'],
            ],
            'budget' => [
                'budget_min' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
                'budget_max' => ['nullable', 'numeric', 'min:0', 'max:9999999', 'gte:budget_min'],
            ],
            'proposals' => [
                // R37 forbids inventing a window. Until GigResource approves a
                // default, the client has to choose the deadline themselves —
                // the platform must not quietly pick one for them.
                // The hard ceiling also applies: a deadline can never fall
                // after the event itself. Enforced here and in persist().
                'proposal_deadline' => [
                    config('bsr.default_proposal_window_days') ? 'nullable' : 'required',
                    'date', 'after:now',
                ],
                'sealed_proposals'  => ['nullable', 'boolean'],
                'questions_enabled' => ['nullable', 'boolean'],
            ],
            'files'  => [],
            'review' => ['confirm' => ['accepted']],
            default  => [],
        };
    }

    private function messagesFor(string $step): array
    {
        return [
            'services.required'          => 'Pick at least one service you need.',
            'characteristic.required'    => 'Choose how urgent or complex this request is.',
            'organization_type.required' => 'Tell us who the request is for.',
            'title.required'             => 'Give your request a name.',
            'description.required'       => 'Describe what you need — professionals bid on this.',
            'description.min'            => 'A little more detail helps professionals bid accurately.',
            'budget_max.gte'             => 'The top of the range must be at least the bottom.',
            'proposal_deadline.after'    => 'The proposal deadline has to be in the future.',
            'proposal_deadline.required' => 'Choose when proposals close. No standard window has been approved yet, so this can’t be set for you.',
            'confirm.accepted'           => 'Confirm the details before publishing.',
        ];
    }

    /**
     * How far into the wizard this state is allowed to reach. A step opens once
     * every step before it has what it needs.
     */
    private function furthestAllowed(array $d): int
    {
        $ok = [
            ! empty($d['services']) && ! empty($d['organization_type']) && ! empty($d['characteristic']),
            ! empty($d['title']),
            ! empty($d['description']),
            true,   // budget is optional
            true,   // proposal settings all have defaults
            true,   // files are optional
        ];

        $furthest = 0;
        foreach ($ok as $i => $passed) {
            if (! $passed) {
                return $i;
            }
            $furthest = $i + 1;
        }

        return $furthest;
    }

    /** Create or update the Event behind this wizard state. */
    private function persist(Request $request, array $d, bool $publish): Event
    {
        $user = $request->user();

        $deadline = ! empty($d['proposal_deadline'])
            ? \Illuminate\Support\Carbon::parse($d['proposal_deadline'])
            : null;
        $startsAt = ! empty($d['starts_at'])
            ? \Illuminate\Support\Carbon::parse($d['starts_at'])
            : null;

        // R37: no invented fallbacks. A window is used only if one has actually
        // been approved; otherwise the deadline came from the client, and if
        // neither exists the request cannot be published safely.
        $approvedWindow = config('bsr.default_proposal_window_days');
        if (! $deadline && $approvedWindow) {
            $deadline = now()->addDays((int) $approvedWindow);
        }

        if (! $deadline && $publish) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'proposal_deadline' => 'This request has no proposal deadline and no approved default window, so it can’t be published yet.',
            ]);
        }

        // R37's hard ceiling: proposals must close before the event happens.
        if ($deadline && $startsAt && $deadline->gt($startsAt)) {
            $deadline = $startsAt->copy()->subHour();
        }

        $attrs = [
            'title'             => $d['title'] ?? 'Untitled request',
            'description'       => $d['description'] ?? null,
            'event_type'        => $d['event_type'] ?? null,
            'organization_type' => $d['organization_type'] ?? null,
            'characteristic'    => $d['characteristic'] ?? 'standard',
            'starts_at'         => $startsAt,
            'location'          => $d['location'] ?? null,
            'venue'             => $d['venue'] ?? null,
            'guest_count'       => $d['guest_count'] ?? null,
            'budget_min'        => $d['budget_min'] ?? null,
            'budget_max'        => $d['budget_max'] ?? null,
            // `budget` is what the rest of the app reads; keep it as the floor of
            // the range so nothing downstream has to know about the new columns.
            'budget'            => $d['budget_min'] ?? null,
            'proposal_deadline' => $deadline,
            'sealed_proposals'  => (bool) ($d['sealed_proposals'] ?? true),
            'questions_enabled' => (bool) ($d['questions_enabled'] ?? true),
            'client_id'         => $user->id,
            'created_by'        => $user->id,
            'source'            => 'user',      // BSR — broadcast, no supplier_id
        ];

        if ($publish) {
            $attrs += ['status' => 'published', 'is_published' => true, 'published_at' => now()];
        } else {
            $attrs += ['status' => 'pending', 'is_published' => false];
        }

        $event = ! empty($d['draft_id'])
            ? tap(Event::where('client_id', $user->id)->findOrFail($d['draft_id']))->update($attrs)
            : Event::create($attrs);

        $event->categories()->sync($d['services'] ?? []);

        return $event;
    }
}
