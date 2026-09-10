<?php

namespace App\Domain\Messaging;

use App\Models\Conversation;
use App\Models\UserBlock;

/**
 * Who may not message whom.
 *
 * A block is between two people and works both ways: neither can send the
 * other a message, in an old conversation or a new one, until it is lifted.
 * It stops messaging only; bookings, agreements and payments between them go
 * on as before.
 *
 * Checked in two places on purpose. The conversation policy refuses early, so
 * a blocked attempt is not counted against anyone's message limit. BlockGuard
 * refuses at the moment a message row is written, so no other route that
 * creates messages, today or later, can get round it.
 */
final class Blocking
{
    /** Said to either side. It does not tell the blocked person they were blocked. */
    public const MESSAGE = "You can't send messages in this conversation.";

    public static function blocked(int $blockerId, int $blockedId): bool
    {
        return UserBlock::where('blocker_id', $blockerId)->where('blocked_id', $blockedId)->exists();
    }

    public static function between(int $a, int $b): bool
    {
        return $a !== $b && UserBlock::query()
            ->where(fn ($q) => $q->where('blocker_id', $a)->where('blocked_id', $b))
            ->orWhere(fn ($q) => $q->where('blocker_id', $b)->where('blocked_id', $a))
            ->exists();
    }

    /** Is there a block, either way, between this person and anyone else in the conversation? */
    public static function stopsMessaging(int $userId, Conversation $c): bool
    {
        $others = $c->participants()->where('users.id', '!=', $userId)->pluck('users.id');
        if ($others->isEmpty()) {
            return false;
        }

        return UserBlock::query()
            ->where(fn ($q) => $q->where('blocker_id', $userId)->whereIn('blocked_id', $others))
            ->orWhere(fn ($q) => $q->whereIn('blocker_id', $others)->where('blocked_id', $userId))
            ->exists();
    }
}
