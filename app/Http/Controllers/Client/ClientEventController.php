<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $stats = [
            'total'       => (clone $baseEvents)->count(),
            'open'        => (clone $baseEvents)->whereIn('status', ['pending', 'published'])->count(),
            'upcoming'    => (clone $baseEvents)->where('starts_at', '>', now())->count(),
            'confirmed'   => (clone $bookingBase)->where('status', 'confirmed')->count(),
            'pending'     => (clone $bookingBase)->where('status', 'requested')->count(),
            'paid'        => (clone $bookingBase)->where('status', 'completed')->count(),
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

        return view('client.events.index', compact(
            'events', 'stats', 'calendarEvents', 'categories', 'month', 'year',
            'totalSpent', 'proStatus', 'payment', 'deadlines'
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
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
        ]);

        $event = Event::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'created_by' => $request->user()->id,
            'client_id' => $request->user()->id,
            'location' => $validated['location'] ?? null,
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

        return view('client.events.show', compact('event', 'categories', 'selectedCategoryIds'));
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
