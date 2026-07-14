@extends('layouts.professional')

@section('title', 'Contracts')

{{-- ════════════════════════════════════════════════════════════════
     Professional Contracts hub. Wired to REAL data (bookings, events,
     reviews) via ProfessionalContractController. Earnings split + AI
     Smart Bid Assistant are derived/illustrative (no payment ledger /
     AI backend yet) — clearly noted.
═══════════════════════════════════════════════════════════════════ --}}

@php
    $money = fn ($n) => '$' . number_format((float) $n, ($n == (int) $n) ? 0 : 2);
    $tot = max($totalRevenue, 0.01);
    $pctPaid    = (int) round($sumPaid    / $tot * 100);
    $pctSecure Payment  = (int) round($sumSecure Payment  / $tot * 100);
    $pctPending = max(0, 100 - $pctPaid - $pctSecure Payment);
@endphp

@push('styles')
<style>
    .pc { --pc-blue: #2563eb; }

    /* Page header */
    .pc-head { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
    .pc-head-ico { width: 48px; height: 48px; border-radius: 12px; background: rgba(37,99,235,0.12); color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pc-head-ico svg { width: 26px; height: 26px; }
    .pc-head h1 { font-size: 26px; font-weight: 800; color: var(--text-primary); margin: 0; line-height: 1.1; }
    .pc-head p { font-size: 13px; color: var(--text-muted); margin: 3px 0 0; }

    /* Stat strip (6) */
    .pc-stats { display: grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
    .pc-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px; min-width: 0; }
    .pc-stat-top { display: flex; align-items: center; gap: 9px; margin-bottom: 9px; }
    .pc-stat-ico { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pc-stat-ico svg { width: 16px; height: 16px; }
    .ci-blue { background: rgba(37,99,235,0.12); color: #2563eb; }
    .ci-green { background: rgba(16,185,129,0.12); color: #10b981; }
    .ci-orange { background: rgba(249,115,22,0.12); color: #f97316; }
    .ci-purple { background: rgba(139,92,246,0.12); color: #8b5cf6; }
    .ci-indigo { background: rgba(99,102,241,0.12); color: #6366f1; }
    .pc-stat-label { font-size: 10.5px; color: var(--text-muted); font-weight: 600; line-height: 1.2; }
    .pc-stat-val { font-size: 21px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; }
    .pc-stat-sub { font-size: 10px; color: var(--text-muted); margin-top: 4px; }
    .pc-stat-sub.up { color: #10b981; font-weight: 700; }

    /* Layout: left (wide) + right (narrow) + full-width pipeline */
    .pc-grid { display: grid; grid-template-columns: minmax(0,1.7fr) minmax(0,1fr); gap: 14px; align-items: start; }
    .pc-col { display: flex; flex-direction: column; gap: 14px; min-width: 0; }
    .pc-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px 18px; min-width: 0; }
    .pc-card-head { display: flex; align-items: center; gap: 9px; margin-bottom: 12px; }
    .pc-card-title { font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .pc-card-link { margin-left: auto; font-size: 11.5px; font-weight: 700; color: var(--pc-blue); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
    .pc-card-link svg { width: 12px; height: 12px; }

    /* Tabs */
    .pc-tabs { display: flex; gap: 20px; border-bottom: 1px solid var(--border-color); margin-bottom: 4px; }
    .pc-tab { font-size: 12.5px; font-weight: 600; color: var(--text-muted); padding-bottom: 10px; border-bottom: 2px solid transparent; cursor: pointer; white-space: nowrap; }
    .pc-tab.active { color: var(--pc-blue); border-bottom-color: var(--pc-blue); }

    /* Contracts table */
    .pc-tbl-wrap { width: 100%; overflow-x: auto; }
    .pc-tbl { width: 100%; border-collapse: collapse; font-size: 11.5px; }
    .pc-tbl th { text-align: left; padding: 9px 8px; font-size: 9px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid var(--border-color); white-space: nowrap; }
    .pc-tbl td { padding: 10px 8px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
    .pc-ev { display: flex; align-items: center; gap: 9px; min-width: 0; }
    .pc-ev-ico { width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0; background: rgba(37,99,235,0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; }
    .pc-ev-ico svg { width: 14px; height: 14px; }
    .pc-ev-name { font-weight: 700; color: var(--text-primary); white-space: nowrap; }
    .pc-ev-client { font-size: 10px; color: var(--text-muted); white-space: nowrap; }
    .pc-cell-sub { font-size: 10px; color: var(--text-muted); }
    .pc-price { font-weight: 800; color: var(--text-primary); }
    .pc-price-sub { font-size: 10px; color: var(--text-muted); text-decoration: line-through; }
    .pc-badge { font-size: 9.5px; font-weight: 800; padding: 3px 8px; border-radius: 6px; white-space: nowrap; }
    .b-confirmed { background: rgba(16,185,129,0.12); color: #059669; }
    .b-progress  { background: rgba(37,99,235,0.12); color: #2563eb; }
    .b-pending   { background: rgba(245,158,11,0.14); color: #d97706; }
    .b-completed { background: rgba(99,102,241,0.12); color: #6366f1; }
    .b-cancelled { background: rgba(239,68,68,0.12); color: #dc2626; }
    .pc-actions { display: inline-flex; gap: 5px; }
    .pc-act { width: 26px; height: 26px; border-radius: 7px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-muted); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; }
    .pc-act svg { width: 13px; height: 13px; }
    .pc-tbl-foot { text-align: center; margin-top: 12px; }
    .pc-tbl-foot a { font-size: 12px; font-weight: 700; color: var(--pc-blue); text-decoration: none; }
    .pc-empty { padding: 26px 12px; text-align: center; color: var(--text-muted); font-size: 13px; }

    /* Earnings donut */
    .pc-earn-total { font-size: 11px; color: var(--text-muted); }
    .pc-earn-amt { font-size: 24px; font-weight: 800; color: var(--text-primary); }
    .pc-earn-up { font-size: 11px; color: #10b981; font-weight: 700; margin: 2px 0 12px; }
    .pc-earn-body { display: flex; align-items: center; gap: 16px; }
    .pc-donut { width: 120px; height: 120px; border-radius: 50%; flex-shrink: 0; position: relative;
        background: conic-gradient(#2563eb 0 {{ $pctPaid }}%, #06b6d4 {{ $pctPaid }}% {{ $pctPaid + $pctSecure Payment }}%, #f97316 {{ $pctPaid + $pctSecure Payment }}% 100%); }
    .pc-donut.empty { background: var(--bg-card-hover); }
    .pc-donut::after { content: ''; position: absolute; inset: 20px; background: var(--bg-card); border-radius: 50%; }
    .pc-earn-legend { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 9px; }
    .pc-earn-leg { display: flex; align-items: center; gap: 8px; font-size: 11.5px; }
    .pc-earn-leg .dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
    .pc-earn-leg .nm { color: var(--text-secondary); flex: 1; }
    .pc-earn-leg .vl { font-weight: 700; color: var(--text-primary); }
    .pc-earn-leg .pc { color: var(--text-muted); font-size: 10.5px; }
    .pc-earn-foot { text-align: center; margin-top: 14px; }
    .pc-earn-foot a { font-size: 12px; font-weight: 700; color: var(--pc-blue); text-decoration: none; }

    /* Upcoming schedule */
    .pc-sch-row { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border-color); }
    .pc-sch-row:last-child { border-bottom: none; }
    .pc-sch-date { flex-shrink: 0; text-align: center; width: 40px; }
    .pc-sch-date .m { font-size: 9px; font-weight: 800; color: var(--pc-blue); text-transform: uppercase; }
    .pc-sch-date .d { font-size: 17px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .pc-sch-body { min-width: 0; }
    .pc-sch-name { font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .pc-sch-meta { font-size: 11px; color: var(--text-muted); margin-top: 1px; }

    /* Live gig opportunities */
    .pc-sub2 { display: grid; grid-template-columns: minmax(0,1.4fr) minmax(0,1fr); gap: 14px; align-items: start; }
    .pc-opps { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 10px; }
    .pc-opp { border: 1px solid var(--border-color); border-radius: 11px; overflow: hidden; background: var(--bg-card); }
    .pc-opp-img { height: 64px; background: linear-gradient(135deg,#1e3a8a,#2563eb); position: relative; display: flex; align-items: center; justify-content: center; }
    .pc-opp-img svg { width: 22px; height: 22px; color: rgba(255,255,255,0.85); }
    .pc-opp-tag { position: absolute; top: 7px; left: 7px; font-size: 8.5px; font-weight: 800; padding: 2px 7px; border-radius: 5px; text-transform: uppercase; letter-spacing: 0.3px; }
    .pc-opp-tag.new { background: #2563eb; color: #fff; }
    .pc-opp-tag.urgent { background: #ef4444; color: #fff; }
    .pc-opp-body { padding: 9px 10px; }
    .pc-opp-name { font-size: 12px; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pc-opp-svc { font-size: 10px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pc-opp-loc { font-size: 10px; color: var(--text-muted); margin-top: 3px; }
    .pc-opp-price { font-size: 12px; font-weight: 800; color: var(--text-primary); margin-top: 4px; }
    .pc-opp-foot { display: flex; align-items: center; justify-content: space-between; margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border-color); }
    .pc-opp-bids { font-size: 9.5px; color: var(--text-muted); }
    .pc-opp-btn { font-size: 10px; font-weight: 800; color: var(--pc-blue); text-decoration: none; }

    /* AI bid assistant */
    .pc-ai { background: linear-gradient(180deg, rgba(37,99,235,0.05), rgba(37,99,235,0.01)); border-color: rgba(37,99,235,0.2); }
    .pc-ai-beta { font-size: 8.5px; font-weight: 800; padding: 2px 6px; border-radius: 5px; background: rgba(37,99,235,0.14); color: #2563eb; letter-spacing: 0.4px; }
    .pc-ai-top { display: flex; align-items: flex-start; gap: 10px; }
    .pc-ai-info { flex: 1; min-width: 0; }
    .pc-ai-name { font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .pc-ai-meta { font-size: 10.5px; color: var(--text-muted); margin-top: 2px; }
    .pc-ai-fit { width: 56px; height: 56px; border-radius: 50%; flex-shrink: 0; position: relative; background: conic-gradient(#10b981 92%, var(--border-color) 0); display: flex; align-items: center; justify-content: center; }
    .pc-ai-fit::after { content: ''; position: absolute; inset: 5px; background: var(--bg-card); border-radius: 50%; }
    .pc-ai-fit span { position: relative; z-index: 1; font-size: 12px; font-weight: 800; color: #059669; }
    .pc-ai-fit small { position: relative; z-index: 1; font-size: 6px; }
    .pc-ai-rows { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 12px 0; }
    .pc-ai-box { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 9px; padding: 8px 10px; }
    .pc-ai-box .k { font-size: 9px; color: var(--text-muted); }
    .pc-ai-box .v { font-size: 12px; font-weight: 800; color: var(--text-primary); }
    .pc-ai-box .v.good { color: #059669; }
    .pc-ai-actions { display: flex; gap: 10px; align-items: center; }
    .pc-ai-gen { display: inline-flex; align-items: center; gap: 6px; flex: 1; justify-content: center; padding: 9px; border-radius: 9px; background: #2563eb; color: #fff; border: none; font-size: 12px; font-weight: 700; cursor: pointer; }
    .pc-ai-gen svg { width: 14px; height: 14px; }
    .pc-ai-view { font-size: 11.5px; font-weight: 700; color: var(--pc-blue); text-decoration: none; white-space: nowrap; }

    /* Bids pipeline */
    .pc-pipe { display: flex; align-items: stretch; gap: 6px; }
    .pc-pipe-step { flex: 1; min-width: 0; background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 11px; padding: 12px 11px; }
    .pc-pipe-step.win { background: rgba(37,99,235,0.06); border-color: rgba(37,99,235,0.25); }
    .pc-pipe-ico { width: 30px; height: 30px; border-radius: 8px; background: var(--bg-card); display: flex; align-items: center; justify-content: center; color: var(--pc-blue); margin-bottom: 8px; }
    .pc-pipe-ico svg { width: 15px; height: 15px; }
    .pc-pipe-name { font-size: 11.5px; font-weight: 800; color: var(--text-primary); }
    .pc-pipe-gigs { font-size: 10px; color: var(--text-muted); margin-top: 3px; }
    .pc-pipe-val { font-size: 10.5px; font-weight: 700; color: var(--text-secondary); }
    .pc-pipe-arrow { display: flex; align-items: center; color: var(--text-muted); flex-shrink: 0; }
    .pc-pipe-arrow svg { width: 16px; height: 16px; }

    @media (max-width: 1300px) { .pc-stats { grid-template-columns: repeat(3, minmax(0,1fr)); } .pc-grid { grid-template-columns: 1fr; } .pc-pipe { flex-wrap: wrap; } .pc-pipe-arrow { display: none; } .pc-pipe-step { flex: 1 1 28%; } }
    @media (max-width: 760px)  { .pc-stats { grid-template-columns: repeat(2, minmax(0,1fr)); } .pc-sub2 { grid-template-columns: 1fr; } .pc-opps { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
<div class="pc">

    {{-- Page header --}}
    <div class="pc-head">
        <span class="pc-head-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg></span>
        <div>
            <h1>Contracts</h1>
            <p>Manage your contracts, proposals, and earnings in one place.</p>
        </div>
    </div>

    {{-- ════════ 6 stat cards ════════ --}}
    <div class="pc-stats">
        <div class="pc-stat">
            <div class="pc-stat-top"><span class="pc-stat-ico ci-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span><span class="pc-stat-label">Total Earnings (MTD)</span></div>
            <div class="pc-stat-val">{{ $money($stats['earnings_mtd']) }}</div>
            <div class="pc-stat-sub">Completed this month</div>
        </div>
        <div class="pc-stat">
            <div class="pc-stat-top"><span class="pc-stat-ico ci-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><span class="pc-stat-label">Active Proposals</span></div>
            <div class="pc-stat-val">{{ $stats['active_proposals'] }}</div>
            <div class="pc-stat-sub">Awaiting response</div>
        </div>
        <div class="pc-stat">
            <div class="pc-stat-top"><span class="pc-stat-ico ci-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></span><span class="pc-stat-label">Leads Received</span></div>
            <div class="pc-stat-val">{{ $stats['leads'] }}</div>
            <div class="pc-stat-sub">Open gigs this month</div>
        </div>
        <div class="pc-stat">
            <div class="pc-stat-top"><span class="pc-stat-ico ci-purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg></span><span class="pc-stat-label">Contracts (Active)</span></div>
            <div class="pc-stat-val">{{ $stats['contracts_active'] }}</div>
            <div class="pc-stat-sub">Confirmed bookings</div>
        </div>
        <div class="pc-stat">
            <div class="pc-stat-top"><span class="pc-stat-ico ci-indigo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg></span><span class="pc-stat-label">Proposal Win Rate</span></div>
            <div class="pc-stat-val">{{ $stats['win_rate'] }}%</div>
            <div class="pc-stat-sub">Won vs decided</div>
        </div>
        <div class="pc-stat">
            <div class="pc-stat-top"><span class="pc-stat-ico ci-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><span class="pc-stat-label">Avg Rating</span></div>
            <div class="pc-stat-val">{{ $stats['avg_rating'] ?: '—' }}</div>
            <div class="pc-stat-sub">From your reviews</div>
        </div>
    </div>

    {{-- ════════ Main grid ════════ --}}
    <div class="pc-grid">

        {{-- ─── LEFT column ─── --}}
        <div class="pc-col">

            {{-- My Active Contracts --}}
            <div class="pc-card">
                <div class="pc-card-head">
                    <span class="pc-card-title">My Active Contracts</span>
                    <a href="{{ route('professional.proposals.index') }}" class="pc-card-link">View All Contracts <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                </div>
                <div class="pc-tabs">
                    <span class="pc-tab active">Active ({{ $tabCounts['active'] }})</span>
                    <span class="pc-tab">Upcoming ({{ $tabCounts['upcoming'] }})</span>
                    <span class="pc-tab">Completed ({{ $tabCounts['completed'] }})</span>
                    <span class="pc-tab">Cancelled ({{ $tabCounts['cancelled'] }})</span>
                </div>
                <div class="pc-tbl-wrap">
                <table class="pc-tbl">
                    <thead><tr><th>Event / Client</th><th>Date &amp; Time</th><th>Location</th><th>Service</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($contracts as $c)
                            @php
                                $ev = $c->event;
                                $svc = $ev?->categories->first()?->name;
                                $statusMap = ['confirmed' => ['Confirmed','b-confirmed'], 'requested' => ['In Progress','b-progress'], 'completed' => ['Completed','b-completed'], 'cancelled' => ['Cancelled','b-cancelled']];
                                [$stLabel, $stClass] = $statusMap[$c->status] ?? [ucfirst($c->status), 'b-pending'];
                            @endphp
                            <tr>
                                <td>
                                    <div class="pc-ev">
                                        <span class="pc-ev-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                                        <div style="min-width:0;">
                                            <div class="pc-ev-name">{{ \Illuminate\Support\Str::limit($ev?->title ?? 'Event', 22) }}</div>
                                            <div class="pc-ev-client">{{ $c->client?->name ?? '—' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $ev?->starts_at?->format('M d, Y') ?? '—' }}</div>
                                    <div class="pc-cell-sub">{{ $ev?->starts_at?->format('g:i A') ?? '' }}</div>
                                </td>
                                <td>{{ $ev?->location ? \Illuminate\Support\Str::limit($ev->location, 16) : '—' }}</td>
                                <td>{{ $svc ?? 'Service' }}</td>
                                <td><span class="pc-price">{{ $c->price ? $money($c->price) : ($ev?->budget ? $money($ev->budget) : '—') }}</span></td>
                                <td><span class="pc-badge {{ $stClass }}">{{ $stLabel }}</span></td>
                                <td>
                                    <span class="pc-actions">
                                        <a href="{{ route('professional.chat.index') }}" class="pc-act" title="Message"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></a>
                                        <a href="{{ route('professional.gigs.show', $ev?->id ?? 0) }}" class="pc-act" title="View gig"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></a>
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><div class="pc-empty">No active contracts yet. Win a gig from the opportunities below to get started.</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
                @if($contracts->count())
                    <div class="pc-tbl-foot"><a href="{{ route('professional.proposals.index') }}">View All Contracts →</a></div>
                @endif
            </div>

            {{-- Live Gig Opportunities + Bid Calculator --}}
            <div class="pc-sub2">
                <div class="pc-card">
                    <div class="pc-card-head">
                        <span class="pc-card-title">Live Gig Opportunities</span>
                        <a href="{{ route('professional.gigs.index') }}" class="pc-card-link">View All Gigs <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                    </div>
                    @if($opportunities->count())
                        <div class="pc-opps">
                            @foreach($opportunities as $i => $op)
                                <div class="pc-opp">
                                    <div class="pc-opp-img">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        @if($i === 0)<span class="pc-opp-tag new">New</span>@elseif($i === 1)<span class="pc-opp-tag urgent">Urgent</span>@endif
                                    </div>
                                    <div class="pc-opp-body">
                                        <div class="pc-opp-name">{{ $op->title }}</div>
                                        <div class="pc-opp-svc">{{ $op->categories->first()?->name ?? 'Event Service' }}</div>
                                        <div class="pc-opp-loc">{{ $op->location ?? 'Location TBD' }}</div>
                                        <div class="pc-opp-price">{{ $op->budget ? $money($op->budget) : 'Open budget' }}</div>
                                        <div class="pc-opp-foot">
                                            <span class="pc-opp-bids">{{ $op->starts_at?->format('M d') ?? '' }}</span>
                                            <form action="{{ route('professional.proposals.send', $op) }}" method="POST" style="margin:0;">@csrf<button type="submit" class="pc-opp-btn" style="background:none;border:none;cursor:pointer;">View &amp; Bid</button></form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="pc-empty">No open gig opportunities right now. Check back soon.</div>
                    @endif
                </div>

                {{-- AI Smart Bid Assistant (illustrative) --}}
                <div class="pc-card pc-ai">
                    <div class="pc-card-head">
                        <span class="pc-card-title" style="font-size:13.5px;">AI Smart Bid Assistant</span>
                        <span class="pc-ai-beta">BETA</span>
                    </div>
                    @php $pick = $opportunities->first(); @endphp
                    <div class="pc-ai-top">
                        <div class="pc-ai-info">
                            <div class="pc-ai-name">{{ $pick?->title ?? 'No gig selected' }}</div>
                            <div class="pc-ai-meta">{{ $pick ? ($pick->categories->first()?->name ?? 'Event Service') . ' · ' . ($pick->location ?? 'TBD') : 'Pick a gig to analyse' }}</div>
                        </div>
                        <div class="pc-ai-fit"><span>92<small>%</small></span></div>
                    </div>
                    <div class="pc-ai-rows">
                        <div class="pc-ai-box"><div class="k">Suggested Bid</div><div class="v">{{ $pick?->budget ? $money($pick->budget) : '$2,000 - $3,000' }}</div></div>
                        <div class="pc-ai-box"><div class="k">Win Probability</div><div class="v good">High (78%)</div></div>
                    </div>
                    <div class="pc-ai-actions">
                        <button class="pc-ai-gen"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.9 4.6L18.5 9.5 13.9 11.4 12 16l-1.9-4.6L5.5 9.5 10.1 7.6 12 3z"/></svg>Generate Proposal</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── RIGHT column ─── --}}
        <div class="pc-col">

            {{-- Earnings Overview --}}
            <div class="pc-card">
                <div class="pc-card-head"><span class="pc-card-title">Earnings Overview</span></div>
                <div class="pc-earn-total">Total Revenue</div>
                <div class="pc-earn-amt">{{ $money($totalRevenue) }}</div>
                <div class="pc-earn-up">{{ $money($stats['earnings_mtd']) }} this month</div>
                <div class="pc-earn-body">
                    <div class="pc-donut {{ $totalRevenue <= 0 ? 'empty' : '' }}"></div>
                    <div class="pc-earn-legend">
                        <div class="pc-earn-leg"><span class="dot" style="background:#2563eb;"></span><span class="nm">Paid</span><span class="vl">{{ $money($sumPaid) }}</span> <span class="pc">({{ $pctPaid }}%)</span></div>
                        <div class="pc-earn-leg"><span class="dot" style="background:#06b6d4;"></span><span class="nm">In Secure Payment</span><span class="vl">{{ $money($sumSecure Payment) }}</span> <span class="pc">({{ $pctSecure Payment }}%)</span></div>
                        <div class="pc-earn-leg"><span class="dot" style="background:#f97316;"></span><span class="nm">Pending</span><span class="vl">{{ $money($sumPending) }}</span> <span class="pc">({{ $pctPending }}%)</span></div>
                    </div>
                </div>
                <div class="pc-earn-foot"><a href="{{ route('professional.earnings.index') }}">View Earnings Report →</a></div>
            </div>

            {{-- Upcoming Gig Schedule --}}
            <div class="pc-card">
                <div class="pc-card-head">
                    <span class="pc-card-title">Upcoming Gig Schedule</span>
                    <a href="{{ route('professional.gigs.index') }}" class="pc-card-link">View Calendar <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                </div>
                @forelse($upcoming as $b)
                    <div class="pc-sch-row">
                        <div class="pc-sch-date"><div class="m">{{ $b->event?->starts_at?->format('M') }}</div><div class="d">{{ $b->event?->starts_at?->format('d') }}</div></div>
                        <div class="pc-sch-body">
                            <div class="pc-sch-name">{{ \Illuminate\Support\Str::limit($b->event?->title ?? 'Event', 26) }}</div>
                            <div class="pc-sch-meta">{{ $b->event?->starts_at?->format('g:i A') }} · {{ $b->client?->name ?? '—' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="pc-empty" style="padding:16px;">No upcoming gigs scheduled.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ════════ My Bids Pipeline ════════ --}}
    <div class="pc-card" style="margin-top:14px;">
        <div class="pc-card-head"><span class="pc-card-title">My Bids Pipeline</span></div>
        <div class="pc-pipe">
            @php
                $steps = [
                    ['Drafts', 0, '$0', 'edit'],
                    ['Submitted', $pipeline['submitted'], $money($pipeline['submitted_value']), 'send'],
                    ['Interviewing', 0, '$0', 'users'],
                    ['Shortlisted', 0, '$0', 'star'],
                    ['Hired', $pipeline['hired'], $money($pipeline['hired_value']), 'check'],
                ];
            @endphp
            @foreach($steps as $idx => $s)
                <div class="pc-pipe-step">
                    <div class="pc-pipe-ico">
                        @switch($s[3])
                            @case('send') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> @break
                            @case('users') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg> @break
                            @case('star') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> @break
                            @case('check') <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg> @break
                            @default <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                        @endswitch
                    </div>
                    <div class="pc-pipe-name">{{ $s[0] }} ({{ $s[1] }})</div>
                    <div class="pc-pipe-gigs">{{ $s[1] }} {{ \Illuminate\Support\Str::plural('gig', $s[1]) }}</div>
                    <div class="pc-pipe-val">{{ $s[2] }} value</div>
                </div>
                <div class="pc-pipe-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
            @endforeach
            <div class="pc-pipe-step win">
                <div class="pc-pipe-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg></div>
                <div class="pc-pipe-name">Won This Month ({{ $pipeline['won_month'] }})</div>
                <div class="pc-pipe-gigs">{{ $pipeline['won_month'] }} {{ \Illuminate\Support\Str::plural('gig', $pipeline['won_month']) }}</div>
                <div class="pc-pipe-val">{{ $money($pipeline['won_value']) }} earned</div>
            </div>
        </div>
    </div>
</div>
@endsection
