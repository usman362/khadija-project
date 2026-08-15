<?php

namespace App\Support;

use App\Models\Message;
use App\Models\User;

/**
 * How quickly, and how often, someone replies — from the messages themselves.
 *
 * Both the professional portfolio and the client portfolio show these two
 * figures. The professional page used to state them as facts it did not have:
 * a fully-verified pro was shown "Within 2 hours" and "98%", and everyone else
 * "Within 24 hours", with nothing behind either. Verification says a licence
 * was checked; it says nothing about how fast anyone answers their messages.
 *
 * The method here is the plain reading of the words:
 *
 *   A message counts as one someone had to answer when it was sent TO them and
 *   they were not the previous sender in that conversation — a run of three
 *   messages from the other side is one thing to reply to, not three.
 *
 *   It was ANSWERED if they later sent a message in the same conversation. The
 *   gap between the two is the response time; the mean of those gaps is the
 *   average, and the share that got any reply at all is the rate.
 *
 * Someone with nothing to answer yet has no rate — null, not 0% and not 100%.
 * Both of those would be a claim, and a new account has not earned either.
 */
final class ResponseStats
{
    /**
     * @return array{rate: ?int, hours: ?float, answered: int, prompts: int}
     */
    public static function for(User $user): array
    {
        return self::forMany([$user->id])[$user->id];
    }

    /**
     * The same reading, for a page full of people, in one query.
     *
     * A card list calling for() twelve times is twelve full message scans. The
     * arithmetic below is identical — a user's own messages are pulled out of
     * the shared set and walked exactly as for() walks them — so the two
     * methods cannot drift apart and report different figures for one person.
     *
     * @param  array<int, int>  $userIds
     * @return array<int, array{rate: ?int, hours: ?float, answered: int, prompts: int}> keyed by user id
     */
    public static function forMany(array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        if ($userIds === []) {
            return [];
        }

        $messages = Message::query()
            ->where(fn ($q) => $q->whereIn('recipient_id', $userIds)->orWhereIn('sender_id', $userIds))
            ->orderBy('conversation_id')
            ->orderBy('created_at')
            ->get(['conversation_id', 'sender_id', 'recipient_id', 'created_at']);

        $out = [];

        foreach ($userIds as $id) {
            $out[$id] = self::walk(
                $messages->filter(fn ($m) => (int) $m->sender_id === $id || (int) $m->recipient_id === $id),
                $id
            );
        }

        return $out;
    }

    /**
     * @param  \Illuminate\Support\Collection  $messages  this user's messages, conversation-then-time ordered
     * @return array{rate: ?int, hours: ?float, answered: int, prompts: int}
     */
    private static function walk($messages, int $userId): array
    {
        $prompts = 0;
        $answered = 0;
        $totalHours = 0.0;

        foreach ($messages->groupBy('conversation_id') as $thread) {
            $awaiting = null;   // the inbound message still waiting on a reply

            foreach ($thread as $message) {
                $fromThem = (int) $message->sender_id !== $userId;

                if ($fromThem) {
                    // Only the first of a consecutive run counts as a prompt.
                    if ($awaiting === null) {
                        $awaiting = $message->created_at;
                        $prompts++;
                    }
                    continue;
                }

                if ($awaiting !== null) {
                    $answered++;
                    $totalHours += $awaiting->floatDiffInHours($message->created_at);
                    $awaiting = null;
                }
            }
        }

        return [
            'prompts'  => $prompts,
            'answered' => $answered,
            'rate'     => $prompts > 0 ? (int) round($answered / $prompts * 100) : null,
            'hours'    => $answered > 0 ? round($totalHours / $answered, 1) : null,
        ];
    }

    /**
     * The card-sized version — "Responds in ~2 hrs" — or null.
     *
     * Null rather than a dash, because a card line reading "Responds —" is
     * worse than no line at all.
     */
    public static function brief(?float $hours): ?string
    {
        if ($hours === null) {
            return null;
        }

        if ($hours < 1) {
            return 'Responds in ~' . max(1, (int) round($hours * 60)) . ' min';
        }

        if ($hours < 48) {
            return 'Responds in ~' . (int) round($hours) . ' hr' . (round($hours) == 1 ? '' : 's');
        }

        $days = (int) round($hours / 24);

        return 'Responds in ~' . $days . ' day' . ($days === 1 ? '' : 's');
    }

    /** "1.5 hours" / "2 days" / "—" when there is nothing to go on. */
    public static function describe(?float $hours): string
    {
        if ($hours === null) {
            return '—';
        }

        if ($hours < 1) {
            $minutes = max(1, (int) round($hours * 60));

            return $minutes . ' ' . ($minutes === 1 ? 'minute' : 'minutes');
        }

        if ($hours < 48) {
            return rtrim(rtrim(number_format($hours, 1), '0'), '.') . ' ' . ($hours == 1 ? 'hour' : 'hours');
        }

        $days = round($hours / 24);

        return $days . ' ' . ($days == 1 ? 'day' : 'days');
    }
}
