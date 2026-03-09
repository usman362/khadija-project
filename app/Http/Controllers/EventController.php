<?php

namespace App\Http\Controllers;

use App\Domain\Auth\Enums\RoleName;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EventController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Event::class, 'event');
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Event::query()->with(['client:id,name,email', 'supplier:id,name,email', 'creator:id,name,email']);

        if (! $user->isAdmin()) {
            if ($user->hasRole(RoleName::CLIENT->value)) {
                $query->where('client_id', $user->id);
            } elseif ($user->hasRole(RoleName::SUPPLIER->value)) {
                $query->where('supplier_id', $user->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'client_id' => ['nullable', 'exists:users,id'],
            'supplier_id' => ['nullable', 'exists:users,id'],
            'source' => ['nullable', 'string', 'in:user,ai,system'],
        ]);

        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $event = Event::query()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'pending',
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'created_by' => $user->id,
            'client_id' => $isAdmin ? ($validated['client_id'] ?? $user->id) : $user->id,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'source' => $validated['source'] ?? 'user',
        ]);

        return response()->json($event->load(['client:id,name,email', 'supplier:id,name,email']), 201);
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json($event->load([
            'client:id,name,email',
            'supplier:id,name,email',
            'creator:id,name,email',
        ]));
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', 'string', 'max:50'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'supplier_id' => ['nullable', 'exists:users,id'],
        ]);

        if (! $request->user()->isAdmin()) {
            unset($validated['supplier_id']);
        }

        $event->update($validated);

        return response()->json($event->fresh()->load(['client:id,name,email', 'supplier:id,name,email']));
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully.',
        ]);
    }

    public function publish(Request $request, Event $event): JsonResponse
    {
        $this->authorize('publish', $event);

        if ($event->is_published) {
            return response()->json([
                'message' => 'Event is already published.',
                'event' => $event,
            ]);
        }

        $event->update([
            'is_published' => true,
            'published_at' => Carbon::now(),
            'status' => $event->status === 'pending' ? 'published' : $event->status,
        ]);

        return response()->json([
            'message' => 'Event published successfully.',
            'event' => $event->fresh(),
        ]);
    }

    public function details(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $event->load([
            'client:id,name,email',
            'supplier:id,name,email',
            'creator:id,name,email',
            'bookings.client:id,name,email',
            'bookings.supplier:id,name,email',
            'messages.sender:id,name,email',
            'messages.recipient:id,name,email',
        ]);

        return response()->json($event);
    }
}
