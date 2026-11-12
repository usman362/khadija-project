<?php

namespace App\Http\Controllers\Bookings;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Check-in on the day of the event.
 *
 * From Khadijah's 5 November checklist, under the Gig Operations Hub: "live
 * event management, active gig status, client check-in".
 *
 * One controller for both parties, like disputes and cancellations before it.
 * The professional marks that they are on site and the client confirms it,
 * which is the same action taken from two directions, and two controllers
 * would be two places to forget that it can only happen on the day.
 *
 * Nothing here decides anything. It does not move the booking's status, start
 * a timer, or bill an hour. It records that somebody said they were there, at
 * a time, and which of them said it. Everything that reads these columns says
 * exactly that and no more.
 */
class CheckInController extends Controller
{
    public function store(Request $request, Booking $booking): RedirectResponse
    {
        $user = $request->user();

        abort_unless($booking->canCheckIn($user), 403, 'Check-in opens on the day of the event.');

        $column = $booking->checkInColumnFor($user);

        /*
         * Written once. A second tap is not a second arrival, and
         * overwriting the first would quietly move the time somebody may
         * later be relying on.
         */
        if ($booking->{$column} === null) {
            $booking->forceFill([$column => now()])->save();
        }

        return back()->with('status', 'Checked in.');
    }

    /**
     * Undo, for the tap that was a mistake.
     *
     * Only your own, and only while it is still the day it was recorded on.
     * After that it is a record of what happened rather than something to
     * tidy up, and a correction belongs in a dispute, not in a button.
     */
    public function destroy(Request $request, Booking $booking): RedirectResponse
    {
        $user = $request->user();
        $column = $booking->checkInColumnFor($user);

        abort_unless($column !== null, 403);

        $at = $booking->{$column};

        abort_unless($at !== null && $at->isSameDay(now()), 403,
            'A check-in can only be undone on the day it was made.');

        $booking->forceFill([$column => null])->save();

        return back()->with('status', 'Check-in removed.');
    }
}
