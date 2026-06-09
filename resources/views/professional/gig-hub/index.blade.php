@extends('layouts.professional')

@section('title', 'Gig Operations Hub')

{{-- Gig Operations Hub — explainer + a live snapshot of the pro's gigs
     (real bookings + crew from shifts + message counts). "View All Gigs"
     opens the full My Gigs page. Deep-dive cards are explainer/UI. --}}

@php
    $money = fn ($n) => '$' . number_format((float) $n, 0);
    $statusMap = ['confirmed'=>['Confirmed','#059669'], 'requested'=>['In Progress','#d97706'], 'completed'=>['Completed','#6366f1'], 'cancelled'=>['Cancelled','#dc2626']];
    $thumbs = ['linear-gradient(135deg,#f59e0b,#b45309)','linear-gradient(135deg,#10b981,#047857)','linear-gradient(135deg,#8b5cf6,#6d28d9)','linear-gradient(135deg,#2563eb,#1d4ed8)','linear-gradient(135deg,#ec4899,#be185d)'];
@endphp

@push('styles')
<style>
    .pg { --pg-blue: #2563eb; }
    .pg-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px 20px; }

    .pg-top { display: grid; grid-template-columns: minmax(0,0.85fr) minmax(0,1.15fr); gap: 18px; align-items: start; margin-bottom: 20px; }

    /* Left intro */
    .pg-art { width: 110px; height: 100px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; }
    .pg-art svg { width: 100%; height: auto; }
    .pg-intro h1 { font-size: 30px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .pg-intro .tag { font-size: 14px; color: var(--text-secondary); margin: 4px 0 10px; }
    .pg-intro p { font-size: 12.5px; color: var(--text-muted); line-height: 1.6; margin: 0 0 14px; }
    .pg-feat { display: flex; gap: 11px; padding: 11px 13px; border-radius: 11px; margin-bottom: 8px; }
    .pg-feat.x1 { background: rgba(139,92,246,0.06); } .pg-feat.x2 { background: rgba(16,185,129,0.06); }
    .pg-feat.x3 { background: rgba(249,115,22,0.06); } .pg-feat.x4 { background: rgba(37,99,235,0.06); }
    .pg-feat-ico { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pg-feat-ico svg { width: 15px; height: 15px; }
    .pg-feat b { font-size: 12.5px; color: var(--text-primary); }
    .pg-feat p { font-size: 11px; color: var(--text-muted); margin: 2px 0 0; line-height: 1.4; }

    /* Right gig card */
    .pg-hub-h { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
    .pg-hub-ico { width: 48px; height: 48px; border-radius: 50%; background: #eef2ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pg-hub-ico svg { width: 30px; height: 28px; }
    .pg-hub-h b { font-size: 17px; font-weight: 800; color: var(--text-primary); }
    .pg-hub-h p { font-size: 12px; color: var(--text-muted); margin: 1px 0 0; }
    .pg-scores { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 14px; }
    .pg-score { border: 1px solid var(--border-color); border-radius: 11px; padding: 12px 14px; display: flex; align-items: center; justify-content: space-between; }
    .pg-score-k { font-size: 11px; color: var(--text-muted); font-weight: 600; }
    .pg-score-v { font-size: 24px; font-weight: 800; color: var(--text-primary); }
    .pg-score svg { width: 18px; height: 18px; color: var(--text-muted); }
    .pg-gig { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-top: 1px solid var(--border-color); }
    .pg-gig-thumb { width: 60px; height: 46px; border-radius: 9px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.85); }
    .pg-gig-thumb svg { width: 18px; height: 18px; }
    .pg-gig-mid { flex: 1; min-width: 0; }
    .pg-gig-name { font-size: 13px; font-weight: 800; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pg-gig-meta { font-size: 11px; color: var(--text-muted); }
    .pg-gig-svc { font-size: 11px; color: var(--text-muted); }
    .pg-gig-price b { font-size: 12.5px; font-weight: 800; color: var(--text-primary); white-space: nowrap; }
    .pg-gig-status { font-size: 11px; font-weight: 700; }
    .pg-gig-stats { display: flex; align-items: center; gap: 14px; flex-shrink: 0; }
    .pg-gig-stat { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 700; color: var(--text-secondary); }
    .pg-gig-stat svg { width: 14px; height: 14px; color: #2563eb; }
    .pg-viewall { display: block; text-align: center; padding: 13px; margin-top: 6px; font-size: 13px; font-weight: 800; color: var(--pg-blue); text-decoration: none; }
    .pg-empty { padding: 26px 10px; text-align: center; color: var(--text-muted); font-size: 13px; }

    /* Breakdown */
    .pg-sec-h { display: flex; align-items: center; gap: 9px; margin-bottom: 4px; }
    .pg-sec-h svg { width: 20px; height: 20px; color: #2563eb; }
    .pg-sec-h b { font-size: 18px; font-weight: 800; color: var(--text-primary); }
    .pg-sec-sub { font-size: 12.5px; color: var(--text-muted); margin: 0 0 14px; }
    .pg-bd { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .pg-bd-h { font-size: 13px; font-weight: 800; color: #2563eb; margin-bottom: 8px; }
    .pg-bd-sub { font-size: 11.5px; color: var(--text-muted); margin: 0 0 12px; line-height: 1.4; }
    .pg-bd-row { display: flex; gap: 9px; padding: 7px 0; }
    .pg-bd-row svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }
    .pg-bd-row p { font-size: 11.5px; color: var(--text-secondary); margin: 0; line-height: 1.45; }
    .pg-bd-row b { color: var(--text-primary); }
    .pg-mini-scores { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; margin-bottom: 12px; }
    .pg-mini { border: 1px solid var(--border-color); border-radius: 9px; padding: 9px; text-align: center; }
    .pg-mini .k { font-size: 9px; color: var(--text-muted); }
    .pg-mini .v { font-size: 18px; font-weight: 800; color: var(--text-primary); }
    .pg-bd-cta { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 13px; border: 1px solid var(--border-color); border-radius: 10px; font-size: 13px; font-weight: 800; color: var(--pg-blue); text-decoration: none; }
    .pg-bd-cta svg { width: 15px; height: 15px; }

    /* Click-a-gig cards */
    .pg-cc { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; }
    .pg-cc-card { border: 1px solid var(--border-color); border-radius: 12px; padding: 13px; background: var(--bg-card); }
    .pg-cc-h { display: flex; align-items: center; gap: 7px; margin-bottom: 4px; }
    .pg-cc-h svg { width: 15px; height: 15px; color: #2563eb; flex-shrink: 0; }
    .pg-cc-h b { font-size: 11.5px; font-weight: 800; color: var(--text-primary); }
    .pg-cc-card > p { font-size: 10px; color: var(--text-muted); line-height: 1.4; margin: 0 0 9px; }
    .pg-cc-prev { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px; font-size: 9.5px; color: var(--text-secondary); }
    .pg-cc-map { height: 70px; border-radius: 6px; background: linear-gradient(135deg,#dbeafe,#eff6ff); position: relative; margin-bottom: 6px; overflow: hidden; }
    .pg-cc-map .route { position: absolute; top: 28%; left: 16%; width: 58%; height: 42%; border-left: 2.5px solid #2563eb; border-bottom: 2.5px solid #2563eb; }
    .pg-cc-pin { position: absolute; width: 9px; height: 9px; border-radius: 50% 50% 50% 0; background: #ef4444; transform: rotate(-45deg); }
    .pg-cc-line { height: 5px; border-radius: 3px; background: var(--border-color); margin: 4px 0; }
    .pg-cc-btn { display: block; text-align: center; padding: 6px; border-radius: 7px; color: #fff; font-size: 10px; font-weight: 800; text-decoration: none; margin-top: 8px; }
    .pg-cc-chk { display: flex; align-items: center; gap: 6px; font-size: 9.5px; color: var(--text-secondary); padding: 2px 0; }
    .pg-cc-chk svg { width: 11px; height: 11px; color: #10b981; }
    .pg-cc-tabs { display: flex; gap: 8px; border-bottom: 1px solid var(--border-color); margin-bottom: 7px; }
    .pg-cc-tab { font-size: 9.5px; font-weight: 700; padding-bottom: 5px; color: var(--text-muted); }
    .pg-cc-tab.on { color: #2563eb; border-bottom: 2px solid #2563eb; }
    .pg-cc-msg { display: flex; gap: 5px; margin-bottom: 5px; }
    .pg-cc-bub { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 7px; padding: 4px 7px; font-size: 9px; color: var(--text-secondary); }
    .pg-cc-bub.me { background: rgba(37,99,235,0.1); margin-left: auto; }
    .pg-prog { height: 6px; border-radius: 3px; background: var(--border-color); overflow: hidden; margin-top: 4px; }
    .pg-prog > i { display: block; height: 100%; background: #10b981; }

    .pg-banner { display: flex; align-items: center; gap: 14px; background: rgba(37,99,235,0.06); border: 1px solid rgba(37,99,235,0.18); border-radius: 14px; padding: 16px 20px; margin-top: 18px; }
    .pg-banner svg.star { width: 26px; height: 26px; color: #2563eb; flex-shrink: 0; }
    .pg-banner-txt { flex: 1; }
    .pg-banner-txt b { font-size: 13.5px; color: var(--text-primary); }
    .pg-banner-txt p { font-size: 12px; color: var(--text-muted); margin: 1px 0 0; }
    .pg-banner a { display: inline-flex; align-items: center; gap: 8px; background: #2563eb; color: #fff; font-size: 13px; font-weight: 800; padding: 11px 18px; border-radius: 10px; text-decoration: none; white-space: nowrap; }
    .pg-banner a svg { width: 15px; height: 15px; }

    @media (max-width: 1200px) { .pg-top, .pg-bd { grid-template-columns: 1fr; } .pg-cc { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 760px) { .pg-scores, .pg-cc { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="pg">

    {{-- ════════ Top: intro + gig snapshot ════════ --}}
    <div class="pg-top">
        <div class="pg-intro">
            <div class="pg-art">
                <svg viewBox="0 0 120 110" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <ellipse cx="62" cy="60" rx="54" ry="44" fill="#eef2ff"/>
                    <rect x="26" y="44" width="72" height="50" rx="9" fill="#2563eb"/>
                    <rect x="26" y="44" width="72" height="50" rx="9" fill="url(#bg)" opacity="0.25"/>
                    <rect x="48" y="34" width="28" height="16" rx="5" fill="#1d4ed8"/>
                    <rect x="52" y="38" width="20" height="9" rx="3" fill="#3b82f6"/>
                    <rect x="26" y="62" width="72" height="5" fill="rgba(255,255,255,0.35)"/>
                    <rect x="56" y="66" width="12" height="9" rx="2.5" fill="#fff"/>
                    <path d="M150 30" /><path d="M104 40l1.4 4 4 1.4-4 1.4-1.4 4-1.4-4-4-1.4 4-1.4z" fill="#f59e0b"/>
                    <path d="M20 36l1.2 3.4 3.4 1.2-3.4 1.2-1.2 3.4-1.2-3.4-3.4-1.2 3.4-1.2z" fill="#60a5fa"/>
                    <defs><linearGradient id="bg" x1="26" y1="44" x2="98" y2="94"><stop stop-color="#fff"/><stop offset="1" stop-color="#1e40af"/></linearGradient></defs>
                </svg>
            </div>
            <h1>Gig Operations Hub</h1>
            <div class="tag">Manage all your gigs in one place.</div>
            <p>Your centralized control center to track jobs, monitor earnings, manage staffing, and organize communications — all in one powerful dashboard.</p>
            <div class="pg-feat x1"><span class="pg-feat-ico" style="background:rgba(139,92,246,0.14);color:#8b5cf6;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19.5 12.5l-7 7a2 2 0 0 1-2.8 0l-7-7a2 2 0 0 1 0-2.8l7-7a2 2 0 0 1 2.8 0l7 7a2 2 0 0 1 0 2.8z"/></svg></span><div><b>Stops Confusion</b><p>Gathers scattered details into one view.</p></div></div>
            <div class="pg-feat x2"><span class="pg-feat-ico" style="background:rgba(16,185,129,0.14);color:#10b981;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M9 9.5a2.5 2.5 0 0 1 5 0M9 14.5a2.5 2.5 0 0 0 5 0"/></svg></span><div><b>Tracks Money</b><p>Shows exactly how much each job pays.</p></div></div>
            <div class="pg-feat x3"><span class="pg-feat-ico" style="background:rgba(249,115,22,0.14);color:#f97316;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></span><div><b>Manages Teams</b><p>Helps verify that enough workers are assigned to every event.</p></div></div>
            <div class="pg-feat x4"><span class="pg-feat-ico" style="background:rgba(37,99,235,0.14);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><div><b>Saves Time</b><p>Eliminates the need to switch between emails, calendars, and text messages.</p></div></div>
        </div>

        <div class="pg-card">
            <div class="pg-hub-h">
                <span class="pg-hub-ico"><svg viewBox="0 0 32 28" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="9" width="26" height="17" rx="3.5" fill="#2563eb"/><rect x="3" y="9" width="26" height="17" rx="3.5" fill="url(#hbg)" opacity="0.22"/><rect x="11" y="3.5" width="10" height="6.5" rx="2" fill="#1d4ed8"/><rect x="13" y="5.5" width="6" height="3.2" rx="1.4" fill="#3b82f6"/><rect x="3" y="15" width="26" height="2" fill="rgba(255,255,255,0.4)"/><rect x="13.5" y="16" width="5" height="3.6" rx="1.5" fill="#fff"/><defs><linearGradient id="hbg" x1="3" y1="9" x2="29" y2="26"><stop stop-color="#fff"/><stop offset="1" stop-color="#1e40af"/></linearGradient></defs></svg></span>
                <div><b>Gig Operations Hub</b><p>Manage all your gigs in one place.</p></div>
            </div>
            <div class="pg-scores">
                <div class="pg-score"><div><div class="pg-score-k">Active Gigs</div><div class="pg-score-v">{{ $stats['active'] }}</div></div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                <div class="pg-score"><div><div class="pg-score-k">In Progress</div><div class="pg-score-v">{{ $stats['in_progress'] }}</div></div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2v6h-6M3 12a9 9 0 0 1 15-6.7L21 8M3 22v-6h6M21 12a9 9 0 0 1-15 6.7L3 16"/></svg></div>
                <div class="pg-score"><div><div class="pg-score-k">Completed</div><div class="pg-score-v">{{ $stats['completed'] }}</div></div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg></div>
            </div>
            @forelse($gigs as $i => $g)
                @php
                    $ev = $g->event;
                    [$stLabel, $stColor] = $statusMap[$g->status] ?? [ucfirst($g->status), '#64748b'];
                    $cr = $crew->get($g->event_id);
                    $crewTxt = $cr ? ((int)$cr->filled).'/'.((int)$cr->total) : '—';
                    $mc = optional($msg->get($g->id))->messages_count ?? 0;
                @endphp
                <div class="pg-gig">
                    <span class="pg-gig-thumb" style="background:{{ $thumbs[$i % count($thumbs)] }};"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                    <div class="pg-gig-mid">
                        <div class="pg-gig-name">{{ \Illuminate\Support\Str::limit($ev?->title ?? 'Gig', 26) }}</div>
                        <div class="pg-gig-meta">{{ $ev?->starts_at?->format('M d, Y') ?? '—' }} · {{ $ev?->location ? \Illuminate\Support\Str::limit($ev->location,16) : 'TBD' }}</div>
                        <div class="pg-gig-svc">{{ $ev?->categories->first()?->name ?? 'Service' }}</div>
                    </div>
                    <div class="pg-gig-price" style="text-align:right;min-width:96px;">
                        <b>{{ $g->price ? $money($g->price) : ($ev?->budget ? $money($ev->budget) : '—') }}</b>
                        <div class="pg-gig-status" style="color:{{ $stColor }};">{{ $stLabel }}</div>
                    </div>
                    <div class="pg-gig-stats">
                        <span class="pg-gig-stat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>{{ $crewTxt }}</span>
                        <span class="pg-gig-stat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>{{ $mc }}</span>
                    </div>
                </div>
            @empty
                <div class="pg-empty">No gigs yet. Win a gig from Live Opportunities to see it here.</div>
            @endforelse
            <a href="{{ route('professional.gigs.index') }}" class="pg-viewall">View All Gigs →</a>
        </div>
    </div>

    {{-- ════════ Detailed Section Breakdown ════════ --}}
    <div class="pg-card" style="margin-bottom:20px;">
        <div class="pg-sec-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg><b>Detailed Section Breakdown</b></div>
        <p class="pg-sec-sub">The dashboard is split into three main areas that keep your business running smoothly.</p>
        <div class="pg-bd">
            <div>
                <div class="pg-bd-h">1. The Scoreboard <span style="color:var(--text-muted);font-weight:600;">(Top Section)</span></div>
                <p class="pg-bd-sub">This bar gives a quick status report of your business health.</p>
                <div class="pg-mini-scores">
                    <div class="pg-mini"><div class="k">Active</div><div class="v">{{ $stats['active'] }}</div></div>
                    <div class="pg-mini"><div class="k">In Progress</div><div class="v">{{ $stats['in_progress'] }}</div></div>
                    <div class="pg-mini"><div class="k">Completed</div><div class="v">{{ $stats['completed'] }}</div></div>
                </div>
                <div class="pg-bd-row"><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg><p><b>Active Gigs:</b> total upcoming jobs currently booked on the calendar.</p></div>
                <div class="pg-bd-row"><svg viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/></svg><p><b>In Progress:</b> jobs happening right now or needing active prep.</p></div>
                <div class="pg-bd-row"><svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg><p><b>Completed:</b> finished jobs ready for final billing or archiving.</p></div>
            </div>
            <div>
                <div class="pg-bd-h">2. The Live Event List <span style="color:var(--text-muted);font-weight:600;">(Middle)</span></div>
                <p class="pg-bd-sub">This section highlights the most urgent events on your schedule.</p>
                <div class="pg-bd-row"><svg viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><p><b style="color:#8b5cf6;">The Job Details:</b> event name, date, location, and service.</p></div>
                <div class="pg-bd-row"><svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg><p><b style="color:#10b981;">The Paycheck:</b> estimated price range of the contract.</p></div>
                <div class="pg-bd-row"><svg viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg><p><b style="color:#f97316;">Booking Status:</b> color codes — <b style="color:#059669;">Confirmed</b> (green) / In Progress (orange).</p></div>
                <div class="pg-bd-row"><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg><p><b style="color:#2563eb;">The Crew Counter:</b> staffing levels (6/6 fully staffed; 3/5 = hire 2 more).</p></div>
                <div class="pg-bd-row"><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><p><b style="color:#2563eb;">The Message Bubble:</b> unread questions or updates from the client or crew.</p></div>
            </div>
            <div>
                <div class="pg-bd-h">3. The Navigation Gate <span style="color:var(--text-muted);font-weight:600;">(Bottom)</span></div>
                <p class="pg-bd-sub">This is your gateway to the full picture.</p>
                <a href="{{ route('professional.gigs.index') }}" class="pg-bd-cta">View All Gigs <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                <div class="pg-bd-row" style="margin-top:12px;"><svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg><p>Opens your entire historical archive and master calendar, letting you search through every job you have ever taken.</p></div>
            </div>
        </div>
    </div>

    {{-- ════════ What happens if you click a gig ════════ --}}
    <div class="pg-sec-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 9l3 3m0 0l3-3m-3 3v6"/><circle cx="12" cy="12" r="10"/></svg><b>What Happens if You Click a Gig?</b></div>
    <p class="pg-sec-sub">Clicking on any event card opens a deep-dive page with powerful tools to manage that specific job.</p>
    <div class="pg-cc">
        <div class="pg-cc-card">
            <div class="pg-cc-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><b>Interactive GPS Maps</b></div>
            <p>Get driving directions for your crew and equipment trucks.</p>
            <div class="pg-cc-prev">
                <div class="pg-cc-map"><div class="route"></div><span class="pg-cc-pin" style="top:24%;left:14%;"></span><span class="pg-cc-pin" style="bottom:18%;right:18%;"></span></div>
                <div style="font-weight:700;color:var(--text-primary);">Driving Directions</div>
                <div>1 hr 23 min (84.6 mi)</div>
                <a href="#" class="pg-cc-btn" style="background:#2563eb;">Open in Maps</a>
            </div>
        </div>
        <div class="pg-cc-card">
            <div class="pg-cc-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><b>Digital Contract Signing</b></div>
            <p>Sign paperwork and view payment schedules.</p>
            <div class="pg-cc-prev">
                <div style="font-weight:700;color:var(--text-primary);">Service Agreement</div>
                <div class="pg-cc-line"></div><div class="pg-cc-line" style="width:70%;"></div>
                <div style="margin-top:5px;">Total: <b style="color:var(--text-primary);">$4,000.00</b></div>
                <div style="margin-top:4px;">50% Deposit · 50% Final</div>
                <a href="#" class="pg-cc-btn" style="background:#10b981;">Sign Contract</a>
            </div>
        </div>
        <div class="pg-cc-card">
            <div class="pg-cc-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><b>Live Team Chat</b></div>
            <p>Connect with your hired staff in real time.</p>
            <div class="pg-cc-prev">
                <div style="font-weight:700;color:var(--text-primary);margin-bottom:5px;">Team Chat</div>
                <div class="pg-cc-msg"><span class="pg-cc-bub">What time should I arrive?</span></div>
                <div class="pg-cc-msg"><span class="pg-cc-bub me">Arrive by 2:00 PM 👍</span></div>
                <div class="pg-cc-msg"><span class="pg-cc-bub">Got it! See you then.</span></div>
            </div>
        </div>
        <div class="pg-cc-card">
            <div class="pg-cc-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg><b>Menu &amp; Inventory</b></div>
            <p>Keep track of supplies and equipment.</p>
            <div class="pg-cc-prev">
                <div class="pg-cc-tabs"><span class="pg-cc-tab on">Menu</span><span class="pg-cc-tab">Inventory</span></div>
                <div class="pg-cc-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Chicken (30 lbs)</div>
                <div class="pg-cc-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Salmon (20 lbs)</div>
                <div class="pg-cc-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Chafing Dishes (6)</div>
            </div>
        </div>
        <div class="pg-cc-card">
            <div class="pg-cc-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg><b>Job Overview</b></div>
            <p>View all important job details in one place.</p>
            <div class="pg-cc-prev">
                <div style="font-weight:700;color:var(--text-primary);">Event Details</div>
                <div>Outdoor Wedding · Jun 14</div>
                <div style="margin-top:4px;">Guests: 120 · Budget: $2k–$4k</div>
                <div style="margin-top:5px;">Assigned Crew: 3 / 5</div>
                <div class="pg-prog"><i style="width:60%;"></i></div>
                <a href="{{ route('professional.team.index') }}" class="pg-cc-btn" style="background:#2563eb;">Manage Crew</a>
            </div>
        </div>
    </div>

    {{-- ════════ Bottom banner ════════ --}}
    <div class="pg-banner">
        <svg class="star" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        <div class="pg-banner-txt"><b>The Gig Operations Hub keeps your entire business organized, profitable, and stress-free.</b><p>Everything you need, right when you need it.</p></div>
        <a href="{{ route('professional.dashboard') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>Go to Dashboard</a>
    </div>
</div>
@endsection
