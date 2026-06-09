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

        // Open marketplace opportunities = fresh leads.
        $openLeadsQuery = Event::where('is_published', true)
            ->whereNull('supplier_id')
            ->whereIn('status', ['pending', 'published']);
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
            ->orderByRaw('starts_at is null, starts_at asc')
            ->take(6)
            ->get();

        foreach ($opportunities as $ev) {
            $leads->push($this->leadRow(
                name: optional($ev->client)->name ?: $ev->title,
                location: $ev->location,
                date: $ev->starts_at,
                budget: $ev->budget,
                soonCutoff: $soonCutoff,
                now: $now,
                stage: 'New Lead',
            ));
        }

        $liveBookings = $base()->where('status', 'requested')
            ->with(['client:id,name', 'event:id,title,location,starts_at,budget'])
            ->latest()
            ->take(6)
            ->get();

        foreach ($liveBookings as $bk) {
            $leads->push($this->leadRow(
                name: optional($bk->client)->name ?: optional($bk->event)->title ?: 'Prospective client',
                location: optional($bk->event)->location,
                date: optional($bk->event)->starts_at,
                budget: $bk->price ?: optional($bk->event)->budget,
                soonCutoff: $soonCutoff,
                now: $now,
                stage: $negotiatingIds->contains($bk->id) ? 'Negotiation' : 'Proposal Sent',
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

        return view('professional.leads.index', compact('pipeline', 'leads', 'stats', 'conversion'));
    }

    /**
     * Build one normalised lead row with a derived value bracket + priority.
     */
    private function leadRow(?string $name, ?string $location, $date, $budget, $soonCutoff, $now, string $stage): array
    {
        $budget = (float) ($budget ?: 0);

        // Value bracket: ±20% band around the known/estimated budget.
        if ($budget > 0) {
            $low  = (int) round($budget * 0.8 / 100) * 100;
            $high = (int) round($budget * 1.2 / 100) * 100;
        } else {
            // No budget on record — show a typical event band.
            $low  = 2500;
            $high = 4500;
        }

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
            'priority'  => $priority,
            'stage'     => $stage,
        ];
    }
}
