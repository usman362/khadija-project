<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lead Pipeline (Leads CRM) — tracks a professional's prospective work from
 * first inquiry through to a confirmed booking, plus an explainer of how the
 * pipeline works and the tools that move a lead forward.
 *
 * REAL data mapping (this marketplace models a booking lifecycle of
 * requested → confirmed → completed | cancelled and has no separate "lead"
 * table, so the four CRM stages are derived honestly):
 *
 *   New Leads      → open marketplace opportunities (published events with no
 *                    supplier yet) — fresh inquiries the pro can pursue.
 *   Proposal Sent  → the pro's `requested` bookings that have NO conversation
 *                    yet (a request/offer is out, awaiting first contact).
 *   Negotiation    → `requested` bookings that already have an active
 *                    conversation (both sides are discussing details).
 *   Booked         → `confirmed` + `completed` bookings (won).
 *
 * The "Active Leads" list unifies open opportunities + the pro's live
 * requested bookings into prioritised lead rows. Value brackets are derived
 * from the event budget; priority is derived from how soon the event is and
 * its budget size. The "what happens if you click" cards are explainer UI.
 *
 * Route: GET /professional/leads
 */
class ProfessionalLeadController extends Controller
{
    public function index(Request $request): View
    {
        $user      = $request->user();
        $now        = now();
        $base       = fn () => Booking::where('supplier_id', $user->id);
        $soonCutoff = $now->copy()->addDays(14);

        // ── Pipeline stage counts (real) ───────────────────────────
        // requested bookings split by whether a conversation exists.
        $requestedIds = $base()->where('status', 'requested')->pluck('id');
        $negotiatingIds = collect();
        if ($requestedIds->isNotEmpty()) {
            $negotiatingIds = Conversation::whereIn('booking_id', $requestedIds)
                ->has('messages')
                ->pluck('booking_id')
                ->unique();
        }
        $cProposal    = $requestedIds->reject(fn ($id) => $negotiatingIds->contains($id))->count();
        $cNegotiation = $negotiatingIds->count();

        $cBooked = $base()->whereIn('status', ['confirmed', 'completed'])->count();

        /*
         * Open marketplace opportunities = fresh leads.
         *
         * Checklist rows 135 and 136. Two faults, one cause: this counted
         * every open event on the platform.
         *
         *   It counted events in other states, which R38 means this
         *   professional can never work — a lead they cannot take is not a
         *   lead.
         *
         *   It counted events they had ALREADY pursued, so a request they had
         *   sent a proposal on appeared twice: once here as a new lead, and
         *   again below as Proposal Sent. Those are the duplicate rows, and
         *   they were also why the four stage counts summed past the pipeline.
         *
         * Excluded here rather than in the list, so the counter and the list
         * are drawn from the same set — which is the whole of row 136.
         */
        $pursuedEventIds = $base()->pluck('event_id')
            ->merge(\App\Models\Bid::where('supplier_id', $user->id)->pluck('event_id'))
            ->filter()->unique();

        $openLeadsQuery = Event::where('is_published', true)
            ->whereNull('supplier_id')
            ->whereIn('status', ['pending', 'published'])
            ->when($pursuedEventIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $pursuedEventIds));

        \App\Support\StateMatching::scopeForViewer($openLeadsQuery, $user);

        $cNew = (clone $openLeadsQuery)->count();

        $pipeline = [
            ['key' => 'new',        'label' => 'New Leads',     'count' => $cNew],
            ['key' => 'proposal',   'label' => 'Proposal Sent', 'count' => $cProposal],
            ['key' => 'negotiation','label' => 'Negotiation',   'count' => $cNegotiation],
            ['key' => 'booked',     'label' => 'Booked',        'count' => $cBooked],
        ];

        // Conversion: booked / (all leads that entered the funnel).
        $totalFunnel = $cNew + $cProposal + $cNegotiation + $cBooked;
        $conversion  = $totalFunnel > 0 ? (int) round($cBooked / $totalFunnel * 100) : 0;

        // ── Active leads list (real) ───────────────────────────────
        // Open opportunities (events) + live requested bookings, unified.
        $leads = collect();

        $opportunities = (clone $openLeadsQuery)
            ->with(['client:id,name', 'categories:id,name'])
            ->select(['id', 'title', 'client_id', 'location', 'starts_at', 'budget', 'budget_min', 'budget_max', 'source', 'created_at', 'published_at', 'is_published'])
            ->orderByRaw('starts_at is null, starts_at asc')
            ->take(6)
            ->get();

        foreach ($opportunities as $ev) {
            $leads->push($this->leadRow(
                name: optional($ev->client)->name ?: $ev->title,
                location: $ev->location,
                date: $ev->starts_at,
                event: $ev,
                budget: $ev->budget,
                soonCutoff: $soonCutoff,
                now: $now,
                stage: 'New Lead',
                since: $ev->postedAt() ?? $ev->created_at,
            ));
        }

        $liveBookings = $base()->where('status', 'requested')
            ->with(['client:id,name', 'event:id,title,location,starts_at,budget,budget_min,budget_max,source'])
            ->with('event.categories:id')
            ->latest()
            ->take(6)
            ->get();

        foreach ($liveBookings as $bk) {
            $leads->push($this->leadRow(
                name: optional($bk->client)->name ?: optional($bk->event)->title ?: 'Prospective client',
                location: optional($bk->event)->location,
                date: optional($bk->event)->starts_at,
                event: $bk->event,
                budget: $bk->price ?: optional($bk->event)->budget,
                soonCutoff: $soonCutoff,
                now: $now,
                stage: $negotiatingIds->contains($bk->id) ? 'Negotiation' : 'Proposal Sent',
                // How long this lead has been sitting — measured from when the
                // professional acted, not from when the row was written.
                since: $bk->created_at,
            ));
        }

        // Highest-value / hottest first, cap at 5 for the list.
        $leads = $leads->sortByDesc('valueHigh')->values()->take(5);

        $stats = [
            'new'         => $cNew,
            'proposal'    => $cProposal,
            'negotiation' => $cNegotiation,
            'booked'      => $cBooked,
            'total'       => $totalFunnel,
            'conversion'  => $conversion,
        ];

        // Row 134 — one "now" for the page, so the lead ages and the pipeline
        // numbers are all measured from the same instant.
        $today = $now;

        return view('professional.leads.index', compact('pipeline', 'leads', 'stats', 'conversion', 'today'));
    }

    /**
     * Build one normalised lead row: value, priority, stage and age.
     */
    private function leadRow(
        ?string $name,
        ?string $location,
        $date,
        ?Event $event,
        $budget,
        $soonCutoff,
        $now,
        string $stage,
        $since = null,
    ): array {
        $budget = (float) ($budget ?: 0);

        /*
         * Checklist row 138 — the value reads the way the request was made.
         *
         * Every row used to print a ±20% band around whatever single figure
         * was on record: a client who stated $4,000 was shown to the
         * professional as "$3,200 – $4,800". Nobody said that. The band was
         * arithmetic the platform did to a number it had been given exactly,
         * and on a rush job or a fixed-fee direct offer it is worse than
         * useless, because those quote one figure by design.
         *
         * A range is now shown only where the client actually gave one.
         */
        $low = $high = null;

        $statedRange = $event
            && $event->budget_min !== null
            && $event->budget_max !== null
            && (float) $event->budget_max > (float) $event->budget_min;

        // A rush request and a direct offer name one figure; so does anything
        // whose client filled in a single budget.
        $fixedByType = $event && in_array($event->source, ['esr', 'direct_offer'], true);

        if ($statedRange && ! $fixedByType) {
            $low  = (float) $event->budget_min;
            $high = (float) $event->budget_max;
        } elseif ($budget > 0) {
            $low = $high = $budget;
        }
        // Otherwise both stay null: no budget on record means no value to
        // show. This used to print a "typical event band" of $2,500–$4,500,
        // a figure invented about somebody else's event and shown to a
        // professional deciding whether to chase it.

        // Priority: soon + high budget = High, else Medium, else Low.
        $daysOut = $date ? $now->diffInDays($date, false) : null;
        if (($budget >= 5000) || ($daysOut !== null && $daysOut >= 0 && $date <= $soonCutoff)) {
            $priority = 'High';
        } elseif ($budget >= 2500 || $daysOut === null) {
            $priority = 'Medium';
        } else {
            $priority = 'Low';
        }

        return [
            'name'      => $name ?: 'Prospective client',
            'location'  => $location ?: 'Location TBD',
            'date'      => $date,
            'valueLow'  => $low,
            'valueHigh' => $high,
            // A single stated figure and a stated range are different things,
            // so the view is told which it has rather than inferring it from
            // two numbers that happen to be equal.
            'isRange'   => $low !== null && $high !== null && $high > $low,
            'priority'  => $priority,
            'stage'     => $stage,
            // Row 134 — how long this lead has been waiting, measured from the
            // page's one shared "now" so every age on the screen is counted
            // from the same instant.
            'ageDays'   => $since ? (int) floor($since->diffInDays($now)) : null,
        ];
    }
}
