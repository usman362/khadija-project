<?php

namespace App\Domain\Money;

use App\Models\User;
use App\Support\Commission;

/**
 * Every fee on GigResource, in one list.
 *
 * This exists because the same figure went stale in three separate places
 * inside one month. "$0 to post. You only pay a single $2.99" was written by
 * hand into the Review & Submit partial, into config/request-help.php for all
 * four request rails, and into the landing page's database row. Each had to be
 * found and corrected on its own, twice by Peter noticing it on screen rather
 * than by anybody reading the code.
 *
 * So the fees are described once, here, and every screen that quotes one reads
 * it from this list. Peter's own instruction on 20 October: "Maybe we should
 * add a webpage named 'pricing and rates'", and a page like that is only honest
 * if it and the forms cannot disagree.
 *
 * What a row carries beyond its amount:
 *
 *   `audience` — who pays it. A provider fee and a client fee sitting in one
 *     undifferentiated list is how somebody ends up quoted the other side's
 *     price, and Peter changed his mind once already about whether providers
 *     pay the agreement fee.
 *   `refundable` — his rule of 20 October: "No refunds or credits ... for their
 *     reposts / extension posts, or changes their minds ... this needs to be
 *     added everywhere." Carried per fee rather than written into each page,
 *     because that sentence is the next one to go stale otherwise.
 */
final class FeeSchedule
{
    public const CLIENT   = 'client';
    public const PROVIDER = 'provider';

    /**
     * Every fee, in the order a person meets them.
     *
     * @return list<array{
     *     key: string, label: string, amount: float, formatted: string,
     *     audience: string, when: string, refundable: bool, note: ?string
     * }>
     */
    public static function all(?User $viewer = null): array
    {
        $rows = [
            [
                'key'        => 'posting',
                'label'      => 'Post a request',
                'amount'     => Pricing::postingFee($viewer),
                'audience'   => self::CLIENT,
                'when'       => 'Charged when your request goes out to professionals. Drafts are free.',
                'refundable' => false,
                // Only when it IS a different rate. Both are $2.99 since 20
                // October, and telling somebody they have a discount that
                // everybody has is worse than saying nothing.
                'note'       => self::founderRateDiffers() && $viewer && Pricing::isFounder($viewer)
                    ? 'You are on the founder rate.'
                    : null,
            ],
            [
                'key'        => 'agreement',
                'label'      => 'Agreement fee',
                'amount'     => Pricing::agreementFee(),
                'audience'   => self::CLIENT,
                'when'       => 'Charged once, when you finalize with a professional or buy a package.',
                'refundable' => false,
                'note'       => 'Nothing is charged if your request goes unfilled.',
            ],
            [
                'key'        => 'request_extension',
                'label'      => 'Extend a request',
                'amount'     => (float) config('payments.request_extension_fee', 1.99),
                'audience'   => self::CLIENT,
                'when'       => 'Per extension, for ' . self::days('payments.request_extension_days') . ' more days.',
                'refundable' => false,
                'note'       => 'Your existing proposals stay where they are.',
            ],
            [
                'key'        => 'repost',
                'label'      => 'Repost a request',
                'amount'     => (float) config('payments.repost_fee', 2.99),
                'audience'   => self::CLIENT,
                // His lifecycle document is explicit, and it is the one thing
                // in this list that is free, so it says so here rather than
                // leaving somebody to assume an edit is chargeable.
                'when'       => 'A repost is a new posting. Editing a request that is still live is free.',
                'refundable' => false,
                'note'       => null,
            ],
            [
                'key'        => 'package_posting',
                'label'      => 'Post a package',
                'amount'     => (float) config('payments.package_posting_fee', 2.99),
                'audience'   => self::PROVIDER,
                'when'       => 'Charged when a new package of services goes live.',
                'refundable' => false,
                'note'       => null,
            ],
            [
                'key'        => 'package_agreement',
                'label'      => 'Package agreement fee',
                'amount'     => self::packageAgreementFee($viewer),
                'audience'   => self::PROVIDER,
                'when'       => 'Charged when a client books your package. The greater of the flat fee and your commission rate.',
                'refundable' => false,
                'note'       => $viewer
                    ? 'Your rate is ' . rtrim(rtrim(number_format(Commission::rateFor($viewer), 2), '0'), '.') . '%.'
                    : null,
            ],
            [
                'key'        => 'package_extension',
                'label'      => 'Extend a package',
                'amount'     => (float) config('payments.package_extension_fee', 1.99),
                'audience'   => self::PROVIDER,
                'when'       => 'Per extension, for ' . self::days('payments.package_extension_days') . ' more days.',
                'refundable' => false,
                'note'       => null,
            ],
            [
                'key'        => 'package_repost',
                'label'      => 'Repost a package',
                'amount'     => (float) config('payments.package_repost_fee', 2.99),
                'audience'   => self::PROVIDER,
                'when'       => 'A repost is a new package listing.',
                'refundable' => false,
                'note'       => null,
            ],
        ];

        return array_map(fn (array $row) => $row + [
            'formatted' => '$' . number_format($row['amount'], 2),
        ], $rows);
    }

    /** Just the fees one side of the marketplace pays. */
    public static function forAudience(string $audience, ?User $viewer = null): array
    {
        return array_values(array_filter(
            self::all($viewer),
            fn (array $row) => $row['audience'] === $audience,
        ));
    }

    /** One fee by key, for a screen quoting a single figure. */
    public static function find(string $key, ?User $viewer = null): ?array
    {
        foreach (self::all($viewer) as $row) {
            if ($row['key'] === $key) {
                return $row;
            }
        }

        return null;
    }

    /**
     * The wording for Peter's refund rule, in one sentence.
     *
     * 20 October: "No refunds or credits for if the user cancels their monthly
     * plans, especially for their reposts/extension posts, or changes their
     * minds ... this needs to be added everywhere in the certain webpages too."
     *
     * Said once so that "everywhere" stays the same sentence everywhere.
     */
    public static function refundPolicy(): string
    {
        return 'All fees are charged once and are not refundable. '
            . 'Cancelling a plan, reposting, extending, or changing your mind does not produce a refund or a credit.';
    }

    /**
     * The package agreement fee: the greater of the flat fee and their rate.
     *
     * "The applicable percentage" from Peter's message of 20 October, read as
     * the provider's own tier rate, matching the Founding Starter Plan's "$4.99
     * or 3% of the booking, whichever is higher". Without a booking in front of
     * it the percentage has nothing to apply to, so the flat figure is what a
     * schedule can honestly quote; the real charge is worked out per booking.
     */
    private static function packageAgreementFee(?User $viewer): float
    {
        return (float) config('payments.package_agreement_fee', 4.99);
    }

    /** Is the founder posting rate actually cheaper than the regular one? */
    private static function founderRateDiffers(): bool
    {
        return (float) config('payments.posting_fee_founder', 2.99)
            < (float) config('payments.posting_fee_regular', 2.99);
    }

    /** "30, 60 or 90" from a list of day counts. */
    private static function days(string $key): string
    {
        $days = (array) config($key, []);

        if ($days === []) {
            return 'more';
        }

        $last = array_pop($days);

        return $days === [] ? (string) $last : implode(', ', $days) . ' or ' . $last;
    }
}
