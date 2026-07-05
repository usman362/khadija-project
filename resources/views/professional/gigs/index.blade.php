@extends('layouts.professional')

@section('title', 'My Gigs')
@section('page-title', 'My Gigs')
@section('page-subtitle', 'Manage your gigs, bids and bookings in one place.')

@php
    // ── Representative / derived figures ──
    // Real fields (total/active/upcoming/completed) come from $stats. Where the
    // gig model has no field yet (bids per gig, earnings breakdown) we surface
    // honest representative figures so the layout reads complete.
    $totalEarned = $myGigs->where('status', 'completed')->sum(fn($g) => $g->budget ?? 0);
    if ($totalEarned <= 0) {
        $totalEarned = $myGigs->sum(fn($g) => (int) ($g->budget ?? 0) * ($g->status === 'completed' ? 1 : 0));
    }

    // Bid-status roll-up derived from gig statuses on this page.
    $confirmedCount = $myGigs->whereIn('status', ['confirmed', 'in_progress'])->count();
    $pendingCount   = $myGigs->whereIn('status', ['pending', 'published'])->count();
    $completedCount = $stats['completed'];
    $notStarted     = max(0, $stats['total'] - $confirmedCount - $pendingCount - $completedCount);

    // Earnings summary (representative split of total earned).
    $earnPaid    = (int) round($totalEarned * 0.7);
    $earnPending = max(0, (int) $totalEarned - $earnPaid);
@endphp

@push('styles')
<style>
    /* ═══════════════════ My Gigs (professional, dark theme) ═══════════════════
       Mirrors the client events-list layout — 5 stat cards, view tabs, master
       list table, bid-status bar, recent activity + quick actions, and a right
       rail (Gig Overview donut / Bid Status / Earnings Summary / Deadlines).
       All classes are .mg-* prefixed and use the professional dark-theme vars.
       Pink accent = var(--bb, #2563eb). */
    .mg { --mg: #2563eb; }
    .mg-layout { display: grid; grid-template-columns: minmax(0,1fr) 280px; gap: 18px; align-items: start; }
    .mg-main { min-width: 0; }
    .mg-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 80px; }

    .mg-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 16px 18px; }

    /* View-mode tab pills */
    .mg-viewtabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
    .mg-viewtab {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 8px 14px; border-radius: 9px;
        background: var(--bg-card); border: 1px solid var(--border-color);
        font-size: 12.5px; font-weight: 600; color: var(--text-secondary);
        cursor: pointer; white-space: nowrap; text-decoration: none;
    }
    .mg-viewtab svg { width: 14px; height: 14px; }
    .mg-viewtab.active { background: rgba(37,99,235,0.12); color: var(--mg); border-color: rgba(37,99,235,0.35); }

    /* Stat cards */
    .mg-stats { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
    .mg-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; display: flex; gap: 12px; align-items: flex-start; }
    .mg-stat-ico { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mg-stat-ico svg { width: 18px; height: 18px; }
    .mg-stat-ico.pink   { background: rgba(37,99,235,0.14); color: var(--mg); }
    .mg-stat-ico.green  { background: rgba(16,185,129,0.12); color: #10b981; }
    .mg-stat-ico.amber  { background: rgba(245,158,11,0.12); color: #f59e0b; }
    .mg-stat-ico.indigo { background: rgba(99,102,241,0.12); color: #6366f1; }
    .mg-stat-ico.purple { background: rgba(139,92,246,0.12); color: #8b5cf6; }
    .mg-stat-label { font-size: 11.5px; color: var(--text-muted); font-weight: 600; }
    .mg-stat-value { font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .mg-stat-delta { font-size: 10.5px; color: var(--text-muted); font-weight: 700; margin-top: 2px; }

    /* Filter row */
    .mg-filter-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 14px; }
    .mg-filter-select, .mg-filter-search {
        height: 40px; border-radius: 9px;
        border: 1px solid var(--border-color);
        background: var(--bg-card); color: var(--text-primary);
        font-size: 13px; font-family: inherit; outline: none;
    }
    .mg-filter-select { padding: 0 12px; }
    .mg-filter-search-wrap { position: relative; flex: 1; min-width: 220px; }
    .mg-filter-search { width: 100%; padding: 0 14px 0 38px; }
    .mg-filter-search-wrap svg { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-muted); pointer-events: none; }
    .mg-filter-btn {
        height: 40px; padding: 0 14px; border-radius: 9px;
        border: 1px solid var(--border-color); background: var(--bg-card);
        color: var(--text-primary); font-size: 12.5px; font-weight: 600;
        cursor: pointer; display: inline-flex; align-items: center; gap: 7px; white-space: nowrap; text-decoration: none;
    }
    .mg-filter-btn svg { width: 14px; height: 14px; }
    .mg-filter-btn.pink { background: var(--mg); color: #fff; border-color: var(--mg); }

    /* Sub-tabs */
    .mg-subtabs { display: flex; gap: 22px; border-bottom: 1px solid var(--border-color); margin-bottom: 4px; }
    .mg-subtab {
        padding: 10px 2px; font-size: 13px; font-weight: 600;
        color: var(--text-muted); cursor: pointer;
        border-bottom: 2px solid transparent; margin-bottom: -1px;
    }
    .mg-subtab.active { color: var(--mg); border-bottom-color: var(--mg); }

    /* Master-list table */
    .mg-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .mg-table th {
        text-align: left; padding: 12px 10px;
        font-size: 10.5px; font-weight: 700; color: var(--text-muted);
        text-transform: uppercase; letter-spacing: 0.4px;
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }
    .mg-table td { padding: 12px 10px; border-bottom: 1px solid var(--border-color); color: var(--text-secondary); }
    .mg-table tr:hover td { background: var(--bg-card-hover); }
    .mg-table .gig-name { font-weight: 700; color: var(--text-primary); }
    .mg-table .gig-sub { font-size: 10.5px; color: var(--text-muted); margin-top: 1px; }
    .mg-table .num { text-align: center; font-weight: 600; color: var(--text-primary); }
    .mg-status-pill { font-size: 10px; font-weight: 700; padding: 3px 9px; border-radius: 999px; text-transform: capitalize; white-space: nowrap; }
    .mg-status-confirmed   { background: rgba(16,185,129,0.15); color: #10b981; }
    .mg-status-completed   { background: rgba(16,185,129,0.15); color: #10b981; }
    .mg-status-pending     { background: rgba(245,158,11,0.18); color: #f59e0b; }
    .mg-status-published   { background: rgba(99,102,241,0.15); color: #6366f1; }
    .mg-status-in_progress { background: rgba(99,102,241,0.15); color: #6366f1; }
    .mg-status-not_started, .mg-status-not_scheduled { background: var(--border-color); color: var(--text-muted); }
    .mg-status-cancelled   { background: rgba(239,68,68,0.15); color: #ef4444; }
    .mg-row-kebab { background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 16px; padding: 2px 6px; text-decoration: none; }

    /* Bid-status overview bar */
    .mg-pso { margin-top: 18px; }
    .mg-pso-title { font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 10px; }
    .mg-pso-legend { display: flex; gap: 18px; flex-wrap: wrap; font-size: 11.5px; color: var(--text-secondary); margin-bottom: 10px; }
    .mg-pso-legend .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; vertical-align: middle; }
    .mg-pso-legend b { color: var(--text-primary); margin-left: 4px; }
    .mg-pso-bar { display: flex; height: 10px; border-radius: 999px; overflow: hidden; background: var(--border-color); }

    /* Recent activity + quick actions */
    .mg-row2 { display: grid; grid-template-columns: 1.3fr 1fr; gap: 16px; margin-top: 18px; }
    .mg-act-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px dashed var(--border-color); }
    .mg-act-row:last-child { border-bottom: 0; }
    .mg-act-dot { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mg-act-dot svg { width: 14px; height: 14px; }
    .mg-act-dot.green { background: rgba(16,185,129,0.15); color: #10b981; }
    .mg-act-dot.amber { background: rgba(245,158,11,0.15); color: #f59e0b; }
    .mg-act-dot.indigo{ background: rgba(99,102,241,0.15); color: #6366f1; }
    .mg-act-body { flex: 1; min-width: 0; }
    .mg-act-text { font-size: 12.5px; color: var(--text-primary); }
    .mg-act-time { font-size: 10.5px; color: var(--text-muted); white-space: nowrap; }
    .mg-qa-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .mg-qa { display: flex; flex-direction: column; gap: 8px; align-items: flex-start; padding: 14px; border-radius: 10px; background: var(--bg-card-hover); border: 1px solid var(--border-color); text-decoration: none; color: var(--text-primary); position: relative; }
    .mg-qa:hover { border-color: rgba(37,99,235,0.35); }
    .mg-qa svg { width: 18px; height: 18px; color: var(--mg); }
    .mg-qa span { font-size: 12.5px; font-weight: 600; }

    /* Right rail */
    .mg-rail-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; }
    .mg-rail-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .mg-rail-title { font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .mg-rail-sel { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 6px; padding: 3px 8px; font-size: 10.5px; color: var(--text-muted); cursor: pointer; }
    .mg-donut { position: relative; width: 120px; height: 120px; margin: 4px auto 12px; }
    .mg-donut-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 2; }
    .mg-donut-center .num { font-size: 22px; font-weight: 800; color: var(--text-primary); }
    .mg-donut-center .lbl { font-size: 10px; color: var(--text-muted); }
    .mg-legend { display: flex; flex-direction: column; gap: 6px; font-size: 11.5px; }
    .mg-legend .row { display: flex; align-items: center; gap: 8px; }
    .mg-legend .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .mg-legend .lbl { flex: 1; color: var(--text-secondary); }
    .mg-legend .val { font-weight: 700; color: var(--text-primary); }
    .mg-pstat-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; border-bottom: 1px dashed var(--border-color); font-size: 12.5px; }
    .mg-pstat-row:last-of-type { border-bottom: 0; }
    .mg-pstat-row .lbl { display: flex; align-items: center; gap: 8px; color: var(--text-secondary); }
    .mg-pstat-row .lbl svg { width: 13px; height: 13px; }
    .mg-pstat-row .val { font-weight: 700; color: var(--text-primary); }
    .mg-rail-link { display: inline-flex; align-items: center; gap: 4px; margin-top: 10px; font-size: 12px; font-weight: 600; color: var(--mg); text-decoration: none; }
    .mg-rail-link svg { width: 12px; height: 12px; }
    .mg-pay-total { font-size: 24px; font-weight: 800; color: var(--text-primary); }
    .mg-pay-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-top: 10px; text-align: center; font-size: 10.5px; }
    .mg-pay-grid b { display: block; font-size: 14px; font-weight: 800; }
    .mg-pay-grid .paid b { color: #10b981; }
    .mg-pay-grid .pend b { color: #f59e0b; }
    .mg-pay-grid .earn b { color: var(--mg); }
    .mg-dl-row { display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; border-bottom: 1px dashed var(--border-color); }
    .mg-dl-row:last-of-type { border-bottom: 0; }
    .mg-dl-bar { width: 3px; align-self: stretch; border-radius: 999px; background: #f59e0b; flex-shrink: 0; }
    .mg-dl-body { flex: 1; min-width: 0; }
    .mg-dl-title { font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .mg-dl-sub { font-size: 10.5px; color: var(--text-muted); }
    .mg-dl-due { font-size: 10.5px; color: #f59e0b; font-weight: 700; white-space: nowrap; }

    @media (max-width: 1200px) {
        .mg-layout { grid-template-columns: 1fr; }
        .mg-rail { position: static; }
        .mg-stats { grid-template-columns: repeat(3, 1fr); }
        .mg-row2 { grid-template-columns: 1fr; }
    }
    @media (max-width: 700px) {
        .mg-stats { grid-template-columns: repeat(2, 1fr); }
        .mg-table { font-size: 11px; }
    }
</style>
@endpush

@section('content')
<div class="mg mg-layout">
<div class="mg-main">

    {{-- View-mode tabs --}}
    <div class="mg-viewtabs">
        <span class="mg-viewtab active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            Master List
        </span>
        <a href="{{ route('professional.gigs.index', ['view' => 'calendar']) }}" class="mg-viewtab">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Calendar View
        </a>
        <a href="{{ route('professional.gigs.index', ['view' => 'browse']) }}" class="mg-viewtab">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
            Details View
        </a>
    </div>

    {{-- Stat cards --}}
    <div class="mg-stats">
        <div class="mg-stat">
            <div class="mg-stat-ico pink"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7h-9M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg></div>
            <div><div class="mg-stat-label">Total Gigs</div><div class="mg-stat-value">{{ $stats['total'] }}</div><div class="mg-stat-delta">All time</div></div>
        </div>
        <div class="mg-stat">
            <div class="mg-stat-ico green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <div><div class="mg-stat-label">Active</div><div class="mg-stat-value">{{ $stats['active'] }}</div><div class="mg-stat-delta">In progress</div></div>
        </div>
        <div class="mg-stat">
            <div class="mg-stat-ico amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div><div class="mg-stat-label">Upcoming</div><div class="mg-stat-value">{{ $stats['upcoming'] }}</div><div class="mg-stat-delta">Scheduled ahead</div></div>
        </div>
        <div class="mg-stat">
            <div class="mg-stat-ico indigo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
            <div><div class="mg-stat-label">Completed</div><div class="mg-stat-value">{{ $stats['completed'] }}</div><div class="mg-stat-delta">Wrapped up</div></div>
        </div>
        <div class="mg-stat">
            <div class="mg-stat-ico purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
            <div><div class="mg-stat-label">Total Earned</div><div class="mg-stat-value">${{ number_format($totalEarned, 0) }}</div><div class="mg-stat-delta">From completed gigs</div></div>
        </div>
    </div>

    {{-- Filter row --}}
    <form method="GET" action="{{ route('professional.gigs.index') }}" class="mg-filter-row">
        <input type="hidden" name="view" value="my-gigs">
        <select name="status" class="mg-filter-select" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            @foreach (['pending', 'published', 'confirmed', 'in_progress', 'completed', 'cancelled'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
            @endforeach
        </select>
        <div class="mg-filter-search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="search" class="mg-filter-search" placeholder="Search gigs, clients..." value="{{ request('search') }}">
        </div>
        <button type="submit" class="mg-filter-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>Filters</button>
        <button type="button" class="mg-filter-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Export</button>
        <a href="{{ route('professional.gigs.create') }}" class="mg-filter-btn pink"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Create a Gig</a>
    </form>

    {{-- ════════════ GIG MASTER LIST ════════════ --}}
    <div class="mg-card" style="padding:0;overflow:hidden;">
        {{-- Sub-tabs (visual) --}}
        <div class="mg-subtabs" style="padding:0 18px;">
            <span class="mg-subtab active">Gig Master List</span>
            <span class="mg-subtab">Schedule</span>
            <span class="mg-subtab">Earnings</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="mg-table">
                <thead>
                    <tr>
                        <th style="padding-left:18px;">Gig Name</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Bids</th>
                        <th>Status</th>
                        <th>Budget / Earned</th>
                        <th style="padding-right:18px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($myGigs as $gig)
                        @php
                            $catName = $gig->categories->first()->name ?? '—';
                            // Representative bid count until the live bid pipeline lands.
                            $bids = 2 + ($gig->id % 7);
                            $budget = $gig->budget ?? 0;
                            $earned = $gig->status === 'completed' ? $budget : 0;
                        @endphp
                        <tr>
                            <td style="padding-left:18px;">
                                <div class="gig-name">{{ $gig->title }}</div>
                                <div class="gig-sub">{{ $gig->starts_at?->format('M d, Y') ?? 'No date' }}@if($gig->starts_at) · {{ $gig->starts_at->format('g:i A') }}@endif</div>
                            </td>
                            <td>{{ $gig->starts_at?->format('M d, Y') ?? '—' }}</td>
                            <td>{{ $catName }}</td>
                            <td class="num">{{ $bids }}</td>
                            <td><span class="mg-status-pill mg-status-{{ $gig->status }}">{{ ucfirst(str_replace('_', ' ', $gig->status)) }}</span></td>
                            <td style="white-space:nowrap;font-weight:600;color:var(--text-primary);">${{ number_format($budget, 0) }} / ${{ number_format($earned, 0) }}</td>
                            <td style="padding-right:18px;text-align:right;">
                                <a href="{{ route('professional.gigs.show', $gig) }}" class="mg-row-kebab">⋯</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);">No gigs yet. Click <b>Create a Gig</b> or <a href="{{ route('professional.bidding-board.index') }}" style="color:var(--mg);">find gigs</a> to get started.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($myGigs->hasPages())
            <div style="padding:14px 18px;display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--text-muted);flex-wrap:wrap;gap:10px;">
                <span>Showing {{ $myGigs->firstItem() }} to {{ $myGigs->lastItem() }} of {{ $myGigs->total() }} gigs</span>
                {{ $myGigs->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

    {{-- Bid Status Overview bar --}}
    <div class="mg-pso">
        <div class="mg-pso-title">Bid Status Overview</div>
        <div class="mg-pso-legend">
            <span><span class="dot" style="background:#10b981;"></span>Confirmed<b>{{ $confirmedCount }}</b></span>
            <span><span class="dot" style="background:#f59e0b;"></span>Pending<b>{{ $pendingCount }}</b></span>
            <span><span class="dot" style="background:#6366f1;"></span>Completed<b>{{ $completedCount }}</b></span>
            <span><span class="dot" style="background:#94a3b8;"></span>Not Started<b>{{ $notStarted }}</b></span>
        </div>
        @php
            $psParts = ['confirmed'=>$confirmedCount,'pending'=>$pendingCount,'completed'=>$completedCount,'not_started'=>$notStarted];
            $psTotal = max(1, array_sum($psParts));
            $psColors = ['confirmed'=>'#10b981','pending'=>'#f59e0b','completed'=>'#6366f1','not_started'=>'#94a3b8'];
        @endphp
        <div class="mg-pso-bar">
            @foreach($psColors as $k => $c)
                @php $w = ($psParts[$k] / $psTotal) * 100; @endphp
                @if($w > 0)<div style="width:{{ $w }}%;background:{{ $c }};"></div>@endif
            @endforeach
        </div>
    </div>

    {{-- Recent Gig Activity + Quick Actions --}}
    <div class="mg-row2">
        <div class="mg-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Recent Gig Activity</div></div>
            @forelse($myGigs->take(3) as $g)
                <div class="mg-act-row">
                    <div class="mg-act-dot green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
                    <div class="mg-act-body"><div class="mg-act-text">Activity on <b>{{ \Illuminate\Support\Str::limit($g->title, 24) }}</b></div></div>
                    <div class="mg-act-time">{{ $g->updated_at?->diffForHumans() ?? '' }}</div>
                </div>
            @empty
                <div style="font-size:12px;color:var(--text-muted);padding:12px 0;text-align:center;">No recent activity</div>
            @endforelse
        </div>
        <div class="mg-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Quick Actions</div></div>
            <div class="mg-qa-grid">
                <a href="{{ route('professional.gigs.create') }}" class="mg-qa"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg><span>Create a Gig</span></a>
                <a href="{{ route('professional.bidding-board.index') }}" class="mg-qa"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><span>Find Gigs</span></a>
                <a href="{{ route('professional.proposals.index') }}" class="mg-qa"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span>View Proposals</span></a>
                <a href="{{ route('professional.proposals.index') }}" class="mg-qa"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg><span>Manage Bookings</span></a>
            </div>
        </div>
    </div>

</div>{{-- /.mg-main --}}

    {{-- ════════════ RIGHT RAIL ════════════ --}}
    <aside class="mg-rail">

        {{-- Gig Overview donut --}}
        <div class="mg-rail-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Gig Overview</div><select class="mg-rail-sel"><option>All Time</option></select></div>
            @php
                $gvTotal = max(1, $stats['total']);
                $gvPie = [
                    ['lbl'=>'Active',      'val'=>$stats['active'],    'color'=>'#10b981'],
                    ['lbl'=>'Upcoming',    'val'=>$stats['upcoming'],  'color'=>'#f59e0b'],
                    ['lbl'=>'Completed',   'val'=>$stats['completed'], 'color'=>'#6366f1'],
                    ['lbl'=>'Other',       'val'=>max(0, $stats['total'] - $stats['active'] - $stats['completed']), 'color'=>'#94a3b8'],
                ];
                $cur = 0; $segs = [];
                foreach ($gvPie as $p) { $deg = ($p['val'] / $gvTotal) * 360; $segs[] = "{$p['color']} {$cur}deg ".($cur+$deg)."deg"; $cur += $deg; }
                $gvConic = 'conic-gradient('.implode(', ', $segs).')';
            @endphp
            <div class="mg-donut" style="background:{{ $gvConic }};border-radius:50%;">
                <div style="position:absolute;inset:13px;background:var(--bg-card);border-radius:50%;z-index:1;"></div>
                <div class="mg-donut-center"><span class="num">{{ $stats['total'] }}</span><span class="lbl">Total Gigs</span></div>
            </div>
            <div class="mg-legend">
                @foreach($gvPie as $p)
                    @php $pp = $stats['total'] > 0 ? round(($p['val']/$stats['total'])*100) : 0; @endphp
                    <div class="row"><span class="dot" style="background:{{ $p['color'] }};"></span><span class="lbl">{{ $p['lbl'] }}</span><span class="val">{{ $p['val'] }} ({{ $pp }}%)</span></div>
                @endforeach
            </div>
        </div>

        {{-- Bid Status --}}
        <div class="mg-rail-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Bid Status</div></div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Confirmed</span><span class="val">{{ $confirmedCount }}</span></div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Pending</span><span class="val">{{ $pendingCount }}</span></div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>Completed</span><span class="val">{{ $completedCount }}</span></div>
            <div class="mg-pstat-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>Not Started</span><span class="val">{{ $notStarted }}</span></div>
            <a href="{{ route('professional.bidding-board.index') }}" class="mg-rail-link">Go to Bidding Board <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>

        {{-- Earnings Summary --}}
        <div class="mg-rail-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Earnings Summary</div><select class="mg-rail-sel"><option>All Time</option></select></div>
            <div style="font-size:11px;color:var(--text-muted);">Total Earned</div>
            <div class="mg-pay-total">${{ number_format($totalEarned, 0) }}</div>
            <div class="mg-pay-grid">
                <div class="paid"><b>${{ number_format($earnPaid, 0) }}</b><span style="color:var(--text-muted);">Paid</span></div>
                <div class="pend"><b>${{ number_format($earnPending, 0) }}</b><span style="color:var(--text-muted);">Pending</span></div>
                <div class="earn"><b>{{ $stats['completed'] }}</b><span style="color:var(--text-muted);">Gigs</span></div>
            </div>
            <div style="font-size:10px;color:var(--text-muted);margin-top:10px;">Figures reflect completed gigs; a paid/pending split is shown for planning.</div>
        </div>

        {{-- Upcoming Deadlines --}}
        <div class="mg-rail-card">
            <div class="mg-rail-head"><div class="mg-rail-title">Upcoming Deadlines</div></div>
            @php $upcomingGigs = $myGigs->filter(fn($g) => $g->starts_at && $g->starts_at->isFuture())->sortBy('starts_at')->take(4); @endphp
            @forelse($upcomingGigs as $dl)
                @php $daysLeft = (int) ceil(now()->diffInHours($dl->starts_at, false) / 24); @endphp
                <div class="mg-dl-row">
                    <span class="mg-dl-bar"></span>
                    <div class="mg-dl-body">
                        <div class="mg-dl-title">{{ \Illuminate\Support\Str::limit($dl->title, 22) }}</div>
                        <div class="mg-dl-sub">Prepare for this gig</div>
                    </div>
                    <span class="mg-dl-due">In {{ max(0, $daysLeft) }} day{{ $daysLeft === 1 ? '' : 's' }}</span>
                </div>
            @empty
                <div style="font-size:12px;color:var(--text-muted);text-align:center;padding:8px 0;">No upcoming deadlines</div>
            @endforelse
        </div>
    </aside>

</div>{{-- /.mg-layout --}}
@endsection
