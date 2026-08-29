<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * What a client's planning actually looks like, read from their own records.
 *
 * The AI tool pages used to open onto a fabricated wedding — "Sarah & Alex",
 * The Garden Estate, $12,450 remaining — identical for every account. It read
 * as the client's own plan, so a client with nothing booked saw six confirmed
 * vendors, and a client with a real event saw someone else's.
 *
 * This reads the real thing. Where there is no source for a number, there is
 * no number: no invented "Event Health", no invented task count. A client with
 * no event gets `hasEvent() === false` and the page says so plainly instead of
 * borrowing a stranger's plan.
 */
class ClientPlanningSnapshot
{
    private function __construct(
        public readonly ?Event $event,
        public readonly array $vendors,
        public readonly array $budgetLines,
        public readonly float $spent,
        public readonly ?float $budgetTotal,
        public readonly int $prosBooked,
    ) {
    }

    /** Statuses that mean money is actually committed. */
    private const COMMITTED = ['confirmed', 'accepted', 'completed', 'in_progress'];

    /** Statuses that mean the client is still waiting on the pro. */
    private const PENDING = ['requested', 'pending'];

    public static function for(?User $client): self
    {
        if (! $client) {
            return new self(null, [], [], 0.0, null, 0);
        }

        $event = self::nextEvent($client);

        $bookings = Booking::query()
            ->where('client_id', $client->id)
            ->when($event, fn ($q) => $q->where('event_id', $event->id))
            ->whereIn('status', array_merge(self::COMMITTED, self::PENDING))
            ->with(['supplier:id,name', 'supplier.serviceCategories:id,name'])
            ->get();

        $col = self::priceColumn();

        $vendors = $bookings->map(fn (Booking $b) => [
            'name'     => $b->supplier?->name ?: 'Awaiting a professional',
            'service'  => $b->supplier?->serviceCategories->first()?->name ?: 'Service',
            'status'   => in_array($b->status, self::COMMITTED, true) ? 'Confirmed' : 'Pending',
            'amount'   => $col ? (float) ($b->{$col} ?? 0) : 0.0,
            'is_firm'  => in_array($b->status, self::COMMITTED, true),
        ])->values()->all();

        $committed = array_filter($vendors, fn ($v) => $v['is_firm']);
        $spent     = array_sum(array_column($committed, 'amount'));

        // One line per service, so the summary adds up to what was actually spent.
        $lines = [];
        foreach ($committed as $v) {
            $lines[$v['service']] = ($lines[$v['service']] ?? 0) + $v['amount'];
        }
        arsort($lines);

        $suppliers = array_filter(array_map(
            fn (Booking $b) => $b->supplier_id,
            $bookings->filter(fn ($b) => in_array($b->status, self::COMMITTED, true))->all()
        ));

        return new self(
            event:       $event,
            vendors:     $vendors,
            budgetLines: $lines,
            spent:       (float) $spent,
            budgetTotal: self::budgetOf($event),
            prosBooked:  count(array_unique($suppliers)),
        );
    }

    public function hasEvent(): bool
    {
        return $this->event !== null;
    }

    /** Null when the event has no date — never a guessed number of days. */
    public function daysToEvent(): ?int
    {
        $starts = $this->event?->starts_at;

        return $starts ? max(0, Carbon::now()->startOfDay()->diffInDays($starts->copy()->startOfDay(), false)) : null;
    }

    /** Null when the client never set a budget, so nothing claims to be "remaining". */
    public function budgetRemaining(): ?float
    {
        return $this->budgetTotal === null ? null : $this->budgetTotal - $this->spent;
    }

    public function spentPercent(): ?int
    {
        if (! $this->budgetTotal || $this->budgetTotal <= 0) {
            return null;
        }

        return (int) round($this->spent / $this->budgetTotal * 100);
    }

    private static function nextEvent(User $client): ?Event
    {
        $q = Event::query()->where('client_id', $client->id);

        // Prefer the soonest event still ahead; otherwise the most recent one.
        return (clone $q)->whereNotNull('starts_at')->where('starts_at', '>=', now())->orderBy('starts_at')->first()
            ?? $q->latest('id')->first();
    }

    private static function budgetOf(?Event $event): ?float
    {
        if (! $event) {
            return null;
        }

        foreach (['budget', 'budget_max'] as $field) {
            if (! is_null($event->{$field}) && (float) $event->{$field} > 0) {
                return (float) $event->{$field};
            }
        }

        return null;
    }

    private static function priceColumn(): ?string
    {
        foreach (['price', 'total_amount', 'agreed_price'] as $col) {
            if (Schema::hasColumn('bookings', $col)) {
                return $col;
            }
        }

        return null;
    }
}
