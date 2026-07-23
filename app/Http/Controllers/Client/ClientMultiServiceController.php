<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Multi-Service Request for Proposal (RFP). Lets a client post ONE event
 * brief and request matched bids across MULTIPLE service categories at
 * once (catering + AV + decor + …) instead of sourcing each separately.
 *
 * STATUS: UI scaffold. The RFP workflow (master brief → selected services
 * → per-service detail → match & bidding) needs its own backend models
 * (RfpBrief, RfpServiceLine, RfpBid). This controller currently feeds the
 * form with category options and the client's active event so the wizard
 * renders with real data; submission persistence is a follow-up task.
 *
 * Route: GET /client/multi-service
 */
class ClientMultiServiceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Service categories grouped for the "Select Services" step.
        $categories = Category::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'icon']);

        // The client's most recent open event pre-fills the Event Summary.
        $activeEvent = Event::where('client_id', $user->id)
            ->whereIn('status', ['pending', 'published', 'confirmed'])
            ->latest('starts_at')
            ->first();

        return view('client.multi-service.index', compact('categories', 'activeEvent'));
    }

    /**
     * Publish an MSR as a real, biddable Event. Each selected service is a
     * category on the event; professionals see it on the Bidding Board and
     * bid per service. Ends at Publish — bids arrive async on Proposals
     * (Fix Spec: posting routes end at Publish, not Checkout).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_name'   => ['required', 'string', 'max:200'],
            'event_type'   => ['nullable', 'string', 'max:120'],
            'event_date'   => ['nullable', 'date'],
            'start_time'   => ['nullable', 'string', 'max:20'],
            'end_time'     => ['nullable', 'string', 'max:20'],
            'location'     => ['nullable', 'string', 'max:200'],
            'guest_count'  => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'budget_range' => ['nullable', 'string', 'max:60'],
            'services'     => ['required', 'array', 'min:1'],
            'services.*'   => ['string', 'max:120'],
        ], [
            'services.required' => 'Select at least one service for your request.',
            'event_name.required' => 'Give your event a name.',
        ]);

        $user = $request->user();

        // Compose a start timestamp from the date + optional start time.
        $startsAt = null;
        if (! empty($data['event_date'])) {
            $startsAt = trim($data['event_date'] . ' ' . ($data['start_time'] ?? ''));
        }

        // Parse the lower bound of a "$10,000 – $25,000" range into a number.
        $budget = null;
        if (! empty($data['budget_range']) && preg_match('/[\d,]+/', $data['budget_range'], $m)) {
            $budget = (int) str_replace(',', '', $m[0]);
        }

        $event = Event::create([
            'title'       => $data['event_name'],
            'description' => $data['description'] ?? null,
            'status'      => 'published',
            'is_published' => true,
            'published_at' => now(),
            'starts_at'   => $startsAt,
            'budget'      => $budget,
            'location'    => $data['location'] ?? null,
            'guest_count' => $data['guest_count'] ?? null,
            'created_by'  => $user->id,
            'client_id'   => $user->id,
        ]);

        // Attach the requested services as categories (each = its own gig line).
        // The legacy category tree has duplicate names across branches, so take
        // one category id per selected service name.
        $categoryIds = Category::active()
            ->whereIn('name', $data['services'])
            ->get(['id', 'name'])
            ->unique('name')
            ->pluck('id');
        if ($categoryIds->isNotEmpty()) {
            $event->categories()->sync($categoryIds);
        }

        // Land on the request itself so the client sees what was published;
        // incoming bids show up under Proposals.
        return redirect()
            ->route('client.events.show', $event)
            ->with('status', 'Your Multi-Service Request is live. Professionals can now bid on each service — offers will appear under Proposals as they come in.');
    }
}
