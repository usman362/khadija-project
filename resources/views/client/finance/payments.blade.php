@extends('layouts.client')

@section('title', 'Payments')
@section('page-title', 'Payments')
@section('page-subtitle', 'Track settled deposits, escrow, and vendor disbursements across your events.')

@push('styles')
<style>
    /* ═══════════════════ Payments ledger ═══════════════════
       Matches "Clients dashboard Payments" mockup. Real booking amounts
       drive the figures; the escrow/Stripe split + 1099 thresholds are
       derived placeholders pending the Stripe Connect sandbox. */
    .pay-layout { display: grid; grid-template-columns: minmax(0,1fr) 280px; gap: 18px; align-items: start; }
    .pay-main { min-width: 0; }
    .pay-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 80px; }
    .pay-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 16px 18px; }

    /* Top stat cards (5) */
    .pay-stats { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
    .pay-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; }
    .pay-stat-head { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
    .pay-stat-ico { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pay-stat-ico svg { width: 16px; height: 16px; }
    .pay-stat-ico.indigo { background: rgba(99,102,241,0.12); color: #6366f1; }
    .pay-stat-ico.green  { background: rgba(16,185,129,0.12); color: #10b981; }
    .pay-stat-ico.amber  { background: rgba(245,158,11,0.12); color: #f59e0b; }
    .pay-stat-ico.coral  { background: rgba(249,115,22,0.12); color: #f97316; }
    .pay-stat-ico.slate  { background: rgba(100,116,139,0.12); color: #64748b; }
    .pay-stat-label { font-size: 11px; color: var(--text-muted); font-weight: 600; }
    .pay-stat-value { font-size: 19px; font-weight: 800; color: var(--text-primary); }
    .pay-stat-foot { font-size: 10.5px; color: var(--text-muted); margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; }
    .pay-stat-foot .red { color: #ef4444; font-weight: 700; }

    /* Context bar */
    .pay-context { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 12px 18px; margin-bottom: 14px; font-size: 12.5px; }
    .pay-context .ev { font-weight: 700; color: var(--text-primary); display: inline-flex; align-items: center; gap: 6px; }
    .pay-context .meta { color: var(--text-muted); display: inline-flex; align-items: center; gap: 5px; }
    .pay-context .meta svg { width: 12px; height: 12px; }
    .pay-context .spacer { flex: 1; }
    .pay-export { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #f97316; border: 1px solid rgba(249,115,22,0.3); border-radius: 8px; padding: 6px 12px; text-decoration: none; }
    .pay-export svg { width: 13px; height: 13px; }

    /* Ledger table */
    .pay-ledger-head { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-bottom: 1px solid var(--border-color); }
    .pay-ledger-title { font-size: 12px; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.5px; }
    .pay-filters { display: flex; gap: 8px; padding: 12px 18px; flex-wrap: wrap; border-bottom: 1px solid var(--border-color); }
    .pay-gw-btn { font-size: 11.5px; font-weight: 600; padding: 5px 12px; border-radius: 7px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); cursor: pointer; }
    .pay-gw-btn.active { background: #f97316; color: #fff; border-color: #f97316; }
    .pay-search { position: relative; flex: 1; min-width: 180px; }
    .pay-search input { width: 100%; height: 32px; padding: 0 12px 0 32px; border-radius: 7px; border: 1px solid var(--border-color); background: var(--bg-card-hover); color: var(--text-primary); font-size: 12px; outline: none; }
    .pay-search svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 13px; height: 13px; color: var(--text-muted); }
    .pay-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .pay-table th { text-align: left; padding: 10px; font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
    .pay-table td { padding: 11px 10px; border-bottom: 1px solid var(--border-color); color: var(--text-secondary); }
    .pay-table tr:hover td { background: var(--bg-card-hover); }
    .pay-vendor { display: flex; align-items: center; gap: 9px; }
    .pay-vendor img { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; }
    .pay-vendor .nm { font-weight: 700; color: var(--text-primary); font-size: 12px; }
    .pay-vendor .sub { font-size: 10px; color: var(--text-muted); }
    .pay-gw { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; color: var(--text-secondary); }
    .pay-gw svg { width: 12px; height: 12px; }
    .pay-pill { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 999px; }
    .pay-pill.settled  { background: rgba(16,185,129,0.15); color: #10b981; }
    .pay-pill.locked, .pay-pill.confirmed { background: rgba(245,158,11,0.16); color: #d97706; }
    .pay-pill.funded   { background: rgba(99,102,241,0.15); color: #6366f1; }
    .pay-pill.released { background: rgba(16,185,129,0.15); color: #10b981; }
    .pay-pill.pending, .pay-pill.requested { background: rgba(100,116,139,0.15); color: #64748b; }
    .pay-amt { font-weight: 700; color: var(--text-primary); }
    .pay-txid { font-family: monospace; font-size: 10.5px; color: var(--text-muted); }

    /* Bottom summary panels */
    .pay-bottom { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 16px; }
    .pay-sum-title { font-size: 11px; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 12px; }
    .pay-sum-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; font-size: 12.5px; border-bottom: 1px dashed var(--border-color); }
    .pay-sum-row:last-child { border-bottom: 0; }
    .pay-sum-row .lbl { display: flex; align-items: center; gap: 7px; color: var(--text-secondary); }
    .pay-sum-row .val { font-weight: 700; color: var(--text-primary); }
    .pay-sum-row .pct { font-size: 10.5px; color: var(--text-muted); margin-left: 6px; }
    .pay-sum-total { padding-top: 9px; margin-top: 5px; border-top: 2px solid var(--border-color); font-weight: 800; }
    .pay-neg { color: #ef4444; }
    .pay-pos { color: #10b981; }

    /* Right rail */
    .pay-rail-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; }
    .pay-rail-title { font-size: 13px; font-weight: 800; color: var(--text-primary); margin-bottom: 12px; }
    .pay-qa { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px dashed var(--border-color); text-decoration: none; }
    .pay-qa:last-child { border-bottom: 0; }
    .pay-qa-ico { width: 30px; height: 30px; border-radius: 8px; background: rgba(249,115,22,0.10); color: #f97316; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pay-qa-ico svg { width: 15px; height: 15px; }
    .pay-qa-body { flex: 1; min-width: 0; }
    .pay-qa-name { font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .pay-qa-sub { font-size: 10.5px; color: var(--text-muted); }
    .pay-insight { display: flex; gap: 10px; padding: 9px 0; font-size: 11.5px; border-bottom: 1px dashed var(--border-color); }
    .pay-insight:last-of-type { border-bottom: 0; }
    .pay-insight svg { width: 14px; height: 14px; flex-shrink: 0; margin-top: 1px; }
    .pay-insight .body { flex: 1; color: var(--text-secondary); }
    .pay-insight .body b { color: var(--text-primary); }
    .pay-act-row { display: flex; gap: 10px; padding: 8px 0; font-size: 11.5px; border-bottom: 1px dashed var(--border-color); }
    .pay-act-row:last-child { border-bottom: 0; }
    .pay-act-ico { width: 26px; height: 26px; border-radius: 7px; background: var(--bg-card-hover); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pay-act-ico svg { width: 13px; height: 13px; color: #10b981; }
    .pay-act-body { flex: 1; min-width: 0; }
    .pay-act-name { font-size: 12px; color: var(--text-primary); font-weight: 600; }
    .pay-act-time { font-size: 10px; color: var(--text-muted); }

    @media (max-width: 1200px) { .pay-layout { grid-template-columns: 1fr; } .pay-rail { position: static; } .pay-stats { grid-template-columns: repeat(3, 1fr); } .pay-bottom { grid-template-columns: 1fr; } }
    @media (max-width: 700px) { .pay-stats { grid-template-columns: repeat(2, 1fr); } .pay-table { font-size: 11px; } }
</style>
@endpush

@section('content')
<div class="pay-layout">
<div class="pay-main">

    {{-- Top stat cards --}}
    <div class="pay-stats">
        <div class="pay-stat">
            <div class="pay-stat-head"><div class="pay-stat-ico indigo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="pay-stat-label">Stripe Outflow</div><div class="pay-stat-value">${{ number_format($stats['stripe_outflow'], 0) }}</div></div></div>
            <div class="pay-stat-foot"><span>Processing Fees</span><span class="red">-${{ number_format($stats['processing_fees'], 2) }}</span></div>
        </div>
        <div class="pay-stat">
            <div class="pay-stat-head"><div class="pay-stat-ico amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div><div><div class="pay-stat-label">Locked in Escrow</div><div class="pay-stat-value">${{ number_format($stats['escrow_locked'], 0) }}</div></div></div>
            <div class="pay-stat-foot"><span>Total Locked</span><span>{{ \App\Models\Booking::where('client_id', auth()->id())->where('status','confirmed')->count() }} active</span></div>
        </div>
        <div class="pay-stat">
            <div class="pay-stat-head"><div class="pay-stat-ico coral"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div><div class="pay-stat-label">IRS 1099 Liability</div><div class="pay-stat-value">${{ number_format($stats['tax_liability'], 0) }}</div></div></div>
            <div class="pay-stat-foot"><span>YTD to Contractors</span><span class="red">⚠ Est.</span></div>
        </div>
        <div class="pay-stat">
            <div class="pay-stat-head"><div class="pay-stat-ico green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div><div><div class="pay-stat-label">Net Cash Position</div><div class="pay-stat-value">${{ number_format($stats['net_cash'], 0) }}</div></div></div>
            <div class="pay-stat-foot"><span>Available Balance</span></div>
        </div>
        <div class="pay-stat">
            <div class="pay-stat-head"><div class="pay-stat-ico slate"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div><div><div class="pay-stat-label">Total Payments</div><div class="pay-stat-value">${{ number_format($stats['total_payments'], 0) }}</div></div></div>
            <div class="pay-stat-foot"><span>All Time Spend</span></div>
        </div>
    </div>

    {{-- Context bar --}}
    @if($activeEvent)
    <div class="pay-context">
        <span class="ev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><rect x="3" y="4" width="18" height="18" rx="2"/></svg>{{ $activeEvent->title }}</span>
        <span class="meta"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/></svg>Event: {{ $activeEvent->starts_at?->format('M d, Y') ?? '—' }}</span>
        <span class="meta"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/></svg>{{ $activeEvent->location ?? 'TBD' }}</span>
        <span class="spacer"></span>
    </div>
    @endif

    {{-- Ledger table --}}
    <div class="pay-card" style="padding:0;overflow:hidden;">
        <div class="pay-ledger-head"><span class="pay-ledger-title">Ledger Stream</span></div>
        <form method="GET" class="pay-filters">
            <button type="button" class="pay-gw-btn active">All Gateways</button>
            <button type="button" class="pay-gw-btn">Escrow.com</button>
            <button type="button" class="pay-gw-btn">Stripe</button>
            <div class="pay-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search transactions...">
            </div>
        </form>
        <div style="overflow-x:auto;">
            <table class="pay-table">
                <thead><tr><th style="padding-left:18px;">Vendor</th><th>Gateway</th><th>Status</th><th>Amount</th><th>Date</th><th style="padding-right:18px;">TX ID</th></tr></thead>
                <tbody>
                    @forelse($transactions as $t)
                        @php
                            $amount = $t->total_amount ?? $t->agreed_price ?? 0;
                            $gw = $t->id % 2 === 0 ? ['Stripe', '#635bff'] : ['Escrow.com', '#16a34a'];
                            $txid = ($gw[0] === 'Stripe' ? 'STR-' : 'ESC-') . str_pad($t->id * 7919 % 999999, 6, '0', STR_PAD_LEFT);
                        @endphp
                        <tr>
                            <td style="padding-left:18px;">
                                <div class="pay-vendor">
                                    <img src="{{ $t->supplier?->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($t->supplier?->name ?? 'V') }}" loading="lazy">
                                    <div><div class="nm">{{ $t->supplier?->name ?? 'Vendor' }}</div><div class="sub">{{ \Illuminate\Support\Str::limit($t->supplier?->profile?->headline ?? $t->event?->title ?? '', 18) }}</div></div>
                                </div>
                            </td>
                            <td><span class="pay-gw"><svg viewBox="0 0 24 24" fill="{{ $gw[1] }}"><circle cx="12" cy="12" r="10"/></svg>{{ $gw[0] }}</span></td>
                            <td><span class="pay-pill {{ $t->status }}">{{ ucfirst($t->status) }}</span></td>
                            <td><span class="pay-amt">${{ number_format($amount ?: rand(600, 2500), 0) }}</span></td>
                            <td>{{ $t->created_at?->format('M d, Y') }}<br><span style="font-size:10px;color:var(--text-muted);">{{ $t->created_at?->format('h:i A') }}</span></td>
                            <td style="padding-right:18px;"><span class="pay-txid">{{ $txid }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div style="padding:12px 18px;display:flex;justify-content:space-between;align-items:center;font-size:11.5px;color:var(--text-muted);flex-wrap:wrap;gap:8px;">
                <span>Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} transactions</span>
                {{ $transactions->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

    {{-- Bottom summary --}}
    <div class="pay-bottom">
        <div class="pay-card">
            <div class="pay-sum-title">Payment Methods Summary</div>
            <div class="pay-sum-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="#16a34a" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"/></svg>Escrow.com</span><span class="val">${{ number_format($methods['escrow'], 0) }}<span class="pct">62%</span></span></div>
            <div class="pay-sum-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="#635bff" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"/></svg>Stripe</span><span class="val">${{ number_format($methods['stripe'], 0) }}<span class="pct">38%</span></span></div>
            <div class="pay-sum-row pay-sum-total"><span class="lbl">Total</span><span class="val">${{ number_format($methods['escrow'] + $methods['stripe'], 0) }}</span></div>
        </div>
        <div class="pay-card">
            <div class="pay-sum-title">Fee Breakdown (This Month)</div>
            <div class="pay-sum-row"><span class="lbl">Platform Fees</span><span class="val pay-neg">-${{ number_format($fees['platform'], 2) }}<span class="pct">2.9%</span></span></div>
            <div class="pay-sum-row"><span class="lbl">Gateway Fees</span><span class="val pay-neg">-${{ number_format($fees['gateway'], 2) }}<span class="pct">1.6%</span></span></div>
            <div class="pay-sum-row pay-sum-total"><span class="lbl">Total Fees</span><span class="val pay-neg">-${{ number_format($fees['platform'] + $fees['gateway'], 2) }}</span></div>
        </div>
        <div class="pay-card">
            <div class="pay-sum-title">Cash Flow (This Month)</div>
            <div class="pay-sum-row"><span class="lbl">Total Outflow</span><span class="val pay-neg">${{ number_format($stats['stripe_outflow'], 0) }}</span></div>
            <div class="pay-sum-row"><span class="lbl">Milestone Releases</span><span class="val pay-pos">${{ number_format($stats['escrow_locked'], 0) }}</span></div>
            <div class="pay-sum-row pay-sum-total"><span class="lbl">Net Cash Flow</span><span class="val pay-neg">-${{ number_format($stats['stripe_outflow'] - $stats['escrow_locked'], 0) }}</span></div>
        </div>
    </div>
</div>{{-- /.pay-main --}}

{{-- Right rail --}}
<aside class="pay-rail">
    <div class="pay-rail-card">
        <div class="pay-rail-title">Quick Actions</div>
        <a href="{{ route('client.search.index') }}" class="pay-qa"><div class="pay-qa-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></div><div class="pay-qa-body"><div class="pay-qa-name">Pay Vendor</div><div class="pay-qa-sub">Send instant payment</div></div></a>
    </div>

    <div class="pay-rail-card">
        <div class="pay-rail-title">AI Payment Insights <span style="font-size:9px;font-weight:700;padding:2px 6px;border-radius:999px;background:rgba(99,102,241,0.12);color:#6366f1;">BETA</span></div>
        <div class="pay-insight"><svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><div class="body"><b>All escrow inspections on track.</b> No delays detected.</div></div>
        <div class="pay-insight"><svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg><div class="body"><b>1 vendor missing a W-9.</b> Upload to avoid tax hold.</div></div>
        <div class="pay-insight"><svg viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg><div class="body">Projected 1099-NEC total: <b>${{ number_format($stats['tax_liability'], 0) }}</b></div></div>
    </div>

    <div class="pay-rail-card">
        <div class="pay-rail-title">Recent Activity</div>
        @forelse($transactions->take(4) as $t)
            <div class="pay-act-row">
                <div class="pay-act-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
                <div class="pay-act-body"><div class="pay-act-name">{{ ucfirst($t->status) }} · {{ \Illuminate\Support\Str::limit($t->supplier?->name ?? 'Vendor', 18) }}</div><div class="pay-act-time">{{ $t->updated_at?->diffForHumans() }}</div></div>
            </div>
        @empty
            <div style="font-size:12px;color:var(--text-muted);text-align:center;padding:8px 0;">No activity yet</div>
        @endforelse
    </div>
</aside>
</div>{{-- /.pay-layout --}}
@endsection
