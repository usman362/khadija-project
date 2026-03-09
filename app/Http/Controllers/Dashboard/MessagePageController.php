<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Auth\Enums\RoleName;
use App\Domain\Messaging\Events\MessageInserted;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessagePageController extends Controller
{
    public function index(Request $request): View
    {
        $bookingId = $request->integer('booking_id');

        $messagesQuery = Message::query()->with(['sender:id,name', 'recipient:id,name'])->latest('id');

        if (! $request->user()->isAdmin()) {
            $messagesQuery->where(function ($builder) use ($request): void {
                $builder->where('sender_id', $request->user()->id)
                    ->orWhere('recipient_id', $request->user()->id);
            });
        }

        if ($bookingId) {
            $booking = Booking::query()->findOrFail($bookingId);
            $this->authorize('view', $booking);
            $messagesQuery->where('booking_id', $bookingId);
        }

        if ($request->filled('source') && in_array($request->string('source')->toString(), ['user', 'ai', 'system'], true)) {
            $messagesQuery->where('source', $request->string('source')->toString());
        }

        $bookingsQuery = Booking::query()->with('event:id,title')->latest();

        if (! $request->user()->isAdmin()) {
            if ($request->user()->hasRole(RoleName::CLIENT->value)) {
                $bookingsQuery->where('client_id', $request->user()->id);
            } else {
                $bookingsQuery->where('supplier_id', $request->user()->id);
            }
        }

        return view('dashboard.messages.index', [
            'messages' => $messagesQuery->paginate(20)->withQueryString(),
            'bookings' => $bookingsQuery->get(['id', 'event_id']),
            'selectedBookingId' => $bookingId,
            'selectedSource' => $request->string('source')->toString() ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Message::class);

        $validated = $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'body' => ['required', 'string'],
        ]);

        $booking = Booking::query()->findOrFail($validated['booking_id']);
        $this->authorize('view', $booking);

        $recipientId = $booking->supplier_id === $request->user()->id
            ? $booking->client_id
            : $booking->supplier_id;

        $message = Message::query()->create([
            'booking_id' => $booking->id,
            'event_id' => $booking->event_id,
            'sender_id' => $request->user()->id,
            'recipient_id' => $recipientId,
            'body' => $validated['body'],
            'source' => 'user',
        ]);

        MessageInserted::dispatch($message);

        return back()->with('status', 'Message sent.');
    }
}
