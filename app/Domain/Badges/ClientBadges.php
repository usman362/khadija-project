<?php

namespace App\Domain\Badges;

use App\Models\Booking;
use App\Models\Finalization;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Which badges a client has actually earned.
 *
 * Counted from the record every time rather than stored on the account. A
 * badge is a statement about what somebody did, and the doing is already
 * written down — keeping a second copy of it in a column creates the one
 * failure this must not have: a badge that stays after the thing that earned
 * it was cancelled, refunded or reversed.
 *
 * This is also why nothing here can be granted by hand. There is no "award"
 * method, and no seeder can stamp one — which is the mistake the verification
 * badges made, where a seeded timestamp put a licence badge on ten profiles
 * that had never uploaded a document.
 *
 * The four badges and their rules are Sir Peter's PM-14 spec (Sep 5).
 */
final class ClientBadges
{
    /**
     * @return Collection<int, array{key:string,name:string,blurb:string,icon:string,progress:int,need:int}>
     */
    public static function earnedBy(User $client): Collection
    {
        $measured = self::measure($client);

        return collect(config('badges.client', []))
            ->filter(fn (array $b) => ($measured[$b['rule']] ?? 0) >= $b['need'])
            ->map(fn (array $b) => $b + ['progress' => $measured[$b['rule']] ?? 0])
            ->values();
    }

    /**
     * Everything on offer, earned or not, with how far along they are.
     *
     * A client who can see what is left to do has a reason to come back; one
     * who can only see an empty panel has been told they have nothing.
     */
    public static function progressFor(User $client): Collection
    {
        $measured = self::measure($client);

        return collect(config('badges.client', []))
            ->map(fn (array $b) => $b + [
                'progress' => min($measured[$b['rule']] ?? 0, $b['need']),
                'earned'   => ($measured[$b['rule']] ?? 0) >= $b['need'],
            ])
            ->values();
    }

    /** @return array<string, int> the things the rules are measured against */
    private static function measure(User $client): array
    {
        return [
            /*
             * Verified Client: "automatic on ID verification completion".
             * Completion is the team approving the ID the client uploaded
             * (ClientVerificationController). Not approximated with email or
             * address checks: that would put a "Verified" label on something
             * that was never verified.
             */
            'id_verified' => \App\Models\UserProfile::where('user_id', $client->id)
                ->whereNotNull('identity_verified_at')
                ->exists() ? 1 : 0,

            // Frequent Planner: completed EVENTS, not bookings. Three
            // professionals at one wedding are one event.
            'events_completed' => Booking::where('client_id', $client->id)
                ->where('status', 'completed')
                ->distinct()
                ->count('event_id'),

            'prompt_payer' => self::promptPayer($client),

            // Community Voice: reviews this client has submitted.
            'reviews_written' => Review::where('reviewer_id', $client->id)->count(),
        ];
    }

    /**
     * Prompt Payer: "re-evaluated monthly, revoked if a late/failed payment
     * occurs, not permanent". Held while the client has paid on time at least
     * once and nothing fell due in the last month without being paid on time.
     * A late payment older than a month no longer counts against them.
     */
    private static function promptPayer(User $client): int
    {
        $onTime = Finalization::where('client_id', $client->id)
            ->whereNotNull('funded_at')
            ->whereNotNull('balance_due_on')
            ->whereColumn('funded_at', '<=', 'balance_due_on')
            ->exists();

        if (! $onTime) {
            return 0;
        }

        $lateThisMonth = Finalization::where('client_id', $client->id)
            ->whereNotNull('balance_due_on')
            ->whereBetween('balance_due_on', [now()->subMonth()->startOfDay(), now()->endOfDay()])
            ->where(fn ($q) => $q->whereNull('funded_at')->orWhereColumn('funded_at', '>', 'balance_due_on'))
            ->exists();

        return $lateThisMonth ? 0 : 1;
    }
}
