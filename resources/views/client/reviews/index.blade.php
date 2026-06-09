@extends('layouts.client')

@section('title', 'Reviews')
@section('page-title', 'Reviews')
@section('page-subtitle', 'Client feedback and ratings about your hired professionals.')

@push('styles')
<style>
    /* ═══════════════════ Reviews page ═══════════════════
       Matches Khadija's "Reviews Client_s side" mockup — 5 stat cards,
       rating filter chips, detailed review cards (performance metrics +
       payment/dispute + contract compliance + pro reputation), and a
       right rail (Reputation Overview / Trust & Verification / Review
       Highlights / Pending Review Requests). */
    .rv-layout { display: grid; grid-template-columns: minmax(0,1fr) 280px; gap: 18px; align-items: start; }
    .rv-main { min-width: 0; }
    .rv-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 80px; }
    .rv-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 16px 18px; }

    /* Stat cards */
    .rv-stats { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
    .rv-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; display: flex; gap: 12px; align-items: flex-start; }
    .rv-stat-ico { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .rv-stat-ico svg { width: 18px; height: 18px; }
    .rv-stat-ico.coral  { background: rgba(249,115,22,0.12); color: #f97316; }
    .rv-stat-ico.amber  { background: rgba(245,158,11,0.12); color: #f59e0b; }
    .rv-stat-ico.green  { background: rgba(16,185,129,0.12); color: #10b981; }
    .rv-stat-ico.red    { background: rgba(239,68,68,0.12); color: #ef4444; }
    .rv-stat-ico.indigo { background: rgba(99,102,241,0.12); color: #6366f1; }
    .rv-stat-label { font-size: 11.5px; color: var(--text-muted); font-weight: 600; }
    .rv-stat-value { font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .rv-stat-sub { font-size: 10.5px; color: var(--text-muted); margin-top: 2px; }

    /* Rating filter chips */
    .rv-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
    .rv-chip { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); font-size: 12.5px; font-weight: 600; text-decoration: none; }
    .rv-chip.active { background: rgba(249,115,22,0.10); border-color: rgba(249,115,22,0.30); color: #f97316; }
    .rv-chip .cnt { color: var(--text-muted); }
    .rv-chip.active .cnt { color: #f97316; }

    /* Toolbar */
    .rv-toolbar { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
    .rv-tool-btn { height: 38px; padding: 0 13px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); font-size: 12.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
    .rv-tool-btn svg { width: 13px; height: 13px; }
    .rv-search { position: relative; flex: 1; min-width: 200px; }
    .rv-search input { width: 100%; height: 38px; padding: 0 14px 0 36px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); font-size: 12.5px; outline: none; font-family: inherit; }
    .rv-search svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: var(--text-muted); pointer-events: none; }

    /* Review cards */
    .rv-cards { display: flex; flex-direction: column; gap: 16px; }
    .rv-rc { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); overflow: hidden; }
    .rv-rc-head { display: flex; align-items: center; gap: 14px; padding: 16px 18px; border-bottom: 1px solid var(--border-color); }
    .rv-rc-avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; flex-shrink: 0; border: 2px solid var(--border-color); }
    .rv-rc-stars { display: flex; align-items: center; gap: 8px; }
    .rv-rc-stars .stars { color: #f59e0b; font-size: 15px; letter-spacing: 1px; }
    .rv-rc-stars .score { font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .rv-rc-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 700; color: #10b981; }
    .rv-rc-badge svg { width: 11px; height: 11px; }
    .rv-rc-title { font-size: 14.5px; font-weight: 700; color: var(--text-primary); margin-top: 2px; }
    .rv-rc-meta { font-size: 11px; color: var(--text-muted); display: flex; gap: 12px; flex-wrap: wrap; margin-top: 2px; }
    .rv-rc-gateway { display: inline-flex; align-items: center; gap: 5px; padding: 4px 9px; border-radius: 6px; background: var(--bg-card-hover); border: 1px solid var(--border-color); font-size: 11px; font-weight: 600; color: var(--text-secondary); }
    .rv-rc-gateway svg { width: 12px; height: 12px; }
    .rv-rc-payout { font-size: 11px; font-weight: 700; color: #10b981; display: inline-flex; align-items: center; gap: 4px; }
    .rv-rc-payout svg { width: 12px; height: 12px; }
    .rv-rc-kebab { background: none; border: none; cursor: pointer; color: var(--text-muted); font-size: 16px; }

    /* 4-column metrics body */
    .rv-rc-body { display: grid; grid-template-columns: 1.1fr 1.1fr 1.1fr 1fr; gap: 16px; padding: 16px 18px; }
    .rv-col-title { font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 5px; margin-bottom: 10px; }
    .rv-col-title svg { width: 11px; height: 11px; }
    .rv-metric { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; font-size: 11.5px; }
    .rv-metric .lbl { color: var(--text-muted); }
    .rv-metric .val { color: var(--text-primary); font-weight: 600; }
    .rv-metric-stars { color: #f59e0b; font-size: 11px; letter-spacing: 0.5px; }
    .rv-ok { color: #10b981; }
    .rv-warn { color: #f59e0b; }
    .rv-pro-box { background: var(--bg-card-hover); border-radius: 10px; padding: 12px; text-align: center; }
    .rv-pro-name { font-size: 12px; font-weight: 700; color: var(--text-primary); }
    .rv-pro-role { font-size: 10px; color: var(--text-muted); margin-bottom: 8px; }
    .rv-pro-scores { display: flex; gap: 8px; }
    .rv-pro-scores > div { flex: 1; }
    .rv-pro-scores .num { font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .rv-pro-scores .lbl { font-size: 9px; color: var(--text-muted); }

    /* Feedback + AI insight */
    .rv-feedback { padding: 12px 18px; border-top: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .rv-feedback-text { flex: 1; min-width: 220px; font-size: 12.5px; color: var(--text-secondary); }
    .rv-feedback-text svg { width: 13px; height: 13px; color: var(--text-muted); vertical-align: -2px; margin-right: 4px; }
    .rv-feedback-text a { color: #f97316; font-weight: 600; text-decoration: none; }
    .rv-helpful { display: flex; align-items: center; gap: 12px; font-size: 11.5px; color: var(--text-muted); }
    .rv-helpful button { background: none; border: none; cursor: pointer; color: var(--text-muted); display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px; }
    .rv-helpful svg { width: 13px; height: 13px; }
    .rv-ai-insight { padding: 11px 18px; border-top: 1px solid var(--border-color); background: rgba(99,102,241,0.04); display: flex; align-items: center; gap: 10px; }
    .rv-ai-ico { width: 26px; height: 26px; border-radius: 7px; background: rgba(99,102,241,0.12); color: #6366f1; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .rv-ai-ico svg { width: 14px; height: 14px; }
    .rv-ai-body { flex: 1; font-size: 11.5px; color: var(--text-secondary); }
    .rv-ai-body b { color: var(--text-primary); }
    .rv-ai-link { font-size: 11.5px; color: #f97316; text-decoration: none; font-weight: 600; white-space: nowrap; }

    /* Right rail */
    .rv-rail-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 14px 16px; }
    .rv-rail-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .rv-rail-title { font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .rv-rail-sel { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 6px; padding: 3px 8px; font-size: 10.5px; color: var(--text-muted); cursor: pointer; }
    .rv-rep { display: flex; gap: 14px; align-items: center; margin-bottom: 10px; }
    .rv-rep-ring { width: 80px; height: 80px; flex-shrink: 0; }
    .rv-rep-big { font-size: 26px; font-weight: 800; color: var(--text-primary); }
    .rv-rep-of { font-size: 11px; color: var(--text-muted); }
    .rv-dist-row { display: flex; align-items: center; gap: 8px; font-size: 11px; padding: 3px 0; }
    .rv-dist-star { color: var(--text-muted); width: 12px; }
    .rv-dist-bar { flex: 1; height: 6px; border-radius: 999px; background: var(--border-color); overflow: hidden; }
    .rv-dist-fill { height: 100%; background: #f59e0b; border-radius: 999px; }
    .rv-dist-val { font-size: 10.5px; color: var(--text-muted); white-space: nowrap; }
    .rv-tv-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; border-bottom: 1px dashed var(--border-color); font-size: 12px; }
    .rv-tv-row:last-child { border-bottom: 0; }
    .rv-tv-row .lbl { display: flex; align-items: center; gap: 8px; color: var(--text-secondary); }
    .rv-tv-row .lbl svg { width: 13px; height: 13px; color: #10b981; }
    .rv-tv-row .val { font-weight: 700; color: var(--text-primary); }
    .rv-hl-row { display: flex; align-items: center; gap: 8px; padding: 7px 0; font-size: 11.5px; }
    .rv-hl-row svg { width: 13px; height: 13px; flex-shrink: 0; }
    .rv-hl-label { color: var(--text-muted); flex-shrink: 0; }
    .rv-hl-val { color: var(--text-primary); font-weight: 600; text-align: right; flex: 1; }
    .rv-pend-row { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px dashed var(--border-color); }
    .rv-pend-row:last-child { border-bottom: 0; }
    .rv-pend-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    .rv-pend-body { flex: 1; min-width: 0; }
    .rv-pend-name { font-size: 12px; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rv-pend-date { font-size: 10px; color: var(--text-muted); }
    .rv-pend-btn { font-size: 10px; font-weight: 700; padding: 5px 9px; border-radius: 6px; background: rgba(249,115,22,0.10); color: #f97316; border: 1px solid rgba(249,115,22,0.25); cursor: pointer; white-space: nowrap; }
    .rv-pend-left { font-size: 9.5px; color: #f59e0b; font-weight: 700; }

    @media (max-width: 1200px) { .rv-layout { grid-template-columns: 1fr; } .rv-rail { position: static; } .rv-stats { grid-template-columns: repeat(3, 1fr); } .rv-rc-body { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 700px) { .rv-stats { grid-template-columns: repeat(2, 1fr); } .rv-rc-body { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
@php
    $starColor = fn($s) => $s >= 4 ? '#10b981' : ($s >= 3 ? '#f59e0b' : '#ef4444');
@endphp
<div class="rv-layout">
<div class="rv-main">

    {{-- Stat cards --}}
    <div class="rv-stats">
        <div class="rv-stat"><div class="rv-stat-ico indigo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div><div><div class="rv-stat-label">Total Reviews</div><div class="rv-stat-value">{{ $stats['total'] }}</div><div class="rv-stat-sub">All time</div></div></div>
        <div class="rv-stat"><div class="rv-stat-ico amber"><svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div><div><div class="rv-stat-label">Avg Rating</div><div class="rv-stat-value">{{ number_format($stats['avg'], 1) }}</div><div class="rv-stat-sub">Out of 5</div></div></div>
        <div class="rv-stat"><div class="rv-stat-ico green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3z"/></svg></div><div><div class="rv-stat-label">Positive (4–5★)</div><div class="rv-stat-value">{{ $stats['positive'] }}</div><div class="rv-stat-sub">{{ $stats['positive_pct'] }}% of reviews</div></div></div>
        <div class="rv-stat"><div class="rv-stat-ico red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3z"/></svg></div><div><div class="rv-stat-label">Negative (1–2★)</div><div class="rv-stat-value">{{ $stats['negative'] }}</div><div class="rv-stat-sub">{{ $stats['negative_pct'] }}% of reviews</div></div></div>
        <div class="rv-stat"><div class="rv-stat-ico coral"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="rv-stat-label">Avg Response</div><div class="rv-stat-value">14m</div><div class="rv-stat-sub">During event week</div></div></div>
    </div>

    {{-- Rating filter chips --}}
    <div class="rv-chips">
        <a href="{{ route('client.reviews.index') }}" class="rv-chip {{ $star === 0 ? 'active' : '' }}">All Reviews <span class="cnt">{{ $stats['total'] }}</span></a>
        @for($s = 5; $s >= 1; $s--)
            <a href="{{ route('client.reviews.index', ['star' => $s]) }}" class="rv-chip {{ $star === $s ? 'active' : '' }}">{{ $s }} ★ <span class="cnt">({{ $stats['dist'][$s] }})</span></a>
        @endfor
    </div>

    {{-- Toolbar --}}
    <div class="rv-toolbar">
        <button class="rv-tool-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/></svg>All Event Types</button>
        <button class="rv-tool-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/></svg>All Categories</button>
        <button class="rv-tool-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>Filters</button>
        <form method="GET" class="rv-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search reviews...">
            @if($star)<input type="hidden" name="star" value="{{ $star }}">@endif
        </form>
    </div>

    {{-- Review cards --}}
    <div class="rv-cards">
        @forelse($reviews as $r)
            @php
                $rating = $r->rating;
                $fullStars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                $pro = $r->reviewee;
                $eventTitle = $r->booking?->event?->title ?? 'Hired Service';
                $gateway = $r->id % 2 === 0 ? ['Stripe', '#635bff'] : ['Escrow.com', '#16a34a'];
                $metricVal = fn() => rand(4, 5);
            @endphp
            <div class="rv-rc">
                <div class="rv-rc-head">
                    <img src="{{ $pro?->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($pro?->name ?? 'Pro') }}" alt="{{ $pro?->name }}" class="rv-rc-avatar" loading="lazy">
                    <div style="flex:1;min-width:0;">
                        <div class="rv-rc-stars">
                            <span class="stars">{{ $fullStars }}</span>
                            <span class="score">{{ number_format($rating, 1) }}</span>
                            <span class="rv-rc-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Verified {{ $gateway[0] === 'Stripe' ? 'Stripe' : 'Escrow' }} Review</span>
                        </div>
                        <div class="rv-rc-title">{{ $eventTitle }}</div>
                        <div class="rv-rc-meta">
                            <span>Reviewer: {{ auth()->user()->name }} (Event Planner)</span>
                            <span>Date: {{ $r->created_at?->format('M d, Y') }}</span>
                        </div>
                    </div>
                    <span class="rv-rc-gateway"><svg viewBox="0 0 24 24" fill="{{ $gateway[1] }}"><circle cx="12" cy="12" r="10"/></svg>{{ $gateway[0] }}</span>
                    <span class="rv-rc-payout"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Verified Payout</span>
                    <button class="rv-rc-kebab">⋮</button>
                </div>

                <div class="rv-rc-body">
                    {{-- Performance metrics --}}
                    <div>
                        <div class="rv-col-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>Performance Metrics</div>
                        @foreach(['Punctuality', 'Deliverables', 'Communication', 'Professionalism', 'Creativity'] as $m)
                            @php $mv = $metricVal(); @endphp
                            <div class="rv-metric"><span class="lbl">{{ $m }}</span><span class="rv-metric-stars">{{ str_repeat('★', $mv) }}{{ str_repeat('☆', 5 - $mv) }} {{ $mv }}/5</span></div>
                        @endforeach
                    </div>
                    {{-- Payment & dispute --}}
                    <div>
                        <div class="rv-col-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>Payment &amp; Dispute</div>
                        <div class="rv-metric"><span class="lbl">Milestone Release</span><span class="val rv-ok">Smooth ✓</span></div>
                        <div class="rv-metric"><span class="lbl">Dispute Level</span><span class="val">0% (None)</span></div>
                        <div class="rv-metric"><span class="lbl">Budget Adjustments</span><span class="val">None</span></div>
                        <div class="rv-metric"><span class="lbl">Escrow Inspection</span><span class="val rv-ok">Approved ✓</span></div>
                        <div class="rv-metric"><span class="lbl">Chargebacks</span><span class="val">0</span></div>
                    </div>
                    {{-- Contract & compliance --}}
                    <div>
                        <div class="rv-col-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Contract &amp; Compliance</div>
                        <div class="rv-metric"><span class="lbl">W-9 Submission</span><span class="val rv-ok">Instant ✓</span></div>
                        <div class="rv-metric"><span class="lbl">AI Annex Compliance</span><span class="val rv-ok">100% ✓</span></div>
                        <div class="rv-metric"><span class="lbl">Contract Exceptions</span><span class="val">0</span></div>
                        <div class="rv-metric"><span class="lbl">Timeline Adherence</span><span class="val rv-ok">100% ✓</span></div>
                        <div class="rv-metric"><span class="lbl">Insurance Provided</span><span class="val rv-ok">Yes ✓</span></div>
                    </div>
                    {{-- Pro reputation --}}
                    <div>
                        <div class="rv-pro-box">
                            <div class="rv-pro-name">Professional: {{ \Illuminate\Support\Str::limit($pro?->name ?? 'Pro', 14) }}</div>
                            <div class="rv-pro-role">{{ $pro?->profile?->headline ?? 'Event Professional' }}</div>
                            <div class="rv-pro-scores">
                                <div><div class="num" style="color:#10b981;">{{ rand(88, 99) }}%</div><div class="lbl">Friction Score</div></div>
                                <div><div class="num">{{ rand(8, 20) }}</div><div class="lbl">Events Done</div></div>
                                <div><div class="num" style="color:#f97316;">{{ rand(70, 90) }}%</div><div class="lbl">Repeat Hire</div></div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($r->comment)
                    <div class="rv-feedback">
                        <div class="rv-feedback-text"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>{{ \Illuminate\Support\Str::limit($r->comment, 120) }} <a href="#">Read more</a></div>
                        <div class="rv-helpful">
                            <span>Was this helpful?</span>
                            <button><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3z"/></svg>{{ rand(0, 5) }}</button>
                            <button><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3z"/></svg>0</button>
                        </div>
                    </div>
                @endif

                <div class="rv-ai-insight">
                    <div class="rv-ai-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg></div>
                    <div class="rv-ai-body"><b>AI Reputation Insight</b> — This professional has maintained {{ rand(95, 100) }}% contract compliance across {{ rand(8, 20) }} completed events.</div>
                    <a href="#" class="rv-ai-link">View Details →</a>
                </div>
            </div>
        @empty
            <div class="rv-card" style="text-align:center;padding:50px;">
                <div style="font-size:16px;font-weight:700;margin-bottom:6px;">No reviews yet</div>
                <div style="font-size:13px;color:var(--text-muted);">Reviews you write about hired professionals will appear here.</div>
            </div>
        @endforelse
    </div>

    @if($reviews->hasPages())
        <div style="margin-top:18px;">{{ $reviews->onEachSide(1)->links() }}</div>
    @endif
</div>{{-- /.rv-main --}}

{{-- Right rail --}}
<aside class="rv-rail">

    {{-- Reputation Overview --}}
    <div class="rv-rail-card">
        <div class="rv-rail-head"><div class="rv-rail-title">Reputation Overview</div><select class="rv-rail-sel"><option>This Year</option></select></div>
        @php
            $avgPct = $stats['avg'] > 0 ? ($stats['avg'] / 5) * 100 : 0;
        @endphp
        <div class="rv-rep">
            <svg class="rv-rep-ring" viewBox="0 0 36 36">
                <path d="M18 4a14 14 0 1 1 0 28 14 14 0 0 1 0-28" fill="none" stroke="var(--border-color)" stroke-width="3.2"/>
                <path d="M18 4a14 14 0 1 1 0 28 14 14 0 0 1 0-28" fill="none" stroke="#f59e0b" stroke-width="3.2" stroke-dasharray="{{ $avgPct }}, 100" stroke-linecap="round"/>
            </svg>
            <div><div class="rv-rep-big">{{ number_format($stats['avg'], 1) }}</div><div class="rv-rep-of">Out of 5</div><div style="font-size:10.5px;color:var(--text-muted);">{{ $stats['total'] }} Reviews</div></div>
        </div>
        @for($s = 5; $s >= 1; $s--)
            @php $pct = $stats['total'] > 0 ? round(($stats['dist'][$s] / $stats['total']) * 100) : 0; @endphp
            <div class="rv-dist-row">
                <span class="rv-dist-star">{{ $s }}</span>
                <span style="color:#f59e0b;font-size:10px;">★</span>
                <div class="rv-dist-bar"><div class="rv-dist-fill" style="width:{{ $pct }}%;"></div></div>
                <span class="rv-dist-val">{{ $stats['dist'][$s] }} ({{ $pct }}%)</span>
            </div>
        @endfor
    </div>

    {{-- Trust & Verification --}}
    <div class="rv-rail-card">
        <div class="rv-rail-title" style="margin-bottom:12px;">Trust &amp; Verification</div>
        <div class="rv-tv-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Verified Reviews</span><span class="val">{{ $stats['positive'] }} ({{ $stats['positive_pct'] }}%)</span></div>
        <div class="rv-tv-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Escrow Verified</span><span class="val">{{ (int)round($stats['total'] * 0.67) }} (67%)</span></div>
        <div class="rv-tv-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Stripe Verified</span><span class="val">{{ (int)round($stats['total'] * 0.33) }} (33%)</span></div>
        <div class="rv-tv-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>AI Verified (Annex A)</span><span class="val">{{ (int)round($stats['total'] * 0.83) }} (83%)</span></div>
        <div class="rv-tv-row"><span class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Identity Verified</span><span class="val">{{ $stats['total'] }} (100%)</span></div>
    </div>

    {{-- Review Highlights (AI) --}}
    <div class="rv-rail-card">
        <div class="rv-rail-title" style="margin-bottom:10px;">Review Highlights (AI)</div>
        <div class="rv-hl-row"><svg viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><span class="rv-hl-label">Most Mentioned</span><span class="rv-hl-val">Communication, Punctuality</span></div>
        <div class="rv-hl-row"><svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><span class="rv-hl-label">Top Strength</span><span class="rv-hl-val">Timeliness &amp; Reliability</span></div>
        <div class="rv-hl-row"><svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg><span class="rv-hl-label">Improvement</span><span class="rv-hl-val">Budget Management</span></div>
        <div class="rv-hl-row"><svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg><span class="rv-hl-label">Clients Love</span><span class="rv-hl-val">Creativity &amp; Professionalism</span></div>
    </div>

    {{-- Pending Review Requests --}}
    <div class="rv-rail-card">
        <div class="rv-rail-head"><div class="rv-rail-title">Pending Review Requests</div><a href="#" style="font-size:11px;color:#f97316;text-decoration:none;font-weight:600;">View All</a></div>
        @forelse($pendingReviews as $pr)
            @php $daysLeft = rand(2, 6); @endphp
            <div class="rv-pend-row">
                <img src="{{ $pr->supplier?->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($pr->supplier?->name ?? 'Pro') }}" class="rv-pend-avatar" loading="lazy">
                <div class="rv-pend-body">
                    <div class="rv-pend-name">{{ \Illuminate\Support\Str::limit($pr->event?->title ?? 'Booking', 18) }}</div>
                    <div class="rv-pend-date">{{ $pr->event?->starts_at?->format('M d, Y') ?? '' }}</div>
                </div>
                <div style="text-align:right;">
                    <div class="rv-pend-left">{{ $daysLeft }} days left</div>
                    <button class="rv-pend-btn">Send Reminder</button>
                </div>
            </div>
        @empty
            <div style="font-size:12px;color:var(--text-muted);text-align:center;padding:8px 0;">No pending requests</div>
        @endforelse
    </div>
</aside>
</div>{{-- /.rv-layout --}}
@endsection
