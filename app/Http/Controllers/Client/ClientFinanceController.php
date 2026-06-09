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

        // Top-card metrics (escrow/Stripe split is a derived placeholder).
        $stats = [
            'stripe_outflow' => round($s['settled'] * 0.55),     // ~ portion settled via Stripe
            'escrow_locked'  => $s['inEscrow'],
            'tax_liability'  => round($s['total'] * 0.27),        // 1099 reportable estimate
            'net_cash'       => max(0, round($s['total'] * 0.20)),// available balance proxy
            'total_payments' => $s['total'],
            'processing_fees'=> round($s['settled'] * 0.029, 2),  // ~2.9% Stripe fee
        ];

        // Payment-methods summary + fee breakdown (derived split).
        $methods = [
            'escrow' => round($s['total'] * 0.62),
            'stripe' => round($s['total'] * 0.38),
        ];
        $fees = [
            'platform' => round($s['settled'] * 0.029, 2),
            'gateway'  => round($s['settled'] * 0.016, 2),
        ];

        $activeEvent = Event::where('client_id', $user->id)
            ->whereIn('status', ['pending', 'published', 'confirmed'])
            ->latest('starts_at')->first();

        return view('client.finance.payments', compact(
            'stats', 'transactions', 'methods', 'fees', 'activeEvent'
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
        $stats = [
            'total_earnings'   => $s['total'],     // total project value managed
            'withdrawn'        => $s['settled'],   // released to vendors
            'pending_release'  => $s['inEscrow'],
            'available'        => max(0, round($s['total'] - $s['settled'] - $s['inEscrow'])),
            'pending_count'    => Booking::where('client_id', $user->id)->where('status', 'confirmed')->count(),
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
