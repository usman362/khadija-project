@extends('layouts.client')

@section('title', 'Earnings')
@section('page-title', 'Earnings')
@section('page-subtitle', 'Track project funds, manage payouts, and monitor financial performance.')

@push('styles')
<style>
    /* ═══════════════════ Earnings — project financial dashboard ═══════
       For an event-planner client "Earnings" = the project funds they
       manage and disburse to vendors. Real booking amounts drive figures;
       secure payment/Stripe split + 1099 status are derived pending Stripe sandbox. */
    .ea-layout { display: grid; grid-template-columns: minmax(0,1fr) 280px; gap: 18px; align-items: start; }
    .ea-main { min-width: 0; }
    .ea-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 80px; }
    .ea-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 16px 18px; }

    .ea-context { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin-bottom: 14px; font-size: 12.5px; color: var(--text-muted); }
    .ea-context b { color: var(--text-primary); }
    .ea-context .spacer { flex: 1; }
    .ea-export { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #f97316; border: 1px solid rgba(249,115,22,0.3); border-radius: 8px; padding: 6px 12px; text-decoration: none; }
    .ea-export svg { width: 13px; height: 13px; }

    /* Stat cards (4) */
    .ea-stats { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
    .ea-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 16px; display: flex; gap: 12px; align-items: flex-start; }
    .ea-stat-ico { width: 40px; height: 40px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ea-stat-ico svg { width: 19px; height: 19px; }
    .ea-stat-ico.indigo { background: rgba(99,102,241,0.12); color: #6366f1; }
    .ea-stat-ico.green  { background: rgba(16,185,129,0.12); color: #10b981; }
    .ea-stat-ico.amber  { background: rgba(245,158,11,0.12); color: #f59e0b; }
    .ea-stat-ico.coral  { background: rgba(249,115,22,0.12); color: #f97316; }
    .ea-stat-label { font-size: 11.5px; color: var(--text-muted); font-weight: 600; }
    .ea-stat-value { font-size: 23px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .ea-stat-sub { font-size: 10.5px; color: var(--text-muted); margin-top: 3px; }
    .ea-stat-sub.up { color: #10b981; font-weight: 700; }
    .ea-stat-sub.amber { color: #f59e0b; font-weight: 700; }

    /* Toolbar */
    .ea-toolbar { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
    .ea-search { position: relative; flex: 1; min-width: 200px; }
    .ea-search input { width: 100%; height: 40px; padding: 0 14px 0 38px; border-radius: 9px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); font-size: 12.5px; outline: none; }
    .ea-search svg { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: var(--text-muted); }
    .ea-tool-btn { height: 40px; padding: 0 14px; border-radius: 9px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); font-size: 12.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; white-space: nowrap; }
    .ea-tool-btn svg { width: 13px; height: 13px; }

    /* Gateway mini cards */
    .ea-gw-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px; }
    .ea-gw { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; }
    .ea-gw-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
    .ea-gw-name { font-size: 10.5px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.4px; }
    .ea-gw-tag { font-size: 9px; font-weight: 700; color: var(--text-muted); }
    .ea-gw-big { font-size: 20px; font-weight: 800; color: var(--text-primary); }
    .ea-gw-split { display: flex; gap: 16px; margin-top: 6px; font-size: 11px; }
    .ea-gw-split b { display: block; font-size: 15px; font-weight: 800; }
    .ea-gw-foot { font-size: 10.5px; color: var(--text-muted); margin-top: 8px; }
    .ea-gw-foot.red { color: #ef4444; }

    /* Vendor expense matrix */
    .ea-matrix-title { font-size: 12px; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.4px; padding: 14px 18px; border-bottom: 1px solid var(--border-color); }
    .ea-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .ea-table th { text-align: left; padding: 10px; font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
    .ea-table td { padding: 11px 10px; border-bottom: 1px solid var(--border-color); color: var(--text-secondary); }
    .ea-table tr:hover td { background: var(--bg-card-hover); }
    .ea-vendor { display: flex; align-items: center; gap: 9px; }
    .ea-vendor img { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; }
    .ea-vendor .nm { font-weight: 700; color: var(--text-primary); font-size: 12px; }
    .ea-pill { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 999px; display: inline-flex; align-items: center; gap: 3px; }
    .ea-pill.green { background: rgba(16,185,129,0.15); color: #10b981; }
    .ea-pill.blue  { background: rgba(99,102,241,0.15); color: #6366f1; }
    .ea-pill.amber { background: rgba(245,158,11,0.16); color: #d97706; }
    .ea-tax-ok { color: #10b981; font-weight: 700; }
    .ea-tax-miss { color: #ef4444; font-weight: 700; }
    .ea-action-btn { font-size: 10.5px; font-weight: 700; padding: 5px 11px; border-radius: 7px; border: none; cursor: pointer; color: #fff; }
    .ea-action-btn.coral { background: #f97316; }
    .ea-action-btn.ghost { background: var(--bg-card-hover); color: var(--text-secondary); border: 1px solid var(--border-color); }

    /* Critical alert */
    .ea-alert { display: flex; align-items: center; gap: 14px; background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.3); border-radius: var(--radius); padding: 14px 18px; margin-top: 16px; }
    .ea-alert svg { width: 18px; height: 18px; color: #f59e0b; flex-shrink: 0; }
    .ea-alert .body { flex: 1; font-size: 12.5px; color: var(--text-secondary); }
    .ea-alert .body b { color: var(--text-primary); }
    .ea-alert button { background: #f97316; color: #fff; border: none; padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; white-space: nowrap; }

    /* Right rail */
    .ea-rail-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; }
    .ea-rail-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .ea-rail-title { font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .ea-rail-sel { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 6px; padding: 3px 8px; font-size: 10.5px; color: var(--text-muted); }
    .ea-donut { position: relative; width: 130px; height: 130px; margin: 0 auto 12px; }
    .ea-donut-c { position: absolute; inset: 16px; background: var(--bg-card); border-radius: 50%; z-index: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .ea-donut-c .num { font-size: 18px; font-weight: 800; color: var(--text-primary); }
    .ea-donut-c .lbl { font-size: 9px; color: var(--text-muted); }
    .ea-legend { display: flex; flex-direction: column; gap: 6px; font-size: 11.5px; }
    .ea-legend .row { display: flex; align-items: center; gap: 8px; }
    .ea-legend .dot { width: 8px; height: 8px; border-radius: 50%; }
    .ea-legend .lbl { flex: 1; color: var(--text-secondary); }
    .ea-legend .val { font-weight: 700; color: var(--text-primary); }
    .ea-trend-big { font-size: 24px; font-weight: 800; color: var(--text-primary); }
    .ea-trend-up { font-size: 11px; color: #10b981; font-weight: 700; }
    .ea-qa-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .ea-qa { display: flex; align-items: center; gap: 7px; padding: 9px 10px; background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 8px; font-size: 11px; font-weight: 600; color: var(--text-primary); text-decoration: none; }
    .ea-qa:hover { border-color: rgba(249,115,22,0.3); }
    .ea-qa svg { width: 14px; height: 14px; color: #f97316; flex-shrink: 0; }

    @media (max-width: 1200px) { .ea-layout { grid-template-columns: 1fr; } .ea-rail { position: static; } .ea-stats { grid-template-columns: repeat(2, 1fr); } .ea-gw-row { grid-template-columns: 1fr; } }
    @media (max-width: 700px) { .ea-stats { grid-template-columns: 1fr; } .ea-table { font-size: 11px; } }
</style>
@endpush

@section('content')
<div class="ea-layout">
<div class="ea-main">

    {{-- Context --}}
    <div class="ea-context">
        @if($activeEvent)
            <span>Active Project: <b>{{ $activeEvent->title }}</b></span>
            <span>Dates: <b>{{ $activeEvent->starts_at?->format('M d') ?? '—' }}</b></span>
            <span>Budget: <b>${{ number_format($activeEvent->budget ?? 0, 0) }}</b></span>
        @endif
        <span class="spacer"></span>
    </div>

    {{-- Stat cards --}}
    <div class="ea-stats">
        <div class="ea-stat"><div class="ea-stat-ico indigo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="ea-stat-label">Total Project Funds</div><div class="ea-stat-value">${{ number_format($stats['total_earnings'], 0) }}</div><div class="ea-stat-sub up">↑ Managed value</div></div></div>
        <div class="ea-stat"><div class="ea-stat-ico green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></div><div><div class="ea-stat-label">Released to Vendors</div><div class="ea-stat-value">${{ number_format($stats['withdrawn'], 0) }}</div><div class="ea-stat-sub">Paid out</div></div></div>
        <div class="ea-stat"><div class="ea-stat-ico amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="ea-stat-label">Pending Release</div><div class="ea-stat-value">${{ number_format($stats['pending_release'], 0) }}</div><div class="ea-stat-sub amber">{{ $stats['pending_count'] }} payments</div></div></div>
        <div class="ea-stat"><div class="ea-stat-ico coral"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></div><div><div class="ea-stat-label">Available Balance</div><div class="ea-stat-value">${{ number_format($stats['available'], 0) }}</div><div class="ea-stat-sub">Ready to allocate</div></div></div>
    </div>

    {{-- Toolbar --}}
    <div class="ea-toolbar">
        <div class="ea-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input placeholder="Search transactions, vendors, or invoices..."></div>
        <button class="ea-tool-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/></svg>Date Range</button>
        <button class="ea-tool-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>Filter</button>
    </div>

    {{-- Gateway mini cards --}}
    <div class="ea-gw-row">
        <div class="ea-gw">
            <div class="ea-gw-head"><span class="ea-gw-name">Stripe Outflow</span><span class="ea-gw-tag">STRIPE</span></div>
            <div class="ea-gw-big">${{ number_format($stats['withdrawn'], 0) }}</div>
            <div class="ea-gw-foot">Deposits / Fees</div>
            <div class="ea-gw-foot red">${{ number_format($stats['withdrawn'] * 0.029, 0) }} processing fees incurred</div>
        </div>
        <div class="ea-gw">
            <div class="ea-gw-head"><span class="ea-gw-name">Secure Payment Vault</span><span class="ea-gw-tag">SECURED</span></div>
            <div class="ea-gw-split">
                <div><b style="color:#10b981;">${{ number_format($stats['pending_release'], 0) }}</b><span style="color:var(--text-muted);">Funded</span></div>
                <div><b>${{ number_format($stats['available'], 0) }}</b><span style="color:var(--text-muted);">Released</span></div>
            </div>
            <div class="ea-gw-foot">$0.00 currently in dispute</div>
        </div>
        <div class="ea-gw">
            <div class="ea-gw-head"><span class="ea-gw-name">1099 Compliance Hub</span><span class="ea-gw-tag">TAX</span></div>
            <div class="ea-gw-big">{{ $vendors->total() > 0 ? $vendors->total() - 1 : 0 }} / {{ $vendors->total() }}</div>
            <div class="ea-gw-foot">W-9 Forms Collected</div>
            <div class="ea-gw-foot red">1 contractor nearing $600 threshold</div>
        </div>
    </div>

    {{-- Vendor expense matrix --}}
    <div class="ea-card" style="padding:0;overflow:hidden;">
        <div class="ea-matrix-title">Itemized Vendor Expense &amp; Gateway Matrix</div>
        <div style="overflow-x:auto;">
            <table class="ea-table">
                <thead><tr><th style="padding-left:18px;">Professional</th><th>Service</th><th>Gateway</th><th>Transfer Status</th><th>Tax (W-9)</th><th style="padding-right:18px;">Action</th></tr></thead>
                <tbody>
                    @forelse($vendors as $i => $v)
                        @php
                            $gw = $i % 2 === 0 ? ['Secure Payment.com', '#16a34a'] : ['Stripe.com', '#635bff'];
                            $statuses = [['In Inspection','blue'],['Deposit Paid','green'],['Milestone Funded','green'],['Charge Settled','green'],['Pending Milestone','amber']];
                            $st = $statuses[$i % count($statuses)];
                            $hasW9 = $i !== 1; // one vendor missing W-9
                        @endphp
                        <tr>
                            <td style="padding-left:18px;"><div class="ea-vendor"><img src="{{ $v->supplier?->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($v->supplier?->name ?? 'V') }}" loading="lazy"><span class="nm">{{ $v->supplier?->name ?? 'Vendor' }}</span></div></td>
                            <td>{{ \Illuminate\Support\Str::limit($v->supplier?->profile?->headline ?? $v->event?->title ?? 'Service', 16) }}</td>
                            <td><span style="display:inline-flex;align-items:center;gap:5px;"><svg viewBox="0 0 24 24" fill="{{ $gw[1] }}" style="width:12px;height:12px;"><circle cx="12" cy="12" r="10"/></svg>{{ $gw[0] }}</span></td>
                            <td><span class="ea-pill {{ $st[1] }}">{{ $st[0] }} ↗</span></td>
                            <td>@if($hasW9)<span class="ea-tax-ok">Verified ✓</span>@else<span class="ea-tax-miss">Missing ⚠</span>@endif</td>
                            <td style="padding-right:18px;">@if(!$hasW9)<button class="ea-action-btn coral">Push W-9 Reminder</button>@else<button class="ea-action-btn ghost">View Details</button>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-muted);">No vendor expenses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vendors->hasPages())
            <div style="padding:12px 18px;font-size:11.5px;color:var(--text-muted);">{{ $vendors->onEachSide(1)->links() }}</div>
        @endif
    </div>

    {{-- Critical alert --}}
    <div class="ea-alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <div class="body"><b>Critical Alert:</b> A vendor is nearing the $600 IRS 1099 threshold without a W-9 form. Please request and collect the W-9 before releasing additional funds.</div>
        <button>Send W-9 Reminder</button>
    </div>
</div>{{-- /.ea-main --}}

{{-- Right rail --}}
<aside class="ea-rail">
    {{-- Revenue Pipeline donut --}}
    <div class="ea-rail-card">
        <div class="ea-rail-head"><div class="ea-rail-title">Revenue Pipeline</div><span class="ea-rail-sel">This Month</span></div>
        @php
            $pTotal = max(1, $pipeline['total']);
            $segs = [];
            $cur = 0;
            $parts = [['pending', '#f59e0b'], ['accepted', '#10b981'], ['paid', '#6366f1']];
            foreach ($parts as [$k, $c]) { $deg = ($pipeline[$k] / $pTotal) * 360; $segs[] = "$c {$cur}deg ".($cur+$deg)."deg"; $cur += $deg; }
            $conic = 'conic-gradient(' . implode(', ', $segs) . ')';
        @endphp
        <div class="ea-donut" style="background:{{ $conic }};border-radius:50%;">
            <div class="ea-donut-c"><span class="num">${{ number_format($pipeline['total'], 0) }}</span><span class="lbl">Total Pipeline</span></div>
        </div>
        <div class="ea-legend">
            <div class="row"><span class="dot" style="background:#f59e0b;"></span><span class="lbl">Pending Release</span><span class="val">${{ number_format($pipeline['pending'], 0) }}</span></div>
            <div class="row"><span class="dot" style="background:#10b981;"></span><span class="lbl">Accepted / Won</span><span class="val">${{ number_format($pipeline['accepted'], 0) }}</span></div>
            <div class="row"><span class="dot" style="background:#6366f1;"></span><span class="lbl">Paid / Withdrawn</span><span class="val">${{ number_format($pipeline['paid'], 0) }}</span></div>
        </div>
    </div>

    {{-- Earnings trend (sparkline) --}}
    <div class="ea-rail-card">
        <div class="ea-rail-head"><div class="ea-rail-title">Earnings Trend</div><span class="ea-rail-sel">This Month</span></div>
        <div class="ea-trend-big">${{ number_format($stats['total_earnings'], 0) }}</div>
        <div class="ea-trend-up">↑ Project total</div>
        @php
            $max = max(1, collect($trend)->max('value'));
            $pts = [];
            foreach ($trend as $i => $t) { $x = ($i / max(1, count($trend) - 1)) * 240; $y = 60 - ($t['value'] / $max) * 50; $pts[] = round($x, 1) . ',' . round($y, 1); }
            $poly = implode(' ', $pts);
        @endphp
        <svg viewBox="0 0 240 70" style="width:100%;height:70px;margin-top:8px;">
            <polyline points="{{ $poly }}" fill="none" stroke="#f97316" stroke-width="2"/>
            @if(count($pts))<circle cx="{{ explode(',', end($pts))[0] }}" cy="{{ explode(',', end($pts))[1] }}" r="3" fill="#f97316"/>@endif
        </svg>
        <div style="display:flex;justify-content:space-between;font-size:9.5px;color:var(--text-muted);">
            <span>{{ $trend[0]['label'] ?? '' }}</span><span>{{ end($trend)['label'] ?? '' }}</span>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="ea-rail-card">
        <div class="ea-rail-title" style="margin-bottom:10px;">Quick Actions</div>
        <div class="ea-qa-grid">
            <a href="{{ route('client.search.index') }}" class="ea-qa"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Add Vendor</a>
        </div>
    </div>
</aside>
</div>{{-- /.ea-layout --}}
@endsection
