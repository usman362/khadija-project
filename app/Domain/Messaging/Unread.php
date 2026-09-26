<?php

namespace App\Domain\Messaging;

use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * How many messages are waiting for this person, answered in one place.
 *
 * Three surfaces were each counting it their own way on the same screen. The
 * sidebar badge counted rows addressed to the user by recipient_id; the dock
 * and the Messages page counted messages in the user's conversations that
 * somebody else sent and the user has not read. Those are different
 * questions, and a message in a group conversation, where recipient_id is
 * null, answered only the second one — so the badge beside "Messages (Inbox)"
 * could say two while the list showed five.
 *
 * The sidebar also had no idea about the conversations a client is not
 * allowed to open (a client with another client, MessagingPairs). It counted
 * their messages, and nothing the client could do would ever clear them.
 *
 * This is that one question: unread, in something this person may open,
 * excluding what they muted.
 */
class Unread
{
    public static function forUser(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        $muted = DB::table('conversation_participants')
            ->where('user_id', $user->id)
            ->whereNotNull('muted_at')
            ->pluck('conversation_id');

        $barred = MessagingPairs::barredRoles($user);

        return Message::query()
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))
            ->whereNotIn('conversation_id', $muted)
            ->where(function ($q) use ($user, $barred) {
                // In a conversation this person is part of and may open.
                $q->whereHas('conversation', function ($c) use ($user, $barred) {
                    $c->whereHas('participants', fn ($p) => $p->where('users.id', $user->id))
                        ->when($barred, fn ($x) => $x->whereDoesntHave('participants',
                            fn ($p) => $p->where('users.id', '!=', $user->id)->whereIn('primary_role', $barred)));
                })
                    // Or addressed to them with no conversation behind it.
                    ->orWhere(fn ($m) => $m->whereNull('conversation_id')->where('recipient_id', $user->id));
            })
            ->count();
    }
}
