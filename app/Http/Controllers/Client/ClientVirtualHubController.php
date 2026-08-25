<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Domain\Auth\Enums\RoleName;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
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

        /*
         * The event the client is working on — the one most recently posted,
         * not the one furthest in the future.
         *
         * This ordered by starts_at, so posting a new event and landing here
         * showed a DIFFERENT event: the flash said "Wesley Calderon is posted"
         * while the workspace beside it described a gala in October. Whichever
         * event has the latest date is not the one you just created.
         */
        $activeEvent = Event::where('client_id', $user->id)
            ->whereIn('status', ['pending', 'published', 'confirmed'])
            ->whereNull('closed_at')
            ->latest('id')->first();

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

        return view('client.virtual-hub.index', compact(
            'categories', 'pros', 'gigs', 'activeEvent', 'workspace'
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
    /** Where the half-finished brief lives between the two steps. */
    private const DRAFT = 'virtual_hub_brief';

    /**
     * The brief, in the two steps the client's workflow shows: plan the event,
     * then choose the services. They used to sit on one page, which made
     * submitting it look like a jump from step 2 to step 4 with step 3 skipped.
     */
    public function brief(Request $request, string $step = 'plan'): View|RedirectResponse
    {
        $draft = (array) session(self::DRAFT, []);

        // The services step needs the plan behind it. Arriving cold sends the
        // client to the start rather than to a form that cannot be submitted.
        if ($step === 'services' && empty($draft['title'])) {
            return redirect()->route('client.virtual-hub.brief', 'plan');
        }

        $services = $step === 'services'
            ? Category::active()->bookableServices()
                ->with('parent:id,name')->orderBy('name')->get(['id', 'name', 'parent_id'])
            : collect();

        return view('client.virtual-hub.brief', [
            'step'     => $step,
            'draft'    => $draft,
            'services' => $services,
        ]);
    }

    /**
     * Save a step. The plan step is remembered and passes on; the services step
     * is the one that actually creates the event.
     */
    public function save(Request $request, string $step): RedirectResponse
    {
        return $step === 'plan'
            ? $this->savePlan($request)
            : $this->saveServices($request);
    }

    private function savePlan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'        => ['required', 'string', 'max:200'],
            'event_format' => ['required', 'in:virtual,hybrid'],
            'event_type'   => ['nullable', 'string', 'max:80'],
            'starts_at'    => ['required', 'date'],
            'ends_at'      => ['nullable', 'date', 'after:starts_at'],
            'guest_count'  => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'location'     => ['nullable', 'required_if:event_format,hybrid', 'string', 'max:200'],
            'platform'     => ['nullable', 'string', 'max:60'],
            'meeting_url'  => ['nullable', 'url', 'max:500'],
        ], $this->messages());

        session([self::DRAFT => array_merge((array) session(self::DRAFT, []), $data)]);

        return redirect()->route('client.virtual-hub.brief', 'services');
    }

    private function saveServices(Request $request): RedirectResponse
    {
        $draft = (array) session(self::DRAFT, []);

        if (empty($draft['title'])) {
            return redirect()->route('client.virtual-hub.brief', 'plan')
                ->with('error', 'Tell us about the event first.');
        }

        $data = $request->validate([
            'services'    => ['required', 'array', 'min:1'],
            'services.*'  => ['integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'budget_min'  => ['nullable', 'numeric', 'min:0'],
            'budget_max'  => ['nullable', 'numeric', 'min:0', 'gte:budget_min'],
        ], $this->messages());

        $starts = \Illuminate\Support\Carbon::parse($draft['starts_at']);

        $event = Event::create([
            'title'             => $draft['title'],
            'description'       => $data['description'] ?? null,
            'event_type'        => $draft['event_type'] ?? null,
            'event_format'      => $draft['event_format'],
            'platform'          => $draft['platform'] ?? null,
            'meeting_url'       => $draft['meeting_url'] ?? null,
            'status'            => 'published',
            'is_published'      => true,
            'published_at'      => now(),
            'starts_at'         => $starts,
            'ends_at'           => ! empty($draft['ends_at']) ? \Illuminate\Support\Carbon::parse($draft['ends_at']) : null,
            'guest_count'       => $draft['guest_count'] ?? null,
            'budget_min'        => $data['budget_min'] ?? null,
            'budget_max'        => $data['budget_max'] ?? null,
            'budget'            => $data['budget_min'] ?? null,
            'location'          => $draft['location'] ?? null,
            'state'             => \App\Support\StateMatching::requestState($request->user(), null),
            'proposal_deadline' => $this->deadlineFor($starts),
            'source'            => 'virtual_hub',
            'created_by'        => $request->user()->id,
            'client_id'         => $request->user()->id,
        ]);

        $event->categories()->sync(array_map('intval', $data['services']));

        session()->forget(self::DRAFT);

        return redirect()
            ->route('client.virtual-hub.index')
            ->with('status', '“' . $event->title . '” is posted — professionals can send proposals now.');
    }

    /** One set of wordings for both steps. */
    private function messages(): array
    {
        return [
            'title.required'        => 'Give your event a name.',
            'event_format.required' => 'Choose whether this is fully virtual or hybrid.',
            'starts_at.required'    => 'Pick the date and time your event starts.',
            'ends_at.after'         => 'The end time has to be after the start.',
            'location.required_if'  => 'A hybrid event needs a venue — tell professionals where the in-person half is.',
            'services.required'     => 'Pick at least one service you need.',
            'budget_max.gte'        => 'The top of the budget must be at least the bottom.',
            'meeting_url.url'       => 'A joining link should start with http:// or https://',
        ];
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
