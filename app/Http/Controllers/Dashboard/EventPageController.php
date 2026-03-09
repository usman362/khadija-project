<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventPageController extends Controller
{
    public function index(Request $request): View
    {
        $query = Event::query()->with(['client:id,name', 'supplier:id,name'])->latest();

        if (! $request->user()->isAdmin()) {
            $query->where(function ($builder) use ($request): void {
                $builder->where('client_id', $request->user()->id)
                    ->orWhere('supplier_id', $request->user()->id);
            });
        }

        if ($request->filled('source') && in_array($request->string('source')->toString(), ['user', 'ai', 'system'], true)) {
            $query->where('source', $request->string('source')->toString());
        }

        return view('dashboard.events.index', [
            'events' => $query->paginate(12)->withQueryString(),
            'suppliers' => User::query()->whereHas('roles', fn ($q) => $q->where('name', 'supplier'))->get(['id', 'name']),
            'selectedSource' => $request->string('source')->toString() ?: null,
        ]);
    }

    public function show(Event $event): View
    {
        $this->authorize('view', $event);

        $event->load([
            'client:id,name,email',
            'supplier:id,name,email',
            'bookings.client:id,name,email',
            'bookings.supplier:id,name,email',
            'messages.sender:id,name',
        ]);

        return view('dashboard.events.show', compact('event'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Event::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'supplier_id' => ['nullable', 'exists:users,id'],
        ]);

        Event::query()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'created_by' => $request->user()->id,
            'client_id' => $request->user()->isAdmin() ? ($request->user()->id) : $request->user()->id,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'is_published' => false,
            'source' => 'user',
        ]);

        return back()->with('status', 'Event created successfully.');
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'supplier_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', 'in:pending,published,confirmed,in_progress,completed,cancelled'],
        ]);

        $event->update($validated);

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

        return back()->with('status', 'Event published.');
    }
}
