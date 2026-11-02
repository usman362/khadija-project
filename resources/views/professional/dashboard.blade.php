@extends('layouts.professional')

@section('title', 'Dashboard')

{{-- ════════════════════════════════════════════════════════════════
     Professional Dashboard · reference-exact redesign (blue theme).
     UI scaffold matching the client's professional dashboard mockup:
     8-stat strip + Emergency Gigs · Priority Actions · Gig Operations
     Hub · Bid Intelligence · Finance Hub · Today's Schedule.

     NOTE: charts (donut/sparklines) are pure CSS/SVG; the map + weather
     are static placeholders (need Maps/Weather APIs). Live data wiring
     (real gigs, bids, finance, schedule) is a follow-up.
═══════════════════════════════════════════════════════════════════ --}}

@push('styles')
<style>
    /* Used for text as well as fills, so it follows the theme. */
    .pd { --pd-blue: var(--info-text); }

    /* ── Stats strip (8 cards) ── */
    /* Asks for the service origin. Sits above everything because it is about
       whether this professional can be matched at all. */
    .so-prompt { display: flex; align-items: flex-start; gap: 14px; padding: 15px 18px; margin-bottom: 18px;
                 border: 1.5px solid var(--border-color); border-left: 4px solid #f97316; border-radius: 12px;
                 background: var(--bg-card); flex-wrap: wrap; }
    .so-prompt-ico { flex-shrink: 0; width: 34px; height: 34px; display: grid; place-items: center; border-radius: 9px;
                     background: rgba(249,115,22,.12); color: #ea580c; }
    .so-prompt-ico svg { width: 18px; height: 18px; }
    .so-prompt-body { flex: 1; min-width: 240px; }
    .so-prompt-body b { display: block; font-size: 14px; color: var(--text-primary); margin-bottom: 3px; }
    .so-prompt-body p { margin: 0; font-size: 13px; color: var(--text-muted); line-height: 1.5; max-width: 640px; }
    .so-prompt-btn { flex-shrink: 0; align-self: center; padding: 9px 16px; border-radius: 9px; background: #ea580c;
                     color: #fff; font-size: 13px; font-weight: 700; text-decoration: none; white-space: nowrap; }

    .pd-stats { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
    .pd-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 13px 13px 11px; min-width: 0; }
    .pd-stat-top { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 9px; min-height: 38px; }
    .pd-stat-ico { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pd-stat-ico svg { width: 15px; height: 15px; }
    .c-green { background: rgba(16,185,129,0.12); color: var(--ok-text); }
    .c-blue { background: rgba(37,99,235,0.12); color: var(--info-text); }
    .c-teal { background: rgba(20,184,166,0.12); color: #14b8a6; }
    .c-purple { background: rgba(139,92,246,0.12); color: var(--accent-text); }
    .c-indigo { background: rgba(99,102,241,0.12); color: var(--accent-text); }
    .c-cyan { background: rgba(6,182,212,0.12); color: #06b6d4; }
    .c-orange { background: rgba(249,115,22,0.12); color: var(--brand-text); }
    .pd-stat-label { font-size: 10px; color: var(--text-muted); font-weight: 600; line-height: 1.2; min-width: 0; }
    .pd-stat-ico { margin-top: 1px; }
    .pd-stat-info { margin-left: auto; width: 13px; height: 13px; color: var(--text-muted); flex-shrink: 0; }
    .pd-stat-val { font-size: 19px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; }
    .pd-stat-foot { display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-top: 6px; }
    .pd-stat-delta { font-size: 9.5px; font-weight: 700; color: var(--ok-text); min-width: 0; }
    .pd-stat-delta.muted { color: var(--text-muted); }
    .pd-stat-spark { flex-shrink: 0; }

    /* ── two 3-column rows ── */
    .pd-row { display: grid; gap: 14px; margin-bottom: 14px; align-items: start; }
    .pd-row-1 { grid-template-columns: minmax(0,0.9fr) minmax(0,1fr) minmax(0,1.75fr); }
    .pd-row-2 { grid-template-columns: minmax(0,1fr) minmax(0,1fr) minmax(0,1.15fr); }
    .pd-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px 17px; min-width: 0; }
    .pd-card-head { display: flex; align-items: center; gap: 9px; margin-bottom: 14px; }
    .pd-card-ico { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pd-card-ico svg { width: 16px; height: 16px; }
    .pd-card-title { font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .pd-card-link { margin-left: auto; font-size: 11.5px; font-weight: 700; color: var(--pd-blue); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
    .pd-card-link svg { width: 12px; height: 12px; }
    .pd-pill-count { background: rgba(239,68,68,0.14); color: var(--bad-text); font-size: 10px; font-weight: 800; min-width: 17px; height: 17px; padding: 0 5px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; }

    /* ── Emergency Gigs card ── */
    .pd-emergency { background: linear-gradient(180deg, rgba(239,68,68,0.06), rgba(249,115,22,0.02)); border-color: rgba(239,68,68,0.22); }
    .pd-emerg-head { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .pd-emerg-ico { width: 34px; height: 34px; border-radius: 9px; background: #ef4444; color: #fff; display: flex; align-items: center; justify-content: center; }
    .pd-emerg-ico svg { width: 18px; height: 18px; }
    .pd-emerg-title { font-size: 13.5px; font-weight: 800; color: var(--text-primary); letter-spacing: 0.3px; }
    .pd-emerg-urgent { margin-left: auto; background: rgba(239,68,68,0.14); color: var(--bad-text); font-size: 8.5px; font-weight: 800; padding: 3px 7px; border-radius: 5px; letter-spacing: 0.5px; }
    .pd-emerg-job { font-size: 15px; font-weight: 800; color: var(--bad-text); margin-bottom: 4px; }
    .pd-emerg-sub { font-size: 12px; color: var(--text-secondary); margin-bottom: 2px; }
    .pd-emerg-meta { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin: 13px 0; }
    .pd-emerg-meta .k { font-size: 9.5px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.4px; }
    .pd-emerg-meta .v { font-size: 12px; font-weight: 700; color: var(--text-primary); margin-top: 2px; }
    .pd-emerg-meta .v.pay { color: var(--ok-text); }
    .pd-emerg-meta .v svg { width: 11px; height: 11px; vertical-align: -1px; }
    .pd-emerg-tag { font-size: 9.5px; font-weight: 800; padding: 2px 7px; border-radius: 6px; background: rgba(16,185,129,0.14); color: var(--ok-text); display: inline-block; }
    .pd-accept { display: block; width: 100%; text-align: center; padding: 11px; border-radius: 10px; background: #ef4444; color: #fff; border: none; font-size: 13px; font-weight: 800; cursor: pointer; margin: 4px 0 12px; }
    .pd-accept:hover { background: #dc2626; }
    .pd-emerg-note { font-size: 11px; color: var(--text-muted); line-height: 1.45; text-align: center; }
    .pd-emerg-countdown { text-align: center; font-size: 17px; font-weight: 800; color: var(--bad-text); margin-top: 12px; }

    /* ── Priority Actions list ── */
    .pd-pa { display: flex; flex-direction: column; }
    .pd-pa-row { display: flex; align-items: center; gap: 11px; padding: 10px 0; border-bottom: 1px solid var(--border-color); }
    .pd-pa-row:last-of-type { border-bottom: none; }
    .pd-pa-ico { width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .pd-pa-ico svg { width: 15px; height: 15px; }
    .pd-pa-body { flex: 1; min-width: 0; }
    .pd-pa-title { font-size: 12.5px; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pd-pa-sub { font-size: 11px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pd-pa-pri { font-size: 9.5px; font-weight: 800; padding: 2px 8px; border-radius: 6px; flex-shrink: 0; }
    .pri-high { background: rgba(239,68,68,0.12); color: var(--bad-text); }
    .pri-medium { background: rgba(245,158,11,0.14); color: var(--warn-text); }
    .pri-low { background: rgba(16,185,129,0.12); color: var(--ok-text); }
    .pd-pa-foot { text-align: center; margin-top: 12px; }
    .pd-pa-foot a { font-size: 12px; font-weight: 700; color: var(--pd-blue); text-decoration: none; }

    /* ── Gig Operations Hub ── */
    .pd-tabs { display: flex; gap: 18px; border-bottom: 1px solid var(--border-color); margin-bottom: 4px; }
    .pd-tab { font-size: 12px; font-weight: 600; color: var(--text-muted); padding-bottom: 10px; border-bottom: 2px solid transparent; cursor: pointer; white-space: nowrap; }
    .pd-tab.active { color: var(--pd-blue); border-bottom-color: var(--pd-blue); }
    .pd-gtable-wrap { width: 100%; overflow-x: auto; }
    .pd-gtable { width: 100%; border-collapse: collapse; font-size: 11px; }
    .pd-gtable th { text-align: left; padding: 8px 6px; font-size: 9px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid var(--border-color); white-space: nowrap; }
    .pd-gtable td { padding: 8px 6px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
    .pd-g-event { display: flex; align-items: center; gap: 9px; }
    .pd-g-event > div { min-width: 0; }
    .pd-g-thumb { width: 30px; height: 28px; border-radius: 7px; flex-shrink: 0; background: linear-gradient(135deg,#3b82f6,#1d4ed8); display: flex; align-items: center; justify-content: center; color: #fff; }
    .pd-g-thumb svg { width: 13px; height: 13px; }
    .pd-g-name { font-size: 11.5px; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px; }
    .pd-g-client { font-size: 10px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px; }
    .pd-g-date, .pd-g-budget { font-size: 11.5px; color: var(--text-secondary); white-space: nowrap; }
    .pd-g-budget { font-weight: 600; color: var(--text-primary); }
    .pd-g-status { font-size: 9.5px; font-weight: 800; padding: 3px 8px; border-radius: 6px; white-space: nowrap; }
    .st-confirmed { background: rgba(16,185,129,0.12); color: var(--ok-text); }
    .st-inprogress { background: rgba(245,158,11,0.14); color: var(--warn-text); }
    .st-today { background: rgba(37,99,235,0.12); color: var(--info-text); }
    .pd-g-staff { font-size: 11px; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 7px; white-space: nowrap; }
    .pd-g-staff .chat { display: inline-flex; align-items: center; gap: 3px; color: var(--text-muted); }
    .pd-g-staff .chat svg { width: 11px; height: 11px; }
    .pd-ring { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 8.5px; font-weight: 800; color: var(--text-primary); }
    .pd-ghub-foot { text-align: center; margin-top: 12px; }
    .pd-ghub-foot a { font-size: 12px; font-weight: 700; color: var(--pd-blue); text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
    .pd-ghub-foot svg { width: 13px; height: 13px; }

    /* ── Bid Intelligence donut ── */
    .pd-bid-body { display: flex; gap: 16px; align-items: center; }
    .pd-donut { width: 124px; height: 124px; border-radius: 50%; flex-shrink: 0; position: relative;
        background: conic-gradient(#2563eb 0 32%, #8b5cf6 32% 56%, #06b6d4 56% 72%, #f59e0b 72% 84%, #10b981 84% 96%, #ef4444 96% 100%); }
    .pd-donut::after { content: ''; position: absolute; inset: 21px; background: var(--bg-card); border-radius: 50%; }
    .pd-donut-c { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 1; }
    .pd-donut-c b { font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .pd-donut-c span { font-size: 9.5px; color: var(--text-muted); margin-top: 2px; }
    .pd-bid-legend { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 7px; }
    .pd-bid-leg { display: flex; align-items: center; gap: 8px; font-size: 11.5px; }
    .pd-bid-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
    .pd-bid-leg .nm { color: var(--text-secondary); flex: 1; }
    .pd-bid-leg .ct { font-weight: 700; color: var(--text-primary); }
    .pd-bid-leg .pc { color: var(--text-muted); width: 38px; text-align: right; }
    .pd-bid-rec { display: flex; gap: 10px; margin-top: 14px; padding: 11px 12px; border-radius: 10px; background: rgba(37,99,235,0.06); border: 1px solid rgba(37,99,235,0.16); }
    .pd-bid-rec .ic { width: 26px; height: 26px; border-radius: 7px; background: rgba(37,99,235,0.14); color: var(--info-text); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pd-bid-rec .ic svg { width: 14px; height: 14px; }
    .pd-bid-rec-txt { font-size: 11px; color: var(--text-secondary); line-height: 1.45; }
    .pd-bid-rec-txt b { color: var(--text-primary); display: block; margin-bottom: 1px; }
    .pd-bid-rec-spark { flex-shrink: 0; align-self: center; }

    /* ── Finance Hub ── */
    .pd-fin-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 9px; }
    .pd-fin-box { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 10px; }
    .pd-fin-box .k { font-size: 10px; color: var(--text-muted); margin-bottom: 4px; }
    .pd-fin-box .v { font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .pd-fin-box .v.green { color: var(--ok-text); }
    .pd-fin-box .v.amber { color: var(--warn-text); }
    .pd-fin-actions { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 13px; }
    .pd-fin-btn { display: flex; flex-direction: column; align-items: center; gap: 5px; padding: 10px 4px; border-radius: 9px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); font-size: 10px; font-weight: 600; cursor: pointer; text-decoration: none; }
    .pd-fin-btn svg { width: 16px; height: 16px; color: var(--pd-blue); }

    /* ── Today's Schedule ── */
    .pd-sch-list { display: flex; flex-direction: column; gap: 0; }
    .pd-sch-row { display: flex; align-items: center; gap: 11px; padding: 9px 0; }
    .pd-sch-ico { width: 28px; height: 28px; border-radius: 7px; background: rgba(37,99,235,0.10); color: var(--info-text); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pd-sch-ico svg { width: 14px; height: 14px; }
    .pd-sch-time { font-size: 11.5px; font-weight: 700; color: var(--text-primary); width: 56px; flex-shrink: 0; }
    .pd-sch-body { flex: 1; min-width: 0; }
    .pd-sch-name { font-size: 12px; font-weight: 600; color: var(--text-primary); }
    .pd-sch-dur { font-size: 11px; color: var(--text-muted); flex-shrink: 0; }
    .pd-sch-open { display: block; text-align: center; margin: 10px 0 14px; padding: 9px; border-radius: 9px; border: 1px solid var(--border-color); background: var(--bg-card-hover); color: var(--pd-blue); font-size: 12px; font-weight: 700; text-decoration: none; }
    .pd-map { height: 130px; border-radius: 11px; position: relative; overflow: hidden; background: linear-gradient(135deg,#dbeafe,#eff6ff); border: 1px solid var(--border-color); }
    .pd-map-grid { position: absolute; inset: 0; background-image: linear-gradient(rgba(37,99,235,0.08) 1px, transparent 1px), linear-gradient(90deg, rgba(37,99,235,0.08) 1px, transparent 1px); background-size: 24px 24px; }
    .pd-map-route { position: absolute; top: 30%; left: 18%; width: 60%; height: 40%; border-left: 3px solid #2563eb; border-bottom: 3px solid #2563eb; border-bottom-left-radius: 12px; }
    .pd-map-pin { position: absolute; width: 14px; height: 14px; }
    .pd-map-pin svg { width: 14px; height: 14px; color: var(--bad-text); }
    .pd-map-label { position: absolute; right: 10px; bottom: 8px; font-size: 10px; font-weight: 700; color: #1e40af; background: rgba(255,255,255,0.8); padding: 2px 7px; border-radius: 5px; }
    .pd-meta-row { display: flex; gap: 9px; margin-top: 11px; }
    .pd-meta-box { flex: 1; background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 9px; padding: 9px 10px; display: flex; align-items: center; gap: 8px; }
    .pd-meta-box svg { width: 17px; height: 17px; flex-shrink: 0; }
    .pd-meta-box .t { font-size: 11.5px; font-weight: 700; color: var(--text-primary); }
    .pd-meta-box .s { font-size: 10px; color: var(--text-muted); }
    .pd-traffic { display: flex; align-items: center; gap: 8px; margin-top: 9px; padding: 9px 10px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); border-radius: 9px; font-size: 11.5px; }
    .pd-traffic svg { width: 15px; height: 15px; color: var(--ok-text); flex-shrink: 0; }
    .pd-traffic b { color: var(--text-primary); }

    @media (min-width: 1680px) { .pd-stats { grid-template-columns: repeat(8, minmax(0,1fr)); } }
    @media (max-width: 1200px) { .pd-row-1, .pd-row-2 { grid-template-columns: 1fr; } }
    @media (max-width: 760px)  { .pd-stats { grid-template-columns: repeat(2, minmax(0,1fr)); } .pd-fin-grid { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
@include('partials._service_origin_prompt')
<div class="pd">

    {{-- ════════ 8 stat cards ════════ --}}
    <div class="pd-stats">
        <div class="pd-stat">
            <div class="pd-stat-top"><span class="pd-stat-ico c-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></span><span class="pd-stat-label">Monthly Revenue</span></div>
            <div class="pd-stat-val">${{ number_format($stats['this_month_earnings'] ?? 0, 2) }}</div>
            @php
                // Built here rather than with directives in the markup:
                // Blade will not compile a directive that follows a word
                // character, so it printed the directive instead.
                $__last = (float) ($stats['last_month_earnings'] ?? 0);
                $__now  = (float) ($stats['this_month_earnings'] ?? 0);
                $__move = $__last > 0 ? (int) round(($__now - $__last) / $__last * 100) : null;
                $__trend = $__move === null
                    ? 'Nothing last month to compare'
                    : ($__move >= 0 ? '▲' : '▼') . ' ' . abs($__move) . '% vs last month';
            @endphp
            <div class="pd-stat-foot"><span class="pd-stat-delta muted">{{ $__trend }}</span></div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-top"><span class="pd-stat-ico c-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><span class="pd-stat-label">Awaiting Your Response</span></div>
            <div class="pd-stat-val">{{ $stats['awaiting_you'] ?? 0 }}</div>
            <div class="pd-stat-foot"><span class="pd-stat-delta muted">Requests nobody has answered yet</span></div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-top"><span class="pd-stat-ico c-teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></span><span class="pd-stat-label">Leads Received</span></div>
            <div class="pd-stat-val">{{ $stats['leads_total'] ?? 0 }}</div>
            <div class="pd-stat-foot"><span class="pd-stat-delta muted">Everything that has reached you</span></div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-top"><span class="pd-stat-ico c-purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><span class="pd-stat-label">Upcoming Gigs</span></div>
            <div class="pd-stat-val">{{ $stats['upcoming_gigs'] ?? 0 }}</div>
            <div class="pd-stat-foot"><span class="pd-stat-delta muted">Confirmed and still ahead of you</span></div>
        </div>
        <div class="pd-stat">
            {{-- "Win Rate" is derived from sealed bid outcomes — a leak the rules
                 name explicitly (R8). Replaced with a request-count metric, which
                 is allowed. --}}
            <div class="pd-stat-top"><span class="pd-stat-ico c-indigo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span><span class="pd-stat-label">Active Bids</span></div>
            <div class="pd-stat-val">{{ \App\Models\Bid::where('supplier_id', auth()->id())->where('status', 'submitted')->count() }}</div>
            <div class="pd-stat-foot"><span class="pd-stat-delta">Open on the board now</span></div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-top"><span class="pd-stat-ico c-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><span class="pd-stat-label">Average Rating</span></div>
            <div class="pd-stat-val">@if(($stats['review_count'] ?? 0) > 0){{ number_format($stats['avg_rating'] ?? 0, 1) }}@else&mdash;@endif</div>
            <div class="pd-stat-foot"><span class="pd-stat-delta muted">{{ $stats['review_count'] ?? 0 }} {{ ($stats['review_count'] ?? 0) === 1 ? 'review' : 'reviews' }}</span></div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-top"><span class="pd-stat-ico c-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span><span class="pd-stat-label">Total Booked</span></div>
            <div class="pd-stat-val">${{ number_format($stats['total_booked'] ?? 0, 2) }}</div>
            <div class="pd-stat-foot"><span class="pd-stat-delta muted">All of your confirmed work</span></div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-top"><span class="pd-stat-ico c-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><span class="pd-stat-label">Payment Pending</span></div>
            <div class="pd-stat-val">${{ number_format($stats['pending_payout'] ?? 0, 2) }}</div>
            <div class="pd-stat-foot"><span class="pd-stat-delta muted">{{ $stats['pending_count'] ?? 0 }} {{ ($stats['pending_count'] ?? 0) === 1 ? 'payment' : 'payments' }}</span><svg class="pd-stat-spark" width="40" height="18" viewBox="0 0 40 18" fill="none"><polyline points="0,9 8,10 16,8 24,11 32,7 40,8" stroke="#f97316" stroke-width="1.6"/></svg></div>
        </div>
    </div>

    {{-- ════════ Row 1 ════════ --}}
    <div class="pd-row pd-row-1">

        {{-- Opportunity Feed — Rule R61, Option B --}}
        @include('professional._opportunity-feed')

        {{-- Priority Actions --}}
        <div class="pd-card">
            <div class="pd-card-head">
                <span class="pd-card-ico c-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
                <span class="pd-card-title">Priority Actions</span>
                <span class="pd-pill-count">{{ $attention->sum('count') }}</span>
                <a href="{{ route('professional.priority.index') }}" class="pd-card-link">View All <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
            <div class="pd-pa">
                @forelse($attention as $__a)
                    <a href="{{ $__a['to'] }}" class="pd-pa-row" style="text-decoration:none;">
                        <span class="pd-pa-ico {{ $__a['tone'] }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></span>
                        <div class="pd-pa-body"><div class="pd-pa-title">{{ $__a['title'] }}</div></div>
                        <span class="pd-pa-pri pri-medium">{{ $__a['count'] }}</span>
                    </a>
                @empty
                    <p style="font-size:12px;color:var(--text-muted);margin:0;padding:4px 0;">Nothing needs you right now.</p>
                @endforelse
            </div>
            <div class="pd-pa-foot"><a href="{{ route('professional.priority.index') }}">View All Actions →</a></div>
        </div>

        {{-- Gig Operations Hub --}}
        <div class="pd-card">
            <div class="pd-card-head">
                <span class="pd-card-ico c-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></span>
                <span class="pd-card-title">Gig Operations Hub</span>
                <a href="{{ route('professional.gig-hub.index', ['tab' => 'gigs']) }}" class="pd-card-link">View All Gigs <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
            <div class="pd-gtable-wrap">
            <table class="pd-gtable">
                {{-- Five invented jobs stood here, with a client's name on each
                     one: Corporate Gala Dinner for ABC Corporation, a wedding
                     for Emily & Michael, and so on, together with staffing
                     counts and progress rings for work nobody had done. The
                     controller was loading this professional's five most recent
                     bookings the whole time and the table never used them.

                     Staff and Progress are gone rather than guessed: a booking
                     carries no progress figure, and the crew assigned to one is
                     the point of contact, which the gig page shows properly. --}}
                <thead><tr><th>Event / Client</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($recentBookings as $__b)
                        <tr>
                            <td>
                                <div class="pd-g-event">
                                    <span class="pd-g-thumb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                                    <div>
                                        <div class="pd-g-name">{{ $__b->event?->title ?? 'Untitled event' }}</div>
                                        <div class="pd-g-client">{{ $__b->client?->name ?? 'Client removed' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="pd-g-date">{{ $__b->event?->starts_at?->format('M d, Y') ?? 'No date set' }}</td>
                            <td class="pd-g-budget">${{ number_format((float) $__b->price, 2) }}</td>
                            <td><span class="pd-g-status st-confirmed">{{ ucfirst($__b->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="padding:18px;text-align:center;color:var(--text-muted);font-size:12px;">Nothing booked yet. Gigs you win appear here.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            <div class="pd-ghub-foot"><a href="{{ route('professional.gig-hub.index') }}">Manage All Gigs <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a></div>
        </div>
    </div>

    {{-- ════════ Row 2 ════════ --}}
    <div class="pd-row pd-row-2">

        {{-- Bid Intelligence --}}
        <div class="pd-card">
            <div class="pd-card-head">
                <span class="pd-card-ico c-purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg></span>
                <span class="pd-card-title">Bid Intelligence</span>
                <a href="{{ route('professional.bid-intelligence.index') }}" class="pd-card-link">View Full Pipeline <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
            <div class="pd-bid-body">
                <div class="pd-donut"><div class="pd-donut-c"><b>{{ $bidTotal }}</b><span>{{ $bidTotal === 1 ? 'Bid' : 'Bids' }}</span></div></div>
                <div class="pd-bid-legend">
                    @forelse($bidBreakdown as $__row)
                        <div class="pd-bid-leg">
                            <span class="pd-bid-dot" style="background:{{ $__row['color'] }};"></span>
                            <span class="nm">{{ $__row['label'] }}</span>
                            <span class="ct">{{ $__row['count'] }}</span>
                            <span class="pc">({{ $bidTotal > 0 ? (int) round($__row['count'] / $bidTotal * 100) : 0 }}%)</span>
                        </div>
                    @empty
                        <p style="font-size:12px;color:var(--text-muted);margin:0;">You have not bid on anything yet.</p>
                    @endforelse
                </div>
            </div>
            {{-- A "Recommendation" sat here: wedding packages were winning 21%
                 more than DJ-only packages. Nothing measured it, and a figure
                 about what wins work is one a professional prices against. --}}
        </div>

        {{-- Finance Hub --}}
        <div class="pd-card">
            <div class="pd-card-head">
                <span class="pd-card-ico c-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg></span>
                <span class="pd-card-title">Finance Hub</span>
            </div>
            <div class="pd-fin-grid">
                {{-- Three boxes, because three are the ones anything stands
                     behind. "Secure Payment Held", "Taxes Owed" and
                     "Est. Profit (MTD)" were $7,850, $1,250 and $6,450 typed
                     into the page: there is no escrow balance, no tax figure
                     and no cost side to profit from anywhere in the system. --}}
                <div class="pd-fin-box"><div class="k">Available Balance</div><div class="v">${{ number_format($stats['available_balance'] ?? 0, 2) }}</div></div>
                <div class="pd-fin-box"><div class="k">Pending Payout</div><div class="v">${{ number_format($stats['pending_payout'] ?? 0, 2) }}</div></div>
                <div class="pd-fin-box"><div class="k">Earned This Month</div><div class="v green">${{ number_format($stats['this_month_earnings'] ?? 0, 2) }}</div></div>
            </div>
            <div class="pd-fin-actions">
                <a href="{{ route('professional.transactions.index') }}" class="pd-fin-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Invoices</a>
            </div>
        </div>

        {{-- Today's Schedule --}}
        <div class="pd-card">
            <div class="pd-card-head">
                <span class="pd-card-ico c-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                <span class="pd-card-title">Today's Schedule</span>
                <a href="{{ route('professional.calendar.index') }}" class="pd-card-link">View Calendar <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
            <div class="pd-sch-list">
                @forelse($todaySchedule as $__t)
                    <div class="pd-sch-row">
                        <span class="pd-sch-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                        <span class="pd-sch-time">{{ $__t->event?->starts_at?->format('g:i A') ?? '' }}</span>
                        <span class="pd-sch-body"><span class="pd-sch-name">{{ $__t->event?->title ?? 'Untitled event' }}</span></span>
                        <span class="pd-sch-dur">{{ $__t->event?->ends_at?->format('g:i A') ?? '' }}</span>
                    </div>
                @empty
                    <p style="font-size:12px;color:var(--text-muted);margin:0;padding:4px 0;">Nothing booked for today.</p>
                @endforelse
            </div>
            <a href="{{ route('professional.calendar.index') }}" class="pd-sch-open">Open Full Calendar →</a>
            {{-- A drawn map with two pins, "84°F Partly Cloudy", "20% Rain"
                 and "Traffic is light · 25 min to venue" stood here. None of
                 it came from anywhere: there is no map or weather provider
                 connected, which is one of the open items with Sir Peter.
                 Put it back when there is something real behind it. --}}
            </div>
        </div>
    </div>
</div>
@endsection
