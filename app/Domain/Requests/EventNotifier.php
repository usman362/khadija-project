<?php

namespace App\Domain\Requests;

use App\Models\Event;
use App\Notifications\EventChanged;
use App\Notifications\EventReopened;
use App\Notifications\NewEventAvailable;
use Illuminate\Support\Facades\Notification;

/**
 * Rule R33 §6 — which of the two notices goes out, and to whom.
 *
 * The rule allows exactly two: "New Event Available" on first publication,
 * and "Event Reopened" for every later reactivation, free or paid. Keeping
 * the choice in one place is the point — the alternative is each call site
 * deciding, and the first one to reach for the louder notice on a paid
 * extension turns the extension fee into an advertising fee.
 *
 * §3's change notice is here too because it shares the audience logic and
 * the same "don't shout" discipline.
 */
final class EventNotifier
{
    /** First publication. Once per listing, ever. */
    public static function published(Event $event): void
    {
        $audience = EventAudience::for($event);

        if ($audience->isNotEmpty()) {
            Notification::send($audience, new NewEventAvailable($event));
        }
    }

    /**
     * A reactivation — the free grace reopen or a paid extension.
     *
     * Both get the same lighter notice. §2 and §6 are explicit that a paid
     * extension must never repeat the first-publication blast: a client who
     * extends three times would otherwise be buying three more rounds of
     * notifications to the same professionals.
     */
    public static function reopened(Event $event): void
    {
        $audience = EventAudience::for($event);

        if ($audience->isNotEmpty()) {
            Notification::send($audience, new EventReopened($event));
        }
    }

    /**
     * §3 — a major field moved on an event that already has proposals.
     *
     * Only the professionals who priced it, and only to ask them to look.
     * Their proposals stay exactly as they are: never auto-withdrawn,
     * auto-rejected or auto-repriced, because only the person who quoted the
     * work can say whether the quote still stands.
     *
     * @param array<int, string> $changed
     */
    public static function changed(Event $event, array $changed): void
    {
        $audience = EventAudience::withProposalsOn($event);

        if ($audience->isNotEmpty()) {
            Notification::send($audience, new EventChanged($event, $changed));
        }
    }
}
