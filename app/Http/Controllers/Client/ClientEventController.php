<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientEventController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Event::where('client_id', $user->id)
            ->with(['categories:id,name,icon', 'supplier:id,name', 'bookings'])
            ->latest();

        // Search filter
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->string('search') . '%');
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        // Category filter
        if ($request->filled('category')) {
            $catId = $request->integer('category');
            $query->where(function ($q) use ($catId) {
                $q->where('category_id', $catId)
                  ->orWhereHas('categories', fn($q2) => $q2->where('categories.id', $catId));
            });
        }

        $events = $query->paginate(12)->withQueryString();

        // ── Stats — the "My Gigs" mockup surfaces Total / Confirmed /
        // Pending / Paid / Total Spent up top, plus a Professional-Status
        // breakdown and a Payment Summary in the right rail. We derive
        // these from events + their bookings so the cards reflect real data.
        $baseEvents = Event::where('client_id', $user->id);
        $bookingBase = \App\Models\Booking::where('client_id', $user->id);

        /*
         * Checklist rows 86, 101 and 125 — the tiles never reconciled with
         * the list, and this is why: they were counting two different things
         * under one heading.
         *
         * "Total Events" counted EVENTS. "Confirmed", "Pending" and "Paid"
         * counted BOOKINGS. A client with one event and three professionals
         * on it saw Total 1 next to Confirmed 3, and no arrangement of those
         * numbers adds up — which is what R1/R6 mean by one counting unit.
         *
         * Every tile counts EVENTS now, and each is a subset of the total, so
         * they reconcile by construction. Total Spent stays money and is
         * labelled as money; that one is honestly a different unit.
         */
        $eventStats = (clone $baseEvents)
            ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $stats = [
            'total'     => (int) $eventStats->sum(),

            // Live and taking proposals — the two statuses that mean "still
            // looking", counted the same way the list's own filter counts them.
            'open'      => (int) (($eventStats['pending'] ?? 0) + ($eventStats['published'] ?? 0)),

            'confirmed' => (int) ($eventStats['confirmed'] ?? 0),
            'completed' => (int) ($eventStats['completed'] ?? 0),
            'cancelled' => (int) ($eventStats['cancelled'] ?? 0),

            'upcoming'  => (clone $baseEvents)->where('starts_at', '>', now())->count(),

            // Kept for the right-rail breakdown, which is explicitly about
            // professionals rather than events — and says so on screen.
            'pending'   => (clone $bookingBase)->where('status', 'requested')->count(),
            'paid'      => (clone $bookingBase)->where('status', 'completed')->count(),
            'total_budget' => 0,
        ];

        // Total spent — sum of completed bookings using whichever price
        // column exists. Used by both the top card and Payment Summary.
        $priceCol = \Illuminate\Support\Facades\Schema::hasColumn('bookings', 'total_amount')
            ? 'total_amount'
            : (\Illuminate\Support\Facades\Schema::hasColumn('bookings', 'agreed_price') ? 'agreed_price' : null);
        $totalSpent = $priceCol ? (float) (clone $bookingBase)->where('status', 'completed')->sum($priceCol) : 0;

        // Professional-status breakdown for the right rail.
        $proStatus = [
            'confirmed'    => $stats['confirmed'],
            'pending'      => $stats['pending'],
            'not_scheduled'=> (clone $bookingBase)->whereNull('event_id')->count(),
            'cancelled'    => (clone $bookingBase)->where('status', 'cancelled')->count(),
            'rescheduled'  => 0, // no schema flag yet
        ];

        // Payment summary — real split from booking status (paid = completed,
        // pending = upcoming unpaid, overdue = unpaid past its event date).
        $paid        = $totalSpent;
        $unpaidTotal = $priceCol ? (float) (clone $bookingBase)->whereIn('status', ['requested', 'confirmed'])->sum($priceCol) : 0;
        $overdue     = $priceCol ? (float) (clone $bookingBase)->whereIn('status', ['requested', 'confirmed'])
            ->whereHas('event', fn ($q) => $q->where('starts_at', '<', now()))->sum($priceCol) : 0;
        $payment = [
            'total'   => $paid + $unpaidTotal,
            'paid'    => $paid,
            'pending' => max(0, $unpaidTotal - $overdue),
            'overdue' => $overdue,
        ];

        // Upcoming deadlines — events starting within the next 14 days.
        $deadlines = (clone $baseEvents)
            ->whereBetween('starts_at', [now(), now()->addDays(14)])
            ->orderBy('starts_at')
            ->take(4)
            ->get(['id', 'title', 'starts_at']);

        // Calendar data: events for current month
        $month = $request->integer('month', (int) now()->format('m'));
        $year = $request->integer('year', (int) now()->format('Y'));
        $calendarEvents = Event::where('client_id', $user->id)
            ->whereNotNull('starts_at')
            ->whereMonth('starts_at', $month)
            ->whereYear('starts_at', $year)
            ->get(['id', 'title', 'starts_at', 'ends_at', 'status']);

        $categories = Category::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        /*
         * Checklist row 84 — "Recent Professional Activity".
         *
         * The panel used to list the three most recently touched events as
         * "Activity on <title>" timestamped `updated_at`. Neither half was
         * true: `updated_at` moves on any save at all, including one nobody
         * made deliberately, and "activity" named nothing that happened. That
         * is how the feed came to contradict the card sitting beside it.
         *
         * Each row now stands for one record that exists, timestamped when
         * that record was written. If nothing has happened, the panel says so.
         */
        $activity = collect();

        foreach ((clone $baseEvents)->whereNotNull('published_at')->latest('published_at')->take(4)->get() as $ev) {
            $activity->push([
                'kind'  => 'posted',
                'text'  => 'You posted',
                'about' => $ev->title,
                'when'  => $ev->postedAt(),
            ]);
        }

        foreach ((clone $bookingBase)->with(['supplier:id,name', 'event:id,title'])->latest()->take(4)->get() as $bk) {
            $activity->push([
                'kind'  => $bk->status === 'cancelled' ? 'cancelled' : 'proposal',
                'text'  => match ($bk->status) {
                    'cancelled' => ($bk->supplier?->name ?? 'A professional') . ' withdrew',
                    'confirmed' => 'You confirmed ' . ($bk->supplier?->name ?? 'a professional'),
                    'completed' => ($bk->supplier?->name ?? 'A professional') . ' completed',
                    default     => 'Proposal from ' . ($bk->supplier?->name ?? 'a professional'),
                },
                'about' => $bk->event?->title ?? 'a request',
                // updated_at is right here and wrong above: a booking's status
                // IS what changed, so the moment it changed is the news.
                'when'  => $bk->status === 'requested' ? $bk->created_at : $bk->updated_at,
            ]);
        }

        $activity = $activity->filter(fn ($a) => $a['when'] !== null)->sortByDesc('when')->take(4)->values();

        return view('client.events.index', compact(
            'events', 'stats', 'calendarEvents', 'categories', 'month', 'year',
            'totalSpent', 'proStatus', 'payment', 'deadlines', 'activity'
        ));
    }

    /** Flash-card "Create a Gig" wizard — one question per screen. */
    public function create(Request $request): View
    {
        $this->authorize('create', Event::class);

        $categories = Category::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'icon']);

        return view('client.events.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Event::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'venue' => ['nullable', 'string', 'max:255'],
            'guest_count' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'inspiration_photos' => ['nullable', 'array', 'max:8'],
            'inspiration_photos.*' => ['image', 'max:5120'],
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:51200'],
            'documents' => ['nullable', 'array', 'max:5'],
            'documents.*' => ['file', 'mimes:pdf,doc,docx,png,jpg,jpeg', 'max:10240'],

            // R55 — required only when something is actually being uploaded.
            // Asking a client who attached nothing to attest to rights over
            // nothing would train everyone to tick it without reading.
            'rights_attested' => [
                Rule::requiredIf(fn () => $request->hasFile('inspiration_photos')
                    || $request->hasFile('video')
                    || $request->hasFile('documents')),
                'accepted',
            ],
        ], [
            'rights_attested.required' => 'Please confirm you have the right to upload these files.',
            'rights_attested.accepted' => 'Please confirm you have the right to upload these files.',
        ]);

        // Merge the optional event time into the start date.
        $startsAt = $validated['starts_at'] ?? null;
        if ($startsAt && ! empty($validated['event_time'])) {
            $startsAt = \Illuminate\Support\Carbon::parse($startsAt)->setTimeFromTimeString($validated['event_time']);
        }

        // Rules R54 and R55 — event media through the one pipeline.
        //
        // These went straight to the PUBLIC disk: a client's wedding and
        // birthday photographs on URLs that needed no sign-in. R55 says
        // plainly that such photographs will contain children and that this
        // is not a reason to refuse them — which makes where they are stored
        // the thing that matters. They are private now, and the JSON holds
        // uploaded_files ids rather than raw paths.
        //
        // R55's attestation is the uploader's, captured with the wording they
        // were shown. It is an attestation and not a detector on purpose: no
        // automated check can tell whether a guardian agreed, and one that
        // merely spotted a child would flag every real event photograph on
        // the platform — turning R55 into the blanket ban it exists to
        // prevent.
        $pipeline = app(\App\Domain\Uploads\UploadPipeline::class);
        $attested = $request->boolean('rights_attested');

        $store = function ($file) use ($pipeline, $request, $attested) {
            $record = $pipeline->accept($file, 'event_media', $request->user(), $attested);

            return $record->status === \App\Models\UploadedFile::REJECTED ? null : (string) $record->id;
        };

        /*
         * Twenty-five images a day — Khadijah's sheet, 29 Aug. Her note is the
         * reason the number is high: it must not block a legitimate event
         * gallery.
         *
         * The whole batch is checked BEFORE any of it is counted. Hitting the
         * limiter once per photo would charge a client for the first two of
         * eight and then refuse the upload — they would lose two from their
         * allowance for something that never happened.
         */
        $photos = (array) $request->file('inspiration_photos', []);

        if ($photos !== []) {
            $left = \App\Support\UserLimit::remaining('client-images', $request->user());

            if ($left !== null && count($photos) > $left) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'inspiration_photos' => $left === 0
                        ? 'You have uploaded 25 images today. Please try again tomorrow.'
                        : "That is more images than you have left today. You can upload {$left} more.",
                ]);
            }

            foreach ($photos as $photo) {
                \App\Support\UserLimit::hit('client-images', $request->user(), null, 'inspiration_photos');
            }
        }

        $media = ['photos' => [], 'video' => null, 'documents' => []];
        foreach ($photos as $photo) {
            $media['photos'][] = $store($photo);
        }
        if ($request->hasFile('video')) {
            $media['video'] = $store($request->file('video'));
        }
        foreach ((array) $request->file('documents', []) as $doc) {
            $media['documents'][] = $store($doc);
        }

        $media['photos']    = array_values(array_filter($media['photos']));
        $media['documents'] = array_values(array_filter($media['documents']));

        $hasMedia = $media['photos'] || $media['video'] || $media['documents'];

        $event = Event::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
            'starts_at' => $startsAt,
            'ends_at' => $validated['ends_at'] ?? null,
            'created_by' => $request->user()->id,
            'client_id' => $request->user()->id,
            'location' => $validated['location'] ?? null,
            'venue' => $validated['venue'] ?? null,
            'guest_count' => $validated['guest_count'] ?? null,
            'media' => $hasMedia ? $media : null,
            'budget' => $validated['budget'] ?? null,
            'is_published' => false,
            'source' => 'user',
        ]);

        // Sync categories via pivot table
        if (!empty($validated['category_ids'])) {
            $event->categories()->sync($validated['category_ids']);
        }

        return redirect()->route('client.events.index')->with('status', 'Event created successfully!');
    }

    public function show(Request $request, Event $event): View
    {
        $this->authorize('view', $event);

        $event->load([
            'categories:id,name',
            'client:id,name,email',
            'supplier:id,name,email',
            'bookings.supplier:id,name',
            'bookings.client:id,name',
            'messages.sender:id,name',
        ]);

        // Categories for the edit form + ids of the ones already attached.
        $categories = Category::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        $selectedCategoryIds = $event->categories->pluck('id')->all();

        // Sealed bids received on this event. The client is the event owner, so
        // they see every amount (bids are only sealed from OTHER professionals).
        $bids = \App\Models\Bid::where('event_id', $event->id)
            ->with(['supplier.profile', 'supplier.reviewsReceived', 'category:id,name', 'replies.user:id,name'])
            ->orderBy('amount')
            ->get();

        // Request type and scope, the same model the professional board uses:
        // BR is broadcast bidding, ER is that with urgency, DR is targeted at
        // one professional. SSR/MSR is the SCOPE — the service count.
        $type  = match ($event->source) {
            'esr'          => 'ER',
            'direct_offer' => 'DR',
            default        => 'BR',
        };
        $scope = $event->categories->count() >= 2 ? 'MSR' : 'SSR';

        // Which tab is open. Everything is rendered server-side and switched by
        // query string, so a tab is linkable and survives a reload.
        $tab = in_array($request->query('tab'), ['overview', 'requirements', 'proposals', 'attendees', 'questions', 'files', 'activity'], true)
            ? $request->query('tab')
            : 'overview';

        // The award, if the client already picked someone. Once this exists the
        // request is no longer open for proposals.
        $award = $event->bookings->first(fn ($b) => ! in_array($b->status, ['cancelled', 'declined'], true));

        // Clarifying questions live on the bid threads — a reply with no counter
        // amount is a question rather than a negotiation move.
        $questions = $bids->flatMap(fn ($b) => $b->replies->map(fn ($r) => [
            'bid'   => $b,
            'reply' => $r,
        ]))->filter(fn ($q) => ! $q['reply']->counter_amount)->values();

        // A single ordered stream for the Activity tab, newest first.
        $activity = collect()
            ->concat($bids->map(fn ($b) => [
                'at'    => $b->created_at,
                'icon'  => '📩',
                'text'  => ($b->supplier->name ?? 'A professional') . ' submitted a proposal',
            ]))
            ->concat($bids->flatMap(fn ($b) => $b->replies)->map(fn ($r) => [
                'at'    => $r->created_at,
                'icon'  => $r->counter_amount ? '↔️' : '💬',
                'text'  => ($r->user->name ?? 'Someone') . ($r->counter_amount
                            ? ' countered at $' . number_format($r->counter_amount)
                            : ' left a message'),
            ]))
            ->concat($event->bookings->map(fn ($b) => [
                'at'    => $b->created_at,
                'icon'  => '🏆',
                'text'  => ($b->supplier->name ?? 'A professional') . ' was selected',
            ]))
            ->push(['at' => $event->created_at, 'icon' => '✳️', 'text' => 'Request created'])
            ->filter(fn ($a) => $a['at'])
            ->sortByDesc('at')
            ->values();

        // Rule R60 — the guest list belongs to this event and is only ever
        // read one event at a time. summaryFor() is the same call the
        // dashboard's summary line uses, so the two cannot disagree.
        $attendees = $event->attendees()->orderBy('name')->get();
        $attendeeSummary = \App\Models\EventAttendee::summaryFor($event);

        /*
         * Row 194 — the client's own toolkit results, from their other
         * events, offered for pulling into this one. Their own only: somebody
         * else's budget is not a library to browse.
         */
        // Already attached to THIS event, so it is not offered again. Pulling
        // a result in now records a placement in toolkit_attachments (R30), not
        // a second library row, so "already here" is checked against the source
        // artifact of those placements.
        $attachedSourceIds = \App\Models\ToolkitAttachment::query()
            ->where('attachable_type', $event::class)
            ->where('attachable_id', $event->id)
            ->pluck('source_artifact_id')
            ->filter()
            ->all();

        $availableArtifacts = \App\Models\EventAiArtifact::where('user_id', $request->user()->id)
            ->where('event_id', '!=', $event->id)
            ->whereNotIn('id', $attachedSourceIds)
            ->latest('id')
            ->get()
            ->unique(fn ($a) => $a->tool_key . '|' . $a->title)
            ->reject(fn ($a) => $event->aiArtifacts
                ->contains(fn ($on) => $on->tool_key === $a->tool_key && $on->title === $a->title))
            ->take(20)
            ->values();

        /*
         * Files on the request. `draft_key` is the event's own id here rather
         * than a wizard token: this page is the event, so anything uploaded
         * from it belongs to it immediately — there is no draft to adopt from.
         */
        $filesKey = 'event-'.$event->id;
        $files    = \App\Http\Controllers\Client\RequestAttachmentController::forDraft(
            $request->user()->id,
            $filesKey,
            $event->id,
        );

        return view('client.events.show', compact(
            'event', 'categories', 'selectedCategoryIds', 'bids',
            'type', 'scope', 'tab', 'award', 'questions', 'activity',
            'attendees', 'attendeeSummary', 'availableArtifacts',
            'files', 'filesKey'
        ));
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
        ]);

        $event->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'location' => $validated['location'] ?? null,
            'budget' => $validated['budget'] ?? null,
        ]);

        $event->categories()->sync($validated['category_ids'] ?? []);

        return back()->with('status', 'Event updated successfully.');
    }

    public function publish(Event $event): RedirectResponse
    {
        $this->authorize('publish', $event);

        $event->update([
            'is_published' => true,
            'published_at' => now(),
            'status' => $event->status === 'pending' ? 'published' : $event->status,
        ]);

        return back()->with('status', 'Event published successfully!');
    }
}
