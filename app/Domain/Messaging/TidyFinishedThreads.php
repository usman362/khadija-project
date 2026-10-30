<?php

namespace App\Domain\Messaging;

use App\Models\Conversation;
use App\Models\DisputeCase;
use Illuminate\Support\Collection;

/**
 * Putting away the conversations about jobs that are over.
 *
 * Sir Peter, 30 October: the left column fills up with threads about the same
 * project and stays full. His own suggestion was to archive anything a week
 * old. Khadijah's correction is the one built here, and she was right: a
 * booking being argued over can sit quiet for a fortnight and still be live,
 * so age alone would hide the one conversation somebody needs.
 *
 * Her rule instead: archive "once the event is completed or the
 * booking/request is formally closed or cancelled, provided there are no
 * outstanding disputes", and never on the agreement being signed, because
 * that is the moment the real coordination starts.
 *
 * ONCE, AND ONLY ONCE. Archiving is recorded per person, and that is also
 * where a hand archive goes. A sweep that read only that would look at a
 * conversation somebody had deliberately pulled back into their inbox, see it
 * unarchived, and put it away again on the next run — and the person would
 * restore it, and the sweep would win, for ever. So the conversation itself
 * carries the date the sweep dealt with it, and a conversation that has one
 * is never touched again.
 */
final class TidyFinishedThreads
{
    /** Booking states that mean the job is not coming back. */
    private const FINISHED = ['completed', 'cancelled'];

    /**
     * Conversations about a job that is over, which the sweep has not seen.
     *
     * @return Collection<int, Conversation>
     */
    public static function due(): Collection
    {
        return Conversation::query()
            ->whereNull('auto_archived_at')
            ->whereNotNull('event_id')
            ->with(['event:id,status', 'booking:id,status'])
            ->get()
            ->filter(fn (Conversation $c) => self::isOver($c) && ! self::hasAnOpenDispute($c))
            ->values();
    }

    /**
     * Archive what is due, for everybody in it.
     *
     * Only for people who have not already put it away themselves: writing
     * today's date over theirs would move a conversation they archived last
     * month to the top of their Archived list for no reason.
     *
     * @return int conversations archived
     */
    public static function run(bool $dryRun = false): int
    {
        $done = 0;

        foreach (self::due() as $conversation) {
            if ($dryRun) {
                $done++;

                continue;
            }

            $conversation->participants()
                ->wherePivotNull('archived_at')
                ->get()
                ->each(fn ($person) => $conversation->participants()
                    ->updateExistingPivot($person->id, ['archived_at' => now()]));

            // The mark that stops this ever running on it again, written
            // whether or not anybody needed archiving.
            $conversation->forceFill(['auto_archived_at' => now()])->save();

            $done++;
        }

        return $done;
    }

    /**
     * Is the job this conversation is about finished?
     *
     * The booking first, because a booking is the firmer fact: a conversation
     * attached to one is about that job whatever its event says. Then the
     * event, for threads that never became a booking — a request the client
     * cancelled or closed leaves its conversations with nothing left to say.
     *
     * NOT the agreement. Khadijah: signing is where project coordination
     * begins, so archiving there would hide a thread at the moment it starts
     * being used.
     */
    public static function isOver(Conversation $conversation): bool
    {
        if ($conversation->booking) {
            return in_array($conversation->booking->status, self::FINISHED, true);
        }

        return in_array($conversation->event?->status, self::FINISHED, true);
    }

    /**
     * Khadijah's "provided there are no outstanding disputes".
     *
     * Asked of the booking AND of every other booking on the same event: a
     * dispute about the catering is a reason to keep the florist's thread to
     * hand as well, because the client arguing about the day needs the day's
     * conversations.
     */
    public static function hasAnOpenDispute(Conversation $conversation): bool
    {
        $query = DisputeCase::query();

        if ($conversation->event_id) {
            $query->whereIn('booking_id', fn ($q) => $q->select('id')
                ->from('bookings')->where('event_id', $conversation->event_id));
        } elseif ($conversation->booking_id) {
            $query->where('booking_id', $conversation->booking_id);
        } else {
            return false;
        }

        return $query->get(['state'])->contains(fn (DisputeCase $case) => $case->isOpen());
    }
}
