<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
 *   GET /client/earnings  → earnings()
 */
class ClientFinanceController extends Controller
{
    private function priceColumn(): ?string
    {
        if (Schema::hasColumn('bookings', 'total_amount')) {
            return 'total_amount';
        }
        if (Schema::hasColumn('bookings', 'agreed_price')) {
            return 'agreed_price';
        }
        return null;
    }

    /** Money the client owes/has paid across all their bookings. */
    private function spend(int $userId): array
    {
        $col  = $this->priceColumn();
        $base = Booking::where('client_id', $userId);

        $amount = fn ($status) => $col
            ? (float) (clone $base)->when($status, fn ($q) => $q->where('status', $status))->sum($col)
            : 0.0;

        $total     = $amount(null);
        $settled   = $amount('completed');
        $inEscrow  = $amount('confirmed');   // confirmed but not released
        $pending   = $amount('requested');   // awaiting confirmation

        return compact('total', 'settled', 'inEscrow', 'pending', 'col');
    }

    public function payments(Request $request): View
    {
        $user = $request->user();
        $s    = $this->spend($user->id);

        // Transaction ledger — every booking is one "transaction" row.
        $query = Booking::where('client_id', $user->id)
            ->with(['event:id,title,starts_at,location', 'supplier:id,name,avatar', 'supplier.profile:id,user_id,headline'])
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
        $stats = [
            'agreed_unpaid' => $s['inEscrow'],   // confirmed — a price both sides accepted
            'paid'          => $s['settled'],    // completed — money that actually moved
            'awaiting'      => $s['pending'],    // requested — not yet accepted
            'total_agreed'  => $s['total'],
        ];

        $activeEvent = Event::where('client_id', $user->id)
            ->whereIn('status', ['pending', 'published', 'confirmed'])
            ->latest('starts_at')->first();

        return view('client.finance.payments', compact(
            'stats', 'transactions', 'activeEvent'
        ));
    }

    public function earnings(Request $request): View
    {
        $user = $request->user();
        $s    = $this->spend($user->id);

        // Itemized vendor expense matrix.
        $query = Booking::where('client_id', $user->id)
            ->with(['event:id,title', 'supplier:id,name,avatar', 'supplier.profile:id,user_id,headline'])
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
            'total_agreed'   => $s['total'],
            'paid'           => $s['settled'],
            'agreed_unpaid'  => $s['inEscrow'],
            'awaiting'       => $s['pending'],
            'pending_count'  => Booking::where('client_id', $user->id)->where('status', 'confirmed')->count(),
        ];

        // Revenue-pipeline donut split.
        $pipeline = [
            'pending'  => $s['inEscrow'],
            'accepted' => $s['settled'],
            'paid'     => max(0, round($s['total'] - $s['settled'] - $s['inEscrow'])),
            'total'    => $s['total'],
        ];

        // Earnings trend — last 8 weeks of cumulative completed-booking value.
        $trend = [];
        $col = $s['col'];
        for ($i = 7; $i >= 0; $i--) {
            $weekEnd = now()->subWeeks($i)->endOfWeek();
            $val = $col
                ? (float) Booking::where('client_id', $user->id)
                    ->where('status', 'completed')
                    ->where('updated_at', '<=', $weekEnd)
                    ->sum($col)
                : 0;
            $trend[] = ['label' => $weekEnd->format('M d'), 'value' => $val];
        }

        $activeEvent = Event::where('client_id', $user->id)
            ->whereIn('status', ['pending', 'published', 'confirmed'])
            ->latest('starts_at')->first();

        return view('client.finance.earnings', compact(
            'stats', 'vendors', 'pipeline', 'trend', 'activeEvent'
        ));
    }
}
