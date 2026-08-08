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
        $messages = Message::query()
            ->where(fn ($q) => $q->where('recipient_id', $user->id)->orWhere('sender_id', $user->id))
            ->orderBy('conversation_id')
            ->orderBy('created_at')
            ->get(['conversation_id', 'sender_id', 'recipient_id', 'created_at']);

        $prompts = 0;
        $answered = 0;
        $totalHours = 0.0;

        foreach ($messages->groupBy('conversation_id') as $thread) {
            $awaiting = null;   // the inbound message still waiting on a reply

            foreach ($thread as $message) {
                $fromThem = (int) $message->sender_id !== $user->id;

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
