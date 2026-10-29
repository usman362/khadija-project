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
     * The three answers to "who is coming", as Sir Peter set them out.
     *
     * PROFESSIONAL is a promise, not a default. It is the answer that stops a
     * staff member being named afterwards, because his rule for changing the
     * contact after signing is "provided this does not change an agreed
     * personal-attendance obligation".
     *
     * LATER is the one a checkbox could not hold, and the one that creates an
     * obligation of its own: somebody has to say who is coming before the
     * day. It is not "no" and it is not "yes".
     */
    public const PROFESSIONAL = 'professional';
    public const STAFF        = 'staff';
    public const LATER        = 'later';

    /** @var array<string, string> How each answer reads on a screen. */
    public const ANSWERS = [
        self::PROFESSIONAL => 'I will attend personally',
        self::STAFF        => 'A member of my staff may attend',
        self::LATER        => 'I will decide nearer the time',
    ];

    /**
     * How long before the event an open answer has to be settled.
     *
     * Sir Peter: "require confirmation at least 24 hours before the event".
     */
    public const CONFIRM_HOURS = 24;

    /**
     * Name the person who will be there, or clear it.
     *
     * @throws ValidationException when the crew member is not this professional's
     */
    public static function set(Booking $booking, ?StaffMember $staff, User $by): void
    {
        self::assertTheirs($booking, $staff, $by);
        self::assertNotPromisedInPerson($booking, $staff);

        $was = $booking->point_of_contact_id;

        // Naming nobody is still an answer where the agreement left it open,
        // so the confirmation is stamped before this returns.
        $alreadyThem = $staff?->id === $was;

        $booking->forceFill([
            'point_of_contact_id'     => $staff?->id,
            // Null when cleared: the question "when was this decided" has no
            // answer once there is nothing decided.
            'point_of_contact_set_at' => $staff ? now() : null,
            'attendance_confirmed_at' => now(),
        ])->save();

        if ($alreadyThem) {
            return;
        }

        if (self::tellTheClient($booking)) {
            self::announce($booking->fresh(), $staff, $by);
        }
    }

    /**
     * A promise to attend personally cannot be undone by picking a name.
     *
     * Sir Peter: the contact may be changed after signing "provided this does
     * not change an agreed personal-attendance obligation". This is that
     * proviso. The professional is not stuck with it — they ask for the term
     * to change through Edit Agreement, which raises the version and puts the
     * question back to the client, where it belongs.
     *
     * Only once the client has signed. Before that the terms are still being
     * built and the whole agreement is in motion.
     */
    private static function assertNotPromisedInPerson(Booking $booking, ?StaffMember $staff): void
    {
        if (! $staff) {
            return;
        }

        $agreement = self::agreementFor($booking);

        if (! $agreement || ! $agreement->client_signed_at) {
            return;
        }

        if ($agreement->attendance === self::PROFESSIONAL) {
            throw ValidationException::withMessages([
                'point_of_contact' => 'This agreement says you will attend personally. '
                    . 'Ask for that term to change before sending somebody else.',
            ]);
        }
    }

    /**
     * Has the open answer been settled, for an agreement that left it open?
     *
     * True for any other answer: there is nothing left to settle.
     */
    public static function settled(Booking $booking): bool
    {
        $agreement = self::agreementFor($booking);

        if (! $agreement || $agreement->attendance !== self::LATER) {
            return true;
        }

        /*
         * The obligation starts at the signature, not at the draft. Until the
         * client signs, the terms are still being argued over and "later" is
         * a position in that argument rather than a promise to come back with
         * an answer. Chasing somebody over an agreement nobody has agreed to
         * is how a reminder becomes noise.
         */
        if (! $agreement->client_signed_at) {
            return true;
        }

        return $booking->attendance_confirmed_at !== null;
    }

    /**
     * An open answer that is running out of time.
     *
     * Null when there is nothing owed, or when the event has no date to count
     * back from. A deadline invented from a missing date would chase people
     * over an event that may not be happening.
     */
    public static function confirmBy(Booking $booking): ?\Illuminate\Support\Carbon
    {
        if (self::settled($booking)) {
            return null;
        }

        $starts = $booking->event?->starts_at;

        return $starts?->copy()->subHours(self::CONFIRM_HOURS);
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

        // Agreed that staff may come: this is exactly what the client said
        // yes to, so it is not news. Left open: they are owed the answer.
        return $agreement->attendance !== self::STAFF;
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

        $lines[] = match ($f->attendance) {
            self::STAFF => 'The Professional may send a member of their staff to attend in their '
                . 'place. The Client has agreed to this.',
            self::LATER => 'The Professional has not yet said whether they will attend personally '
                . 'or send a member of their staff. They will tell the Client at least '
                . self::CONFIRM_HOURS . ' hours before the service begins.',
            default     => 'The Professional will attend personally. A member of their staff may '
                . 'not be sent in their place without the Client agreeing to it.',
        };

        // The rule itself, in his words, on the document rather than merely
        // understood.
        $lines[] = 'The Professional remains responsible for fulfilling this agreement, '
            . 'even when a staff member attends instead.';

        return $lines;
    }

    /**
     * Which of the three answers this booking's agreement carries.
     *
     * Null when there is no agreement behind it yet. Asked by the screens, so
     * that none of them has to find the agreement for itself and reach a
     * different one.
     */
    public static function termFor(Booking $booking): ?string
    {
        return self::agreementFor($booking)?->attendance;
    }

    /** Is the professional held to coming in person? */
    public static function promisedInPerson(Booking $booking): bool
    {
        $agreement = self::agreementFor($booking);

        return (bool) $agreement?->client_signed_at
            && $agreement->attendance === self::PROFESSIONAL;
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
