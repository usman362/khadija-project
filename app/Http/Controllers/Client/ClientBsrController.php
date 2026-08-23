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
 * Client — create a BR (Bidding Request).
 *
 * A BR is the broadcast bidding route: the client posts, every eligible
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
     *  ER request type, not a characteristic of a BR. */
    public const CHARACTERISTICS = [
        'standard'   => ['Standard', 'Typical timeline and scope.'],
        'urgent'     => ['Urgent', 'Shorter timeline than standard.'],
        'recurring'  => ['Recurring', 'Occurs on a regular schedule.'],
        'high_value' => ['High-Value', 'Large budget or complex request.'],
    ];

    /** One definition, on the model that owns the column. */
    public const ORG_TYPES = \App\Models\Event::ORGANIZATION_TYPES;

    private const KEY = 'bsr_wizard';

    public function show(Request $request, string $step = 'service'): View|RedirectResponse
    {
        if (! array_key_exists($step, self::STEPS)) {
            return redirect()->route('client.bsr.step', 'service');
        }

        $data  = $this->state($request);

        /*
         * The client already answered on the event-type page, and step 1 asked
         * again — the Owner's words, "the marked in red selection was already
         * answered".
         *
         * It was not quite the same question. They chose a service CATEGORY
         * there ("Decor, Floral & Balloon Design"); the checkboxes here are the
         * individual services under it ("Balloon Arches & Columns"). Level 2
         * and level 3 of the same hierarchy.
         *
         * So it is not answered twice — it is narrowed. The page opens on the
         * services inside what they chose, says so, and offers the full list
         * for anyone who wants to add something else. What it must not do is
         * drop them into an alphabetical wall of 241 as if they had said
         * nothing, which is what it did.
         */

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
            // Ordered so the ones this event type actually needs come first.
            // The message on arrival says they will be — "the ones that matter
            // most for this kind of event are first" — and the page was
            // alphabetical, which made that line untrue.
            'categories'    => $this->serviceCatalogue($data, $request),
            'focusNames'    => $this->focusNames($data),
            'showingAll'    => $this->showingAll($data, $request),
            'eventTypes' => Category::active()->eventTypes()
                ->orderBy('name')->get(['id', 'name']),
            'characteristics' => self::CHARACTERISTICS,
            'otherEventType'  => self::OTHER_EVENT_TYPE,
            'orgTypes'        => self::ORG_TYPES,
            'draftId'         => $data['draft_id'] ?? null,
            'defaultWindowHours' => config('bsr.default_proposal_window_hours'),
        ]);
    }

    /**
     * The services offered at step 1.
     *
     * Narrowed to what sits under the areas the client picked on the event-type
     * page, unless they ask for everything. Row 91's shared definition still
     * decides what counts as a bookable service, so this form's catalogue
     * cannot drift from the emergency and direct-offer ones — this only decides
     * which of them to show.
     */
    private function serviceCatalogue(array $data, Request $request)
    {
        $all = Category::active()->bookableServices()
            ->orderBy('name')->get(['id', 'name', 'parent_id'])->unique('name')->values();

        if ($this->showingAll($data, $request)) {
            return $all;
        }

        $focus = array_map('intval', (array) ($data['focus_categories'] ?? []));

        if ($focus === []) {
            return $all;
        }

        $narrowed = $all->filter(fn ($c) => in_array((int) $c->parent_id, $focus, true)
            || in_array((int) $c->id, $focus, true))->values();

        // If nothing bookable sits under what they chose, showing them an empty
        // step is worse than showing them everything.
        return $narrowed->isEmpty() ? $all : $narrowed;
    }

    /** Are we showing the whole catalogue rather than the chosen areas? */
    private function showingAll(array $data, Request $request): bool
    {
        return $request->boolean('all') || empty($data['focus_categories']);
    }

    /** The areas the client picked, by name, so the step can say them back. */
    private function focusNames(array $data): array
    {
        $focus = array_map('intval', (array) ($data['focus_categories'] ?? []));

        return $focus === []
            ? []
            : Category::whereIn('id', $focus)->orderBy('name')->pluck('name')->all();
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

            /*
             * The client's own wording becomes the request's working title, so
             * they name their event once rather than twice — step 2 asks for a
             * title, and somebody who has just typed "Maryland's Horse Show
             * Event" should find it already there.
             *
             * Only seeded while step 2 has not been answered; editing the title
             * later must stick.
             */
            if (filled($validated['event_title'] ?? null) && empty($data['title'])) {
                $validated['title'] = trim($validated['event_title']);
            }

            // Picked something off the list after picking Other — the private
            // wording goes with it rather than lingering on a request it no
            // longer describes.
            if (($validated['event_type'] ?? null) !== self::OTHER_EVENT_TYPE) {
                $validated['event_title'] = null;
            }
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
            'event_state'       => $event->state,
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
    /**
     * Row 226, Phase 2 — which tools may hand off, and why these.
     *
     * Phase 1 proved the handoff on three. The test for the rest is not "is it
     * a client tool" but "do its INPUTS describe an event someone could be
     * asked to work at".
     *
     * The row's own spec names three exceptions and they are right:
     *
     *   vendor-matchmaking — already runs against a chosen event; its next
     *                        step is a direct offer to a professional it
     *                        named, which is a different leg
     *   review-writer      — a review is written after the job is done
     *   translator         — a phrase is not a planning artefact
     *
     * message-assistant is a fourth, and this is a DISAGREEMENT with the spec
     * worth flagging rather than burying. Its inputs are a recipient, a tone,
     * a purpose and some talking points; not one of them is a fact a request
     * needs. The control would render a panel promising "what you entered
     * above is carried across" over nothing at all — a promise the click
     * cannot keep. If the Owner wants it there anyway it needs its own copy,
     * because this copy would be false.
     *
     * contract-assistant IS included, on the spec's side rather than mine: a
     * client can draft terms before asking anyone, and it carries a date and
     * a price, which are two facts a request actually uses.
     */
    public const FROM_TOOL = [
        'budget-allocator',
        'event-planner',
        'timeline-builder',
        'checklist-generator',
        'theme-advisor',
        'venue-analyzer',
        'guest-capacity',
        'contract-assistant',
    ];

    /** The outcomes a tool result can become. */
    public const OUTCOMES = ['bidding', 'emergency', 'draft'];

    /**
     * The list row a client picks when their event is not on the list.
     *
     * It is a real event type in the taxonomy, not a magic string, so the
     * relevance matrix still has something to order services by. Their own
     * wording goes in `event_title` beside it — free text REPLACING the event
     * type would leave the matrix nothing to work with (Peter + Khadijah,
     * 2026-08-20: they may type their own, and our team reviews it).
     */
    public const OTHER_EVENT_TYPE = 'Other Event';

    public function fromTool(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tool_key'    => ['required', 'string', 'in:' . implode(',', self::FROM_TOOL)],
            'tool_name'   => ['required', 'string', 'max:80'],
            'outcome'     => ['required', 'string', 'in:' . implode(',', self::OUTCOMES)],
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

        $carried = array_filter([
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
        ], fn ($v) => $v !== null && $v !== '');

        return match ($data['outcome']) {
            'emergency' => $this->toEmergency($carried, $data['tool_name']),
            'draft'     => $this->toDraft($request, $carried, $data['tool_name']),
            default     => $this->toBidding($carried, $data['tool_name']),
        };
    }

    /**
     * Straight to the first step regardless: services, organisation type and
     * characteristic are things no tool asked for, and they are what the
     * wizard needs before anything else.
     */
    private function toBidding(array $carried, string $toolName): RedirectResponse
    {
        Session::put(self::KEY, $carried);

        return redirect()
            ->route('client.bsr.step', 'service')
            ->with('status', $toolName . ' details carried over. Choose the services you need — you can change everything else as you go.');
    }

    /**
     * An emergency request, prefilled.
     *
     * The ER form reads old(), so flashing the carried facts as input fills it
     * with no view change and no second copy of the mapping.
     *
     * Deliberately NOT gated on how far away the date is. The temptation was
     * to refuse anything months out as "not an emergency", but no rule sets a
     * maximum — R7 only sets the five-hour floor, which the ER form already
     * enforces — and inventing one here would reject requests the platform
     * allows. What the form does ask is why it is urgent, in the client's own
     * words. A gate made of their own answer is better than a number I chose.
     *
     * `reason` is not carried: no tool asks why something is urgent, and
     * picking one for them would put words in their mouth on a form they sign.
     */
    private function toEmergency(array $carried, string $toolName): RedirectResponse
    {
        return redirect()
            ->route('client.esr.create')
            ->withInput(array_filter([
                'event_name'  => $carried['title'] ?? null,
                'needed_by'   => isset($carried['starts_at'])
                    ? \Illuminate\Support\Carbon::parse($carried['starts_at'])->format('Y-m-d\TH:i')
                    : null,
                'location'    => $carried['location'] ?? null,
                'guest_count' => $carried['guest_count'] ?? null,
                'budget_min'  => $carried['budget_min'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''))
            ->with('status', $toolName . ' details carried over. Tell us why this is urgent and which services you need.');
    }

    /**
     * Saved, not sent. Nothing is published and no professional is notified —
     * the client came to a tool to think, and "I will come back to this" is a
     * real answer to what should happen next.
     */
    private function toDraft(Request $request, array $carried, string $toolName): RedirectResponse
    {
        $event = $this->persist($request, $carried, publish: false);

        Session::put(self::KEY, $carried + ['draft_id' => $event->id]);

        return redirect()
            ->route('client.bsr.resume', $event)
            ->with('status', 'Saved as a draft from ' . $toolName . '. Nothing has been posted — it is in My Events until you publish it.');
    }

    /**
     * From an event-type page into the request wizard.
     *
     * The page offers SERVICE CATEGORIES, because that is the tier the Category
     * Masterlist ranks per occasion. The wizard needs specific services, so the
     * chosen categories arrive as a focus rather than as the answer — the
     * picker there is already ordered by the same matrix, and the client ticks
     * the actual services.
     *
     * Nothing is chosen on their behalf. Turning "Catering" into every catering
     * service under it would put a dozen requests in front of professionals
     * that the client never asked for.
     */
    public function fromEventType(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_type'   => ['required', 'string', 'max:120'],
            'categories'   => ['required', 'array', 'min:1', 'max:27'],
            'categories.*' => ['integer', 'exists:categories,id'],
        ]);

        // Only an event type we actually have, for the same reason the tool
        // handoff checks it: free text must not become a public listing title.
        $eventType = Category::active()->eventTypes()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['event_type']))])
            ->value('name');

        Session::put(self::KEY, array_filter([
            'event_type'    => $eventType,
            'title'         => $this->titleFrom($eventType, null),
            'focus_categories' => array_values(array_map('intval', $data['categories'])),
        ], fn ($v) => $v !== null && $v !== []));

        return redirect()
            ->route('client.bsr.step', 'service')
            ->with('status', $eventType
                ? "Planning your {$eventType}. Choose the services you need — the ones that matter most for this kind of event are first."
                : 'Choose the services you need to get started.');
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
                /*
                 * Required, and first on the page.
                 *
                 * It was nullable and sat below the services, so a client who
                 * started from Post Event rather than from an event-type page
                 * could skip it and the request was saved with no event type —
                 * two entry paths producing disconnected requests. The event
                 * type is what the archetype relevance matrix orders the
                 * services by, so a request without one has nothing to order by.
                 */
                'event_type'        => ['required', 'string', 'max:80', new \App\Rules\KnownEventType],
                // Only asked for, and only kept, when they picked "Other".
                'event_title'       => ['nullable', 'string', 'max:120', 'required_if:event_type,' . self::OTHER_EVENT_TYPE],
                'organization_type' => ['required', 'in:' . implode(',', array_keys(self::ORG_TYPES))],
                'characteristic'    => ['required', 'in:' . implode(',', array_keys(self::CHARACTERISTICS))],
            ],
            'event' => [
                'title'       => ['required', 'string', 'max:200'],
                'starts_at'   => ['nullable', 'date'],
                'location'    => ['nullable', 'string', 'max:200'],
                // R38 / R71 — the state the WORK happens in, asked here beside
                // the address rather than assumed from the account.
                'event_state' => ['nullable', 'string', 'in:' . implode(',', array_keys(config('geo.allowed_states', [])))],
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
                    config('bsr.default_proposal_window_hours') ? 'nullable' : 'required',
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
        $approvedWindow = config('bsr.default_proposal_window_hours');
        if (! $deadline && $approvedWindow) {
            $deadline = now()->addHours((int) $approvedWindow);
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
            'state'             => \App\Support\StateMatching::requestState($user, $d['event_state'] ?? null),
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
            'source'            => 'user',      // BR — broadcast, no supplier_id
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
