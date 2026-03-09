<?php

namespace App\Http\Controllers;

use App\Domain\Messaging\Events\MessageInserted;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Message::class, 'message');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Message::class);

        $query = Message::query()->with(['sender:id,name,email', 'recipient:id,name,email']);

        if (! $request->user()->isAdmin()) {
            $query->where(function ($builder) use ($request): void {
                $builder->where('sender_id', $request->user()->id)
                    ->orWhere('recipient_id', $request->user()->id);
            });
        }

        if ($request->filled('event_id')) {
            $event = Event::query()->findOrFail((int) $request->integer('event_id'));
            $this->authorize('view', $event);
            $query->where('event_id', $event->id);
        }

        if ($request->filled('booking_id')) {
            $booking = Booking::query()->findOrFail((int) $request->integer('booking_id'));
            $this->authorize('view', $booking);
            $query->where('booking_id', $booking->id);
        }

        return response()->json($query->latest('id')->paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Message::class);

        $validated = $request->validate([
            'event_id' => ['nullable', 'exists:events,id'],
            'booking_id' => ['nullable', 'exists:bookings,id'],
            'recipient_id' => ['nullable', 'exists:users,id'],
            'body' => ['required', 'string'],
            'source' => ['nullable', 'string', 'in:user,ai,system'],
        ]);

        if (empty($validated['event_id']) && empty($validated['booking_id'])) {
            return response()->json([
                'message' => 'Either event_id or booking_id is required.',
            ], 422);
        }

        if (! empty($validated['event_id'])) {
            $event = Event::query()->findOrFail((int) $validated['event_id']);
            $this->authorize('view', $event);
        }

        if (! empty($validated['booking_id'])) {
            $booking = Booking::query()->findOrFail((int) $validated['booking_id']);
            $this->authorize('view', $booking);
        }

        $message = Message::query()->create([
            'event_id' => $validated['event_id'] ?? null,
            'booking_id' => $validated['booking_id'] ?? null,
            'sender_id' => $request->user()->id,
            'recipient_id' => $validated['recipient_id'] ?? null,
            'body' => $validated['body'],
            'source' => $validated['source'] ?? 'user',
        ]);

        MessageInserted::dispatch($message);

        return response()->json($message->load(['sender:id,name,email', 'recipient:id,name,email']), 201);
    }

    public function show(Message $message): JsonResponse
    {
        return response()->json($message->load(['sender:id,name,email', 'recipient:id,name,email', 'event:id,title', 'booking:id,status']));
    }
}
