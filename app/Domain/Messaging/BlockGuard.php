<?php

namespace App\Domain\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * The last word on a block: refuses the message row itself.
 *
 * Three controllers write messages, and whichever route a message takes, it
 * is created here. An AuthorizationException renders as a 403 with the
 * message, wherever it is thrown.
 */
class BlockGuard
{
    public function creating(Message $message): void
    {
        if (! $message->sender_id) {
            return;   // system messages have no sender to block
        }

        if ($message->recipient_id && Blocking::between((int) $message->sender_id, (int) $message->recipient_id)) {
            throw new AuthorizationException(Blocking::MESSAGE);
        }

        if ($message->conversation_id
            && ($c = Conversation::find($message->conversation_id))
            && Blocking::stopsMessaging((int) $message->sender_id, $c)) {
            throw new AuthorizationException(Blocking::MESSAGE);
        }
    }
}
