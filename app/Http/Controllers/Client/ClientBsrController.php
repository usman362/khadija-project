<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use App\Domain\Budget\ServiceBudgetSuggester;
use App\Domain\Taxonomy\ServiceRelevance;
use Illuminate\Http\JsonResponse;
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
        'availability' => 'Availability Match',
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
            // Step 6. Read on every step so the review step can list them too.
            'filesKey' => $this->filesKey($request),
            'files'    => RequestAttachmentController::forDraft(
                $request->user()->id,
                $this->filesKey($request),
                $data['draft_id'] ?? null,
            ),
        ] + $this->availabilityFor($step, $data, $request));
    }

    /**
     * The availability step's numbers.
     *
     * Only computed on that step -- it reads every matching professional's
     * calendar, which is not work to do on six other screens.
     *
     * The mockup offers four buckets (Available / Limited / Not Confirmed /
     * Unavailable). Three of them do not exist in our data, and the one that
     * does is not called "available": a clear GigResource calendar means no
     * commitment ON GIGRESOURCE, not that the professional is free. So the
     * screen states the two things that are true and nothing between them.
     */
    private function availabilityFor(string $step, array $data, Request $request): array
    {
        if ($step !== 'availability') {
            return [];
        }

        $services = array_map('intval', (array) ($data['services'] ?? []));
        $state    = \App\Support\StateMatching::requestState($request->user());
        $date     = ! empty($data['starts_at'])
            ? \Illuminate\Support\Carbon::parse($data['starts_at'])
            : null;

        if ($services === [] || $date === null) {
            // Nothing to count yet. The view says which answer is missing
            // rather than showing a confident zero.
            return ['availability' => null, 'availabilityDays' => [], 'availabilityDate' => $date];
        }

        return [
            'availability'     => \App\Support\ServiceAvailability::on($services, $state, $date),
            'availabilityDays' => \App\Support\ServiceAvailability::around($services, $state, $date),
            'availabilityDate' => $date,
        ];
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

        /*
         * If they said they know the address, hold them to it.
         *
         * A street address has a number in it and a city name does not. Without
         * this the client picks "I know the address", types "Baltimore, MD",
         * and the request is stored as an exact location that the geocoder
         * cannot place — which is the silent version of the bug this whole
         * field was added to fix.
         */
        if (($validated['location_kind'] ?? null) === 'exact') {
            $typed = trim((string) ($validated['location'] ?? ''));

            if ($typed !== '' && ! preg_match('/\d/', $typed)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'location' => 'That looks like an area rather than an address. '
                        . 'Add the street and number, or choose "Only the area so far".',
                ]);
            }
        }

        /*
         * Step 7 asks for the date and the two times separately, because that
         * is how a person says it. The database keeps two timestamps, so they
         * are assembled here — and the assembly is the only place that has to
         * know the three fields exist.
         */
        if ($step === 'availability') {
            $date  = \Illuminate\Support\Carbon::parse($validated['event_date']);
            $start = $date->copy()->setTimeFromTimeString($validated['event_start_time']);

            $validated['starts_at'] = $start->format('Y-m-d H:i:s');

            if (! empty($validated['event_end_time'])) {
                $end = $date->copy()->setTimeFromTimeString($validated['event_end_time']);

                // An event that ends before it starts runs past midnight —
                // the common case for a reception, not a typo. Rolling it to
                // the next day is what the client meant.
                if ($end->lessThanOrEqualTo($start)) {
                    $end->addDay();
                }

                $validated['ends_at'] = $end->format('Y-m-d H:i:s');
            } else {
                $validated['ends_at'] = null;
            }

            // A proposal deadline can never fall after the event; moving the
            // event earlier here can break a deadline that was fine on step 5.
            if (! empty($data['proposal_deadline'])
                && \Illuminate\Support\Carbon::parse($data['proposal_deadline'])->greaterThan($start)) {
                $validated['proposal_deadline'] = $start->copy()->subHour()->format('Y-m-d H:i:s');
            }
        }

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
        /*
         * Ten posted requests a day — Khadijah's sheet, 29 Aug. Counted only
         * when a request actually goes PUBLIC: saving a draft, or a form that
         * fails validation, costs nothing. A client planning a wedding can
         * open and abandon as many drafts as they like.
         */
        \App\Support\UserLimit::hit('client-postings', $request->user(), null, 'confirm');

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
            // Resuming a draft has to bring step 7's end time back with it.
            'ends_at'           => $event->ends_at?->format('Y-m-d\TH:i'),
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
            'event_type'  => ['nullable', 'string', 'max:80'],
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
            'event_type'   => ['required', 'string', 'max:80'],
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
                /*
                 * Not required. Sir Peter, 2026-08-31: "a required field that
                 * does nothing is a broken form."
                 *
                 * Nothing reads this after it is saved — it does not reach a
                 * professional, and it changes no matching, deadline or fee. It
                 * stays on the form while its purpose is decided, but it can no
                 * longer stop a client from posting a request.
                 */
                'characteristic'    => ['nullable', 'in:' . implode(',', array_keys(self::CHARACTERISTICS))],
            ],
            'event' => [
                'title'       => ['required', 'string', 'max:200'],
                'starts_at'   => ['nullable', 'date'],
                'location'    => ['nullable', 'string', 'max:200'],
                /*
                 * Which KIND of answer they gave. The field was one free-text
                 * box, so a city and a full address arrived looking identical
                 * and every request placed as 'unresolved' — a distance from a
                 * professional cannot be worked out from a city name. Knowing
                 * which was intended lets the geocoder be told, and lets the
                 * page say plainly when a location is only approximate.
                 */
                'location_kind' => ['nullable', 'in:exact,area'],
                // The event-state field was removed from the form on
                // 2026-08-25: the State Boundary Rule matches every request by
                // the client's own home state, so choosing one changed nothing.
                // Nothing is validated because nothing is submitted.
                'venue'       => ['nullable', 'string', 'max:200'],
                'guest_count' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            ],
            'requirements' => [
                'description' => ['required', 'string', 'min:20', 'max:4000'],
            ],
            'budget' => [
                'budget_min' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
                'budget_max' => ['nullable', 'numeric', 'min:0', 'max:9999999', 'gte:budget_min'],
                /*
                 * The per-service split. Keyed by category id, and every key has
                 * to be a service this request actually asked for — otherwise a
                 * budget could be attached to a service nobody is bidding on.
                 */
                'service_budgets'   => ['nullable', 'array'],
                'service_budgets.*' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
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
            // The client may move the date here after seeing how crowded it
            // is; everything else on this step is a note to the professional.
            /*
             * The client is looking at who is free on a date — this is the
             * moment they would change it, so the date and the times are asked
             * for here rather than only on step 2.
             *
             * They arrive as three fields because that is how a person thinks
             * about it ("June 28, 6pm to 10pm"); `starts_at` and `ends_at` are
             * assembled from them in save(). An end BEFORE the start is the
             * one combination that is nonsense, and it is rejected rather than
             * quietly stored.
             */
            'availability' => [
                'event_date'         => ['required', 'date', 'after_or_equal:today'],
                'event_start_time'   => ['required', 'date_format:H:i'],
                'event_end_time'     => ['nullable', 'date_format:H:i'],
                'availability_note'  => ['nullable', 'string', 'max:500'],
            ],
            'review' => ['confirm' => ['accepted']],
            default  => [],
        };
    }

    private function messagesFor(string $step): array
    {
        return [
            'services.required'          => 'Pick at least one service you need.',
            'organization_type.required' => 'Tell us who the request is for.',
            'title.required'             => 'Give your request a name.',
            'description.required'       => 'Describe what you need — professionals bid on this.',
            'description.min'            => 'A little more detail helps professionals bid accurately.',
            'budget_max.gte'             => 'The top of the range must be at least the bottom.',
            'proposal_deadline.after'    => 'The proposal deadline has to be in the future.',
            'proposal_deadline.required' => 'Choose when proposals close. No standard window has been approved yet, so this can’t be set for you.',
            'event_date.required'        => 'Set the date your event runs.',
            'event_date.after_or_equal'  => 'Pick a date that has not already passed.',
            'event_start_time.required'  => 'Set the time your event starts.',
            'confirm.accepted'           => 'Confirm the details before publishing.',
        ];
    }

    /**
     * How far into the wizard this state is allowed to reach. A step opens once
     * every step before it has what it needs.
     */
    private function furthestAllowed(array $d): int
    {
        /*
         * One entry per step that can be COMPLETED — seven, for an eight-step
         * wizard, because the last step is the one being unlocked.
         *
         * There were six. Six entries cap `furthest` at 6, which is step 7's
         * own index, so step 8 was never reachable: Continue on step 7
         * redirected to Review, and Review's own guard bounced it straight
         * back to step 7. The wizard said "Step 7 of 8" and had no eighth
         * step. Publishing still worked, because save() has no such guard —
         * the only thing missing was the client's chance to read what they
         * were about to publish.
         */
        $ok = [
            // characteristic deliberately absent: it is optional now.
            ! empty($d['services']) && ! empty($d['organization_type']),
            ! empty($d['title']),
            ! empty($d['description']),
            true,   // budget is optional
            true,   // proposal settings all have defaults
            true,   // files are optional
            ! empty($d['starts_at']),   // step 7 sets the date and start time
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
            // Step 7's optional end time. Null stays null — an event with no
            // stated finish is a real answer, not a missing one.
            'ends_at'           => ! empty($d['ends_at'])
                ? \Illuminate\Support\Carbon::parse($d['ends_at'])
                : null,
            'location'          => $d['location'] ?? null,
            'state'             => \App\Support\StateMatching::requestState($user),
            'venue'             => $d['venue'] ?? null,
            'guest_count'       => $d['guest_count'] ?? null,
            'budget_min'        => $d['budget_min'] ?? null,
            'budget_max'        => $d['budget_max'] ?? null,
            // `budget` is what the rest of the app reads; keep it as the floor of
            // the range so nothing downstream has to know about the new columns.
            'budget'            => $d['budget_min'] ?? null,
            'proposal_deadline' => $deadline,
            // The availability step's note, shown to professionals on the gig
            // page. Collected there, stored here, read there -- a field that
            // is written and never displayed is worse than no field.
            'schedule_note'     => $d['availability_note'] ?? null,
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

        /*
         * The per-service budget, written only for the services this request
         * actually asked for.
         *
         * Rewritten from scratch each save rather than merged: a client who
         * goes back and drops a service must not leave its budget behind on the
         * request, waiting to be shown to somebody bidding on something else.
         */
        $this->saveServiceBudgets($event, $d);

        /*
         * The client attaches a floor plan on step 6, before the Event row
         * exists — the wizard holds its state in the session so an abandoned
         * request leaves nothing behind. The files were uploaded against the
         * wizard's own key; this is where they are handed to the request they
         * were always for. Idempotent, so saving a draft and then publishing
         * does not move them twice.
         */
        RequestAttachmentController::adopt($user->id, $this->filesKey($request), $event);

        return $event;
    }

    /**
     * Suggest a split of the client's budget across the services they chose.
     *
     * Offered, never applied: the response fills the boxes and the client
     * changes whatever they disagree with. It divides THEIR total using the
     * Masterlist's own Essential / Common / Occasional ranking for the
     * occasion — it does not estimate what anything costs.
     */
    public function suggestBudgetSplit(Request $request, ServiceBudgetSuggester $suggester): JsonResponse
    {
        $d = $this->state($request);

        $total = (float) ($request->input('total')
            ?? $d['budget_max']
            ?? $d['budget_min']
            ?? 0);

        $archetype = ServiceRelevance::archetypeByEventType()[
            mb_strtolower(trim((string) ($d['event_type'] ?? '')))
        ] ?? null;

        $split = $suggester->suggest(
            array_map('intval', (array) ($d['services'] ?? [])),
            $total,
            $archetype,
        );

        if ($split === []) {
            return response()->json([
                'ok'      => false,
                // Say which of the two is missing rather than "cannot suggest".
                'message' => $total <= 0
                    ? 'Add a budget above first, then we can suggest a split.'
                    : 'A split needs at least two services.',
            ]);
        }

        return response()->json(['ok' => true, 'split' => $split]);
    }

    /**
     * Store what the client set aside for each service.
     *
     * Bids are per service and the budget was one figure for the whole request,
     * so five professionals bidding on five different services were all shown
     * the same total. Only a multi-service request has anything to divide.
     *
     * @param  array<string, mixed>  $d  the wizard's saved state
     */
    private function saveServiceBudgets(Event $event, array $d): void
    {
        $services = array_values(array_filter(array_map('intval', (array) ($d['services'] ?? []))));
        $split    = (array) ($d['service_budgets'] ?? []);

        $event->serviceBudgets()->delete();

        // Nothing to divide on a single-service request.
        if (count($services) < 2 || $split === []) {
            return;
        }

        foreach ($split as $categoryId => $amount) {
            $categoryId = (int) $categoryId;

            // A figure may only attach to a service actually being requested.
            if (! in_array($categoryId, $services, true)) {
                continue;
            }

            if ($amount === null || $amount === '' || ! is_numeric($amount)) {
                continue;
            }

            $event->serviceBudgets()->create([
                'category_id' => $categoryId,
                'amount'      => (float) $amount,
            ]);
        }
    }

    /**
     * A stable token for the files uploaded during this run of the wizard.
     *
     * It lives in the wizard's own session state, so Back and Continue keep
     * finding the same files, and a second request started later gets its own
     * key rather than inheriting the first one's attachments.
     */
    private function filesKey(Request $request): string
    {
        $data = $this->state($request);

        if (empty($data['files_key'])) {
            $data['files_key'] = (string) \Illuminate\Support\Str::uuid();
            Session::put(self::KEY, $data);
        }

        return $data['files_key'];
    }
}
