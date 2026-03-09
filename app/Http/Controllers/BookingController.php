<?php

namespace App\Http\Controllers;

use App\Domain\Auth\Enums\RoleName;
use App\Models\AgreementLog;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Booking::class, 'booking');
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Booking::query()->with(['event:id,title,is_published', 'client:id,name,email', 'supplier:id,name,email']);

        if (! $user->isAdmin()) {
            if ($user->hasRole(RoleName::CLIENT->value)) {
                $query->where('client_id', $user->id);
            } elseif ($user->hasRole(RoleName::SUPPLIER->value)) {
                $query->where('supplier_id', $user->id);
            }
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Booking::class);

        $validated = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'supplier_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'source' => ['nullable', 'string', 'in:user,ai,system'],
        ]);

        $event = Event::query()->findOrFail($validated['event_id']);

        if (! $event->is_published) {
            return response()->json([
                'message' => 'Only published events can be booked.',
            ], 422);
        }

        $user = $request->user();

        $booking = Booking::query()->create([
            'event_id' => $event->id,
            'client_id' => $user->isAdmin() ? $event->client_id : $user->id,
            'supplier_id' => $validated['supplier_id'] ?? $event->supplier_id,
            'created_by' => $user->id,
            'status' => 'requested',
            'notes' => $validated['notes'] ?? null,
            'booked_at' => now(),
            'source' => $validated['source'] ?? 'user',
        ]);

        AgreementLog::query()->create([
            'subject_type' => 'booking',
            'subject_id' => $booking->id,
            'from_status' => null,
            'to_status' => 'requested',
            'changed_by' => $user->id,
            'notes' => null,
        ]);

        return response()->json($booking->load(['event:id,title', 'client:id,name,email', 'supplier:id,name,email']), 201);
    }

    public function show(Booking $booking): JsonResponse
    {
        return response()->json($booking->load(['event', 'client:id,name,email', 'supplier:id,name,email', 'messages.sender:id,name,email']));
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('update', $booking);

        $validated = $request->validate([
            'status' => ['sometimes', 'required', 'in:requested,confirmed,cancelled,completed'],
            'notes' => ['nullable', 'string'],
        ]);

        $previousStatus = $booking->status;

        $booking->update($validated);

        if (isset($validated['status']) && $validated['status'] !== $previousStatus) {
            AgreementLog::query()->create([
                'subject_type' => 'booking',
                'subject_id' => $booking->id,
                'from_status' => $previousStatus,
                'to_status' => $validated['status'],
                'changed_by' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
            ]);
        }

        return response()->json($booking->fresh()->load(['event:id,title', 'client:id,name,email', 'supplier:id,name,email']));
    }
}
