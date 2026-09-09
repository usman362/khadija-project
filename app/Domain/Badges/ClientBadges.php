<?php

namespace App\Domain\Badges;

use App\Models\Booking;
use App\Models\Finalization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /** @return array<string, int> the three things the rules are measured against */
    private static function measure(User $client): array
    {
        return [
            'events_completed' => Booking::where('client_id', $client->id)
                ->where('status', 'completed')
                ->count(),

            /*
             * On or before the day the balance was due.
             *
             * Both dates have to be there. An agreement with no due date on it
             * cannot be late, and counting it as paid on time would hand out a
             * badge for a deadline nobody set.
             */
            'paid_on_time' => Finalization::where('client_id', $client->id)
                ->whereNotNull('funded_at')
                ->whereNotNull('balance_due_on')
                ->whereColumn('funded_at', '<=', 'balance_due_on')
                ->count(),

            // Professionals booked more than once — the count is of people,
            // not of bookings, so five jobs with one professional is one.
            'repeat_professional' => DB::table('bookings')
                ->where('client_id', $client->id)
                ->whereNotNull('supplier_id')
                ->groupBy('supplier_id')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('supplier_id')
                ->count(),
        ];
    }
}
