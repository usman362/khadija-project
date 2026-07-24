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
    .pd { --pd-blue: #2563eb; }

    /* ── Stats strip (8 cards) ── */
    .pd-stats { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
    .pd-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 13px 13px 11px; min-width: 0; }
    .pd-stat-top { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 9px; min-height: 38px; }
    .pd-stat-ico { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pd-stat-ico svg { width: 15px; height: 15px; }
    .c-green { background: rgba(16,185,129,0.12); color: #10b981; }
    .c-blue { background: rgba(37,99,235,0.12); color: #2563eb; }
    .c-teal { background: rgba(20,184,166,0.12); color: #14b8a6; }
    .c-purple { background: rgba(139,92,246,0.12); color: #8b5cf6; }
    .c-indigo { background: rgba(99,102,241,0.12); color: #6366f1; }
    .c-cyan { background: rgba(6,182,212,0.12); color: #06b6d4; }
    .c-orange { background: rgba(249,115,22,0.12); color: #f97316; }
    .pd-stat-label { font-size: 10px; color: var(--text-muted); font-weight: 600; line-height: 1.2; min-width: 0; }
    .pd-stat-ico { margin-top: 1px; }
    .pd-stat-info { margin-left: auto; width: 13px; height: 13px; color: var(--text-muted); flex-shrink: 0; }
    .pd-stat-val { font-size: 19px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; }
    .pd-stat-foot { display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-top: 6px; }
    .pd-stat-delta { font-size: 9.5px; font-weight: 700; color: #10b981; min-width: 0; }
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
    .pd-pill-count { background: rgba(239,68,68,0.14); color: #ef4444; font-size: 10px; font-weight: 800; min-width: 17px; height: 17px; padding: 0 5px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; }

    /* ── Emergency Gigs card ── */
    .pd-emergency { background: linear-gradient(180deg, rgba(239,68,68,0.06), rgba(249,115,22,0.02)); border-color: rgba(239,68,68,0.22); }
    .pd-emerg-head { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .pd-emerg-ico { width: 34px; height: 34px; border-radius: 9px; background: #ef4444; color: #fff; display: flex; align-items: center; justify-content: center; }
    .pd-emerg-ico svg { width: 18px; height: 18px; }
    .pd-emerg-title { font-size: 13.5px; font-weight: 800; color: var(--text-primary); letter-spacing: 0.3px; }
    .pd-emerg-urgent { margin-left: auto; background: rgba(239,68,68,0.14); color: #ef4444; font-size: 8.5px; font-weight: 800; padding: 3px 7px; border-radius: 5px; letter-spacing: 0.5px; }
    .pd-emerg-job { font-size: 15px; font-weight: 800; color: #ef4444; margin-bottom: 4px; }
    .pd-emerg-sub { font-size: 12px; color: var(--text-secondary); margin-bottom: 2px; }
    .pd-emerg-meta { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin: 13px 0; }
    .pd-emerg-meta .k { font-size: 9.5px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.4px; }
    .pd-emerg-meta .v { font-size: 12px; font-weight: 700; color: var(--text-primary); margin-top: 2px; }
    .pd-emerg-meta .v.pay { color: #16a34a; }
    .pd-emerg-meta .v svg { width: 11px; height: 11px; vertical-align: -1px; }
    .pd-emerg-tag { font-size: 9.5px; font-weight: 800; padding: 2px 7px; border-radius: 6px; background: rgba(16,185,129,0.14); color: #059669; display: inline-block; }
    .pd-accept { display: block; width: 100%; text-align: center; padding: 11px; border-radius: 10px; background: #ef4444; color: #fff; border: none; font-size: 13px; font-weight: 800; cursor: pointer; margin: 4px 0 12px; }
    .pd-accept:hover { background: #dc2626; }
    .pd-emerg-note { font-size: 11px; color: var(--text-muted); line-height: 1.45; text-align: center; }
    .pd-emerg-countdown { text-align: center; font-size: 17px; font-weight: 800; color: #ef4444; margin-top: 12px; }

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
    .pri-high { background: rgba(239,68,68,0.12); color: #dc2626; }
    .pri-medium { background: rgba(245,158,11,0.14); color: #d97706; }
    .pri-low { background: rgba(16,185,129,0.12); color: #059669; }
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
    .st-confirmed { background: rgba(16,185,129,0.12); color: #059669; }
    .st-inprogress { background: rgba(245,158,11,0.14); color: #d97706; }
    .st-today { background: rgba(37,99,235,0.12); color: #2563eb; }
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
    .pd-bid-rec .ic { width: 26px; height: 26px; border-radius: 7px; background: rgba(37,99,235,0.14); color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pd-bid-rec .ic svg { width: 14px; height: 14px; }
    .pd-bid-rec-txt { font-size: 11px; color: var(--text-secondary); line-height: 1.45; }
    .pd-bid-rec-txt b { color: var(--text-primary); display: block; margin-bottom: 1px; }
    .pd-bid-rec-spark { flex-shrink: 0; align-self: center; }

    /* ── Finance Hub ── */
    .pd-fin-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 9px; }
    .pd-fin-box { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 10px; }
    .pd-fin-box .k { font-size: 10px; color: var(--text-muted); margin-bottom: 4px; }
    .pd-fin-box .v { font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .pd-fin-box .v.green { color: #10b981; }
    .pd-fin-box .v.amber { color: #f59e0b; }
    .pd-fin-actions { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 13px; }
    .pd-fin-btn { display: flex; flex-direction: column; align-items: center; gap: 5px; padding: 10px 4px; border-radius: 9px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); font-size: 10px; font-weight: 600; cursor: pointer; text-decoration: none; }
    .pd-fin-btn svg { width: 16px; height: 16px; color: var(--pd-blue); }

    /* ── Today's Schedule ── */
    .pd-sch-list { display: flex; flex-direction: column; gap: 0; }
    .pd-sch-row { display: flex; align-items: center; gap: 11px; padding: 9px 0; }
    .pd-sch-ico { width: 28px; height: 28px; border-radius: 7px; background: rgba(37,99,235,0.10); color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
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
    .pd-map-pin svg { width: 14px; height: 14px; color: #ef4444; }
    .pd-map-label { position: absolute; right: 10px; bottom: 8px; font-size: 10px; font-weight: 700; color: #1e40af; background: rgba(255,255,255,0.8); padding: 2px 7px; border-radius: 5px; }
    .pd-meta-row { display: flex; gap: 9px; margin-top: 11px; }
    .pd-meta-box { flex: 1; background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 9px; padding: 9px 10px; display: flex; align-items: center; gap: 8px; }
    .pd-meta-box svg { width: 17px; height: 17px; flex-shrink: 0; }
    .pd-meta-box .t { font-size: 11.5px; font-weight: 700; color: var(--text-primary); }
    .pd-meta-box .s { font-size: 10px; color: var(--text-muted); }
    .pd-traffic { display: flex; align-items: center; gap: 8px; margin-top: 9px; padding: 9px 10px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); border-radius: 9px; font-size: 11.5px; }
    .pd-traffic svg { width: 15px; height: 15px; color: #10b981; flex-shrink: 0; }
    .pd-traffic b { color: var(--text-primary); }

    @media (min-width: 1680px) { .pd-stats { grid-template-columns: repeat(8, minmax(0,1fr)); } }
    @media (max-width: 1200px) { .pd-row-1, .pd-row-2 { grid-template-columns: 1fr; } }
    @media (max-width: 760px)  { .pd-stats { grid-template-columns: repeat(2, minmax(0,1fr)); } .pd-fin-grid { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
<div class="pd">

    {{-- ════════ 8 stat cards ════════ --}}
    <div class="pd-stats">
        <div class="pd-stat">
            <div class="pd-stat-top"><span class="pd-stat-ico c-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></span><span class="pd-stat-label">Monthly Revenue</span></div>
            <div class="pd-stat-val">$12,840</div>
            <div class="pd-stat-foot"><span class="pd-stat-delta">▲ 22% vs last month</span><svg class="pd-stat-spark" width="40" height="18" viewBox="0 0 40 18" fill="none"><polyline points="0,14 8,11 16,12 24,6 32,8 40,2" stroke="#10b981" stroke-width="1.6"/></svg></div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-top"><span class="pd-stat-ico c-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><span class="pd-stat-label">Active Proposals</span></div>
            <div class="pd-stat-val">14</div>
            <div class="pd-stat-foot"><span class="pd-stat-delta">▲ 27% vs last month</span><svg class="pd-stat-spark" width="40" height="18" viewBox="0 0 40 18" fill="none"><polyline points="0,12 8,13 16,8 24,10 32,5 40,4" stroke="#2563eb" stroke-width="1.6"/></svg></div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-top"><span class="pd-stat-ico c-teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></span><span class="pd-stat-label">Leads Received</span></div>
            <div class="pd-stat-val">32</div>
            <div class="pd-stat-foot"><span class="pd-stat-delta">▲ 12% last month</span><svg class="pd-stat-spark" width="40" height="18" viewBox="0 0 40 18" fill="none"><polyline points="0,13 8,9 16,11 24,7 32,9 40,3" stroke="#14b8a6" stroke-width="1.6"/></svg></div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-top"><span class="pd-stat-ico c-purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><span class="pd-stat-label">Upcoming Gigs</span></div>
            <div class="pd-stat-val">15</div>
            <div class="pd-stat-foot"><span class="pd-stat-delta">▲ 3 this month</span><svg class="pd-stat-spark" width="40" height="18" viewBox="0 0 40 18" fill="none"><polyline points="0,10 8,11 16,9 24,10 32,6 40,5" stroke="#8b5cf6" stroke-width="1.6"/></svg></div>
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
            <div class="pd-stat-top"><span class="pd-stat-ico c-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><span class="pd-stat-label">Response Time (Avg)</span><svg class="pd-stat-info" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg></div>
            <div class="pd-stat-val">18m</div>
            <div class="pd-stat-foot"><span class="pd-stat-delta">Excellent</span><svg class="pd-stat-spark" width="40" height="18" viewBox="0 0 40 18" fill="none"><polyline points="0,8 8,9 16,7 24,8 32,6 40,7" stroke="#2563eb" stroke-width="1.6"/></svg></div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-top"><span class="pd-stat-ico c-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span><span class="pd-stat-label">AI Visibility Score</span><svg class="pd-stat-info" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg></div>
            <div class="pd-stat-val">92<span style="font-size:12px;color:var(--text-muted);font-weight:700;">/100</span></div>
            <div class="pd-stat-foot"><span class="pd-stat-delta">Excellent</span><svg class="pd-stat-spark" width="40" height="18" viewBox="0 0 40 18" fill="none"><polyline points="0,11 8,8 16,9 24,5 32,6 40,3" stroke="#10b981" stroke-width="1.6"/></svg></div>
        </div>
        <div class="pd-stat">
            <div class="pd-stat-top"><span class="pd-stat-ico c-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span><span class="pd-stat-label">Payment Pending</span></div>
            <div class="pd-stat-val">$4,250</div>
            <div class="pd-stat-foot"><span class="pd-stat-delta muted">2 payments</span><svg class="pd-stat-spark" width="40" height="18" viewBox="0 0 40 18" fill="none"><polyline points="0,9 8,10 16,8 24,11 32,7 40,8" stroke="#f97316" stroke-width="1.6"/></svg></div>
        </div>
    </div>

    {{-- ════════ Row 1 ════════ --}}
    <div class="pd-row pd-row-1">

        {{-- Emergency Gigs --}}
        <div class="pd-card pd-emergency">
            <div class="pd-emerg-head">
                <span class="pd-emerg-ico"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h6l-1 8 10-12h-6l1-8z"/></svg></span>
                <span class="pd-emerg-title">EMERGENCY GIGS</span>
                <span class="pd-emerg-urgent">URGENT</span>
            </div>
            <div class="pd-emerg-job">DJ Needed Tonight</div>
            <div class="pd-emerg-sub">Previous DJ Canceled</div>
            <div class="pd-emerg-sub">Private Birthday — 50 Guests</div>
            <div class="pd-emerg-meta">
                <div><div class="k"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:10px;height:10px;vertical-align:-1px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> Location</div><div class="v">Miami, FL</div></div>
                <div><div class="k">Date</div><div class="v">Tonight</div></div>
                <div><div class="k">Payout</div><div class="v pay">$1,200 - $1,800</div></div>
                <div><div class="k">Start</div><div class="v">8:00 PM</div></div>
                <div><div class="k">End</div><div class="v">9:00 PM</div></div>
                <div><div class="k">Priority</div><div class="v"><span class="pd-emerg-tag">High</span></div></div>
            </div>
            <button class="pd-accept">Accept Now</button>
            <div class="pd-emerg-note">Be first to respond. High payout for quick availability!</div>
            <div class="pd-emerg-countdown">02h 15m left</div>
        </div>

        {{-- Priority Actions --}}
        <div class="pd-card">
            <div class="pd-card-head">
                <span class="pd-card-ico c-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
                <span class="pd-card-title">Priority Actions</span>
                <span class="pd-pill-count">7</span>
                <a href="{{ route('professional.priority.index') }}" class="pd-card-link">View All <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
            <div class="pd-pa">
                <div class="pd-pa-row"><span class="pd-pa-ico c-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><div class="pd-pa-body"><div class="pd-pa-title">Contract awaiting signature</div><div class="pd-pa-sub">Corporate Gala Dinner</div></div><span class="pd-pa-pri pri-high">High</span></div>
                <div class="pd-pa-row"><span class="pd-pa-ico c-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><div class="pd-pa-body"><div class="pd-pa-title">Staffing shortage detected</div><div class="pd-pa-sub">Outdoor Wedding</div></div><span class="pd-pa-pri pri-medium">Medium</span></div>
                <div class="pd-pa-row"><span class="pd-pa-ico c-purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><div class="pd-pa-body"><div class="pd-pa-title">2 bids expire in 2 hours</div><div class="pd-pa-sub">Tech Conference 2025</div></div><span class="pd-pa-pri pri-medium">Medium</span></div>
                <div class="pd-pa-row"><span class="pd-pa-ico c-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg></span><div class="pd-pa-body"><div class="pd-pa-title">Payment released</div><div class="pd-pa-sub">Private Birthday Party</div></div><span class="pd-pa-pri pri-low">Low</span></div>
                <div class="pd-pa-row"><span class="pd-pa-ico c-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><div class="pd-pa-body"><div class="pd-pa-title">Calendar conflict</div><div class="pd-pa-sub">May 24 – Double Booking</div></div><span class="pd-pa-pri pri-high">High</span></div>
                <div class="pd-pa-row"><span class="pd-pa-ico c-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><div class="pd-pa-body"><div class="pd-pa-title">Late invoice</div><div class="pd-pa-sub">Invoice #INV-2025-1048</div></div><span class="pd-pa-pri pri-medium">Medium</span></div>
            </div>
            <div class="pd-pa-foot"><a href="{{ route('professional.priority.index') }}">View All Actions →</a></div>
        </div>

        {{-- Gig Operations Hub --}}
        <div class="pd-card">
            <div class="pd-card-head">
                <span class="pd-card-ico c-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></span>
                <span class="pd-card-title">Gig Operations Hub</span>
                <a href="{{ route('professional.gigs.index') }}" class="pd-card-link">View All Gigs <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
            <div class="pd-tabs">
                <span class="pd-tab active">All Gigs (15)</span>
                <span class="pd-tab">In Progress (6)</span>
                <span class="pd-tab">Completed (48)</span>
                <span class="pd-tab">Upcoming Today (5)</span>
            </div>
            <div class="pd-gtable-wrap">
            <table class="pd-gtable">
                <thead><tr><th>Event / Client</th><th>Date</th><th>Budget</th><th>Status</th><th>Staff</th><th>Progress</th></tr></thead>
                <tbody>
                    <tr>
                        <td><div class="pd-g-event"><span class="pd-g-thumb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><div><div class="pd-g-name">Corporate Gala Dinner</div><div class="pd-g-client">ABC Corporation</div></div></div></td>
                        <td class="pd-g-date">May 30, 2025</td>
                        <td class="pd-g-budget">$3,000 - $5,000</td>
                        <td><span class="pd-g-status st-confirmed">Confirmed</span></td>
                        <td><span class="pd-g-staff">6/6 <span class="chat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>2</span></span></td>
                        <td><span class="pd-ring" style="background:conic-gradient(#10b981 92%, var(--border-color) 0);"><span style="width:22px;height:22px;border-radius:50%;background:var(--bg-card);display:flex;align-items:center;justify-content:center;">92%</span></span></td>
                    </tr>
                    <tr>
                        <td><div class="pd-g-event"><span class="pd-g-thumb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></span><div><div class="pd-g-name">Outdoor Wedding Reception</div><div class="pd-g-client">Emily & Michael</div></div></div></td>
                        <td class="pd-g-date">Jun 14, 2025</td>
                        <td class="pd-g-budget">$2,000 - $4,000</td>
                        <td><span class="pd-g-status st-confirmed">Confirmed</span></td>
                        <td><span class="pd-g-staff">3/5 <span class="chat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>4</span></span></td>
                        <td><span class="pd-ring" style="background:conic-gradient(#f59e0b 75%, var(--border-color) 0);"><span style="width:22px;height:22px;border-radius:50%;background:var(--bg-card);display:flex;align-items:center;justify-content:center;">75%</span></span></td>
                    </tr>
                    <tr>
                        <td><div class="pd-g-event"><span class="pd-g-thumb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></span><div><div class="pd-g-name">Tech Conference 2025</div><div class="pd-g-client">Tech Innovators</div></div></div></td>
                        <td class="pd-g-date">Jun 17, 2025</td>
                        <td class="pd-g-budget">$1,500 - $3,000</td>
                        <td><span class="pd-g-status st-inprogress">In Progress</span></td>
                        <td><span class="pd-g-staff">3/5 <span class="chat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>4</span></span></td>
                        <td><span class="pd-ring" style="background:conic-gradient(#f59e0b 60%, var(--border-color) 0);"><span style="width:22px;height:22px;border-radius:50%;background:var(--bg-card);display:flex;align-items:center;justify-content:center;">60%</span></span></td>
                    </tr>
                    <tr>
                        <td><div class="pd-g-event"><span class="pd-g-thumb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg></span><div><div class="pd-g-name">Private Birthday Party</div><div class="pd-g-client">Jason Carter</div></div></div></td>
                        <td class="pd-g-date">May 22, 2025</td>
                        <td class="pd-g-budget">$900 - $1,400</td>
                        <td><span class="pd-g-status st-today">Today</span></td>
                        <td><span class="pd-g-staff">7/7 <span class="chat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>1</span></span></td>
                        <td><span class="pd-ring" style="background:conic-gradient(#10b981 100%, var(--border-color) 0);"><span style="width:22px;height:22px;border-radius:50%;background:var(--bg-card);display:flex;align-items:center;justify-content:center;">100%</span></span></td>
                    </tr>
                    <tr>
                        <td><div class="pd-g-event"><span class="pd-g-thumb"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20"/><path d="M5 12a7 7 0 0 1 14 0"/><path d="M2 12a10 10 0 0 0 20 0"/></svg></span><div><div class="pd-g-name">Beach Wedding Ceremony</div><div class="pd-g-client">Sarah Johnson</div></div></div></td>
                        <td class="pd-g-date">Jun 6, 2025</td>
                        <td class="pd-g-budget">$1,800 - $2,400</td>
                        <td><span class="pd-g-status st-confirmed">Confirmed</span></td>
                        <td><span class="pd-g-staff">4/6 <span class="chat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>0</span></span></td>
                        <td><span class="pd-ring" style="background:conic-gradient(#10b981 80%, var(--border-color) 0);"><span style="width:22px;height:22px;border-radius:50%;background:var(--bg-card);display:flex;align-items:center;justify-content:center;">80%</span></span></td>
                    </tr>
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
                <div class="pd-donut"><div class="pd-donut-c"><b>25</b><span>Total Bids</span></div></div>
                <div class="pd-bid-legend">
                    <div class="pd-bid-leg"><span class="pd-bid-dot" style="background:#2563eb;"></span><span class="nm">Invited</span><span class="ct">8</span><span class="pc">(32%)</span></div>
                    <div class="pd-bid-leg"><span class="pd-bid-dot" style="background:#8b5cf6;"></span><span class="nm">Submitted</span><span class="ct">6</span><span class="pc">(24%)</span></div>
                    <div class="pd-bid-leg"><span class="pd-bid-dot" style="background:#06b6d4;"></span><span class="nm">Viewed</span><span class="ct">4</span><span class="pc">(16%)</span></div>
                    <div class="pd-bid-leg"><span class="pd-bid-dot" style="background:#f59e0b;"></span><span class="nm">Negotiation</span><span class="ct">3</span><span class="pc">(12%)</span></div>
                    <div class="pd-bid-leg"><span class="pd-bid-dot" style="background:#10b981;"></span><span class="nm">Won</span><span class="ct">3</span><span class="pc">(12%)</span></div>
                    <div class="pd-bid-leg"><span class="pd-bid-dot" style="background:#ef4444;"></span><span class="nm">Lost</span><span class="ct">1</span><span class="pc">(4%)</span></div>
                </div>
            </div>
            <div class="pd-bid-rec">
                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.9 4.6L18.5 9.5 13.9 11.4 12 16l-1.9-4.6L5.5 9.5 10.1 7.6 12 3z"/></svg></span>
                <div class="pd-bid-rec-txt"><b>AI Recommendation</b>Wedding packages are winning 21% more than DJ-only packages.</div>
                <svg class="pd-bid-rec-spark" width="44" height="26" viewBox="0 0 44 26" fill="none"><polyline points="0,22 9,16 18,18 27,9 36,11 44,3" stroke="#10b981" stroke-width="1.8"/></svg>
            </div>
        </div>

        {{-- Finance Hub --}}
        <div class="pd-card">
            <div class="pd-card-head">
                <span class="pd-card-ico c-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg></span>
                <span class="pd-card-title">Finance Hub</span>
            </div>
            <div class="pd-fin-grid">
                <div class="pd-fin-box"><div class="k">Available Balance</div><div class="v">$4,250.00</div></div>
                <div class="pd-fin-box"><div class="k">Secure Payment Held</div><div class="v">$7,850.00</div></div>
                <div class="pd-fin-box"><div class="k">Pending Payout</div><div class="v">$3,200.00</div></div>
                <div class="pd-fin-box"><div class="k">Released This Month</div><div class="v green">$9,640.00</div></div>
                <div class="pd-fin-box"><div class="k">Taxes Owed</div><div class="v amber">$1,250.00</div></div>
                <div class="pd-fin-box"><div class="k">Est. Profit (MTD)</div><div class="v">$6,450.00</div></div>
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
                <div class="pd-sch-row"><span class="pd-sch-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><span class="pd-sch-time">1:00 PM</span><span class="pd-sch-body"><span class="pd-sch-name">Drive to Venue</span></span><span class="pd-sch-dur">30 min</span></div>
                <div class="pd-sch-row"><span class="pd-sch-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></span><span class="pd-sch-time">1:30 PM</span><span class="pd-sch-body"><span class="pd-sch-name">Setup Time</span></span><span class="pd-sch-dur">1 hr</span></div>
                <div class="pd-sch-row"><span class="pd-sch-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><span class="pd-sch-time">2:30 PM</span><span class="pd-sch-body"><span class="pd-sch-name">Corporate Gala Dinner</span></span><span class="pd-sch-dur">6:00 PM</span></div>
                <div class="pd-sch-row"><span class="pd-sch-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><span class="pd-sch-time">6:00 PM</span><span class="pd-sch-body"><span class="pd-sch-name">Breakdown</span></span><span class="pd-sch-dur">1 hr</span></div>
            </div>
            <a href="{{ route('professional.calendar.index') }}" class="pd-sch-open">Open Full Calendar →</a>
            <div class="pd-map">
                <div class="pd-map-grid"></div>
                <div class="pd-map-route"></div>
                <span class="pd-map-pin" style="top:26%; left:16%;"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7z"/></svg></span>
                <span class="pd-map-pin" style="bottom:24%; right:22%;"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7z"/></svg></span>
                <span class="pd-map-label">Miami, FL</span>
            </div>
            <div class="pd-meta-row">
                <div class="pd-meta-box"><svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.2" y1="4.2" x2="5.6" y2="5.6"/><line x1="18.4" y1="18.4" x2="19.8" y2="19.8"/></svg><div><div class="t">84°F</div><div class="s">Partly Cloudy</div></div></div>
                <div class="pd-meta-box"><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg><div><div class="t">20%</div><div class="s">Rain</div></div></div>
            </div>
            <div class="pd-traffic">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 17H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h11v11"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/><path d="M16 8h3l3 4v3h-2"/></svg>
                <span><b>Traffic is light</b> · 25 min to venue</span>
            </div>
        </div>
    </div>
</div>
@endsection
