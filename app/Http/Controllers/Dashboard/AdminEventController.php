<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminEventController extends Controller
{
    public function index(Request $request): View
    {
        $query = Event::query()->with(['client:id,name', 'supplier:id,name', 'creator:id,name', 'categories:id,name']);

        // Search by title or description
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by source
        if ($request->filled('source') && in_array($request->source, ['user', 'ai', 'system'])) {
            $query->where('source', $request->source);
        }

        // Filter by published
        if ($request->filled('published')) {
            if ($request->published === 'yes') {
                $query->where('is_published', true);
            } elseif ($request->published === 'no') {
                $query->where('is_published', false);
            }
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('starts_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('starts_at', '<=', $request->date_to);
        }

        // Sort
        $sortBy = $request->get('sort', 'latest');
        match ($sortBy) {
            'oldest' => $query->oldest(),
            'title_asc' => $query->orderBy('title', 'asc'),
            'title_desc' => $query->orderBy('title', 'desc'),
            'starts_at' => $query->orderBy('starts_at', 'asc'),
            default => $query->latest(),
        };

        // Stats
        $stats = [
            'total' => Event::count(),
            'pending' => Event::where('status', 'pending')->count(),
            'published' => Event::where('status', 'published')->count(),
            'confirmed' => Event::where('status', 'confirmed')->count(),
            'in_progress' => Event::where('status', 'in_progress')->count(),
            'completed' => Event::where('status', 'completed')->count(),
            'cancelled' => Event::where('status', 'cancelled')->count(),
        ];

        $suppliers = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'supplier'))
            ->get(['id', 'name']);

        $clients = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'client'))
            ->get(['id', 'name']);

        $categories = Category::active()->orderBy('name')->get(['id', 'name']);

        return view('dashboard.admin.events.index', [
            'events' => $query->paginate(15)->withQueryString(),
            'stats' => $stats,
            'suppliers' => $suppliers,
            'clients' => $clients,
            'categories' => $categories,
            'filters' => $request->only(['search', 'status', 'source', 'published', 'date_from', 'date_to', 'sort']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:pending,published,confirmed,in_progress,completed,cancelled'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'client_id' => ['required', 'exists:users,id'],
            'supplier_id' => ['nullable', 'exists:users,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $event = Event::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'created_by' => $request->user()->id,
            'client_id' => $validated['client_id'],
            'supplier_id' => $validated['supplier_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'published_at' => $request->boolean('is_published') ? now() : null,
            'source' => 'user',
        ]);

        // Sync categories via pivot
        if (!empty($validated['category_ids'])) {
            $event->categories()->sync($validated['category_ids']);
        } elseif (!empty($validated['category_id'])) {
            $event->categories()->sync([$validated['category_id']]);
        }

        return redirect()->route('app.admin.events.index')->with('status', 'Event created successfully.');
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:pending,published,confirmed,in_progress,completed,cancelled'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'client_id' => ['required', 'exists:users,id'],
            'supplier_id' => ['nullable', 'exists:users,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $wasPublished = $event->is_published;
        $nowPublished = $request->boolean('is_published');

        $event->update([
            ...$validated,
            'category_id' => $validated['category_id'] ?? null,
            'is_published' => $nowPublished,
            'published_at' => (!$wasPublished && $nowPublished) ? now() : $event->published_at,
        ]);

        // Sync categories via pivot
        if (isset($validated['category_ids'])) {
            $event->categories()->sync($validated['category_ids']);
        }

        return redirect()->route('app.admin.events.index')->with('status', 'Event updated successfully.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('app.admin.events.index')->with('status', 'Event deleted successfully.');
    }
}
