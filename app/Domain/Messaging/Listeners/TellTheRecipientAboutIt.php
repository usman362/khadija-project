<?php

namespace App\Domain\Messaging\Listeners;

use App\Domain\Messaging\Events\MessageInserted;
use App\Models\User;
use App\Notifications\NewMessageReceived;

/**
 * A message reaches the person who is not looking at the screen.
 *
 * Sir Peter, 18 October: notifications must be "fully functional and getting
 * every notification from whatever the clients do ... to alerts, inbox messages
 * to etc".
 *
 * A message broadcast, which updates a thread somebody already has open. That
 * was the only signal. Somebody not on the site at that moment got nothing: no
 * bell, no email, and the unread count only surfaced if they happened to visit
 * Messages. On a marketplace an unanswered message is a booking lost rather
 * than a feature missing.
 *
 * It is a LISTENER rather than a line in the controller because three different
 * places create a message — the conversation endpoint, the older recipient
 * based endpoint, and the dashboard page — and all three dispatch this event.
 * A notification added to one of them is a notification missing from two, which
 * is the same lesson the posting fee taught: put it where it cannot be
 * forgotten.
 */
class TellTheRecipientAboutIt
{
    public function handle(MessageInserted $event): void
    {
        $message = $event->message;

        /*
         * A system line is skipped. The agreement notices post one into the
         * thread as the written record of a step they have already announced
         * through their own notification, and this would be a second bell for
         * one event.
         */
        if ($message->source === 'system') {
            return;
        }

        foreach ($this->audience($message) as $person) {
            $person->notify(new NewMessageReceived($message));
        }
    }

    /**
     * Everybody in the thread except whoever wrote it.
     *
     * Two shapes of message exist. A conversation message belongs to a thread
     * with participants; the older endpoint addresses one recipient directly
     * and has no thread at all. Both are answered here rather than only the
     * shape this was written for.
     *
     * @return iterable<User>
     */
    private function audience($message): iterable
    {
        if ($message->conversation_id && $message->conversation) {
            return $message->conversation->participants
                ->reject(fn (User $p) => $p->id === (int) $message->sender_id)
                ->all();
        }

        $recipient = $message->recipient_id
            ? User::find($message->recipient_id)
            : null;

        return $recipient && $recipient->id !== (int) $message->sender_id
            ? [$recipient]
            : [];
    }
}
