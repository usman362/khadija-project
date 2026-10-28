<?php

namespace App\Domain;

use App\Models\Booking;
use App\Models\Finalization;
use App\Models\StaffMember;
use App\Models\User;
use App\Notifications\SomebodyElseIsComing;
use Illuminate\Validation\ValidationException;

/**
 * Who the client will actually meet on the day.
 *
 * Sir Peter, 28 October: a professional may send a worker or supervisor as
 * the main point of contact at the site, and the client should be told —
 * unless it was already said during the agreement. Then the rule that
 * governs all of it: "The Professional remains responsible for fulfilling
 * the agreement, even when a staff member attends instead."
 *
 * That rule is why nothing here transfers anything. The booking's supplier
 * does not change, the agreement is not reassigned, and the staff member
 * gains no standing of any kind. All that is recorded is a name and a number
 * for the client to look for, which is the thing they were missing.
 *
 * WHEN THE CLIENT IS TOLD, and it is the only subtle part. A point of contact
 * named before the agreement is signed is part of what the client agreed to,
 * so it needs no announcement. One named or changed afterwards is new
 * information — unless the agreement itself carried "a staff member may
 * attend", in which case the client already agreed to exactly this
 * happening. Silence is not that agreement: staff_may_attend is false by
 * default for the same reason.
 *
 * These staff are rows, not accounts. They cannot sign in, they see nothing,
 * and they are not party to the agreement. Whether they should be users is
 * Sir Peter's decision and a much larger build.
 */
final class PointOfContact
{
    /**
     * Name the person who will be there, or clear it.
     *
     * @throws ValidationException when the crew member is not this professional's
     */
    public static function set(Booking $booking, ?StaffMember $staff, User $by): void
    {
        self::assertTheirs($booking, $staff, $by);

        $was = $booking->point_of_contact_id;

        if ($staff?->id === $was) {
            return;
        }

        $booking->forceFill([
            'point_of_contact_id'     => $staff?->id,
            // Null when cleared: the question "when was this decided" has no
            // answer once there is nothing decided.
            'point_of_contact_set_at' => $staff ? now() : null,
        ])->save();

        if (self::tellTheClient($booking)) {
            self::announce($booking->fresh(), $staff, $by);
        }
    }

    /**
     * Should this change be announced to the client?
     *
     * Before signing, no: the client is about to read the terms anyway, and
     * a notice for every adjustment made while the agreement is still being
     * built is noise in the one place that must not be noisy.
     *
     * After signing, yes — unless the agreement they signed said a staff
     * member might come. That is what Sir Peter asked for: "unless it was
     * already said during the agreement".
     */
    public static function tellTheClient(Booking $booking): bool
    {
        $agreement = self::agreementFor($booking);

        if (! $agreement || ! $agreement->client_signed_at) {
            return false;
        }

        return ! $agreement->staff_may_attend;
    }

    /**
     * What the client should be shown, or null when there is nobody to show.
     *
     * The professional's own name is deliberately NOT returned as a fallback.
     * A page that always prints somebody under "your point of contact" cannot
     * be read: the client could not tell the day a different person was named
     * from the day nobody was.
     *
     * @return array{name:string, role:?string, phone:?string, since:?\Illuminate\Support\Carbon}|null
     */
    public static function forClient(Booking $booking): ?array
    {
        $staff = $booking->pointOfContact;

        if (! $staff) {
            return null;
        }

        return [
            'name'  => $staff->name,
            'role'  => $staff->role,
            // The reason the client is told at all: somebody to reach on the
            // day. A name with no number is half an answer.
            'phone' => $staff->phone,
            'since' => $booking->point_of_contact_set_at,
        ];
    }

    /**
     * The sentence that goes in the agreement, always.
     *
     * Always, because Sir Peter's rule does not depend on whether staff are
     * expected. An agreement that says nothing about it leaves the question
     * open on the document both sides sign, and that is the question people
     * argue about afterwards.
     *
     * @return list<string>
     */
    public static function contractLines(Finalization $f): array
    {
        $lines = ['WHO ATTENDS'];

        $lines[] = $f->staff_may_attend
            ? 'The Professional may send a member of their staff to attend in their place. '
              . 'The Client has agreed to this.'
            : 'The Professional is expected to attend. If a member of their staff attends '
              . 'instead, the Client will be told.';

        // The rule itself, in his words, on the document rather than merely
        // understood.
        $lines[] = 'The Professional remains responsible for fulfilling this agreement, '
            . 'even when a staff member attends instead.';

        return $lines;
    }

    /** The agreement behind a booking, where there is one. */
    private static function agreementFor(Booking $booking): ?Finalization
    {
        return Finalization::where('booking_id', $booking->id)->first()
            ?? Finalization::where('event_id', $booking->event_id)
                ->where('supplier_id', $booking->supplier_id)
                ->first();
    }

    /**
     * Only the professional on the booking, and only their own crew.
     *
     * Both halves matter. Without the first, anybody could name somebody on
     * somebody else's job; without the second, a professional could put
     * another company's staff member in front of their client.
     */
    private static function assertTheirs(Booking $booking, ?StaffMember $staff, User $by): void
    {
        if ((int) $booking->supplier_id !== (int) $by->id) {
            throw ValidationException::withMessages([
                'point_of_contact' => 'This is not your booking.',
            ]);
        }

        if ($staff && (int) $staff->supplier_id !== (int) $by->id) {
            throw ValidationException::withMessages([
                'point_of_contact' => 'That person is not on your team.',
            ]);
        }
    }

    private static function announce(Booking $booking, ?StaffMember $staff, User $by): void
    {
        $client = $booking->client;

        if (! $client) {
            return;
        }

        $client->notify(new SomebodyElseIsComing($booking, $staff, $by->name));
    }
}
