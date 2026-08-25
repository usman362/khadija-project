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
        // bookableServices(): unfiltered, this row listed "Baby Shower" as a
        // virtual SERVICE beside Event Staffing and Beverage Catering. An
        // occasion is not something a professional can be booked to perform.
        $categories = Category::active()->bookableServices()
            ->orderBy('sort_order')->orderBy('name')
            ->take(6)
            ->get(['id', 'name', 'icon']);

        // Top-matching professionals — real suppliers, framed as virtual pros.
        $pros = User::query()
            ->whereHas('roles', fn ($r) => $r->where('name', RoleName::PROFESSIONAL->value))
            ->excludingSelf()
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

        /*
         * The event workspace from the client's Virtual & Hybrid mockup
         * (stages 5-7): what stage this event is at, which services are hired,
         * and what is still open.
         *
         * Built from bookings and bids -- the systems the mockup itself says
         * this workflow should reuse. It replaces the panels that stood here:
         * a Live Stream Monitor, Stream Alerts, Audience Overview and Active
         * Integrations, reporting bitrate, dropped frames and CDN health for a
         * streaming backend that does not exist.
         */
        $workspace = null;

        if ($activeEvent) {
            $activeEvent->loadMissing('categories:id,name');

            $bookings = \App\Models\Booking::where('event_id', $activeEvent->id)
                ->with(['supplier:id,name'])
                ->get();

            $waitingByService = \App\Models\Bid::where('event_id', $activeEvent->id)
                ->whereIn('status', ['submitted', 'pending'])
                ->get()
                ->groupBy('category_id');

            // One row per service the event asked for, in whichever of the
            // three states it is actually in -- booked, proposals in, or still
            // looking. No fourth state is invented.
            $rows = $activeEvent->categories->map(function ($cat) use ($bookings, $waitingByService) {
                $booking = $bookings->firstWhere('category_id', $cat->id);
                $waiting = $waitingByService->get($cat->id)?->count() ?? 0;

                return [
                    'service'      => $cat->name,
                    'professional' => $booking?->supplier?->name,
                    'state'        => $booking && in_array($booking->status, ['confirmed', 'completed'], true)
                        ? 'booked'
                        : ($waiting > 0 ? 'proposals' : 'searching'),
                    'waiting'      => $waiting,
                ];
            })->values();

            $confirmed = $bookings->whereIn('status', ['confirmed', 'completed'])->count();
            $completed = $bookings->where('status', 'completed')->count();

            $workspace = [
                'event'    => $activeEvent,
                // Stage 6 needs a real countdown and only the joining details
                // the client actually gave us. There is no Zoom integration, so
                // there is no connection status -- the mockup's "Connection ·
                // Ready" would be a green tick for something nobody checked.
                'starts_in' => $activeEvent->starts_at && $activeEvent->starts_at->isFuture()
                    ? $activeEvent->starts_at->humanAgo(true)
                    : null,
                'is_today'  => (bool) $activeEvent->starts_at?->isToday(),
                'rows'     => $rows,
                'booked'   => $confirmed,
                'services' => $rows->count(),
                'stage'    => match (true) {
                    $bookings->count() > 0 && $completed === $bookings->count() => 'complete',
                    (bool) $activeEvent->starts_at?->isPast()                   => 'event_day',
                    $confirmed > 0                                              => 'preparation',
                    $rows->contains(fn ($r) => $r['waiting'] > 0)               => 'hiring',
                    default                                                     => 'planning',
                },
            ];
        }

        /*
         * One stage at a time.
         *
         * The mockup's own closing promise is "contextual tools appear when
         * needed, not all at once", and this page was the opposite: entry
         * choices, hiring routes, filters, a service grid, a professional
         * grid, an RFP table and three event panels, all on screen together.
         * Ali's words were "jahan click karo kahin na kahin chala jata" -- of
         * course, because everything was everywhere.
         *
         * The strip is now a set of tabs. Whichever stage is open, that is the
         * only panel below it. Where the event has got to decides which one
         * opens first; after that the client chooses.
         */
        $default = match ($workspace['stage'] ?? null) {
            'complete'    => 7,
            'event_day'   => 6,
            'preparation' => 5,
            'hiring', 'planning' => 4,
            default       => 1,
        };

        $stage = (int) $request->integer('stage', $default);
        if ($stage < 1 || $stage > 7) {
            $stage = $default;
        }

        // Stages 5-7 describe an event. Without one there is nothing to show,
        // so the client is put back at the start rather than at an empty panel.
        if ($stage >= 5 && ! $activeEvent) {
            $stage = 1;
        }

        return view('client.virtual-hub.index', compact(
            'categories', 'pros', 'gigs', 'activeEvent', 'workspace'
        ) + ['stage' => $stage]);
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

        // The same bookable-service definition every other request form uses,
        // so this catalogue cannot drift from the BSR, ER and Direct Request
        // ones. Grouped by their service category, which is what gives the
        // mockup its Technical Production / Event Support / Content & Media
        // headings without inventing a second grouping.
        $services = Category::active()->bookableServices()
            ->with('parent:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return view('client.virtual-hub.brief', compact('activeEvent', 'services'));
    }

    /**
     * Persist a Virtual & Hybrid Event Brief as a real published Event (RFP)
     * so it flows into the same proposals/bidding pipeline as other gigs.
     *
     * Route: POST /client/virtual-hub/brief
     */
    /**
     * Post a virtual or hybrid event (mockup stages 2 and 3).
     *
     * Every field this form asks for is now stored. It used to ask for the
     * platform as a set of radio buttons that carried no value, so the browser
     * submitted "on" -- and the controller neither validated nor saved it
     * regardless. The client chose Zoom and the answer went nowhere.
     *
     * The event itself is an ordinary Event, published through the same
     * systems as any other request, which is what the mockup asks for: the
     * virtual workflow reuses professionals, requests, proposals, messages,
     * bookings and payments rather than growing a parallel set.
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'title'         => ['required', 'string', 'max:200'],
            'event_format'  => ['required', 'in:virtual,hybrid'],
            'event_type'    => ['nullable', 'string', 'max:120'],
            'starts_at'     => ['required', 'date'],
            'ends_at'       => ['nullable', 'date', 'after:starts_at'],
            'guest_count'   => ['nullable', 'integer', 'min:1', 'max:1000000'],
            // Only a hybrid event has somewhere to be.
            'location'      => ['nullable', 'required_if:event_format,hybrid', 'string', 'max:200'],
            'platform'      => ['nullable', 'string', 'max:60'],
            'meeting_url'   => ['nullable', 'url', 'max:500'],
            'services'      => ['required', 'array', 'min:1'],
            'services.*'    => ['integer', 'exists:categories,id'],
            'description'   => ['nullable', 'string', 'max:5000'],
            'budget_min'    => ['nullable', 'numeric', 'min:0'],
            'budget_max'    => ['nullable', 'numeric', 'min:0', 'gte:budget_min'],
        ], [
            'location.required_if' => 'A hybrid event needs a venue — tell professionals where the in-person half is.',
            'services.required'    => 'Pick at least one service you need.',
        ]);

        $starts = \Illuminate\Support\Carbon::parse($data['starts_at']);

        $event = Event::create([
            'title'             => $data['title'],
            'description'       => $data['description'] ?? null,
            'event_type'        => $data['event_type'] ?? null,
            'event_format'      => $data['event_format'],
            'platform'          => $data['platform'] ?? null,
            'meeting_url'       => $data['meeting_url'] ?? null,
            'status'            => 'published',
            'is_published'      => true,
            'published_at'      => now(),
            'starts_at'         => $starts,
            'ends_at'           => ! empty($data['ends_at']) ? \Illuminate\Support\Carbon::parse($data['ends_at']) : null,
            'guest_count'       => $data['guest_count'] ?? null,
            'budget_min'        => $data['budget_min'] ?? null,
            'budget_max'        => $data['budget_max'] ?? null,
            'budget'            => $data['budget_min'] ?? null,
            // A virtual event has no venue, so it has no state of its own --
            // it takes the client's, which is what R38 matches professionals
            // against either way.
            'location'          => $data['location'] ?? null,
            'state'             => \App\Support\StateMatching::requestState($request->user(), null),
            'proposal_deadline' => $this->deadlineFor($starts),
            'source'            => 'virtual_hub',
            'created_by'        => $request->user()->id,
            'client_id'         => $request->user()->id,
        ]);

        $event->categories()->sync(array_map('intval', $data['services']));

        return redirect()
            ->route('client.virtual-hub.index')
            ->with('status', 'Your ' . $data['event_format'] . ' event is posted — professionals can send proposals now.');
    }

    /**
     * The same approved window every other broadcast request uses, pulled back
     * if the event itself lands inside it. Nothing invented here (R37).
     */
    private function deadlineFor(\Illuminate\Support\Carbon $startsAt): ?\Illuminate\Support\Carbon
    {
        $hours = (int) config('bsr.default_proposal_window_hours');

        if ($hours <= 0) {
            return null;
        }

        $deadline = now()->addHours($hours);

        return $deadline->gt($startsAt) ? $startsAt->copy()->subHour() : $deadline;
    }
}
