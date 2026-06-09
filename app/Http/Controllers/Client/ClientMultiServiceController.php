<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
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
}
