<?php

namespace App\Domain\Requests;

use App\Models\Event;
use App\Models\EventExtension;

/**
 * Rule R33 — where a request is in its life, and what the client may do next.
 *
 * Two decisions shape this class.
 *
 * Expiry is DERIVED, not stored. A listing is expired when its deadline has
 * passed and it has not been awarded or closed. A stored `expired` status
 * would need a scheduled job to become true, and would be wrong for everyone
 * who loaded the page between the deadline and the next run — including the
 * professional deciding whether to spend an hour on a proposal.
 *
 * And the paid-extension count is COUNTED from the extension rows rather than
 * kept as a column. A counter and a payment history are two copies of one
 * fact; the day they disagree, a client is either charged for a fourth
 * extension or refused a second.
 */
final class RequestLifecycle
{
    /* ── §1's statuses ── */

    public const DRAFT       = 'draft';
    public const OPEN        = 'open_for_proposals';
    public const EXPIRED     = 'expired';
    public const AWARDED     = 'awarded';
    public const CLOSED      = 'closed';
    public const COMPLETED   = 'completed';
    public const DATE_PASSED = 'date_passed';

    /**
     * §8 — "Open for Bidding" becomes "Open for Proposals", scoped to the
     * listing STATUS TEXT only. The Main Bidding Board, R8's sealed bidding,
     * bid counts and bid analytics keep their names: those describe the
     * mechanism, and renaming them would rename the feature.
     */
    public const LABELS = [
        self::DRAFT       => 'Draft',
        self::OPEN        => 'Open for Proposals',
        self::EXPIRED     => 'Expired',
        self::AWARDED     => 'Booked / Awarded',
        self::CLOSED      => 'Closed',
        self::COMPLETED   => 'Completed',
        self::DATE_PASSED => 'Closed — event date passed',
    ];

    /** §2 — the tiers, in days => dollars. */
    public const TIERS = [3 => 1.99, 7 => 2.99, 14 => 4.99, 30 => 7.99];

    /** §2 — after the third paid extension it is Close or Duplicate only. */
    public const MAX_PAID_EXTENSIONS = 3;

    /** §2 — one free reopen inside this window after the deadline. */
    public const GRACE_HOURS = 24;

    /**
     * Where this request is.
     *
     * Order matters. Closed beats everything because it is the client's own
     * decision; awarded beats expired because §1 says an awarded request
     * bypasses Expired entirely — a professional has been hired and the
     * deadline stopped mattering the moment they were.
     */
    public static function statusFor(Event $event): string
    {
        if ($event->status === 'completed') {
            return self::COMPLETED;
        }

        if ($event->closed_at !== null || $event->status === 'cancelled') {
            return self::CLOSED;
        }

        if (! $event->is_published) {
            return self::DRAFT;
        }

        // §1 — awarded bypasses Expired, independent of the deadline.
        // supplier_id is the fast path (a request fully awarded to one pro);
        // isFullyAwarded also catches the multi-service request whose services
        // went to different pros, which no single supplier_id can name (A10).
        if ($event->supplier_id !== null || $event->isFullyAwarded()) {
            return self::AWARDED;
        }

        // §1 — Expired never survives past the actual event date.
        if ($event->starts_at !== null && $event->starts_at->isPast()) {
            return self::DATE_PASSED;
        }

        if ($event->proposal_deadline !== null && $event->proposal_deadline->isPast()) {
            return self::EXPIRED;
        }

        return self::OPEN;
    }

    public static function label(Event $event): string
    {
        return self::LABELS[self::statusFor($event)] ?? 'Unknown';
    }

    public static function isExpired(Event $event): bool
    {
        return self::statusFor($event) === self::EXPIRED;
    }

    /**
     * §7 — no new proposal can come in while a listing is expired.
     *
     * And none while the client is finalising with somebody, which checklist
     * row 90 caught as a working end-to-end bypass: an SSR already in
     * exclusive negotiation at step 3 of 7 still offered a full six-step bid
     * wizard to another professional, auto-save and computed commission
     * included.
     *
     * The cause is that starting a finalization does not claim the event —
     * `supplier_id` is only stamped when a BOOKING is confirmed, several
     * steps later. So between "you selected Epic Eats" and the signed
     * contract, the request looked wide open to everybody else. Somebody was
     * being invited to price work that was already spoken for.
     */
    public static function acceptsProposals(Event $event): bool
    {
        return self::statusFor($event) === self::OPEN && ! self::inExclusiveNegotiation($event);
    }

    /**
     * Is the client already finalising with a professional?
     *
     * Cancelled finalizations do not count — the client walked away from that
     * one and the request genuinely is open again, which is exactly what the
     * cancel path exists to do.
     */
    public static function inExclusiveNegotiation(Event $event): bool
    {
        return \App\Models\Finalization::where('event_id', $event->id)
            ->where('status', '!=', 'cancelled')
            ->exists();
    }

    /* ── §2 Grace period ── */

    /**
     * The free reopen: within 24 hours of the deadline, once per event.
     *
     * "Once" is counted from the extension rows, so a client who uses it,
     * lets the listing expire again and comes back inside another 24 hours
     * does not get a second free one.
     */
    public static function inGracePeriod(Event $event): bool
    {
        if (! self::isExpired($event) || $event->proposal_deadline === null) {
            return false;
        }

        if ($event->proposal_deadline->diffInHours(now()) >= self::GRACE_HOURS) {
            return false;
        }

        return ! self::graceUsed($event);
    }

    public static function graceUsed(Event $event): bool
    {
        return EventExtension::where('event_id', $event->id)
            ->where('is_grace', true)
            ->where('status', 'completed')
            ->exists();
    }

    /* ── §2 Paid extension ── */

    public static function paidExtensionsUsed(Event $event): int
    {
        return EventExtension::where('event_id', $event->id)
            ->where('is_grace', false)
            ->where('status', 'completed')
            ->count();
    }

    public static function extensionsRemaining(Event $event): int
    {
        return max(0, self::MAX_PAID_EXTENSIONS - self::paidExtensionsUsed($event));
    }

    /**
     * The tiers this event can actually buy.
     *
     * Filtered by §2's hard ceiling: a new deadline can never move past the
     * event date. A 30-day option on an event nine days away is an option
     * that has to be refused after payment, which is the worst place to
     * refuse it — so it is not offered.
     *
     * @return array<int, array{days:int, price:float, new_deadline:\Illuminate\Support\Carbon}>
     */
    public static function extensionOptions(Event $event): array
    {
        if (! self::mayBuyExtension($event)) {
            return [];
        }

        $from    = self::extendFrom($event);
        $ceiling = $event->starts_at;
        $out     = [];

        foreach (self::TIERS as $days => $price) {
            $newDeadline = $from->copy()->addDays($days);

            if ($ceiling !== null && $newDeadline->greaterThan($ceiling)) {
                continue;
            }

            $out[] = ['days' => $days, 'price' => $price, 'new_deadline' => $newDeadline];
        }

        return $out;
    }

    /**
     * Whether a paid extension is available at all.
     *
     * §5 — ER gets none. Its 72-hour eligibility window and sub-5-hour
     * response deadline mean even the 3-day tier lands past the event date
     * essentially every time, so the option is removed rather than offered
     * and then refused.
     */
    public static function mayBuyExtension(Event $event): bool
    {
        return self::isExpired($event)
            && ! self::isEsr($event)
            && self::extensionsRemaining($event) > 0;
    }

    /** §5 — what an expired ER client is offered instead. */
    public static function esrOptions(): array
    {
        return ['close', 'duplicate', 'convert_to_ssr'];
    }

    public static function isEsr(Event $event): bool
    {
        return $event->source === 'esr';
    }

    /**
     * Where a new deadline is measured from.
     *
     * §2 puts the paid counter after the grace period, so extending on day
     * three does not silently eat two of the days paid for. Measuring from
     * the old deadline would have.
     */
    public static function extendFrom(Event $event): \Illuminate\Support\Carbon
    {
        $graceEnds = $event->proposal_deadline?->copy()->addHours(self::GRACE_HOURS);

        return $graceEnds !== null && $graceEnds->isFuture() ? $graceEnds : now();
    }

    /* ── §2 Search ranking ── */

    public const RANK_NEW_TODAY = 0;
    public const RANK_REOPENED  = 1;
    public const RANK_OLDER     = 2;

    /**
     * §2 — "Published Today → Extended Event → Older Active Events".
     *
     * A reopened listing is never boosted to the very top, which is the whole
     * point: without this, paying repeatedly would buy permanent first place.
     */
    public static function rankBucket(Event $event): int
    {
        if ($event->published_at !== null && $event->published_at->isToday()) {
            return self::RANK_NEW_TODAY;
        }

        if ($event->reopened_at !== null) {
            return self::RANK_REOPENED;
        }

        return self::RANK_OLDER;
    }

    /* ── §3 Editing an expired event ── */

    /**
     * Fields whose change makes an existing proposal worth re-reading.
     *
     * The list is §3's, and the distinction is the point: a client fixing a
     * typo in the title should not fire a notice at eight professionals,
     * while a client moving the date or halving the budget must.
     */
    public const MAJOR_FIELDS = [
        'budget', 'budget_min', 'budget_max',
        'starts_at', 'ends_at',
        'location', 'venue', 'guest_count',
        'category_id',
    ];

    /** Which of the changed attributes are material to a proposal? */
    public static function majorChanges(array $dirty): array
    {
        return array_values(array_intersect(array_keys($dirty), self::MAJOR_FIELDS));
    }

    /* ── §1 and §4 MSR per-service ── */

    /**
     * Services on this request that already have a professional (R12).
     *
     * Awarding on an MSR is per service line, so the unawarded lines carry on
     * through the normal expire/extend flow while the awarded ones are done.
     * Read from accepted bids, which is where the category lives — a booking
     * records who and how much, not which service line it settles.
     *
     * @return array<int, int> category ids
     */
    public static function awardedServiceIds(Event $event): array
    {
        return $event->bids()
            ->where('status', 'accepted')
            ->whereNotNull('category_id')
            ->pluck('category_id')
            ->unique()
            ->values()
            ->all();
    }

    /** Service lines still looking for someone. */
    public static function openServiceIds(Event $event): array
    {
        $requested = $event->categories()->pluck('categories.id')->all();

        if ($requested === [] && $event->category_id) {
            $requested = [$event->category_id];
        }

        return array_values(array_diff($requested, self::awardedServiceIds($event)));
    }

    /**
     * Is every requested service awarded?
     *
     * An MSR with three services and one professional hired is not awarded —
     * it is one third awarded, and the other two lines are still expiring.
     */
    public static function fullyAwarded(Event $event): bool
    {
        return self::awardedServiceIds($event) !== [] && self::openServiceIds($event) === [];
    }
}
