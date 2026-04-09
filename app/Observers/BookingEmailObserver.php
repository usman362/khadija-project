<?php

namespace App\Observers;

use App\Mail\BookingCancelled;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BookingEmailObserver
{
    /**
     * Fire transactional emails on booking status transitions.
     */
    public function updated(Booking $booking): void
    {
        if (!$booking->wasChanged('status')) {
            return;
        }

        if ($booking->status === 'cancelled') {
            $this->sendCancellationEmails($booking);
        }
    }

    private function sendCancellationEmails(Booking $booking): void
    {
        $booking->loadMissing(['event', 'client', 'supplier']);

        $cancelledBy = Auth::user();

        $recipients = collect([$booking->client, $booking->supplier])
            ->filter()
            ->reject(fn(User $u) => $cancelledBy && $u->id === $cancelledBy->id)
            ->unique('id');

        foreach ($recipients as $recipient) {
            if (empty($recipient->email)) {
                continue;
            }

            try {
                Mail::to($recipient->email)->send(
                    new BookingCancelled($booking, $recipient, $cancelledBy, $booking->notes)
                );
            } catch (Throwable $e) {
                Log::warning('Failed to send booking cancellation email', [
                    'booking_id'   => $booking->id,
                    'recipient_id' => $recipient->id,
                    'error'        => $e->getMessage(),
                ]);
            }
        }
    }
}
