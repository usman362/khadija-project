<?php

namespace App\Domain\Money;

use App\Models\Event;
use App\Models\LedgerEntry;

/**
 * The posting fee, recorded where it is actually owed.
 *
 * Sir Peter's B1 decision, 22 August 2026: the fee is charged at publish or
 * final submission, not at finalisation, and drafts are free. It was never
 * built, because A1 listed the same fee as a ledger item to design first, so
 * the two items overlapped and both waited. A1 was approved on 16 October, so
 * this is the half that was waiting.
 *
 * There are five places an event can be published from: the BSR wizard, ESR,
 * the event controller, the virtual hub and Post an Event. A fee charged in
 * five places is a fee charged twice, so this is the one door they all go
 * through.
 *
 * What this does NOT do is move money. It records what is owed, as a
 * platform_fee ledger entry. Taking it is the gateway's job and PaymentGuard
 * still decides whether a charge may happen at all. Recording first is
 * deliberate: a fee that was owed and never collected is a thing the platform
 * should be able to see, not something that vanishes because the card failed.
 */
final class PostingFee
{
    /**
     * Record the fee for a published request.
     *
     * Idempotent, which the lifecycle document asks for by name: publishing
     * twice, a retried request, or a repost that reuses this code path must
     * never produce two fees for one posting. The entry is keyed to the event,
     * so the second call finds the first and returns it.
     */
    public static function recordFor(Event $event): ?LedgerEntry
    {
        $client = $event->client;

        if (! $client) {
            return null;
        }

        // Scoped to the POSTING fee, not any platform fee. An extension is
        // also a platform_fee on the same event, and matching on the type
        // alone would make the first extension look like a posting already
        // charged, so a later republish would never be billed.
        $existing = self::postingFees($event)->first();

        if ($existing) {
            return $existing;
        }

        $amount = Pricing::postingFee($client);

        if ($amount <= 0) {
            return null;
        }

        return Ledger::record(
            LedgerEntry::PLATFORM_FEE,
            $amount,
            '+',
            party: $client,
            event: $event,
            state: LedgerEntry::PENDING,
            metadata: [
                'reason'  => 'posting',
                // Which price applied, and why, so a client asking "why $4.99"
                // can be answered from the record rather than recomputed
                // against rules that may have changed since.
                'founder' => Pricing::isFounder($client),
            ],
        );
    }

    /** Has the fee for this posting been settled? */
    public static function isPaid(Event $event): bool
    {
        return self::postingFees($event)
            ->whereIn('state', [LedgerEntry::SECURED, LedgerEntry::RELEASED])
            ->exists();
    }

    /**
     * What is owed on this posting, including any extensions.
     *
     * Deliberately wider than isPaid(): a client who extended and has not paid
     * for it owes something, even though the posting fee itself was settled.
     */
    public static function outstanding(Event $event): float
    {
        return (float) LedgerEntry::where('event_id', $event->id)
            ->where('entry_type', LedgerEntry::PLATFORM_FEE)
            ->where('state', LedgerEntry::PENDING)
            ->whereNull('reverses_id')
            ->sum('amount');
    }

    /** Posting fees for this event, as opposed to extensions. */
    private static function postingFees(Event $event)
    {
        return LedgerEntry::where('event_id', $event->id)
            ->where('entry_type', LedgerEntry::PLATFORM_FEE)
            ->whereNull('reverses_id')
            ->where(function ($q) {
                $q->whereJsonContains('metadata->reason', 'posting')
                    // Rows written before the reason was recorded.
                    ->orWhereNull('metadata');
            });
    }

    /**
     * Put an extension's charge in the ledger.
     *
     * The amount and the rules belong to Rule R33, which already owns
     * extensions: its own day/price ladder, a limit of three paid ones, and a
     * free 24-hour grace reopen. This only records what R33 decided, because a
     * second price list is how one feature comes to disagree with itself.
     *
     * Peter's October figures, a flat $1.99 for 7, 14 or 21 days, conflict with
     * that ladder and are not implemented here. He has to reconcile them with
     * R33 first.
     */
    public static function recordExtension(Event $event, int $days, float $amount): LedgerEntry
    {
        return Ledger::record(
            LedgerEntry::PLATFORM_FEE,
            $amount,
            '+',
            party: $event->client,
            event: $event,
            state: LedgerEntry::PENDING,
            metadata: ['reason' => 'extension', 'days' => $days],
        );
    }
}
