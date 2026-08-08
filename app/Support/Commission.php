<?php

namespace App\Support;

use App\Models\User;

/**
 * Platform commission, per the canonical money model.
 *
 * Every workflow doc states the same rule: commission is taken ONCE, at payout,
 * and is absorbed by the professional — Starter 5% / Pro 3% / Elite 1.5%. The
 * $2.99 sale fee is packages-only and never applies to a won bid.
 *
 * The rate used to live inline in the bidding board controller, so the bid form
 * previewed a net the payout screens never applied. One source now, so a
 * preview and a payout can't disagree.
 */
final class Commission
{
    /** Plan slug → commission percentage. Slugs are the DB values, not labels. */
    private const RATES = [
        'starter'      => 5.0,   // "Starter"
        'professional' => 3.0,   // "Pro"
        'enterprise'   => 1.5,   // "Elite"
    ];

    public const DEFAULT_RATE = 5.0;

    /** Commission percentage this user pays at payout. */
    public static function rateFor(?User $user): float
    {
        $slug = $user?->activeSubscription()?->plan?->slug;

        return self::RATES[$slug] ?? self::DEFAULT_RATE;
    }

    /** The commission itself on a gross amount. */
    public static function on(float $gross, ?User $user): float
    {
        return round($gross * (self::rateFor($user) / 100), 2);
    }

    /** What the professional actually receives: gross minus commission. */
    public static function netOf(float $gross, ?User $user): float
    {
        return round($gross - self::on($gross, $user), 2);
    }

    /**
     * The same gross seen from all three memberships — Rule R61, 2026-08-07.
     *
     * Direct Offers have no membership gate: Starter, Pro and Elite all
     * receive them. Elite is worth something because of what it keeps, not
     * because of what it is shown, so the offer carries the ladder and marks
     * the row the viewer is on.
     *
     * Nothing here is estimated — it is this offer's own amount at the three
     * published rates.
     *
     * @return list<array{slug:string, label:string, rate:float, net:float, current:bool}>
     */
    public static function ladderFor(float $gross, ?User $user = null): array
    {
        $mine = $user?->activeSubscription()?->plan?->slug;
        $labels = ['starter' => 'Starter', 'professional' => 'Pro', 'enterprise' => 'Elite'];

        $ladder = [];
        foreach (self::RATES as $slug => $rate) {
            $ladder[] = [
                'slug'    => $slug,
                'label'   => $labels[$slug],
                'rate'    => $rate,
                'net'     => round($gross - round($gross * ($rate / 100), 2), 2),
                // A pro with no active subscription is on Starter terms, and
                // the ladder should say so rather than highlight nothing.
                'current' => $slug === ($mine ?? 'starter'),
            ];
        }

        return $ladder;
    }
}
