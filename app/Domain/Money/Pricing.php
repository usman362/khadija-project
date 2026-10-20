<?php

namespace App\Domain\Money;

use App\Models\Event;
use App\Models\User;

/**
 * What a client pays, and why that figure and not another.
 *
 * Sir Peter settled this over two days of chat in October 2026. The figures are
 * in config/payments.php so he can change them; the rules for which figure
 * applies are here, because they are rules rather than numbers.
 *
 * The one he changed his mind about, and it matters: the agreement fee is
 * CLIENT ONLY. He asked for $4.99 from both sides, then withdrew it for
 * providers once the arithmetic was in front of him. A professional already
 * pays membership for exposure and commission on the booking, so a flat fee
 * would be a third charge on the same work. At the break-even of about $100 it
 * costs them more than the 5% they already pay, which lands hardest on exactly
 * the small jobs the marketplace needs.
 */
final class Pricing
{
    /**
     * What this client pays to publish a request.
     *
     * The founder rate, or the regular one once their six months are up or the
     * cohort is full.
     */
    public static function postingFee(?User $client): float
    {
        // Nullable on purpose. The fee wording renders on screens that can be
        // looked at before signing in, and we cannot know somebody is a founder
        // without knowing who they are. Quoting the regular rate to an unknown
        // reader is the safe direction: nobody is promised a discount they turn
        // out not to have.
        return $client && self::isFounder($client)
            ? (float) config('payments.posting_fee_founder', 2.99)
            : (float) config('payments.posting_fee_regular', 4.99);
    }

    /** The agreement fee. Charged to the client, never to the provider. */
    public static function agreementFee(): float
    {
        return (float) config('payments.agreement_fee', 4.99);
    }

    /*
     * There is deliberately no extensionFee() or extensionOptions() here.
     *
     * Rule R33 already owns extensions, with its own day and price ladder, its
     * limit of three paid ones and a free 24-hour grace reopen, and it derives
     * expiry from the client's own proposal_deadline. A second set of figures
     * for one feature is the "two definitions" fault this codebase has just
     * been cleaned of twice. Peter's October numbers conflict with that ladder
     * and wait for him to reconcile them.
     */

    /**
     * Is this client inside the founder offer?
     *
     * Two conditions, both of them his:
     *
     *   They are one of the first N Clients. Counted by the order Clients were
     *   created, so the thousandth to register is the thousandth however many
     *   have posted since.
     *
     *   And they are inside their own six months, counted from their FIRST
     *   PUBLISHED POSTING. Khadijah's point, which he took: a signup clock
     *   burns the window of somebody who registered early and was not ready to
     *   post, which is the opposite of what the offer is for.
     *
     * A client who has never published is a founder with their clock not yet
     * started, which is why the window is open rather than closed when there is
     * no first posting to count from.
     */
    public static function isFounder(?User $client): bool
    {
        if (! $client) {
            return false;
        }

        if (! self::inCohort($client)) {
            return false;
        }

        $first = self::firstPublishedAt($client);

        if ($first === null) {
            // Their clock has not started. The next posting is the one that
            // starts it, and it is charged the founder rate.
            return true;
        }

        return $first->copy()
            ->addMonths((int) config('payments.founder_months', 6))
            ->isFuture();
    }

    /**
     * Their place in the queue, for showing them where they stand.
     *
     * @return array{founder: bool, position: int|null, cohort: int, expires_on: \Illuminate\Support\Carbon|null}
     */
    public static function founderStanding(?User $client): array
    {
        $first = self::firstPublishedAt($client);

        return [
            'founder'    => self::isFounder($client),
            'position'   => self::position($client),
            'cohort'     => (int) config('payments.founder_cohort_size', 1000),
            'expires_on' => $first?->copy()->addMonths((int) config('payments.founder_months', 6)),
        ];
    }

    /** One-based place among Clients by signup order, or null if not a client. */
    public static function position(?User $client): ?int
    {
        if (! $client || ! self::isAClient($client)) {
            return null;
        }

        return self::clients()
            ->where(function ($q) use ($client) {
                $q->where('created_at', '<', $client->created_at)
                    ->orWhere(function ($q) use ($client) {
                        // Same instant: fall back to id, so the order is total
                        // and two clients never share a position.
                        $q->where('created_at', $client->created_at)
                            ->where('id', '<=', $client->id);
                    });
            })
            ->count();
    }

    /**
     * Who counts as a client, and why it is the role rather than the column.
     *
     * This asked `primary_role === 'client'` until 20 October, when a test
     * fixture that assigned the role without setting the column turned out to
     * describe four real accounts: plain clients, client role, primary_role
     * null. They were outside the cohort entirely, so they were quoted the
     * regular rate instead of the founder one and everybody else's position was
     * counted wrong behind them.
     *
     * Measured before changing it: every account with primary_role 'client'
     * also holds the role, with no exceptions, while the role catches four more.
     * So the role is the complete answer and the column is the leaky one.
     */
    private static function isAClient(User $user): bool
    {
        return $user->primary_role === 'client' || $user->hasRole('client');
    }

    /**
     * Every client, by the SAME definition isAClient() uses.
     *
     * Both halves or neither: counting the queue by one rule while deciding
     * membership by another is how somebody ends up in the cohort with no
     * position in it, which is what the first version of this did.
     */
    private static function clients()
    {
        return User::query()->where(function ($q) {
            $q->where('primary_role', 'client')
                ->orWhereHas('roles', fn ($r) => $r->where('name', 'client'));
        });
    }

    private static function inCohort(?User $client): bool
    {
        $position = self::position($client);

        return $position !== null
            && $position <= (int) config('payments.founder_cohort_size', 1000);
    }

    private static function firstPublishedAt(?User $client): ?\Illuminate\Support\Carbon
    {
        if (! $client) {
            return null;
        }

        $at = Event::where('client_id', $client->id)
            ->whereNotNull('published_at')
            ->min('published_at');

        return $at ? \Illuminate\Support\Carbon::parse($at) : null;
    }

    /**
     * What the processor will take, as an estimate only.
     *
     * Never recorded from this. The real figure comes back from the charge and
     * is written as a provider_fee entry, because an estimate and a refunded
     * transaction disagree: Stripe keeps its fee when a payment is refunded, so
     * a refunded $2.99 costs the platform $0.39 rather than nothing. Peter
     * confirmed the platform absorbs that.
     */
    public static function estimatedProviderFee(float $amount): float
    {
        return round(
            $amount * ((float) config('payments.provider_rate_percent', 2.9) / 100)
            + (float) config('payments.provider_fee_fixed', 0.30),
            2,
        );
    }

    /** What is left of a charge after the processor's estimated cut. */
    public static function estimatedNet(float $amount): float
    {
        return round($amount - self::estimatedProviderFee($amount), 2);
    }
}
