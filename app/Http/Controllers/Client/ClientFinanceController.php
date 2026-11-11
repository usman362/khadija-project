<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Client-side finance views — Payments (transaction ledger) and Earnings
 * (project-level financial dashboard). For an event-planner client these
 * track money flowing OUT to vendors via escrow / Stripe, plus W-9 / 1099
 * tax-compliance status.
 *
 * DATA NOTE: real booking amounts drive the figures. The escrow/Stripe
 * split, gateway routing, and 1099 thresholds are derived heuristics until
 * the Stripe Connect sandbox is wired — every such value is commented so
 * the next pass knows what to replace with live payment-provider data.
 *
 * Routes:
 *   GET /client/payments  → payments()
 *   GET /client/spending  → spending()
 */
class ClientFinanceController extends Controller
{
    /** Statuses a booking can actually hold — anything else is ignored, not queried. */
    private const SPEND_STATUSES = ['requested', 'pending', 'confirmed', 'accepted', 'in_progress', 'completed', 'cancelled', 'declined'];

    /**
     * The column a booking's money actually lives in.
     *
     * This used to look for `total_amount`, then `agreed_price`, then give up.
     * Neither column has ever existed on `bookings` — the agreed figure is in
     * `price`. So the lookup returned null every time, every sum was skipped,
     * and Payments and Spending reported $0 across the board while all 62
     * bookings in the database carried a price.
     *
     * `price` is checked first and is the real answer; the two legacy names
     * are kept behind it only so an older schema still resolves.
     */
    /**
     * What the client asked for, cleaned up.
     *
     * Every value is echoed back into the form, so a filter that found nothing
     * still shows what was searched for — a page that silently forgets the
     * search is how you conclude the filter is broken.
     *
     * @return array{q: ?string, status: ?string, from: ?string, to: ?string}
     */
    /**
     * Which event the money pages are showing, and everything they could show.
     *
     * Only the client's own events: passing somebody else's id must select
     * nothing rather than reveal that it exists.
     *
     * @return array{selected: ?\App\Models\Event, events: \Illuminate\Support\Collection}
     */
    private function eventScope(Request $request): array
    {
        $user = $request->user();

        $events = Event::where('client_id', $user->id)
            ->orderByRaw('starts_at IS NULL, starts_at DESC')
            ->get(['id', 'title', 'starts_at', 'location']);

        $chosen = (int) $request->query('event', 0);

        return [
            'selected' => $chosen ? $events->firstWhere('id', $chosen) : null,
            'events'   => $events,
        ];
    }

    private function filters(Request $request): array
    {
        $status = (string) $request->query('status', '');
        $from   = (string) $request->query('from', '');
        $to     = (string) $request->query('to', '');

        // A range entered backwards is a typo, not an empty result set.
        if ($from !== '' && $to !== '' && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'q'      => trim((string) $request->query('q', '')) ?: null,
            'status' => in_array($status, self::SPEND_STATUSES, true) ? $status : null,
            'from'   => $from !== '' ? $from : null,
            'to'     => $to !== '' ? $to : null,
        ];
    }

    /**
     * The column a booking's agreed figure lives in.
     *
     * This used to try `price`, then `total_amount`, then `agreed_price`, and
     * return null if it found none — at which point every figure on both money
     * pages quietly became 0.0. Neither of the other two has ever existed on
     * bookings; they are the names that were guessed at while this was being
     * written, and the guard is what made that guessing survivable.
     *
     * Kept as one place that names the column, because two pages and a trend
     * read it, but it no longer answers for the database. If `price` is ever
     * renamed, these pages should break loudly rather than tell a client they
     * have spent nothing. ClientTotals says the same thing in its own words:
     * reading a column that does not exist "is how every finance page once
     * read $0".
     */
    private function priceColumn(): string
    {
        return 'price';
    }

    /**
     * Money the client owes/has paid — across every booking, or within one event.
     *
     * The event argument is not decoration. Both money pages carried a bar
     * naming ONE event above figures that covered EVERY booking, so a client
     * read "$5,080" as that event's total when the event might not have a
     * single row in the ledger below it.
     */
    private function spend(int $userId, ?int $eventId = null): array
    {
        $col  = $this->priceColumn();
        $base = Booking::where('client_id', $userId)
            // Issue #117: the same rule as ClientTotals and the lists below.
            ->notSelfSupplied()
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId));

        $amount = fn ($status) => (float) (clone $base)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->sum($col);

        $total     = $amount(null);
        $settled   = $amount('completed');
        $inEscrow  = $amount('confirmed');   // confirmed but not released
        $pending   = $amount('requested');   // awaiting confirmation

        /*
         * The deposit, which these four figures could not see.
         *
         * Each of them buckets a booking's WHOLE price by the booking's
         * status, and that was right while GigResource took no money: a
         * confirmed booking meant a price agreed and nothing paid. It takes a
         * deposit at the end of the agreement wizard now, so a client could
         * pay $500 and read "Paid $0" with the entire $2,000 sitting under
         * "Agreed, not yet paid" on the page they check before paying again.
         *
         * Read from the ledger rather than from Payments, because the ledger
         * is where the money is now recorded and it already distinguishes
         * money the gateway has (SECURED) from money merely asked for.
         *
         * Only on bookings that are not completed. A completed booking's full
         * price is already inside `settled`, so adding its deposit would count
         * the same money twice.
         *
         * And not on cancelled or declined ones. Their price is deliberately
         * outside every total here — ClientTotals::VOID_STATUSES, so that a
         * cancelled booking is not presented as money still to spend — and
         * counting the deposit from one would put money into "paid" against an
         * agreement nobody is holding, so Paid could exceed the total agreed.
         * What happened to a deposit on a cancelled booking is a refund
         * question, and it has its own place to be answered.
         */
        $depositsOnOpenBookings = (float) \App\Models\LedgerEntry::query()
            ->where('party_user_id', $userId)
            ->where('entry_type', \App\Models\LedgerEntry::BOOKING_CHARGE)
            ->where('state', \App\Models\LedgerEntry::SECURED)
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->whereHas('booking', fn ($q) => $q
                ->where('status', '!=', 'completed')
                ->whereNotIn('status', \App\Domain\Finance\ClientTotals::VOID_STATUSES)
                ->notSelfSupplied())
            ->sum('amount');

        $settled  = round($settled + $depositsOnOpenBookings, 2);
        // What is still owed on those bookings, and no longer their whole price.
        $inEscrow = round(max(0, $inEscrow - $depositsOnOpenBookings), 2);

        return compact('total', 'settled', 'inEscrow', 'pending', 'col');
    }

    public function payments(Request $request): View
    {
        $user = $request->user();

        // The bar above the ledger is a filter now, not a caption.
        ['selected' => $activeEvent, 'events' => $myEvents] = $this->eventScope($request);

        $s = $this->spend($user->id, $activeEvent?->id);

        /*
         * Transaction ledger — every booking is one "transaction" row.
         *
         * Issue #117: this listed the client as her own professional. The
         * guard has existed in PaymentTracker since Issue #51 and in its own
         * query only, so the record disappeared from Bookings and stayed
         * here and on Spending.
         */
        $query = Booking::where('client_id', $user->id)
            ->notSelfSupplied()
            ->with(['event:id,title,starts_at,location', 'supplier:id,name,avatar', 'supplier.profile:id,user_id,headline'])
            ->when($activeEvent, fn ($qr) => $qr->where('event_id', $activeEvent->id))
            ->latest();

        if ($request->filled('search')) {
            $q = $request->string('search')->toString();
            $query->where(fn ($qq) => $qq
                ->whereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$q}%"))
                ->orWhereHas('event', fn ($eq) => $eq->where('title', 'like', "%{$q}%")));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $transactions = $query->paginate(6)->withQueryString();

        /*
         * Only figures that come from a booking row.
         *
         * These cards used to carry "IRS 1099 Liability" (total x 0.27), "Net
         * Cash Position" (total x 0.20), "Stripe Outflow" (settled x 0.55),
         * processing fees at 2.9%, and a 62/38 gateway split — every one of
         * them a percentage applied to a real total and then shown to the
         * client as a figure. A source comment called them placeholders
         * pending the Stripe Connect sandbox, but the comment was in the file
         * and the numbers were on the screen. The 1099 line was the worst of
         * them: a tax figure nobody calculated, presented to someone who might
         * act on it at tax time. There is also no Stripe Connect integration
         * to be pending — neither professionals nor influencers are paid
         * through one.
         *
         * What is left is the Owner's own three states, which the booking
         * status already distinguishes: agreed but not yet paid, actually
         * paid, and still awaiting the professional's confirmation. A budget
         * is a plan; only these are money.
         */
        /*
         * OA-146: total_agreed was $s['total'], which sums EVERY booking —
         * cancelled ones included. Bookings and Reports excluded them, so the
         * same account read $80 on two pages and $5,080 on these two, and the
         * cancelled $5,000 was shown as money still to spend.
         *
         * One calculation now, shared with Bookings. Cancelled money keeps its
         * own figure so a page can show it on purpose.
         */
        // Scoped to the chosen event, the same as everything else on the page.
        $scope = $activeEvent?->id;

        $stats = [
            'agreed_unpaid' => $s['inEscrow'],   // confirmed — a price both sides accepted
            'paid'          => $s['settled'],    // completed — money that actually moved
            'awaiting'      => $s['pending'],    // requested — not yet accepted
            'total_agreed'  => \App\Domain\Finance\ClientTotals::agreed($user, $scope),
            'cancelled'     => \App\Domain\Finance\ClientTotals::cancelled($user, $scope),
        ];

        return view('client.finance.payments', compact(
            'stats', 'transactions', 'activeEvent', 'myEvents'
        ));
    }

    /**
     * One transaction, on its own page.
     *
     * The ledger row carried an amount and a status. It could not say what the
     * money was for, whether any of it had actually moved, or what had to
     * happen next — so the client was looking at a figure with no account of
     * it, and "Transaction detail" stayed open in the audit.
     *
     * It was written when no payment provider was wired to this app, so every
     * line on it said money had not moved. Stripe is wired now, a deposit is
     * really taken at the end of the agreement wizard, and the ledger records
     * both it and what is still owed. A page that goes on saying "it has not
     * been collected" after taking somebody's deposit is worse than one that
     * says nothing, so the ledger is read here and the booking's status is
     * only the fallback for the bookings that have no entries.
     *
     * Test mode is carried through rather than hidden. A deposit paid with a
     * test card is a real row in every table, and the one thing that makes it
     * not a payment is the mode it was taken in.
     */
    public function transaction(Request $request, Booking $booking): View
    {
        abort_unless($booking->client_id === $request->user()->id, 403);

        $booking->load([
            'event:id,title,starts_at,location,guest_count',
            // public_id is listed because a narrowed eager load silently
            // omits it — the chat panel rendered a blank reference for exactly
            // this reason, and a blank field reads as "not built".
            'supplier:id,name,avatar,public_id',
            'supplier.profile:id,user_id,headline,company_name,city,state',
        ]);

        /*
         * What the ledger actually holds for this booking.
         *
         * Two kinds of row, told apart by state rather than by amount, because
         * the amounts can be equal: SECURED is money the gateway has, PENDING
         * is money asked for. The platform fee is listed apart from both, since
         * it is owed to GigResource and not to the professional, and adding it
         * to either would overstate what the client owes.
         */
        $entries = \App\Domain\Money\Ledger::forBooking($booking);
        $charges = $entries->where('entry_type', \App\Models\LedgerEntry::BOOKING_CHARGE);

        $received = round((float) $charges->where('state', \App\Models\LedgerEntry::SECURED)->sum('amount'), 2);
        $stillOwed = round((float) $charges->where('state', \App\Models\LedgerEntry::PENDING)->sum('amount'), 2);
        $platformFee = round((float) $entries->where('entry_type', \App\Models\LedgerEntry::PLATFORM_FEE)->sum('amount'), 2);

        // Was any of it taken with a test card? One row saying so is enough:
        // a booking is funded in one mode, not a mixture.
        $testMode = $entries->contains(fn ($e) => ($e->metadata['mode'] ?? null) === 'test');

        $ledger = [
            'has'      => $entries->isNotEmpty(),
            'total'    => \App\Domain\Money\Ledger::bookingTotal($booking),
            'received' => $received,
            'owed'     => $stillOwed,
            'fee'      => $platformFee,
            'test'     => $testMode,
            'entries'  => $entries,
        ];

        // The status history IS the money timeline — every transition was
        // written by whoever made it, so nothing has to be reconstructed.
        $history = $booking->agreementLogs()
            ->with('changer:id,name')
            ->orderBy('id')
            ->get();

        /*
         * The one honest statement about money for each state.
         *
         * `completed` is the only state in which anything has been paid, and
         * even there the app did not process it — so it is described as
         * settled between the two parties, not as a payment this platform took.
         */
        $money = match ($booking->status) {
            'requested' => [
                'label'  => 'Not agreed yet',
                'tone'   => 'amber',
                'line'   => 'A price has been proposed. Nothing is owed until the professional accepts.',
                'moved'  => false,
            ],
            'confirmed' => [
                'label'  => $received > 0 ? 'Deposit received' : 'Agreed, not yet paid',
                'tone'   => 'indigo',
                'line'   => $received > 0
                    ? 'Both sides have agreed this amount. The deposit has been taken and the balance is still to come.'
                    : 'Both sides have agreed this amount. Nothing has been collected on it yet.',
                'moved'  => $received > 0,
            ],
            'completed' => [
                'label'  => 'Settled',
                'tone'   => 'green',
                'line'   => $received > 0
                    ? 'The work is marked delivered. The deposit was taken here and the rest was settled between you and the professional.'
                    : 'The work is marked delivered and this amount was settled between you and the professional.',
                'moved'  => true,
            ],
            'cancelled' => [
                'label'  => 'Cancelled',
                'tone'   => 'slate',
                'line'   => 'This booking was cancelled. Nothing is owed on it.',
                'moved'  => false,
            ],
            default => [
                'label'  => ucfirst((string) $booking->status),
                'tone'   => 'slate',
                'line'   => 'No amount is due on this booking.',
                'moved'  => false,
            ],
        };

        return view('client.finance.transaction', compact('booking', 'history', 'money', 'ledger'));
    }

    public function spending(Request $request): View
    {
        $user = $request->user();

        // Same bar, same fix: it names an event, so it scopes to that event.
        ['selected' => $activeEvent, 'events' => $myEvents] = $this->eventScope($request);

        $s = $this->spend($user->id, $activeEvent?->id);

        /*
         * Itemized vendor expense matrix, filtered.
         *
         * The toolbar above it — a search box and two buttons — was decoration:
         * neither button had a form or a handler, and nothing on this page ever
         * read a query parameter. A client with forty bookings could see them
         * only eight at a time, in one order, with a search box that did
         * nothing when they typed in it.
         */
        $filters = $this->filters($request);

        $query = Booking::where('client_id', $user->id)
            // Issue #117, same rule as the ledger and the totals.
            ->notSelfSupplied()
            ->when($activeEvent, fn ($qr) => $qr->where('event_id', $activeEvent->id))
            ->with(['event:id,title', 'supplier:id,name,avatar', 'supplier.profile:id,user_id,headline'])
            ->when($filters['q'], function ($qr) use ($filters) {
                $term = '%' . $filters['q'] . '%';

                // Whatever the row shows, they can search: the professional,
                // the service, or the event it belongs to.
                $qr->where(function ($w) use ($term) {
                    $w->whereHas('supplier', fn ($u) => $u->where('name', 'like', $term))
                      ->orWhereHas('event', fn ($e) => $e->where('title', 'like', $term))
                      ->orWhere('notes', 'like', $term);
                });
            })
            ->when($filters['status'], fn ($qr) => $qr->where('status', $filters['status']))
            ->when($filters['from'], fn ($qr) => $qr->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'], fn ($qr) => $qr->whereDate('created_at', '<=', $filters['to']))
            ->latest();

        $vendors = $query->paginate(8)->withQueryString();

        // Top cards — for a planner "earnings" reads as managed project funds.
        /*
         * Same three states as the payments page, on a client screen that used
         * to call them earnings.
         *
         * "Available Balance … Ready to allocate" was total - settled -
         * inEscrow: an arithmetic remainder presented as a balance the client
         * could spend. The Owner's Budget-Does-Not-Equal-Funding rule names
         * exactly this — a planning number must never be shown as money held.
         * There is no balance to show, so none is shown.
         */
        $stats = [
            // OA-146: one calculation, shared with Bookings and Payments.
            'total_agreed'   => \App\Domain\Finance\ClientTotals::agreed($user, $activeEvent?->id),
            'cancelled'      => \App\Domain\Finance\ClientTotals::cancelled($user, $activeEvent?->id),
            'paid'           => $s['settled'],
            'agreed_unpaid'  => $s['inEscrow'],
            'awaiting'       => $s['pending'],
            'pending_count'  => Booking::where('client_id', $user->id)->notSelfSupplied()->where('status', 'confirmed')->count(),
        ];

        /*
         * The donut.
         *
         * Its keys used to be 'pending', 'accepted' and 'paid' while the legend
         * beside it read "Agreed, Not Yet Paid", "Paid" and "Remaining" — three
         * labels, three different words, nothing lining up. They are named for
         * what the client is shown now.
         *
         * "Remaining" is what stands minus what is settled or committed. It was
         * computed from a total that included cancelled bookings, so a
         * cancelled $5,000 appeared as money still to be spent.
         */
        $agreedTotal = \App\Domain\Finance\ClientTotals::agreed($user, $activeEvent?->id);

        $pipeline = [
            'agreed_unpaid' => $s['inEscrow'],
            'paid'          => $s['settled'],
            'remaining'     => max(0, round($agreedTotal - $s['settled'] - $s['inEscrow'])),
            'total'         => $agreedTotal,
            'cancelled'     => \App\Domain\Finance\ClientTotals::cancelled($user, $activeEvent?->id),
        ];

        // Earnings trend — last 8 weeks of cumulative completed-booking value.
        $trend = [];
        $col = $s['col'];
        for ($i = 7; $i >= 0; $i--) {
            $weekEnd = now()->subWeeks($i)->endOfWeek();
            $val = (float) Booking::where('client_id', $user->id)
                ->notSelfSupplied()
                ->where('status', 'completed')
                ->where('updated_at', '<=', $weekEnd)
                ->sum($col);
            $trend[] = ['label' => $weekEnd->format('M d'), 'value' => $val];
        }

        return view('client.finance.spending', compact(
            'stats', 'vendors', 'pipeline', 'trend', 'activeEvent', 'filters', 'myEvents'
        ) + ['statuses' => self::SPEND_STATUSES]);
    }
}
