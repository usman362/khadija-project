<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Domain\Auth\Enums\RoleName;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Virtual & Hybrid Hub — command centre for virtual / hybrid / livestream
 * events: live-stream monitoring, virtual service discovery (streaming
 * directors, broadcast engineers, AV architects…), and project gigs/RFPs.
 *
 * STATUS: UI scaffold for a NEW feature. The live-stream monitor, channel
 * health, and AI-alert telemetry need a streaming/RTMP backend that does
 * not exist yet — those panels render with representative placeholder
 * values (clearly commented). The professional-discovery + RFP sections
 * use real supplier/event data.
 *
 * Route: GET /client/virtual-hub
 */
class ClientVirtualHubController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Specialized virtual-service categories (real, falls back to a
        // curated default list if the taxonomy isn't seeded yet).
        $categories = Category::active()
            ->orderBy('sort_order')->orderBy('name')
            ->take(6)
            ->get(['id', 'name', 'icon']);

        // Top-matching professionals — real suppliers, framed as virtual pros.
        $pros = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::SUPPLIER->value))
            ->with(['profile'])
            ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
            ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)])
            ->orderByRaw('reviews_avg IS NULL, reviews_avg DESC')
            ->take(4)
            ->get();

        // Recent project gigs / RFPs — the client's own hybrid/virtual events.
        $gigs = Event::where('client_id', $user->id)
            ->latest()
            ->take(4)
            ->get(['id', 'title', 'status', 'budget', 'starts_at', 'created_at']);

        $activeEvent = Event::where('client_id', $user->id)
            ->whereIn('status', ['pending', 'published', 'confirmed'])
            ->latest('starts_at')->first();

        return view('client.virtual-hub.index', compact(
            'categories', 'pros', 'gigs', 'activeEvent'
        ));
    }

    /**
     * Virtual & Hybrid Event Brief — dedicated multi-section posting form
     * (Event Details · Technical Environment · Production & Staffing ·
     * Budget & Bidding) where the planner posts a virtual/hybrid gig and
     * qualified professionals submit bids.
     *
     * STATUS: UI scaffold matching the client's "Virtual & Hybrid Event
     * Brief" mockup. Persistence + bidding backend is a follow-up.
     *
     * Route: GET /client/virtual-hub/brief
     */
    public function brief(Request $request): View
    {
        $user = $request->user();

        $activeEvent = Event::where('client_id', $user->id)
            ->whereIn('status', ['pending', 'published', 'confirmed'])
            ->latest('starts_at')->first();

        return view('client.virtual-hub.brief', compact('activeEvent'));
    }
}
