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

        // Stats
        $stats = [
            'total' => Event::where('client_id', $user->id)->count(),
            'open' => Event::where('client_id', $user->id)->whereIn('status', ['pending', 'published'])->count(),
            'upcoming' => Event::where('client_id', $user->id)->where('starts_at', '>', now())->count(),
            'total_budget' => 0,  // placeholder
        ];

        // Calendar data: events for current month
        $month = $request->integer('month', (int) now()->format('m'));
        $year = $request->integer('year', (int) now()->format('Y'));
        $calendarEvents = Event::where('client_id', $user->id)
            ->whereNotNull('starts_at')
            ->whereMonth('starts_at', $month)
            ->whereYear('starts_at', $year)
            ->get(['id', 'title', 'starts_at', 'ends_at', 'status']);

        $categories = Category::active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        return view('client.events.index', compact('events', 'stats', 'calendarEvents', 'categories', 'month', 'year'));
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

        return view('client.events.show', compact('event'));
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
        ]);

        $event->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'location' => $validated['location'] ?? null,
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
